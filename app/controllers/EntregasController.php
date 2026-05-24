<?php
/**
 * EntregasController — Movimientos de Trazaragro (vista nativa OIRSA)
 *
 * Refactor mayo 2026: el módulo "Entregas de Incentivos" ahora refleja 1:1
 * la tabla "Programas nacionales / Registro de Movilización" de OIRSA.
 * Cada fila persistida = un objeto trazable (un MovementId de OData).
 *
 * Tabla principal: sag_trazaragro_movimientos (migración 004)
 *
 * Flujo:
 *  1. Botón "Sincronizar" → fetchEntregas() en TrazaragroClient
 *  2. Para cada línea OData → UPSERT por movement_id en sag_trazaragro_movimientos
 *  3. Vista lee directo de esa tabla y la muestra estilo OIRSA
 *  4. Botón "Descargar reporte" → CSV con las mismas columnas que OIRSA
 */
class EntregasController extends Controller
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
        $pageTitle = 'Entregas de Incentivos — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;

        $movimientos        = $this->cargarMovimientos();
        $movimientos        = $this->enriquecerMovimientos($movimientos);
        $reporteProductores = $this->reportePorProductor($movimientos);
        $reporteBodegas     = $this->reportePorBodega($movimientos);
        $anomalias          = $this->recolectarAnomalias($movimientos);
        $kpis               = $this->calcularKpis($movimientos);
        $catalogos          = $this->cargarCatalogosFiltros();

        // Para badge en sidebar — actualiza el contador de alertas en sesión
        $_SESSION['entregas_alertas_count'] = $kpis['con_alertas'] ?? 0;

        $esAdmin = in_array(($_SESSION['user']['rol_slug'] ?? ''), ['admin', 'coordinador']);

        $this->view('entregas/index', compact(
            'pageTitle', 'movimientos', 'kpis', 'catalogos', 'esAdmin',
            'reporteProductores', 'reporteBodegas', 'anomalias'
        ));
    }

    // ════════════════════════════════════════════════════════════
    //  CARGA DESDE BD
    // ════════════════════════════════════════════════════════════
    private function cargarMovimientos(): array
    {
        try {
            $db = Database::programa();
            return $db->fetchAll("
                SELECT *
                FROM sag_trazaragro_movimientos
                ORDER BY fecha_autorizacion DESC, movement_id DESC
                LIMIT 1000
            ");
        } catch (\Throwable $e) {
            error_log('cargarMovimientos: ' . $e->getMessage());
            return [];
        }
    }

    private function cargarCatalogosFiltros(): array
    {
        try {
            $db = Database::programa();
            $rubros = $db->fetchAll(
                "SELECT DISTINCT rubro FROM sag_trazaragro_movimientos
                 WHERE rubro IS NOT NULL AND rubro <> '' ORDER BY rubro"
            );
            $tipos = $db->fetchAll(
                "SELECT DISTINCT tipo_movimiento FROM sag_trazaragro_movimientos
                 WHERE tipo_movimiento IS NOT NULL AND tipo_movimiento <> '' ORDER BY tipo_movimiento"
            );
            $deptos = $db->fetchAll(
                "SELECT DISTINCT destino_departamento AS depto FROM sag_trazaragro_movimientos
                 WHERE destino_departamento IS NOT NULL AND destino_departamento <> '' ORDER BY depto"
            );
            $objetos = $db->fetchAll(
                "SELECT DISTINCT objeto_trazable FROM sag_trazaragro_movimientos
                 WHERE objeto_trazable IS NOT NULL AND objeto_trazable <> '' ORDER BY objeto_trazable"
            );
            return [
                'rubros'  => array_column($rubros, 'rubro'),
                'tipos'   => array_column($tipos, 'tipo_movimiento'),
                'deptos'  => array_column($deptos, 'depto'),
                'objetos' => array_column($objetos, 'objeto_trazable'),
            ];
        } catch (\Throwable $e) {
            return ['rubros' => [], 'tipos' => [], 'deptos' => [], 'objetos' => []];
        }
    }

    private function calcularKpis(array $movimientos): array
    {
        $kpis = [
            'total_movimientos'    => count($movimientos),
            'entregados'           => 0,
            'pendientes'           => 0,
            'beneficiarios_unicos' => 0,
            'manifiestos_unicos'   => 0,   // GUIASA No.
            'cantidad_total'       => 0,
            'desglose_objetos'     => [],
        ];

        $beneficiarios = [];
        $manifiestos   = [];
        $porObjeto     = [];

        foreach ($movimientos as $m) {
            // Beneficiario único por DNI
            if (!empty($m['destino_dni'])) $beneficiarios[$m['destino_dni']] = true;
            // Manifiesto único por GUIASA
            if (!empty($m['guiasa_no']))   $manifiestos[$m['guiasa_no']]   = true;

            $entregado = !empty($m['is_completed']) || !empty($m['codigo_trazabilidad']);
            if ($entregado) $kpis['entregados']++;
            else            $kpis['pendientes']++;

            $kpis['cantidad_total'] += (float)($m['cantidad'] ?? 0);

            // Desglose por objeto trazable exacto
            $obj = trim((string)($m['objeto_trazable'] ?? '')) ?: '(sin nombre)';
            if (!isset($porObjeto[$obj])) {
                $porObjeto[$obj] = [
                    'objeto'      => $obj,
                    'unidad'      => $m['unidad'] ?? '',
                    'movimientos' => 0,
                    'entregados'  => 0,
                    'pendientes'  => 0,
                    'cantidad'    => 0,
                ];
            }
            $porObjeto[$obj]['movimientos']++;
            $porObjeto[$obj]['cantidad'] += (float)($m['cantidad'] ?? 0);
            if ($entregado) $porObjeto[$obj]['entregados']++;
            else            $porObjeto[$obj]['pendientes']++;
        }

        $kpis['beneficiarios_unicos'] = count($beneficiarios);
        $kpis['manifiestos_unicos']   = count($manifiestos);

        // Conteo adicional de alertas
        $kpis['con_alertas']      = 0;
        $kpis['no_en_padron']     = 0;
        foreach ($movimientos as $m) {
            if (!empty($m['tiene_alerta'])) $kpis['con_alertas']++;
            if (($m['validacion_padron'] ?? '') === 'no_padron') $kpis['no_en_padron']++;
        }

        // Ordenar desglose por más movimientos
        usort($porObjeto, fn($a, $b) => $b['movimientos'] <=> $a['movimientos']);
        $kpis['desglose_objetos'] = $porObjeto;

        return $kpis;
    }

    // ════════════════════════════════════════════════════════════
    //  ENRIQUECIMIENTO: validación padrón + detección anomalías
    // ════════════════════════════════════════════════════════════

    /**
     * Agrega a cada movimiento campos calculados:
     *  - validacion_padron: 'en_padron', 'no_padron', 'sin_dni'
     *  - alertas: array de strings con problemas detectados
     *  - tiene_alerta: bool
     */
    private function enriquecerMovimientos(array $movs): array
    {
        if (empty($movs)) return [];

        // 1) Cargar padrón del programa en memoria (DNI → fila)
        $padron = [];
        try {
            $db = Database::programa();
            $rows = $db->fetchAll("SELECT dni, estado, nombre_completo FROM sag_beneficiarios");
            foreach ($rows as $r) {
                if (!empty($r['dni'])) $padron[trim($r['dni'])] = $r;
            }
        } catch (\Throwable $e) {
            // Tabla sin filas o columna distinta — seguimos sin padrón
        }

        // 2) Contar duplicados: misma combinación DNI + objeto trazable
        $contDniObjeto = [];
        foreach ($movs as $m) {
            $dni  = trim((string)($m['destino_dni'] ?? ''));
            $obj  = trim((string)($m['objeto_trazable'] ?? ''));
            if ($dni === '' || $obj === '') continue;
            $k = $dni . '||' . $obj;
            $contDniObjeto[$k] = ($contDniObjeto[$k] ?? 0) + 1;
        }

        // 3) Enriquecer cada movimiento
        foreach ($movs as &$m) {
            $alertas = [];
            $dni = trim((string)($m['destino_dni'] ?? ''));
            $obj = trim((string)($m['objeto_trazable'] ?? ''));

            // ── Validación padrón ──
            if ($dni === '') {
                $m['validacion_padron'] = 'sin_dni';
                $alertas[] = 'Movimiento sin DNI de beneficiario';
            } elseif (isset($padron[$dni])) {
                $m['validacion_padron'] = 'en_padron';
                $estado = strtolower((string)($padron[$dni]['estado'] ?? ''));
                if ($estado && !in_array($estado, ['activo', 'activa', '1', 'vigente'])) {
                    $alertas[] = "Beneficiario en estado: {$estado}";
                }
            } else {
                $m['validacion_padron'] = empty($padron) ? 'padron_vacio' : 'no_padron';
                if (!empty($padron)) {
                    $alertas[] = 'DNI no encontrado en el padrón del programa';
                }
            }

            // ── Detección de duplicado (mismo DNI + mismo objeto) ──
            if ($dni && $obj) {
                $k = $dni . '||' . $obj;
                if (($contDniObjeto[$k] ?? 0) > 1) {
                    $alertas[] = "Posible duplicado: este productor recibió '{$obj}' en {$contDniObjeto[$k]} movimientos";
                }
            }

            // ── Cantidad sospechosa ──
            if ((float)($m['cantidad'] ?? 0) <= 0) {
                $alertas[] = 'Cantidad cero o no especificada';
            }

            // ── Sin objeto trazable y estado entregado (debería tener producto) ──
            if (($m['estado_local'] ?? '') === 'entregado' && $obj === '') {
                $alertas[] = 'Marcado como entregado pero sin objeto trazable definido';
            }

            $m['alertas']      = $alertas;
            $m['tiene_alerta'] = !empty($alertas);
        }
        unset($m);

        return $movs;
    }

    // ════════════════════════════════════════════════════════════
    //  REPORTE POR PRODUCTOR
    // ════════════════════════════════════════════════════════════

    /**
     * Agrupa movimientos por destino_dni y devuelve un array con la información
     * consolidada de cada productor: nombre, ubicación, manifiestos, objetos recibidos.
     */
    private function reportePorProductor(array $movs): array
    {
        $byProd = [];
        foreach ($movs as $m) {
            $dni = trim((string)($m['destino_dni'] ?? ''));
            $key = $dni !== '' ? $dni : '(sin-dni)-' . md5($m['destino_persona'] ?? '');

            if (!isset($byProd[$key])) {
                $byProd[$key] = [
                    'dni'              => $dni ?: '(sin DNI)',
                    'nombre'           => $m['destino_nombre'] ?: $m['destino_persona'] ?: '(sin nombre)',
                    'departamento'     => $m['destino_departamento'] ?: '',
                    'municipio'        => $m['destino_municipio'] ?: '',
                    'establecimiento'  => $m['destino_establecimiento'] ?: '',
                    'validacion'       => $m['validacion_padron'] ?? '',
                    'objetos'          => [],
                    'manifiestos_set'  => [],
                    'total_cantidad'   => 0,
                    'entregados'       => 0,
                    'pendientes'       => 0,
                    'tiene_alerta'     => false,
                ];
            }
            $byProd[$key]['objetos'][] = [
                'objeto'       => $m['objeto_trazable'] ?: '(sin nombre)',
                'codigo_traza' => $m['codigo_trazabilidad'] ?: '',
                'guiasa'       => $m['guiasa_no'] ?: '',
                'fecha'        => $m['fecha_autorizacion'] ?: '',
                'cantidad'     => (float)($m['cantidad'] ?? 0),
                'unidad'       => $m['unidad'] ?: '',
                'estado'       => $m['estado_local'] ?: 'pendiente',
                'autoriza'     => $m['autorizado_por'] ?: '',
                'movement_id'  => $m['movement_id'] ?? 0,
            ];
            $byProd[$key]['manifiestos_set'][$m['guiasa_no']] = true;
            $byProd[$key]['total_cantidad'] += (float)($m['cantidad'] ?? 0);
            if (($m['estado_local'] ?? '') === 'entregado') $byProd[$key]['entregados']++;
            else                                            $byProd[$key]['pendientes']++;
            if (!empty($m['tiene_alerta']))                 $byProd[$key]['tiene_alerta'] = true;
        }

        // Cerrar set y ordenar por # de objetos descendente
        foreach ($byProd as &$p) {
            $p['num_manifiestos'] = count($p['manifiestos_set']);
            unset($p['manifiestos_set']);
            $p['num_objetos'] = count($p['objetos']);
        }
        unset($p);
        $out = array_values($byProd);
        usort($out, fn($a, $b) => $b['num_objetos'] <=> $a['num_objetos']);
        return $out;
    }

    // ════════════════════════════════════════════════════════════
    //  REPORTE POR BODEGA DE ORIGEN
    // ════════════════════════════════════════════════════════════

    /**
     * Agrupa movimientos por bodega de origen (origen_establecimiento) y devuelve:
     *   - cantidad de movimientos despachados
     *   - cantidad de beneficiarios atendidos
     *   - top objetos despachados (3 más frecuentes)
     *   - total cantidad despachada
     */
    private function reportePorBodega(array $movs): array
    {
        $byBod = [];
        foreach ($movs as $m) {
            $bod = trim((string)($m['origen_establecimiento'] ?? '')) ?: '(sin bodega)';
            // Normalizar: quitar el código que viene como "; 3400301153824"
            $bodLimpio = trim((string)preg_replace('/;\s*\d+\s*$/', '', $bod));
            $bodLimpio = $bodLimpio ?: $bod;

            if (!isset($byBod[$bodLimpio])) {
                $byBod[$bodLimpio] = [
                    'bodega'             => $bodLimpio,
                    'cue'                => $m['origen_cue'] ?? '',
                    'departamento'       => $m['origen_departamento'] ?? '',
                    'movimientos'        => 0,
                    'cantidad_total'     => 0,
                    'entregados'         => 0,
                    'pendientes'         => 0,
                    'beneficiarios_set'  => [],
                    'objetos_count'      => [],
                    'manifiestos_set'    => [],
                ];
            }
            $byBod[$bodLimpio]['movimientos']++;
            $byBod[$bodLimpio]['cantidad_total'] += (float)($m['cantidad'] ?? 0);
            if (($m['estado_local'] ?? '') === 'entregado') $byBod[$bodLimpio]['entregados']++;
            else                                            $byBod[$bodLimpio]['pendientes']++;
            if (!empty($m['destino_dni']))   $byBod[$bodLimpio]['beneficiarios_set'][$m['destino_dni']] = true;
            if (!empty($m['guiasa_no']))     $byBod[$bodLimpio]['manifiestos_set'][$m['guiasa_no']]     = true;
            $obj = $m['objeto_trazable'] ?: '(sin)';
            $byBod[$bodLimpio]['objetos_count'][$obj] = ($byBod[$bodLimpio]['objetos_count'][$obj] ?? 0) + 1;
        }

        foreach ($byBod as &$b) {
            $b['beneficiarios_unicos'] = count($b['beneficiarios_set']);
            $b['manifiestos_unicos']   = count($b['manifiestos_set']);
            unset($b['beneficiarios_set'], $b['manifiestos_set']);
            // Top 3 objetos
            arsort($b['objetos_count']);
            $top = [];
            $i = 0;
            foreach ($b['objetos_count'] as $obj => $cnt) {
                $top[] = ['objeto' => $obj, 'cantidad' => $cnt];
                if (++$i >= 3) break;
            }
            $b['top_objetos']     = $top;
            $b['objetos_unicos']  = count($b['objetos_count']);
            unset($b['objetos_count']);
        }
        unset($b);

        $out = array_values($byBod);
        usort($out, fn($a, $b) => $b['movimientos'] <=> $a['movimientos']);
        return $out;
    }

    // ════════════════════════════════════════════════════════════
    //  ANOMALÍAS
    // ════════════════════════════════════════════════════════════

    private function recolectarAnomalias(array $movs): array
    {
        $out = [];
        foreach ($movs as $m) {
            if (empty($m['alertas'])) continue;
            foreach ($m['alertas'] as $a) {
                $out[] = [
                    'movement_id'         => $m['movement_id'] ?? 0,
                    'dni'                 => $m['destino_dni'] ?? '',
                    'nombre'              => $m['destino_nombre'] ?: $m['destino_persona'] ?: '(sin nombre)',
                    'guiasa'              => $m['guiasa_no'] ?? '',
                    'objeto'              => $m['objeto_trazable'] ?? '',
                    'codigo_trazabilidad' => $m['codigo_trazabilidad'] ?? '',
                    'fecha'               => $m['fecha_autorizacion'] ?? '',
                    'tipo'                => $this->clasificarAlerta($a),
                    'alerta'              => $a,
                ];
            }
        }
        // Ordenar por tipo (más severas primero) y fecha
        $orden = ['sin_dni' => 1, 'no_padron' => 2, 'duplicado' => 3, 'cantidad' => 4, 'otro' => 5];
        usort($out, function ($a, $b) use ($orden) {
            $oa = $orden[$a['tipo']] ?? 9;
            $ob = $orden[$b['tipo']] ?? 9;
            if ($oa !== $ob) return $oa <=> $ob;
            return strcmp($b['fecha'], $a['fecha']);
        });
        return $out;
    }

    private function clasificarAlerta(string $a): string
    {
        $a = mb_strtolower($a);
        if (str_contains($a, 'sin dni') || str_contains($a, 'sin_dni')) return 'sin_dni';
        if (str_contains($a, 'padrón') || str_contains($a, 'padron'))   return 'no_padron';
        if (str_contains($a, 'duplicado'))                              return 'duplicado';
        if (str_contains($a, 'cantidad'))                               return 'cantidad';
        if (str_contains($a, 'estado'))                                 return 'estado';
        return 'otro';
    }

    // ════════════════════════════════════════════════════════════
    //  DESCUENTO DE INVENTARIO (stub — pendiente módulo Inventarios real)
    // ════════════════════════════════════════════════════════════

    /**
     * STUB: cuando una entrega tiene Código de Trazabilidad (= confirmada en OIRSA)
     * deberíamos descontar el insumo del stock de la bodega de origen.
     *
     * Para que esto funcione completo necesitamos:
     *  1) Mapear sag_bodegas con los SourceEndpointCode de OIRSA (CUE)
     *  2) Mantener stock real en sag_inventario_movimientos
     *  3) Cablear InventariosController::registrarSalida() — hoy es mock
     *
     * Por ahora SOLO registramos en sag_logs los movimientos que YA están
     * confirmados, para tener trazabilidad de qué debería haberse descontado.
     */
    private function prepararDescuentoInventario(array $movs): void
    {
        try {
            $db = Database::programa();
        } catch (\Throwable $e) {
            return;
        }
        $confirmados = 0;
        foreach ($movs as $m) {
            if (empty($m['codigo_trazabilidad'])) continue;
            $confirmados++;
            // TODO: cuando InventariosController esté real,
            //       llamar a registrarSalida(bodega, producto, cantidad, ref_movement_id)
        }
        if ($confirmados > 0) {
            $this->logAction(
                'PENDIENTE_DESCUENTO_INV', 'entregas',
                "Movimientos con código de trazabilidad que deberían descontar stock: {$confirmados}"
            );
        }
    }

    // ════════════════════════════════════════════════════════════
    //  SINCRONIZACIÓN (botón "Sincronizar con Trazaragro")
    // ════════════════════════════════════════════════════════════
    public function sincronizarTrazaragro(): void
    {
        try {
            if (!class_exists('TrazaragroClient')) {
                $this->error('core/TrazaragroClient.php no está desplegado.');
                return;
            }

            $cli  = new TrazaragroClient();
            $ping = $cli->ping();
            if (!$ping['ok']) {
                $this->error('No se pudo contactar a Trazaragro: ' . ($ping['msg'] ?? 'sin respuesta'));
                return;
            }

            $desde   = $this->getPost('desde', date('Y-m-d', strtotime('-30 days')));
            $hasta   = $this->getPost('hasta', date('Y-m-d'));
            $top     = (int) $this->getPost('top', 500);
            $limpiar = (int) $this->getPost('limpiar', 0);

            // Rubro del programa activo (filtro OIRSA)
            $progId = $_SESSION['programa']['id'] ?? '';
            $rubro  = PROGRAMAS[$progId]['trazaragro_rubro'] ?? null;

            if ($limpiar) {
                $borradas = $this->limpiarMovimientos();
                error_log("sincronizarTrazaragro: limpieza solicitada, {$borradas} filas borradas");
            }

            $movimientos = $cli->fetchEntregas([
                'top'   => $top,
                'desde' => $desde,
                'hasta' => $hasta,
                'rubro' => $rubro,
            ]);

            $stats = $this->persistirMovimientos($movimientos);

            // Stub: registra qué movimientos deberían descontar stock cuando Inventarios esté listo
            $this->prepararDescuentoInventario($movimientos);

            // Resumen amigable
            $resumen = "Trazaragro ({$ping['mode']}): {$stats['recibidos']} movimientos sincronizados";
            if ($stats['insertados'])   $resumen .= " · {$stats['insertados']} nuevos";
            if ($stats['actualizados']) $resumen .= " · {$stats['actualizados']} actualizados";
            if ($stats['errores'])      $resumen .= " · {$stats['errores']} con error";

            $this->logAction('SYNC_TRAZARAGRO', 'entregas',
                "rubro=" . ($rubro ?: 'todos') . " · {$resumen}");

            $this->success($resumen, [
                'rubro_filtrado' => $rubro,
                'recibidos'      => $stats['recibidos'],
                'insertados'     => $stats['insertados'],
                'actualizados'   => $stats['actualizados'],
                'errores'        => $stats['errores'],
                'errores_det'    => $stats['errores_det'] ?? [],
                'modo'           => $ping['mode'],
                'rango'          => ['desde' => $desde, 'hasta' => $hasta],
            ]);
        } catch (\Throwable $e) {
            error_log('sincronizarTrazaragro EX: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $this->error('Error interno: ' . $e->getMessage() . ' (línea ' . $e->getLine() . ' de ' . basename($e->getFile()) . ')');
        }
    }

    /**
     * Persiste los movimientos recibidos de OIRSA en sag_trazaragro_movimientos.
     * UPSERT por movement_id (PK). Tabla flat — sin agregaciones, sin joins.
     */
    private function persistirMovimientos(array $movs): array
    {
        $stats = [
            'recibidos'    => count($movs),
            'insertados'   => 0,
            'actualizados' => 0,
            'errores'      => 0,
            'errores_det'  => [],
        ];

        if (empty($movs)) return $stats;

        try {
            $db = Database::programa();
        } catch (\Throwable $e) {
            $stats['errores']++;
            $stats['errores_det'][] = 'No se pudo conectar al programa: ' . $e->getMessage();
            return $stats;
        }

        $sql = "INSERT INTO sag_trazaragro_movimientos (
            movement_id, rubro, rubro_id, tipo_movimiento, tipo_movimiento_id, actividad_id,
            objeto_trazable, objeto_trazable_codigo, codigo_trazabilidad,
            guiasa_no, codigo_autorizacion,
            fecha_registro, fecha_autorizacion, fecha_expiracion,
            origen_persona, origen_establecimiento, origen_cue, origen_departamento, origen_municipio,
            destino_persona, destino_dni, destino_nombre, destino_establecimiento, destino_cue, destino_departamento, destino_municipio,
            cantidad, unidad, transportista, vehiculo, condicion, proposito,
            autorizado_por, creado_por, status_oirsa, status_id, event_stage, is_completed,
            estado_local, raw_json, synced_at
        ) VALUES (
            :movement_id, :rubro, :rubro_id, :tipo_movimiento, :tipo_movimiento_id, :actividad_id,
            :objeto_trazable, :objeto_trazable_codigo, :codigo_trazabilidad,
            :guiasa_no, :codigo_autorizacion,
            :fecha_registro, :fecha_autorizacion, :fecha_expiracion,
            :origen_persona, :origen_establecimiento, :origen_cue, :origen_departamento, :origen_municipio,
            :destino_persona, :destino_dni, :destino_nombre, :destino_establecimiento, :destino_cue, :destino_departamento, :destino_municipio,
            :cantidad, :unidad, :transportista, :vehiculo, :condicion, :proposito,
            :autorizado_por, :creado_por, :status_oirsa, :status_id, :event_stage, :is_completed,
            :estado_local, :raw_json, NOW()
        )
        ON DUPLICATE KEY UPDATE
            rubro = VALUES(rubro),
            tipo_movimiento = VALUES(tipo_movimiento),
            objeto_trazable = VALUES(objeto_trazable),
            codigo_trazabilidad = VALUES(codigo_trazabilidad),
            guiasa_no = VALUES(guiasa_no),
            codigo_autorizacion = VALUES(codigo_autorizacion),
            fecha_registro = VALUES(fecha_registro),
            fecha_autorizacion = VALUES(fecha_autorizacion),
            fecha_expiracion = VALUES(fecha_expiracion),
            origen_persona = VALUES(origen_persona),
            origen_establecimiento = VALUES(origen_establecimiento),
            origen_cue = VALUES(origen_cue),
            origen_departamento = VALUES(origen_departamento),
            origen_municipio = VALUES(origen_municipio),
            destino_persona = VALUES(destino_persona),
            destino_dni = VALUES(destino_dni),
            destino_nombre = VALUES(destino_nombre),
            destino_establecimiento = VALUES(destino_establecimiento),
            destino_cue = VALUES(destino_cue),
            destino_departamento = VALUES(destino_departamento),
            destino_municipio = VALUES(destino_municipio),
            cantidad = VALUES(cantidad),
            unidad = VALUES(unidad),
            transportista = VALUES(transportista),
            vehiculo = VALUES(vehiculo),
            condicion = VALUES(condicion),
            proposito = VALUES(proposito),
            autorizado_por = VALUES(autorizado_por),
            creado_por = VALUES(creado_por),
            status_oirsa = VALUES(status_oirsa),
            status_id = VALUES(status_id),
            event_stage = VALUES(event_stage),
            is_completed = VALUES(is_completed),
            estado_local = VALUES(estado_local),
            raw_json = VALUES(raw_json),
            synced_at = NOW()
        ";

        foreach ($movs as $m) {
            try {
                $isCompleted = !empty($m['is_completed']) ? 1 : 0;
                $tieneCodigo = !empty($m['codigo_trazabilidad']);
                $estadoLocal = ($isCompleted || $tieneCodigo) ? 'entregado' : 'pendiente';

                $movId = (int)($m['trazaragro_id'] ?? 0);
                if ($movId <= 0) {
                    $stats['errores']++;
                    $stats['errores_det'][] = 'Movimiento sin MovementId, saltado';
                    continue;
                }

                // Verificar si existía (para contar insert vs update)
                $existe = $db->fetchOne(
                    "SELECT movement_id FROM sag_trazaragro_movimientos WHERE movement_id = ?",
                    [$movId]
                );

                $params = [
                    ':movement_id'           => $movId,
                    ':rubro'                 => $m['rubro'] ?: null,
                    ':rubro_id'              => $m['rubro_id'] ?: null,
                    ':tipo_movimiento'       => $m['tipo_movimiento'] ?: null,
                    ':tipo_movimiento_id'    => $m['tipo_movimiento_id'] ?: null,
                    ':actividad_id'          => $m['actividad_id'] ?: null,
                    ':objeto_trazable'       => $m['objeto_trazable'] ?: null,
                    ':objeto_trazable_codigo'=> $m['objeto_trazable_codigo'] ?: null,
                    ':codigo_trazabilidad'   => $m['codigo_trazabilidad'] ?: null,
                    ':guiasa_no'             => $m['registration_code'] ?: null,
                    ':codigo_autorizacion'   => $m['authorization_code'] ?: null,
                    ':fecha_registro'        => $m['fecha_registro'] ?: null,
                    ':fecha_autorizacion'    => $m['fecha_autorizacion'] ?: null,
                    ':fecha_expiracion'      => $m['fecha_expiracion'] ?: null,
                    ':origen_persona'        => $m['origen_persona'] ?: null,
                    ':origen_establecimiento'=> $m['origen_establecimiento'] ?: null,
                    ':origen_cue'            => $m['origen_cue'] ?: null,
                    ':origen_departamento'   => $m['origen_departamento'] ?: null,
                    ':origen_municipio'      => $m['origen_municipio'] ?: null,
                    ':destino_persona'       => $m['destino_persona'] ?: null,
                    ':destino_dni'           => $m['destino_dni'] ?: null,
                    ':destino_nombre'        => $m['destino_nombre'] ?: null,
                    ':destino_establecimiento'=> $m['destino_establecimiento'] ?: null,
                    ':destino_cue'           => $m['destino_cue'] ?: null,
                    ':destino_departamento'  => $m['destino_departamento'] ?: null,
                    ':destino_municipio'     => $m['destino_municipio'] ?: null,
                    ':cantidad'              => (float)($m['cantidad'] ?? 0),
                    ':unidad'                => $m['unidad'] ?: null,
                    ':transportista'         => $m['transportista'] ?: null,
                    ':vehiculo'              => $m['vehiculo'] ?: null,
                    ':condicion'             => $m['condicion'] ?: null,
                    ':proposito'             => $m['proposito'] ?: null,
                    ':autorizado_por'        => $m['usuario_autoriza'] ?: null,
                    ':creado_por'            => $m['usuario_crea'] ?: null,
                    ':status_oirsa'          => $m['status'] ?: null,
                    ':status_id'             => $m['status_id'] ?: null,
                    ':event_stage'           => $m['event_stage'] ?: null,
                    ':is_completed'          => $isCompleted,
                    ':estado_local'          => $estadoLocal,
                    ':raw_json'              => json_encode($m['raw'] ?? [], JSON_UNESCAPED_UNICODE),
                ];

                $db->execute($sql, $params);
                if ($existe) $stats['actualizados']++;
                else         $stats['insertados']++;
            } catch (\Throwable $e) {
                $stats['errores']++;
                $stats['errores_det'][] = 'MovementId ' . ($m['trazaragro_id'] ?? '?') . ': ' . $e->getMessage();
                error_log('persistirMovimientos: ' . $e->getMessage());
            }
        }

        return $stats;
    }

    private function limpiarMovimientos(): int
    {
        try {
            $db = Database::programa();
            return $db->execute("DELETE FROM sag_trazaragro_movimientos");
        } catch (\Throwable $e) {
            return 0;
        }
    }

    // ════════════════════════════════════════════════════════════
    //  EXPORTAR CSV (idéntico al export de OIRSA)
    // ════════════════════════════════════════════════════════════
    public function exportar(): void
    {
        try {
            $db = Database::programa();
        } catch (\Throwable $e) {
            http_response_code(500);
            echo 'Error: ' . $e->getMessage();
            exit;
        }

        $rows = $db->fetchAll("
            SELECT *
            FROM sag_trazaragro_movimientos
            ORDER BY fecha_autorizacion DESC, movement_id DESC
        ");

        $sigla = $_SESSION['programa']['sigla'] ?? 'SAG';
        $fname = "trazaragro_movimientos_{$sigla}_" . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        echo "\xEF\xBB\xBF"; // BOM UTF-8 para Excel

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'Rubro',
            'Tipo de movimiento',
            'Objeto trazable',
            'Código de Trazabilidad',
            'GUIASA No.',
            'Código de autorización',
            'Fecha',
            'Origen - Persona',
            'Origen - Establecimiento',
            'Origen - Departamento',
            'Destino - Persona',
            'Destino - DNI',
            'Destino - Establecimiento',
            'Destino - CUE',
            'Destino - Departamento',
            'Destino - Municipio',
            'Cantidad',
            'Unidad',
            'Autorizado por',
            'Estado',
        ], ';');

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['rubro'],
                $r['tipo_movimiento'],
                $r['objeto_trazable'],
                $r['codigo_trazabilidad'],
                $r['guiasa_no'],
                $r['codigo_autorizacion'],
                $r['fecha_autorizacion'],
                $r['origen_persona'],
                $r['origen_establecimiento'],
                $r['origen_departamento'],
                $r['destino_persona'],
                $r['destino_dni'],
                $r['destino_establecimiento'],
                $r['destino_cue'],
                $r['destino_departamento'],
                $r['destino_municipio'],
                $r['cantidad'],
                $r['unidad'],
                $r['autorizado_por'],
                $r['estado_local'],
            ], ';');
        }
        fclose($out);
        exit;
    }

    // ════════════════════════════════════════════════════════════
    //  ACTA imprimible por productor
    //  GET /entregas/acta?dni=XXXX
    //  Devuelve una vista HTML formateada para imprimir (Ctrl+P → Guardar como PDF)
    // ════════════════════════════════════════════════════════════
    public function acta(): void
    {
        $dni = trim((string)($_GET['dni'] ?? ''));
        if ($dni === '') {
            http_response_code(400);
            echo 'Falta parámetro ?dni=';
            exit;
        }

        try {
            $db = Database::programa();
        } catch (\Throwable $e) {
            http_response_code(500);
            echo 'Error: ' . $e->getMessage();
            exit;
        }

        $movs = $db->fetchAll(
            "SELECT * FROM sag_trazaragro_movimientos
             WHERE destino_dni = ?
             ORDER BY fecha_autorizacion ASC, guiasa_no ASC",
            [$dni]
        );

        if (empty($movs)) {
            http_response_code(404);
            echo 'No se encontraron movimientos para DNI ' . htmlspecialchars($dni);
            exit;
        }

        // Datos consolidados del productor
        $primero = $movs[0];
        $beneficiario = [
            'dni'             => $primero['destino_dni'],
            'nombre'          => $primero['destino_nombre'] ?: $primero['destino_persona'],
            'departamento'    => $primero['destino_departamento'],
            'municipio'       => $primero['destino_municipio'],
            'establecimiento' => $primero['destino_establecimiento'],
            'cue'             => $primero['destino_cue'],
        ];

        // Agrupar por GUIASA (cada acta puede tener múltiples GUIASAs)
        $porGuiasa = [];
        foreach ($movs as $m) {
            $g = $m['guiasa_no'] ?: '(sin GUIASA)';
            if (!isset($porGuiasa[$g])) {
                $porGuiasa[$g] = [
                    'guiasa'              => $g,
                    'codigo_autorizacion' => $m['codigo_autorizacion'],
                    'fecha'               => $m['fecha_autorizacion'],
                    'autorizado_por'      => $m['autorizado_por'],
                    'bodega_origen'       => $m['origen_establecimiento'],
                    'rubro'               => $m['rubro'],
                    'items'               => [],
                    'total_items'         => 0,
                    'total_cantidad'      => 0,
                ];
            }
            $porGuiasa[$g]['items'][] = $m;
            $porGuiasa[$g]['total_items']++;
            $porGuiasa[$g]['total_cantidad'] += (float)($m['cantidad'] ?? 0);
        }

        $programa = $_SESSION['programa'] ?? [];
        $usuario  = $_SESSION['user'] ?? [];

        // Renderizar vista de acta (sin layout)
        require ROOT_PATH . '/app/views/entregas/acta.php';
        exit;
    }

    // ════════════════════════════════════════════════════════════
    //  DETALLE de un movimiento (modal)
    // ════════════════════════════════════════════════════════════
    public function detalle(): void
    {
        $id = (int) $this->getPost('id', 0);
        try {
            $db  = Database::programa();
            $row = $db->fetchOne(
                "SELECT * FROM sag_trazaragro_movimientos WHERE movement_id = ?",
                [$id]
            );
            if (!$row) { $this->error('Movimiento no encontrado.'); return; }
            // Decodificar el raw_json para enviarlo como objeto
            if (!empty($row['raw_json'])) {
                $row['raw'] = json_decode($row['raw_json'], true);
            }
            $this->success('OK', ['movimiento' => $row]);
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════
    //  DIAGNÓSTICO (mantenido por compatibilidad)
    // ════════════════════════════════════════════════════════════
    public function diag(): void
    {
        $base = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
        $checks = [
            'core/TrazaragroClient.php'                     => $base . '/core/TrazaragroClient.php',
            'app/controllers/EntregasController.php'        => $base . '/app/controllers/EntregasController.php',
            'sql/migracion_004_trazaragro_movimientos.sql'  => $base . '/sql/migracion_004_trazaragro_movimientos.sql',
        ];
        $report = [];
        foreach ($checks as $rel => $abs) {
            $exists = is_file($abs);
            $report[] = [
                'archivo'   => $rel,
                'existe'    => $exists,
                'tamano'    => $exists ? filesize($abs) : 0,
                'modificado'=> $exists ? date('Y-m-d H:i:s', filemtime($abs)) : null,
            ];
        }

        $tablaExiste = false; $totalMovs = 0;
        try {
            $db = Database::programa();
            $r  = $db->fetchOne("SHOW TABLES LIKE 'sag_trazaragro_movimientos'");
            $tablaExiste = (bool)$r;
            if ($tablaExiste) {
                $r2 = $db->fetchOne("SELECT COUNT(*) AS c FROM sag_trazaragro_movimientos");
                $totalMovs = (int)($r2['c'] ?? 0);
            }
        } catch (\Throwable $e) {}

        $this->success('Diagnóstico de movimientos Trazaragro', [
            'archivos' => $report,
            'config'   => [
                'TRAZARAGRO_definido' => defined('TRAZARAGRO'),
                'TRAZARAGRO_url'      => defined('TRAZARAGRO') ? (TRAZARAGRO['base_url'] ?? '') : '',
                'TRAZARAGRO_user_set' => defined('TRAZARAGRO') ? !empty(TRAZARAGRO['username']) : false,
                'php_version'         => PHP_VERSION,
                'session_programa'    => $_SESSION['programa']['sigla'] ?? '(ninguno)',
                'rubro_programa'      => $_SESSION['programa'] && ($_SESSION['programa']['id'] ?? null)
                                          ? (PROGRAMAS[$_SESSION['programa']['id']]['trazaragro_rubro'] ?? null)
                                          : null,
            ],
            'tabla_existe' => $tablaExiste,
            'total_movs'   => $totalMovs,
        ]);
    }

    // ════════════════════════════════════════════════════════════
    //  ENDPOINTS LEGACY (mantenidos por compatibilidad)
    // ════════════════════════════════════════════════════════════
    public function sincronizar(): void
    {
        // KoBo legacy — mock; se implementará cuando KoboClient esté listo
        $this->success('Sincronización con KoBo aún no implementada (mock)', [
            'origen' => 'kobo', 'mock' => true,
        ]);
    }

    public function aprobar(): void
    {
        // No aplica directamente en la vista OIRSA. Reservado para futura
        // funcionalidad de marcar revisión local sobre un movimiento.
        $id  = (int) $this->getPost('id', 0);
        $obs = $this->getPost('observacion', '');
        try {
            $db = Database::programa();
            $db->execute(
                "UPDATE sag_trazaragro_movimientos
                 SET estado_local='entregado', observaciones_local=?, revisado_por_local=?, fecha_revision_local=NOW()
                 WHERE movement_id=?",
                [$obs, $_SESSION['user']['email'] ?? 'sistema', $id]
            );
            $this->logAction('APROBAR_MOV', 'entregas', "#{$id}");
            $this->success("Movimiento #{$id} marcado como entregado.", ['id' => $id]);
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }

    public function rechazar(): void
    {
        $id  = (int) $this->getPost('id', 0);
        $obs = $this->getPost('observacion', '');
        if (!$obs) { $this->error('Debe indicar el motivo del rechazo.'); return; }
        try {
            $db = Database::programa();
            $db->execute(
                "UPDATE sag_trazaragro_movimientos
                 SET estado_local='observado', observaciones_local=?, revisado_por_local=?, fecha_revision_local=NOW()
                 WHERE movement_id=?",
                [$obs, $_SESSION['user']['email'] ?? 'sistema', $id]
            );
            $this->logAction('OBSERVAR_MOV', 'entregas', "#{$id} obs={$obs}");
            $this->success("Movimiento #{$id} observado.", ['id' => $id]);
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
