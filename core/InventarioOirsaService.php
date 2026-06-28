<?php
/**
 * Proyecta movimientos confirmados de OIRSA al kardex de inventario.
 *
 * Tipos soportados:
 *  111 Bodega → Productor  = salida
 *  112 Bodega → Bodega     = salida + entrada
 *  113 Proveedor → Bodega  = entrada
 */
class InventarioOirsaService
{
    public static function sincronizar(array $movimientos, ?int $idUsuario = null): array
    {
        $resumen = [
            'configurado' => true,
            'insertados' => 0,
            'actualizados' => 0,
            'existentes' => 0,
            'anulados' => 0,
            'omitidos' => 0,
            'sin_bodega' => 0,
            'sin_producto' => 0,
            'saldos_negativos' => 0,
            'errores' => 0,
            'detalles' => [],
        ];

        if (!$movimientos) {
            return $resumen;
        }

        $db  = Database::programa();
        $pid = Database::proyectoId();
        if (!$db->columnaExiste('sag_bodegas', 'oirsa_cue')
            || !$db->columnaExiste('sag_inventario_productos', 'oirsa_codigo')
            || !$db->columnaExiste('sag_inventario_movimientos', 'referencia_externa')) {
            $resumen['configurado'] = false;
            $resumen['detalles'][] = 'Falta aplicar migracion_022_inventario_oirsa.sql.';
            return $resumen;
        }

        $productos = [];
        foreach ($db->fetchAll(
            "SELECT id_producto, codigo, oirsa_codigo
               FROM sag_inventario_productos
              WHERE id_proyecto=? AND activo=1",
            [$pid]
        ) as $producto) {
            foreach ([$producto['oirsa_codigo'] ?? '', $producto['codigo'] ?? ''] as $codigo) {
                $codigo = self::clave($codigo);
                if ($codigo !== '') $productos[$codigo] = (int) $producto['id_producto'];
            }
        }

        $bodegas = [];
        foreach ($db->fetchAll(
            "SELECT id_bodega, codigo, oirsa_cue
               FROM sag_bodegas
              WHERE id_proyecto=? AND activo=1",
            [$pid]
        ) as $bodega) {
            foreach ([$bodega['oirsa_cue'] ?? '', $bodega['codigo'] ?? ''] as $codigo) {
                $codigo = self::clave($codigo);
                if ($codigo !== '') $bodegas[$codigo] = (int) $bodega['id_bodega'];
            }
        }

        $propia = !$db->inTransaction();
        $paresAfectados = [];
        try {
            if ($propia) $db->beginTransaction();

            foreach ($movimientos as $movimiento) {
                $tipo = (int) ($movimiento['tipo_movimiento_id'] ?? 0);
                if (!in_array($tipo, [111, 112, 113], true)) {
                    $resumen['omitidos']++;
                    continue;
                }

                $movementId = (int) ($movimiento['trazaragro_id'] ?? $movimiento['movement_id'] ?? 0);
                $codigoTraza = trim((string) ($movimiento['codigo_trazabilidad'] ?? ''));
                $cantidad = (float) ($movimiento['cantidad'] ?? 0);
                if ($movementId <= 0 || $codigoTraza === '' || $cantidad <= 0) {
                    $resumen['omitidos']++;
                    continue;
                }

                $baseRef = self::referenciaBase($movementId, $codigoTraza);
                if (self::estaAnulado($movimiento)) {
                    $resumen['anulados'] += self::eliminarReferencias(
                        $db, $pid, $baseRef, $paresAfectados
                    );
                    continue;
                }

                $codigoProducto = self::clave($movimiento['objeto_trazable_codigo'] ?? '');
                $idProducto = $productos[$codigoProducto] ?? 0;
                if (!$idProducto) {
                    $resumen['sin_producto']++;
                    self::detalle(
                        $resumen,
                        "Producto OIRSA sin mapear: " . ($codigoProducto ?: '(sin código)')
                    );
                    continue;
                }

                $idOrigen  = $bodegas[self::clave($movimiento['origen_cue'] ?? '')] ?? 0;
                $idDestino = $bodegas[self::clave($movimiento['destino_cue'] ?? '')] ?? 0;
                $operaciones = [];
                if ($tipo === 111 && $idOrigen) {
                    $operaciones[] = ['salida', $idOrigen];
                } elseif ($tipo === 113 && $idDestino) {
                    $operaciones[] = ['entrada', $idDestino];
                } elseif ($tipo === 112 && $idOrigen && $idDestino) {
                    $operaciones[] = ['salida', $idOrigen];
                    $operaciones[] = ['entrada', $idDestino];
                } else {
                    $resumen['sin_bodega']++;
                    self::detalle(
                        $resumen,
                        'CUE OIRSA sin mapear: origen='
                        . (string) ($movimiento['origen_cue'] ?? '')
                        . ' destino=' . (string) ($movimiento['destino_cue'] ?? '')
                    );
                    continue;
                }

                $fecha = (string) (
                    $movimiento['fecha_autorizacion']
                    ?? $movimiento['fecha_registro']
                    ?? date('Y-m-d H:i:s')
                );
                foreach ($operaciones as [$clase, $idBodega]) {
                    $estado = self::guardarOperacion(
                        $db,
                        $pid,
                        $movementId,
                        $idProducto,
                        $idBodega,
                        $cantidad,
                        $clase,
                        $baseRef . ':' . $clase,
                        $fecha,
                        $movimiento,
                        $idUsuario,
                        $paresAfectados
                    );
                    $resumen[$estado]++;
                }
            }

            foreach ($paresAfectados as [$idProducto, $idBodega]) {
                $saldo = self::recalcularSaldo($db, $pid, $idProducto, $idBodega);
                if ($saldo < 0) $resumen['saldos_negativos']++;
            }

            if ($propia) $db->commit();
        } catch (\Throwable $e) {
            if ($propia && $db->inTransaction()) $db->rollback();
            if (!$propia) throw $e;
            $resumen['errores']++;
            self::detalle($resumen, 'Error de kardex: ' . $e->getMessage());
            error_log('InventarioOirsaService::sincronizar — ' . $e->getMessage());
        }

        return $resumen;
    }

    private static function guardarOperacion(
        Database $db,
        int $pid,
        int $movementId,
        int $idProducto,
        int $idBodega,
        float $cantidad,
        string $tipo,
        string $referencia,
        string $fecha,
        array $movimiento,
        ?int $idUsuario,
        array &$paresAfectados
    ): string {
        $existente = $db->fetchOne(
            "SELECT id_movimiento,id_producto,id_bodega,cantidad,tipo,fecha
               FROM sag_inventario_movimientos
              WHERE id_proyecto=? AND origen='oirsa' AND referencia_externa=?
              FOR UPDATE",
            [$pid, $referencia]
        );

        $descripcion = mb_substr(
            'OIRSA ' . ($movimiento['registration_code'] ?? $movimiento['guiasa_no'] ?? '')
            . ' · ' . ($movimiento['objeto_trazable'] ?? ''),
            0,
            300
        );
        $fecha = self::fechaSql($fecha);

        if (!$existente) {
            $db->execute(
                "INSERT INTO sag_inventario_movimientos
                 (id_proyecto,fecha,tipo,id_producto,id_bodega,cantidad,saldo_resultante,
                  origen,id_origen,referencia_externa,descripcion,created_by)
                 VALUES (?,?,?,?,?,?,NULL,'oirsa',?,?,?,?)",
                [
                    $pid, $fecha, $tipo, $idProducto, $idBodega, $cantidad,
                    $movementId, $referencia, $descripcion, $idUsuario,
                ]
            );
            self::agregarPar($paresAfectados, $idProducto, $idBodega);
            return 'insertados';
        }

        $cambio = (int) $existente['id_producto'] !== $idProducto
            || (int) $existente['id_bodega'] !== $idBodega
            || (string) $existente['tipo'] !== $tipo
            || abs((float) $existente['cantidad'] - $cantidad) > 0.0001
            || (string) $existente['fecha'] !== $fecha;
        if (!$cambio) {
            return 'existentes';
        }

        self::agregarPar(
            $paresAfectados,
            (int) $existente['id_producto'],
            (int) $existente['id_bodega']
        );
        $db->execute(
            "UPDATE sag_inventario_movimientos
                SET fecha=?,tipo=?,id_producto=?,id_bodega=?,cantidad=?,
                    descripcion=?,created_by=COALESCE(created_by,?)
              WHERE id_movimiento=? AND id_proyecto=?",
            [
                $fecha, $tipo, $idProducto, $idBodega, $cantidad,
                $descripcion, $idUsuario, $existente['id_movimiento'], $pid,
            ]
        );
        self::agregarPar($paresAfectados, $idProducto, $idBodega);
        return 'actualizados';
    }

    private static function eliminarReferencias(
        Database $db,
        int $pid,
        string $baseRef,
        array &$paresAfectados
    ): int {
        $refs = [$baseRef . ':salida', $baseRef . ':entrada'];
        $rows = $db->fetchAll(
            "SELECT id_movimiento,id_producto,id_bodega
               FROM sag_inventario_movimientos
              WHERE id_proyecto=? AND origen='oirsa'
                AND referencia_externa IN (?,?)
              FOR UPDATE",
            [$pid, ...$refs]
        );
        foreach ($rows as $row) {
            self::agregarPar(
                $paresAfectados,
                (int) $row['id_producto'],
                (int) $row['id_bodega']
            );
        }
        if ($rows) {
            $db->execute(
                "DELETE FROM sag_inventario_movimientos
                  WHERE id_proyecto=? AND origen='oirsa'
                    AND referencia_externa IN (?,?)",
                [$pid, ...$refs]
            );
        }
        return count($rows);
    }

    private static function recalcularSaldo(
        Database $db,
        int $pid,
        int $idProducto,
        int $idBodega
    ): float {
        $rows = $db->fetchAll(
            "SELECT id_movimiento,tipo,cantidad
               FROM sag_inventario_movimientos
              WHERE id_proyecto=? AND id_producto=? AND id_bodega=?
              ORDER BY fecha,id_movimiento
              FOR UPDATE",
            [$pid, $idProducto, $idBodega]
        );
        $saldo = 0.0;
        foreach ($rows as $row) {
            $cantidad = (float) $row['cantidad'];
            $saldo += $row['tipo'] === 'salida' ? -$cantidad : $cantidad;
            $db->execute(
                "UPDATE sag_inventario_movimientos
                    SET saldo_resultante=?
                  WHERE id_movimiento=? AND id_proyecto=?",
                [$saldo, $row['id_movimiento'], $pid]
            );
        }
        return $saldo;
    }

    private static function estaAnulado(array $movimiento): bool
    {
        $estado = mb_strtolower(
            (string) ($movimiento['status'] ?? '')
            . ' ' . (string) ($movimiento['event_stage'] ?? '')
            . ' ' . (string) ($movimiento['estado_local'] ?? '')
        );
        return str_contains($estado, 'anulad')
            || str_contains($estado, 'cancel')
            || str_contains($estado, 'rechaz');
    }

    private static function referenciaBase(int $movementId, string $codigo): string
    {
        return 'oirsa:' . $movementId . ':' . substr(hash('sha256', $codigo), 0, 32);
    }

    private static function clave(mixed $valor): string
    {
        return mb_strtoupper(trim((string) $valor));
    }

    private static function fechaSql(string $fecha): string
    {
        $ts = strtotime($fecha);
        return $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
    }

    private static function agregarPar(array &$pares, int $idProducto, int $idBodega): void
    {
        $pares[$idProducto . ':' . $idBodega] = [$idProducto, $idBodega];
    }

    private static function detalle(array &$resumen, string $detalle): void
    {
        if (count($resumen['detalles']) < 10 && !in_array($detalle, $resumen['detalles'], true)) {
            $resumen['detalles'][] = $detalle;
        }
    }
}
