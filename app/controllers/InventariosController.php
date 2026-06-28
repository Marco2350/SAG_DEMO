<?php
/**
 * InventariosController — Módulo de Inventarios de Incentivos
 *
 * Los cronogramas y sus líneas se almacenan en:
 *   · sag_proveedores
 *   · sag_bodegas
 *   · sag_inventario_productos
 *   · sag_inventario_cronogramas (+ sag_inventario_lineas)
 *   · sag_inventario_movimientos (kardex)
 *
 * Pendiente:
 *   - Cron para detectar líneas atrasadas (estado='atrasada')
 */
class InventariosController extends Controller
{
    public function __construct()
    {
        $this->requirePrograma();
    }

    // ════════════════════════════════════════════════════════════
    //  VISTA PRINCIPAL
    // ════════════════════════════════════════════════════════════
    public function index(): void
    {
        $pageTitle = 'Inventarios de Incentivos — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;

        $cronogramas = $this->getCronogramas();
        $stock       = $this->getStockPorBodega();
        $kardex      = $this->getKardex();
        $kpis        = $this->calcularKpis($cronogramas, $stock);

        $proveedores = $this->getProveedores();
        $bodegas     = $this->getBodegas();
        $productos   = $this->getProductos();

        $esAdmin = Permisos::puedeEn('inventarios', ACC_EDITAR);

        $this->view('inventarios/index', compact(
            'pageTitle', 'cronogramas', 'stock', 'kardex',
            'kpis', 'proveedores', 'bodegas', 'productos', 'esAdmin'
        ));
    }

    // ════════════════════════════════════════════════════════════
    //  AJAX endpoints
    // ════════════════════════════════════════════════════════════
    public function listarCronogramas(): void
    {
        $this->success('OK', ['cronogramas' => $this->getCronogramas()]);
    }

    public function getCronograma(): void
    {
        $id = (int) $this->getPost('id', 0);
        foreach ($this->getCronogramas() as $c) {
            if ($c['id_cronograma'] == $id) {
                $this->success('OK', ['cronograma' => $c]);
                return;
            }
        }
        $this->error('Cronograma no encontrado.');
    }

    public function saveCronograma(): void
    {
        $this->requirePermission('inventarios', ACC_EDITAR);

        $datos  = json_decode($this->getPost('datos', '[]'), true) ?: [];
        $lineas = json_decode($this->getPost('lineas', '[]'), true) ?: [];

        $db  = Database::programa();
        $pid = Database::proyectoId();

        if (empty($datos['id_proveedor'])) { $this->error('Debe seleccionar el proveedor.'); return; }
        if (!$db->fetchOne("SELECT id_proveedor FROM sag_proveedores WHERE id_proveedor=? AND id_proyecto=? AND activo=1", [(int)$datos['id_proveedor'], $pid])) {
            $this->error('El proveedor seleccionado no pertenece al programa activo.'); return;
        }
        if (empty($datos['num_contrato']) && empty($datos['num_orden_compra'])) {
            $this->error('Debe indicar al menos número de contrato u orden de compra.');
            return;
        }
        if (count($lineas) === 0) { $this->error('Debe agregar al menos una línea al cronograma.'); return; }

        foreach ($lineas as $i => $l) {
            if (empty($l['id_producto']) || empty($l['id_bodega']) || empty($l['cantidad_programada']) || empty($l['fecha_programada'])) {
                $this->error('La línea #' . ($i + 1) . ' está incompleta.');
                return;
            }
            if (!$db->fetchOne("SELECT id_producto FROM sag_inventario_productos WHERE id_producto=? AND id_proyecto=? AND activo=1", [(int)$l['id_producto'], $pid])) {
                $this->error('El producto de la línea #' . ($i + 1) . ' no pertenece al programa.'); return;
            }
            if (!$db->fetchOne("SELECT id_bodega FROM sag_bodegas WHERE id_bodega=? AND id_proyecto=? AND activo=1", [(int)$l['id_bodega'], $pid])) {
                $this->error('La bodega de la línea #' . ($i + 1) . ' no pertenece al programa.'); return;
            }
        }

        try {
            $db->beginTransaction();
            $n = (int)($db->fetchOne("SELECT COUNT(*)+1 AS n FROM sag_inventario_cronogramas WHERE id_proyecto=?", [$pid])['n'] ?? 1);
            $codigo = 'CRON-' . strtoupper($_SESSION['programa']['sigla'] ?? 'XXX') . '-' . date('Y') . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
            $db->execute(
                "INSERT INTO sag_inventario_cronogramas
                 (id_proyecto,codigo,id_proveedor,num_contrato,num_orden_compra,descripcion,monto_total,fecha_inicio,fecha_fin,estado,created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,'borrador',?)",
                [$pid, $codigo, (int)$datos['id_proveedor'], ($datos['num_contrato'] ?? '') ?: null,
                 ($datos['num_orden_compra'] ?? '') ?: null, ($datos['descripcion'] ?? '') ?: null,
                 (float)($datos['monto_total'] ?? 0), ($datos['fecha_inicio'] ?? '') ?: null,
                 ($datos['fecha_fin'] ?? '') ?: null, $_SESSION['user']['id_usuario'] ?? null]
            );
            $id = (int)$db->lastInsertId();
            foreach ($lineas as $l) {
                $db->execute(
                    "INSERT INTO sag_inventario_lineas
                     (id_proyecto,id_cronograma,id_producto,id_bodega,cantidad_programada,fecha_programada,estado)
                     VALUES (?,?,?,?,?,?,'pendiente')",
                    [$pid, $id, (int)$l['id_producto'], (int)$l['id_bodega'],
                     (float)$l['cantidad_programada'], $l['fecha_programada']]
                );
            }
            $db->commit();
            $this->logAction('CREAR_CRONOGRAMA', 'inventarios', "#{$id} {$codigo} con " . count($lineas) . ' líneas');
            $this->success('Cronograma guardado correctamente.', ['id_cronograma' => $id, 'codigo' => $codigo]);
        } catch (Throwable $e) {
            $db->rollback();
            error_log('InventariosController::saveCronograma - ' . $e->getMessage());
            $this->error('No fue posible guardar el cronograma.');
        }
    }

    public function recibirLinea(): void
    {
        $this->requirePermission('inventarios', ACC_CARGAR);

        $idLinea  = (int) $this->getPost('id_linea', 0);
        $cantidad = (float) $this->getPost('cantidad', 0);
        $fecha    = $this->getPost('fecha_recibida', date('Y-m-d'));
        $resp     = $this->getPost('responsable', $_SESSION['user']['nombre'] ?? '');

        if ($idLinea <= 0)    { $this->error('Línea inválida.');                 return; }
        if ($cantidad <= 0)   { $this->error('La cantidad debe ser mayor a 0.'); return; }

        $db = Database::programa(); $pid = Database::proyectoId();
        $linea = $db->fetchOne("SELECT * FROM sag_inventario_lineas WHERE id_linea=? AND id_proyecto=?", [$idLinea, $pid]);
        if (!$linea) { $this->error('Línea no encontrada.'); return; }
        $pendiente = (float)$linea['cantidad_programada'] - (float)$linea['cantidad_recibida'];
        if ($cantidad > $pendiente) { $this->error('La cantidad supera lo pendiente por recibir.'); return; }
        try {
            $db->beginTransaction();
            $nuevoRecibido = (float)$linea['cantidad_recibida'] + $cantidad;
            $estado = $nuevoRecibido >= (float)$linea['cantidad_programada'] ? 'recibida' : 'parcial';
            $db->execute(
                "UPDATE sag_inventario_lineas SET cantidad_recibida=?,fecha_recibida=?,responsable_recepcion=?,estado=?
                  WHERE id_linea=? AND id_proyecto=?",
                [$nuevoRecibido, $fecha, $resp, $estado, $idLinea, $pid]
            );
            $saldo = (float)($db->fetchOne(
                "SELECT COALESCE(SUM(CASE WHEN tipo='entrada' THEN cantidad WHEN tipo='salida' THEN -cantidad ELSE cantidad END),0) AS saldo
                   FROM sag_inventario_movimientos WHERE id_proyecto=? AND id_producto=? AND id_bodega=?",
                [$pid, $linea['id_producto'], $linea['id_bodega']]
            )['saldo'] ?? 0) + $cantidad;
            $db->execute(
                "INSERT INTO sag_inventario_movimientos
                 (id_proyecto,fecha,tipo,id_producto,id_bodega,cantidad,saldo_resultante,origen,id_origen,descripcion,created_by)
                 VALUES (?,?,'entrada',?,?,?,?, 'cronograma',?,?,?)",
                [$pid, $fecha . ' ' . date('H:i:s'), $linea['id_producto'], $linea['id_bodega'], $cantidad, $saldo,
                 $idLinea, 'Recepción de línea de cronograma', $_SESSION['user']['id_usuario'] ?? null]
            );
            $db->commit();
            $this->logAction('RECIBIR_LINEA', 'inventarios', "Línea #{$idLinea} +{$cantidad} ({$resp})");
            $this->success("Se registró la recepción de {$cantidad} unidades.");
        } catch (Throwable $e) {
            $db->rollback();
            error_log('InventariosController::recibirLinea - ' . $e->getMessage());
            $this->error('No fue posible registrar la recepción.');
        }
    }

    /**
     * Elimina (o cancela) un cronograma completo.
     *  · Solo admin/coordinador
     *  · Si tiene movimientos de recepción ya registrados, lo deja en estado 'cancelado'
     *    en lugar de borrarlo físicamente (preserva trazabilidad del kardex).
     *  · Si no tiene recepciones, lo borra de verdad (DELETE cascada a líneas).
     */
    public function deleteCronograma(): void
    {
        $this->requirePermission('inventarios', ACC_ELIMINAR);

        $id = (int) $this->getPost('id', 0);
        if ($id <= 0) { $this->error('Cronograma inválido.'); return; }

        $db  = Database::programa();
        $pid = Database::proyectoId();
        $cronograma = $db->fetchOne(
            "SELECT * FROM sag_inventario_cronogramas WHERE id_cronograma=? AND id_proyecto=?",
            [$id, $pid]
        );
        if (!$cronograma) { $this->error('Cronograma no encontrado.'); return; }

        $tieneRecepciones = (bool)$db->fetchOne(
            "SELECT id_linea FROM sag_inventario_lineas
              WHERE id_cronograma=? AND id_proyecto=? AND cantidad_recibida>0 LIMIT 1",
            [$id, $pid]
        );

        if ($tieneRecepciones) {
            $db->execute(
                "UPDATE sag_inventario_cronogramas
                    SET estado='cancelado', observaciones=CONCAT(COALESCE(observaciones,''), ?)
                  WHERE id_cronograma=? AND id_proyecto=?",
                ["\nCancelado el " . date('d/m/Y H:i'), $id, $pid]
            );
            $db->execute(
                "UPDATE sag_inventario_lineas SET estado='cancelada'
                  WHERE id_cronograma=? AND id_proyecto=? AND cantidad_recibida=0",
                [$id, $pid]
            );
            $this->logAction('CANCELAR_CRONOGRAMA', 'inventarios',
                "#{$id} ({$cronograma['codigo']}) — tenía recepciones, marcado como cancelado");
            $this->success(
                "Cronograma {$cronograma['codigo']} marcado como CANCELADO (no se eliminó porque tenía recepciones registradas en el kardex).",
                ['id_cronograma' => $id, 'accion' => 'cancelado']
            );
            return;
        }

        $db->execute("DELETE FROM sag_inventario_cronogramas WHERE id_cronograma=? AND id_proyecto=?", [$id, $pid]);
        $this->logAction('ELIMINAR_CRONOGRAMA', 'inventarios',
            "#{$id} ({$cronograma['codigo']}) — eliminado físicamente, sin recepciones");
        $this->success(
            "Cronograma {$cronograma['codigo']} eliminado correctamente.",
            ['id_cronograma' => $id, 'accion' => 'eliminado']
        );
    }

    /** Lee un Excel/CSV y devuelve líneas validadas para el formulario. */
    public function importarExcel(): void
    {
        $this->requirePermission('inventarios', ACC_CARGAR);
        if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $this->error('No se recibió el archivo.'); return;
        }
        if ($_FILES['archivo']['size'] > 5 * 1024 * 1024) {
            $this->error('El archivo no puede superar 5 MB.'); return;
        }
        $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
        $tmp = $_FILES['archivo']['tmp_name'];
        if (!is_uploaded_file($tmp)) {
            $this->error('La carga del archivo no es válida.'); return;
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        $mimesPermitidos = [
            'csv' => ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        ];
        if (!isset($mimesPermitidos[$ext]) || !in_array($mime, $mimesPermitidos[$ext], true)) {
            $this->error('El contenido del archivo no coincide con un CSV o XLSX válido.'); return;
        }
        try {
            $rows = $ext === 'csv'
                ? $this->parsearCsv($tmp)
                : ($ext === 'xlsx' ? $this->parsearXlsx($tmp) : []);
        } catch (Throwable $e) {
            error_log('InventariosController::importarExcel - ' . $e->getMessage());
            $this->error('No se pudo leer el archivo. Use la plantilla indicada.'); return;
        }
        if (!$rows) { $this->error('El archivo no contiene filas válidas.'); return; }

        $productos = []; foreach ($this->getProductos() as $p) $productos[strtoupper($p['codigo'])] = $p;
        $bodegas = []; foreach ($this->getBodegas() as $b) $bodegas[strtoupper($b['codigo'])] = $b;
        $lineas = []; $errores = [];
        foreach ($rows as $i => $r) {
            $fila = $i + 2;
            $codProducto = strtoupper(trim((string)($r['codigo_producto'] ?? '')));
            $codBodega   = strtoupper(trim((string)($r['codigo_bodega'] ?? '')));
            $fecha       = $this->normalizarFechaExcel((string)($r['fecha_programada'] ?? ''));
            $cantidad    = (float)str_replace(',', '', (string)($r['cantidad'] ?? '0'));
            if (!isset($productos[$codProducto])) { $errores[] = "Fila {$fila}: producto {$codProducto} no existe."; continue; }
            if (!isset($bodegas[$codBodega])) { $errores[] = "Fila {$fila}: bodega {$codBodega} no existe."; continue; }
            if (!$fecha) { $errores[] = "Fila {$fila}: fecha inválida."; continue; }
            if ($cantidad <= 0) { $errores[] = "Fila {$fila}: cantidad debe ser mayor a cero."; continue; }
            $lineas[] = [
                'id_producto' => $productos[$codProducto]['id_producto'],
                'producto' => $productos[$codProducto]['nombre'],
                'unidad' => $productos[$codProducto]['unidad'],
                'id_bodega' => $bodegas[$codBodega]['id_bodega'],
                'bodega' => $bodegas[$codBodega]['nombre'],
                'fecha_programada' => $fecha,
                'cantidad_programada' => $cantidad,
            ];
        }
        if (!$lineas) {
            $detalle = $errores ? ' ' . implode(' ', array_slice($errores, 0, 3)) : '';
            $this->error('Ninguna fila pudo importarse.' . $detalle);
            return;
        }
        $this->success(count($lineas) . ' líneas importadas.', ['lineas' => $lineas, 'errores' => $errores]);
    }

    public function kardex(): void
    {
        $idProducto = (int) $this->getPost('id_producto', 0);
        $idBodega   = (int) $this->getPost('id_bodega', 0);
        $movs = $this->getKardex();
        if ($idProducto) $movs = array_filter($movs, fn($m) => $m['id_producto'] == $idProducto);
        if ($idBodega)   $movs = array_filter($movs, fn($m) => $m['id_bodega']   == $idBodega);
        $this->success('OK', ['movimientos' => array_values($movs)]);
    }

    public function stockPorBodega(): void
    {
        $this->success('OK', ['stock' => $this->getStockPorBodega()]);
    }

    /**
     * Punto de entrada que EntregasController llama cuando aprueba una entrega.
     * Registra una salida en el kardex y descuenta del stock disponible.
     *
     * @param int   $idEntrega
     * @param int   $idBodega
     * @param array $productos  [ ['id_producto'=>X, 'cantidad'=>Y], ... ]
     * @return array  ['ok'=>bool,'msg'=>string,'movimientos'=>int[]]
     */
    public static function registrarSalida(int $idEntrega, int $idBodega, array $productos): array
    {
        $db  = Database::programa();
        $pid = Database::proyectoId();
        if (!$db->columnaExiste('sag_inventario_movimientos', 'referencia_externa')) {
            return [
                'ok' => false,
                'msg' => 'Falta aplicar migracion_022_inventario_oirsa.sql.',
                'movimientos' => [],
            ];
        }

        $cantidades = [];
        foreach ($productos as $producto) {
            $idProducto = (int) ($producto['id_producto'] ?? 0);
            $cantidad   = (float) ($producto['cantidad'] ?? 0);
            if ($idProducto > 0 && $cantidad > 0) {
                $cantidades[$idProducto] = ($cantidades[$idProducto] ?? 0) + $cantidad;
            }
        }
        if ($idEntrega <= 0 || !$cantidades) {
            return ['ok' => false, 'msg' => 'La entrega no contiene productos válidos.', 'movimientos' => []];
        }

        $propia = !$db->inTransaction();
        try {
            if ($propia) $db->beginTransaction();
            $entrega = $db->fetchOne(
                "SELECT id_entrega,id_bodega,stock_descontado
                   FROM sag_entregas
                  WHERE id_entrega=? AND id_proyecto=?
                  FOR UPDATE",
                [$idEntrega, $pid]
            );
            if (!$entrega) {
                throw new \InvalidArgumentException('La entrega no pertenece al programa activo.');
            }
            $bodegaEntrega = (int) $entrega['id_bodega'];
            if ($idBodega > 0 && $idBodega !== $bodegaEntrega) {
                throw new \InvalidArgumentException('La bodega no corresponde a la entrega.');
            }
            $idBodega = $bodegaEntrega;

            if ((int) $entrega['stock_descontado'] === 1) {
                $existentes = $db->fetchAll(
                    "SELECT id_movimiento
                       FROM sag_inventario_movimientos
                      WHERE id_proyecto=? AND origen='entrega' AND id_origen=?",
                    [$pid, $idEntrega]
                );
                if ($propia) $db->commit();
                return [
                    'ok' => true,
                    'msg' => 'El inventario de esta entrega ya había sido descontado.',
                    'movimientos' => array_map('intval', array_column($existentes, 'id_movimiento')),
                ];
            }

            $ids = [];
            foreach ($cantidades as $idProducto => $cantidad) {
                $producto = $db->fetchOne(
                    "SELECT id_producto
                       FROM sag_inventario_productos
                      WHERE id_producto=? AND id_proyecto=? AND activo=1",
                    [$idProducto, $pid]
                );
                if (!$producto) {
                    throw new \InvalidArgumentException("Producto #{$idProducto} no válido para el programa.");
                }

                $movimientos = $db->fetchAll(
                    "SELECT tipo,cantidad
                       FROM sag_inventario_movimientos
                      WHERE id_proyecto=? AND id_producto=? AND id_bodega=?
                      FOR UPDATE",
                    [$pid, $idProducto, $idBodega]
                );
                $saldo = 0.0;
                foreach ($movimientos as $movimiento) {
                    $valor = (float) $movimiento['cantidad'];
                    $saldo += $movimiento['tipo'] === 'salida' ? -$valor : $valor;
                }
                if ($cantidad > $saldo) {
                    throw new \InvalidArgumentException(
                        "Stock insuficiente para el producto #{$idProducto}. Disponible: {$saldo}."
                    );
                }

                $referencia = "entrega:{$idEntrega}:producto:{$idProducto}";
                $db->execute(
                    "INSERT INTO sag_inventario_movimientos
                     (id_proyecto,fecha,tipo,id_producto,id_bodega,cantidad,saldo_resultante,
                      origen,id_origen,referencia_externa,descripcion,created_by)
                     VALUES (?,NOW(),'salida',?,?,?,?, 'entrega',?,?,?,?)",
                    [
                        $pid, $idProducto, $idBodega, $cantidad, $saldo - $cantidad,
                        $idEntrega, $referencia, "Salida por entrega #{$idEntrega}",
                        $_SESSION['user']['id_usuario'] ?? null,
                    ]
                );
                $ids[] = (int) $db->lastInsertId();
            }

            $db->execute(
                "UPDATE sag_entregas
                    SET stock_descontado=1
                  WHERE id_entrega=? AND id_proyecto=?",
                [$idEntrega, $pid]
            );
            if ($propia) $db->commit();
            return [
                'ok' => true,
                'msg' => count($ids) . " salidas registradas por la entrega #{$idEntrega}.",
                'movimientos' => $ids,
            ];
        } catch (\Throwable $e) {
            if ($propia && $db->inTransaction()) $db->rollback();
            if (!$propia) throw $e;
            error_log('InventariosController::registrarSalida — ' . $e->getMessage());
            return ['ok' => false, 'msg' => $e->getMessage(), 'movimientos' => []];
        }
    }

    // ════════════════════════════════════════════════════════════
    //  KPIs
    // ════════════════════════════════════════════════════════════
    private function calcularKpis(array $cronogramas, array $stock): array
    {
        $totalRecibido = 0; $totalProgramado = 0; $cronActivos = 0;
        foreach ($cronogramas as $c) {
            if (in_array($c['estado'], ['vigente','borrador'])) $cronActivos++;
            foreach ($c['lineas'] as $l) {
                $totalProgramado += (float) $l['cantidad_programada'];
                $totalRecibido   += (float) $l['cantidad_recibida'];
            }
        }
        $stockTotal = 0;
        foreach ($stock as $s) { $stockTotal += (float) $s['saldo']; }

        return [
            'cronogramas_activos' => $cronActivos,
            'total_programado'    => $totalProgramado,
            'total_recibido'      => $totalRecibido,
            'stock_disponible'    => $stockTotal,
            'avance_pct'          => $totalProgramado > 0 ? round(($totalRecibido / $totalProgramado) * 100, 1) : 0,
        ];
    }

    private function getCronogramas(): array
    {
        $db = Database::programa(); $pid = Database::proyectoId();
        if (!$db->tablaExiste('sag_inventario_cronogramas')) return [];
        $rows = $db->fetchAll(
            "SELECT c.*, p.nombre AS proveedor
               FROM sag_inventario_cronogramas c
               JOIN sag_proveedores p ON p.id_proveedor=c.id_proveedor
              WHERE c.id_proyecto=? ORDER BY c.id_cronograma DESC",
            [$pid]
        );
        foreach ($rows as &$c) {
            $c['lineas'] = $db->fetchAll(
                "SELECT l.*, pr.nombre AS producto, pr.unidad, b.nombre AS bodega
                   FROM sag_inventario_lineas l
                   JOIN sag_inventario_productos pr ON pr.id_producto=l.id_producto
                   JOIN sag_bodegas b ON b.id_bodega=l.id_bodega
                  WHERE l.id_cronograma=? AND l.id_proyecto=?
                  ORDER BY l.fecha_programada, l.id_linea",
                [$c['id_cronograma'], $pid]
            );
        }
        return $rows;
    }

    private function getProveedores(): array
    {
        $db = Database::programa();
        return $db->tablaExiste('sag_proveedores') ? $db->fetchAll(
            "SELECT id_proveedor,nombre,rtn FROM sag_proveedores WHERE id_proyecto=? AND activo=1 ORDER BY nombre",
            [Database::proyectoId()]
        ) : [];
    }

    private function getBodegas(): array
    {
        $db = Database::programa();
        return $db->tablaExiste('sag_bodegas') ? $db->fetchAll(
            "SELECT b.id_bodega,b.codigo,b.nombre,d.nombre AS departamento
               FROM sag_bodegas b LEFT JOIN sag_departamentos d ON d.id_departamento=b.id_departamento
              WHERE b.id_proyecto=? AND b.activo=1 ORDER BY b.nombre",
            [Database::proyectoId()]
        ) : [];
    }

    private function getProductos(): array
    {
        $db = Database::programa();
        return $db->tablaExiste('sag_inventario_productos') ? $db->fetchAll(
            "SELECT id_producto,codigo,nombre,unidad,presentacion
               FROM sag_inventario_productos WHERE id_proyecto=? AND activo=1 ORDER BY nombre",
            [Database::proyectoId()]
        ) : [];
    }

    private function getStockPorBodega(): array
    {
        $db = Database::programa(); $pid = Database::proyectoId();
        if (!$db->tablaExiste('sag_inventario_movimientos')) return [];
        return $db->fetchAll(
            "SELECT m.id_bodega,b.nombre AS bodega,m.id_producto,p.nombre AS producto,p.unidad,
                    SUM(CASE WHEN m.tipo='entrada' THEN m.cantidad WHEN m.tipo='salida' THEN -m.cantidad ELSE m.cantidad END) AS saldo
               FROM sag_inventario_movimientos m
               JOIN sag_bodegas b ON b.id_bodega=m.id_bodega
               JOIN sag_inventario_productos p ON p.id_producto=m.id_producto
              WHERE m.id_proyecto=?
              GROUP BY m.id_bodega,b.nombre,m.id_producto,p.nombre,p.unidad
              ORDER BY b.nombre,p.nombre",
            [$pid]
        );
    }

    private function getKardex(): array
    {
        $db = Database::programa(); $pid = Database::proyectoId();
        if (!$db->tablaExiste('sag_inventario_movimientos')) return [];
        return $db->fetchAll(
            "SELECT m.id_movimiento,m.fecha,m.tipo,m.id_producto,p.nombre AS producto,m.id_bodega,b.nombre AS bodega,
                    m.cantidad,m.saldo_resultante AS saldo,m.origen,m.id_origen,m.descripcion
               FROM sag_inventario_movimientos m
               JOIN sag_bodegas b ON b.id_bodega=m.id_bodega
               JOIN sag_inventario_productos p ON p.id_producto=m.id_producto
              WHERE m.id_proyecto=? ORDER BY m.fecha DESC,m.id_movimiento DESC LIMIT 300",
            [$pid]
        );
    }

    private function parsearCsv(string $path): array
    {
        $h = fopen($path, 'r'); if (!$h) throw new RuntimeException('CSV inválido.');
        $header = fgetcsv($h, 2000, ',');
        if (!$header) { fclose($h); return []; }
        $header = array_map(
            fn($v) => strtolower(trim((string)$v, "\xEF\xBB\xBF \t\n\r\0\x0B")),
            $header
        );
        $rows = [];
        while (($row = fgetcsv($h, 2000, ',')) !== false) {
            $row = array_pad(array_slice($row, 0, count($header)), count($header), '');
            if (array_filter($row, fn($v) => trim((string)$v) !== '')) $rows[] = array_combine($header, $row);
        }
        fclose($h); return $rows;
    }

    private function parsearXlsx(string $path): array
    {
        if (!class_exists('ZipArchive')) throw new RuntimeException('Extensión ZIP no disponible.');
        $zip = new ZipArchive(); if ($zip->open($path) !== true) throw new RuntimeException('XLSX inválido.');
        $shared = [];
        if (($ss = $zip->getFromName('xl/sharedStrings.xml')) !== false && ($xml = @simplexml_load_string($ss))) {
            foreach ($xml->si as $si) {
                $v = isset($si->t) ? (string)$si->t : '';
                if (!$v && isset($si->r)) foreach ($si->r as $r) $v .= (string)$r->t;
                $shared[] = $v;
            }
        }
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml'); $zip->close();
        if ($sheet === false || !($xml = @simplexml_load_string($sheet))) throw new RuntimeException('Hoja inválida.');
        $filas = [];
        foreach ($xml->sheetData->row as $rowXml) {
            $fila = [];
            foreach ($rowXml->c as $c) {
                $col = preg_replace('/\d+/', '', (string)$c['r']); $type = (string)$c['t']; $v = '';
                if ($type === 's' && isset($c->v)) $v = $shared[(int)(string)$c->v] ?? '';
                elseif ($type === 'inlineStr' && isset($c->is->t)) $v = (string)$c->is->t;
                elseif (isset($c->v)) $v = (string)$c->v;
                $fila[$col] = trim($v);
            }
            if (array_filter($fila, fn($v) => $v !== '')) $filas[] = $fila;
        }
        if (!$filas) return [];
        $headerByColumn = [];
        foreach ($filas[0] as $col => $value) {
            $headerByColumn[$col] = strtolower(trim((string)$value));
        }
        $rows = [];
        foreach (array_slice($filas, 1) as $fila) {
            $row = [];
            foreach ($headerByColumn as $col => $header) {
                if ($header !== '') $row[$header] = $fila[$col] ?? '';
            }
            if (array_filter($row, fn($v) => trim((string)$v) !== '')) $rows[] = $row;
        }
        return $rows;
    }

    private function normalizarFechaExcel(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '') return null;
        if (is_numeric($valor)) return date('Y-m-d', strtotime('1899-12-30 +' . (int)$valor . ' days'));
        foreach (['Y-m-d', 'd/m/Y', 'm/d/Y'] as $formato) {
            $d = DateTime::createFromFormat($formato, $valor);
            if ($d && $d->format($formato) === $valor) return $d->format('Y-m-d');
        }
        return null;
    }

    // ════════════════════════════════════════════════════════════
    //  RECEPCIONES OIRSA  (tipo_movimiento_id = 113)
    //  Tab adicional dentro de /inventarios. Lee de sag_trazaragro_movimientos
    //  los movimientos de tipo Recepción (Proveedor → Bodega) ya sincronizados
    //  por el módulo Entregas. Solo lectura — el sync se hace desde Entregas.
    //
    //  Endpoint: GET /inventarios/recepcionesOirsa?limit=200
    // ════════════════════════════════════════════════════════════
    public function recepcionesOirsa(): void
    {
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();

            // Si la tabla no existe (programa que aún no usa OIRSA), devolver vacío
            // sin error visible.
            if (!$db->tablaExiste('sag_trazaragro_movimientos')) {
                $this->success('OK', [
                    'kpis'  => $this->kpisRecepcionesVacios(),
                    'data'  => [],
                    'total' => 0,
                ]);
                return;
            }

            $limit = max(1, min(2000, (int)$this->getQuery('limit', 500)));

            // KPIs agregados (una sola query, escalable)
            $rowK = $db->fetchOne(
                "SELECT
                    COUNT(*)                                         AS total,
                    COALESCE(SUM(cantidad), 0)                       AS cantidad_total,
                    COUNT(DISTINCT NULLIF(origen_establecimiento,'')) AS proveedores_unicos,
                    COUNT(DISTINCT NULLIF(destino_establecimiento,'')) AS bodegas_unicas,
                    COUNT(DISTINCT NULLIF(objeto_trazable,''))        AS productos_unicos,
                    COUNT(DISTINCT NULLIF(guiasa_no,''))              AS manifiestos_unicos,
                    MIN(fecha_autorizacion)                          AS primera_fecha,
                    MAX(fecha_autorizacion)                          AS ultima_fecha
                 FROM sag_trazaragro_movimientos
                 WHERE id_proyecto = ? AND tipo_movimiento_id = 113",
                [$pid]
            );

            $kpis = [
                'total'              => (int)($rowK['total'] ?? 0),
                'cantidad_total'     => (float)($rowK['cantidad_total'] ?? 0),
                'proveedores_unicos' => (int)($rowK['proveedores_unicos'] ?? 0),
                'bodegas_unicas'     => (int)($rowK['bodegas_unicas'] ?? 0),
                'productos_unicos'   => (int)($rowK['productos_unicos'] ?? 0),
                'manifiestos_unicos' => (int)($rowK['manifiestos_unicos'] ?? 0),
                'primera_fecha'      => (string)($rowK['primera_fecha'] ?? ''),
                'ultima_fecha'       => (string)($rowK['ultima_fecha'] ?? ''),
            ];

            // Listado paginado (las más recientes primero)
            $rows = $db->fetchAll(
                "SELECT movement_id, rubro, objeto_trazable, codigo_trazabilidad,
                        guiasa_no, codigo_autorizacion,
                        fecha_autorizacion,
                        origen_persona, origen_establecimiento, origen_departamento, origen_municipio,
                        destino_establecimiento, destino_departamento, destino_municipio,
                        cantidad, unidad, transportista,
                        autorizado_por, status_oirsa
                   FROM sag_trazaragro_movimientos
                  WHERE id_proyecto = ? AND tipo_movimiento_id = 113
               ORDER BY fecha_autorizacion DESC, movement_id DESC
                  LIMIT $limit",
                [$pid]
            );

            $this->success('OK', [
                'kpis'  => $kpis,
                'data'  => $rows,
                'limit' => $limit,
            ]);
        } catch (\Throwable $e) {
            error_log('recepcionesOirsa EX: ' . $e->getMessage());
            $this->error('Error al cargar recepciones OIRSA.');
        }
    }

    private function kpisRecepcionesVacios(): array
    {
        return [
            'total' => 0, 'cantidad_total' => 0,
            'proveedores_unicos' => 0, 'bodegas_unicas' => 0,
            'productos_unicos' => 0, 'manifiestos_unicos' => 0,
            'primera_fecha' => '', 'ultima_fecha' => '',
        ];
    }
}
