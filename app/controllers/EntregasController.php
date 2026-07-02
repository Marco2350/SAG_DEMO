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
    private array $resumenInventarioSync = [];

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

        // KPIs, reporte por bodega y anomalías se calculan recorriendo la tabla
        // en bloques de tamaño fijo (escanearMovimientos). El listado de
        // movimientos y el reporte por productor ya no se materializan aquí:
        // se sirven paginados por AJAX (datos / datosProductores). Cargarlo
        // todo agotaba el memory_limit de PHP en producción (~80k filas).
        $scan                 = $this->escanearMovimientos();
        $kpis                 = $scan['kpis'];
        $reporteBodegas       = $scan['bodegas'];
        $anomalias            = $scan['anomalias'];
        $totalAnomalias       = $scan['total_anomalias'];
        $totalProductores     = $scan['total_productores'];
        $reporteDepartamentos = $this->inventarioOirsaPorDepartamento();
        $catalogos            = $this->cargarCatalogosFiltros();
        $conteosOirsaTipos    = $this->conteosTrazaragroPorTipo();
        // FASE 3 — fecha/hora de la última sincronización con OIRSA del programa actual.
        // Se muestra como chip junto al botón "Sincronizar" para que el usuario
        // sepa qué tan fresca está la data antes de decidir sincronizar.
        $ultimaSyncedAt       = $this->ultimaFechaAutorizacionDelPrograma();

        // Para badge en sidebar — actualiza el contador de alertas en sesión
        $_SESSION['entregas_alertas_count'] = $kpis['con_alertas'] ?? 0;

        $esAdmin = Permisos::puedeEn('entregas', ACC_APROBAR);

        $this->view('entregas/index', compact(
            'pageTitle', 'kpis', 'catalogos', 'esAdmin',
            'reporteDepartamentos', 'totalProductores', 'reporteBodegas',
            'anomalias', 'totalAnomalias', 'conteosOirsaTipos', 'ultimaSyncedAt'
        ));
    }

    // ════════════════════════════════════════════════════════════
    //  LISTADO PAGINADO (AJAX) — tab "Movimientos"
    // ════════════════════════════════════════════════════════════

    /** Columnas que consumen la tabla y el modal de detalle en entregas.js. */
    private const COLS_LISTADO = 'id, movement_id, rubro, tipo_movimiento, objeto_trazable,
            codigo_trazabilidad, guiasa_no, codigo_autorizacion,
            fecha_registro, fecha_autorizacion, fecha_expiracion,
            origen_persona, origen_establecimiento, origen_cue, origen_departamento, origen_municipio,
            destino_persona, destino_dni, destino_nombre, destino_establecimiento, destino_cue,
            destino_departamento, destino_municipio,
            cantidad, unidad, transportista, vehiculo, condicion, proposito,
            autorizado_por, creado_por, status_oirsa, estado_local, synced_at';

    /**
     * Devuelve una página de movimientos con los filtros aplicados en SQL.
     * Reemplaza al antiguo window.OIRSA_MOVS (todo el dataset embebido en la
     * página + filtrado client-side), que agotaba la memoria en producción.
     */
    public function datos(): void
    {
        $this->requireCsrf();
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();

            $porPagina = min(300, max(20, (int)$this->getPost('per_page', 100)));
            $pagina    = max(1, (int)$this->getPost('page', 1));

            [$where, $params] = $this->filtrosListadoSql($pid);

            $total = (int)($db->fetchOne(
                "SELECT COUNT(*) AS c FROM sag_trazaragro_movimientos WHERE {$where}",
                $params
            )['c'] ?? 0);

            // LIMIT/OFFSET van inline (enteros saneados): PDO en modo nativo
            // no acepta placeholders para LIMIT.
            $offset = ($pagina - 1) * $porPagina;
            $rows   = $offset < $total
                ? $db->fetchAll(
                    'SELECT ' . self::COLS_LISTADO . "
                       FROM sag_trazaragro_movimientos
                      WHERE {$where}
                   ORDER BY fecha_autorizacion DESC, id DESC
                      LIMIT {$porPagina} OFFSET {$offset}",
                    $params
                )
                : [];

            $this->success('OK', [
                'total'    => $total,
                'page'     => $pagina,
                'per_page' => $porPagina,
                'rows'     => $rows,
            ]);
        } catch (\Throwable $e) {
            error_log('EntregasController::datos — ' . $e->getMessage());
            $this->error('No se pudo cargar el listado de movimientos.');
        }
    }

    /** Construye el WHERE del listado a partir de los filtros del request. */
    private function filtrosListadoSql(int $pid): array
    {
        $where  = ['id_proyecto = ?', 'tipo_movimiento_id = 111'];
        $params = [$pid];

        foreach ([
            'rubro'  => 'rubro',
            'tipo'   => 'tipo_movimiento',
            'objeto' => 'objeto_trazable',
            'depto'  => 'destino_departamento',
            'estado' => 'estado_local',
        ] as $campo => $columna) {
            $v = (string)$this->getPost($campo, '');
            if ($v !== '') {
                $where[]  = "{$columna} = ?";
                $params[] = $v;
            }
        }

        $desde = (string)$this->getPost('desde', '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $where[]  = 'fecha_autorizacion >= ?';
            $params[] = $desde;
        }
        $hasta = (string)$this->getPost('hasta', '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $where[]  = 'fecha_autorizacion < ?';
            $params[] = date('Y-m-d', strtotime($hasta . ' +1 day'));
        }

        $busca = trim((string)$this->getPost('busca', ''));
        if ($busca !== '') {
            $like = '%' . addcslashes($busca, '%_\\') . '%';
            $cols = ['destino_dni', 'destino_nombre', 'destino_persona', 'guiasa_no',
                     'codigo_autorizacion', 'codigo_trazabilidad', 'objeto_trazable', 'autorizado_por'];
            $where[] = '(' . implode(' OR ', array_map(fn($c) => "{$c} LIKE ?", $cols)) . ')';
            foreach ($cols as $c) {
                $params[] = $like;
            }
        }

        return [implode(' AND ', $where), $params];
    }

    private function cargarCatalogosFiltros(): array
    {
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();
            // Aislamiento: los valores DISTINCT salen sólo de los movimientos del PIP activo.
            // Filtro tipo_movimiento_id=111: los catálogos del módulo Entregas
            // solo deben mostrar valores presentes en Entregas (Bodega→Productor),
            // no en Recepciones (Proveedor→Bodega, que se muestran en Inventario OIRSA).
            $rubros = $db->fetchAll(
                "SELECT DISTINCT rubro FROM sag_trazaragro_movimientos
                 WHERE id_proyecto = ? AND tipo_movimiento_id = 111
                   AND rubro IS NOT NULL AND rubro <> '' ORDER BY rubro",
                [$pid]
            );
            $tipos = $db->fetchAll(
                "SELECT DISTINCT tipo_movimiento FROM sag_trazaragro_movimientos
                 WHERE id_proyecto = ? AND tipo_movimiento_id = 111
                   AND tipo_movimiento IS NOT NULL AND tipo_movimiento <> '' ORDER BY tipo_movimiento",
                [$pid]
            );
            $deptos = $db->fetchAll(
                "SELECT DISTINCT destino_departamento AS depto FROM sag_trazaragro_movimientos
                 WHERE id_proyecto = ? AND tipo_movimiento_id = 111
                   AND destino_departamento IS NOT NULL AND destino_departamento <> '' ORDER BY depto",
                [$pid]
            );
            $objetos = $db->fetchAll(
                "SELECT DISTINCT objeto_trazable FROM sag_trazaragro_movimientos
                 WHERE id_proyecto = ? AND tipo_movimiento_id = 111
                   AND objeto_trazable IS NOT NULL AND objeto_trazable <> '' ORDER BY objeto_trazable",
                [$pid]
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

    // ════════════════════════════════════════════════════════════
    //  ESCANEO POR LOTES: KPIs + bodegas + anomalías + padrón
    // ════════════════════════════════════════════════════════════

    private const SCAN_LOTE               = 5000;
    private const MAX_ANOMALIAS_LISTADAS  = 500;
    private const MAX_OBJETOS_POR_PRODUCTOR = 300;

    /**
     * Recorre las entregas del programa en bloques de SCAN_LOTE filas
     * (keyset sobre movement_id) y acumula KPIs, reporte por bodega,
     * anomalías y productores únicos con memoria acotada.
     *
     * Reemplaza al flujo cargarMovimientos()+enriquecerMovimientos()+
     * calcularKpis()+reportePorBodega()+recolectarAnomalias(), que
     * materializaba el dataset completo y agotaba el memory_limit.
     * Los criterios de alerta son los mismos que aplicaba enriquecerMovimientos.
     */
    private function escanearMovimientos(): array
    {
        $kpis = [
            'total_movimientos'    => 0,
            'entregados'           => 0,
            'pendientes'           => 0,
            'beneficiarios_unicos' => 0,
            'manifiestos_unicos'   => 0,   // GUIASA No.
            'cantidad_total'       => 0,
            'desglose_objetos'     => [],
            'con_alertas'          => 0,
            'no_en_padron'         => 0,
        ];
        $resultado = [
            'kpis'              => $kpis,
            'bodegas'           => [],
            'anomalias'         => [],
            'total_anomalias'   => 0,
            'total_productores' => 0,
        ];

        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();
            if (!$db->tablaExiste('sag_trazaragro_movimientos')) {
                return $resultado;
            }
        } catch (\Throwable $e) {
            return $resultado;
        }

        $padron = $this->cargarPadron();

        // Duplicados DNI+objeto precalculados en SQL. Configurable por programa:
        // algunos PIPs (ej PIPC: 2 sacos de fertilizante por productor como
        // mínimo) generan duplicados legítimos por diseño, así que respetamos
        // el flag 'detectar_dup_dni_objeto' del config.
        $progId        = $_SESSION['programa']['id'] ?? '';
        $detectarDup   = (bool)(PROGRAMAS[$progId]['detectar_dup_dni_objeto'] ?? true);
        $contDniObjeto = [];
        if ($detectarDup) {
            try {
                $dups = $db->fetchAll(
                    "SELECT TRIM(destino_dni) AS dni, TRIM(objeto_trazable) AS obj, COUNT(*) AS c
                       FROM sag_trazaragro_movimientos
                      WHERE id_proyecto = ? AND tipo_movimiento_id = 111
                        AND TRIM(COALESCE(destino_dni, '')) <> ''
                        AND TRIM(COALESCE(objeto_trazable, '')) <> ''
                   GROUP BY TRIM(destino_dni), TRIM(objeto_trazable)
                     HAVING COUNT(*) > 1",
                    [$pid]
                );
                foreach ($dups as $d) {
                    $contDniObjeto[$d['dni'] . '||' . $d['obj']] = (int)$d['c'];
                }
                unset($dups);
            } catch (\Throwable $e) {
                error_log('escanearMovimientos (duplicados): ' . $e->getMessage());
            }
        }

        $beneficiarios  = [];
        $manifiestos    = [];
        $porObjeto      = [];
        $byBod          = [];
        $productores    = [];
        $anomalias      = [];
        $totalAnomalias = 0;
        $ultimoId       = -1;

        while (true) {
            try {
                // Keyset sobre la PK auto-increment `id` (movement_id se repite:
                // un movimiento OIRSA agrupa varios ítems/códigos de trazabilidad).
                $lote = $db->fetchAll(
                    "SELECT id, movement_id, destino_dni, destino_nombre, destino_persona,
                            objeto_trazable, cantidad, unidad, estado_local, is_completed,
                            codigo_trazabilidad, guiasa_no, fecha_autorizacion,
                            origen_establecimiento, origen_cue, origen_departamento
                       FROM sag_trazaragro_movimientos
                      WHERE id_proyecto = ? AND tipo_movimiento_id = 111 AND id > ?
                   ORDER BY id ASC
                      LIMIT " . self::SCAN_LOTE,
                    [$pid, $ultimoId]
                );
            } catch (\Throwable $e) {
                error_log('escanearMovimientos: ' . $e->getMessage());
                break;
            }
            if (!$lote) break;

            foreach ($lote as $m) {
                $ultimoId = (int)$m['id'];
                $dni      = trim((string)($m['destino_dni'] ?? ''));
                $obj      = trim((string)($m['objeto_trazable'] ?? ''));
                $cantidad = (float)($m['cantidad'] ?? 0);

                // ── KPIs ──
                $kpis['total_movimientos']++;
                if (!empty($m['destino_dni'])) $beneficiarios[$m['destino_dni']] = true;
                if (!empty($m['guiasa_no']))   $manifiestos[$m['guiasa_no']]     = true;
                $entregado = !empty($m['is_completed']) || !empty($m['codigo_trazabilidad']);
                if ($entregado) $kpis['entregados']++;
                else            $kpis['pendientes']++;
                $kpis['cantidad_total'] += $cantidad;

                $objKey = $obj !== '' ? $obj : '(sin nombre)';
                if (!isset($porObjeto[$objKey])) {
                    $porObjeto[$objKey] = [
                        'objeto'      => $objKey,
                        'unidad'      => $m['unidad'] ?? '',
                        'movimientos' => 0,
                        'entregados'  => 0,
                        'pendientes'  => 0,
                        'cantidad'    => 0,
                    ];
                }
                $porObjeto[$objKey]['movimientos']++;
                $porObjeto[$objKey]['cantidad'] += $cantidad;
                if ($entregado) $porObjeto[$objKey]['entregados']++;
                else            $porObjeto[$objKey]['pendientes']++;

                // ── Productores únicos (misma clave que datosProductores) ──
                $productores[$dni !== '' ? $dni : '(sin-dni)-' . md5((string)($m['destino_persona'] ?? ''))] = true;

                // ── Alertas ──
                $alertas = [];
                if ($dni === '') {
                    $alertas[] = 'Movimiento sin DNI de beneficiario';
                } elseif (isset($padron[$dni])) {
                    $estado = strtolower($padron[$dni]);
                    if ($estado && !in_array($estado, ['activo', 'activa', '1', 'vigente'])) {
                        $alertas[] = "Beneficiario en estado: {$estado}";
                    }
                } elseif (!empty($padron)) {
                    $alertas[] = 'DNI no encontrado en el padrón del programa';
                    $kpis['no_en_padron']++;
                }
                if ($dni !== '' && $obj !== '' && ($contDniObjeto[$dni . '||' . $obj] ?? 0) > 1) {
                    $alertas[] = "Posible duplicado: este productor recibió '{$obj}' en {$contDniObjeto[$dni . '||' . $obj]} movimientos";
                }
                if ($cantidad <= 0) {
                    $alertas[] = 'Cantidad cero o no especificada';
                }
                if (($m['estado_local'] ?? '') === 'entregado' && $obj === '') {
                    $alertas[] = 'Marcado como entregado pero sin objeto trazable definido';
                }
                if ($alertas) {
                    $kpis['con_alertas']++;
                    foreach ($alertas as $a) {
                        $totalAnomalias++;
                        if (count($anomalias) < self::MAX_ANOMALIAS_LISTADAS) {
                            $anomalias[] = [
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
                }

                // ── Reporte por bodega de origen ──
                $bod = trim((string)($m['origen_establecimiento'] ?? '')) ?: '(sin bodega)';
                // Normalizar: quitar el código que viene como "; 3400301153824"
                $bodLimpio = trim((string)preg_replace('/;\s*\d+\s*$/', '', $bod));
                $bodLimpio = $bodLimpio ?: $bod;
                if (!isset($byBod[$bodLimpio])) {
                    $byBod[$bodLimpio] = [
                        'bodega'            => $bodLimpio,
                        'cue'               => $m['origen_cue'] ?? '',
                        'departamento'      => $m['origen_departamento'] ?? '',
                        'movimientos'       => 0,
                        'cantidad_total'    => 0,
                        'entregados'        => 0,
                        'pendientes'        => 0,
                        'beneficiarios_set' => [],
                        'objetos_count'     => [],
                        'manifiestos_set'   => [],
                    ];
                }
                $byBod[$bodLimpio]['movimientos']++;
                $byBod[$bodLimpio]['cantidad_total'] += $cantidad;
                if (($m['estado_local'] ?? '') === 'entregado') $byBod[$bodLimpio]['entregados']++;
                else                                            $byBod[$bodLimpio]['pendientes']++;
                if (!empty($m['destino_dni'])) $byBod[$bodLimpio]['beneficiarios_set'][$m['destino_dni']] = true;
                if (!empty($m['guiasa_no']))   $byBod[$bodLimpio]['manifiestos_set'][$m['guiasa_no']]     = true;
                $objBod = $m['objeto_trazable'] ?: '(sin)';
                $byBod[$bodLimpio]['objetos_count'][$objBod] = ($byBod[$bodLimpio]['objetos_count'][$objBod] ?? 0) + 1;
            }

            $seguir = count($lote) === self::SCAN_LOTE;
            unset($lote);
            if (!$seguir) break;
        }

        // ── Cierre de acumuladores ──
        $kpis['beneficiarios_unicos'] = count($beneficiarios);
        $kpis['manifiestos_unicos']   = count($manifiestos);
        unset($beneficiarios, $manifiestos);

        usort($porObjeto, fn($a, $b) => $b['movimientos'] <=> $a['movimientos']);
        $kpis['desglose_objetos'] = $porObjeto;

        foreach ($byBod as &$b) {
            $b['beneficiarios_unicos'] = count($b['beneficiarios_set']);
            $b['manifiestos_unicos']   = count($b['manifiestos_set']);
            unset($b['beneficiarios_set'], $b['manifiestos_set']);
            arsort($b['objetos_count']);
            $b['top_objetos'] = [];
            foreach (array_slice($b['objetos_count'], 0, 3, true) as $objNom => $cnt) {
                $b['top_objetos'][] = ['objeto' => $objNom, 'cantidad' => $cnt];
            }
            $b['objetos_unicos'] = count($b['objetos_count']);
            unset($b['objetos_count']);
        }
        unset($b);
        $bodegas = array_values($byBod);
        usort($bodegas, fn($a, $b) => $b['movimientos'] <=> $a['movimientos']);

        // Más severas primero (dentro del tope listado)
        $orden = ['sin_dni' => 1, 'no_padron' => 2, 'duplicado' => 3, 'cantidad' => 4, 'otro' => 5];
        usort($anomalias, function ($a, $b) use ($orden) {
            $oa = $orden[$a['tipo']] ?? 9;
            $ob = $orden[$b['tipo']] ?? 9;
            if ($oa !== $ob) return $oa <=> $ob;
            return strcmp((string)$b['fecha'], (string)$a['fecha']);
        });

        return [
            'kpis'              => $kpis,
            'bodegas'           => $bodegas,
            'anomalias'         => $anomalias,
            'total_anomalias'   => $totalAnomalias,
            'total_productores' => count($productores),
        ];
    }

    /** Padrón del programa activo como mapa DNI → estado (memoria acotada). */
    private function cargarPadron(): array
    {
        $padron = [];
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();
            $rows = $db->fetchAll(
                "SELECT dni, estado FROM sag_beneficiarios
                  WHERE id_proyecto = ? AND dni IS NOT NULL AND dni <> ''",
                [$pid]
            );
            foreach ($rows as $r) {
                $padron[trim((string)$r['dni'])] = (string)($r['estado'] ?? '');
            }
        } catch (\Throwable $e) {
            // Tabla sin filas o columna distinta — seguimos sin padrón
        }
        return $padron;
    }

    private function inventarioOirsaPorDepartamento(): array
    {
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();

            if (!$db->tablaExiste('sag_trazaragro_movimientos')) {
                return [];
            }

            $rows = $db->fetchAll(
                "SELECT departamento,
                        SUM(recibido) AS inventario_registrado,
                        SUM(entregado) AS cantidad_entregada,
                        SUM(num_entregas_guiasa) AS movimientos,
                        COUNT(DISTINCT CASE WHEN bodega_key IS NOT NULL AND bodega_key <> '' THEN bodega_key END) AS bodegas,
                        COUNT(DISTINCT CASE WHEN beneficiario_key IS NOT NULL AND beneficiario_key <> '' THEN beneficiario_key END) AS beneficiarios_unicos,
                        COUNT(DISTINCT CASE WHEN manifiesto IS NOT NULL AND manifiesto <> '' THEN manifiesto END) AS manifiestos_unicos,
                        MAX(fecha_movimiento) AS ultimo_movimiento
                   FROM (
                      -- Entrada: Proveedor -> Bodega
                      SELECT COALESCE(NULLIF(destino_departamento,''), '(sin departamento)') AS departamento,
                             COALESCE(NULLIF(TRIM(destino_cue), ''),
                                      CONCAT('NOMBRE:', LOWER(TRIM(SUBSTRING_INDEX(destino_establecimiento, ';', 1))))) AS bodega_key,
                             SUM(cantidad) AS recibido, 0 AS entregado,
                             0 AS num_entregas_guiasa,
                             NULL AS beneficiario_key, NULL AS manifiesto,
                             MAX(fecha_autorizacion) AS fecha_movimiento
                        FROM sag_trazaragro_movimientos
                       WHERE id_proyecto = ? AND tipo_movimiento_id = 113
                         AND destino_establecimiento IS NOT NULL AND destino_establecimiento <> ''
                    GROUP BY departamento, bodega_key
                      UNION ALL
                      -- Salida: Bodega -> Productor
                      SELECT COALESCE(NULLIF(origen_departamento,''), '(sin departamento)') AS departamento,
                             COALESCE(NULLIF(TRIM(origen_cue), ''),
                                      CONCAT('NOMBRE:', LOWER(TRIM(SUBSTRING_INDEX(origen_establecimiento, ';', 1))))) AS bodega_key,
                             0 AS recibido, SUM(cantidad) AS entregado,
                             SUM(CASE WHEN guiasa_no IS NOT NULL AND TRIM(guiasa_no) <> '' THEN 1 ELSE 0 END) AS num_entregas_guiasa,
                             NULLIF(TRIM(destino_dni), '') AS beneficiario_key,
                             NULLIF(TRIM(guiasa_no), '') AS manifiesto,
                             MAX(fecha_autorizacion) AS fecha_movimiento
                        FROM sag_trazaragro_movimientos
                       WHERE id_proyecto = ? AND tipo_movimiento_id = 111
                         AND origen_establecimiento IS NOT NULL AND origen_establecimiento <> ''
                    GROUP BY departamento, bodega_key, beneficiario_key, manifiesto
                      UNION ALL
                      -- Traslado: la bodega destino recibe
                      SELECT COALESCE(NULLIF(destino_departamento,''), '(sin departamento)') AS departamento,
                             COALESCE(NULLIF(TRIM(destino_cue), ''),
                                      CONCAT('NOMBRE:', LOWER(TRIM(SUBSTRING_INDEX(destino_establecimiento, ';', 1))))) AS bodega_key,
                             SUM(cantidad) AS recibido, 0 AS entregado,
                             0 AS num_entregas_guiasa,
                             NULL AS beneficiario_key, NULL AS manifiesto,
                             MAX(fecha_autorizacion) AS fecha_movimiento
                        FROM sag_trazaragro_movimientos
                       WHERE id_proyecto = ? AND tipo_movimiento_id = 112
                         AND destino_establecimiento IS NOT NULL AND destino_establecimiento <> ''
                    GROUP BY departamento, bodega_key
                      UNION ALL
                      -- Traslado: la bodega origen sale
                      SELECT COALESCE(NULLIF(origen_departamento,''), '(sin departamento)') AS departamento,
                             COALESCE(NULLIF(TRIM(origen_cue), ''),
                                      CONCAT('NOMBRE:', LOWER(TRIM(SUBSTRING_INDEX(origen_establecimiento, ';', 1))))) AS bodega_key,
                             0 AS recibido, SUM(cantidad) AS entregado,
                             0 AS num_entregas_guiasa,
                             NULL AS beneficiario_key, NULL AS manifiesto,
                             MAX(fecha_autorizacion) AS fecha_movimiento
                        FROM sag_trazaragro_movimientos
                       WHERE id_proyecto = ? AND tipo_movimiento_id = 112
                         AND origen_establecimiento IS NOT NULL AND origen_establecimiento <> ''
                    GROUP BY departamento, bodega_key
                   ) AS u
               GROUP BY departamento
               ORDER BY inventario_registrado DESC, cantidad_entregada DESC",
                [$pid, $pid, $pid, $pid]
            );

            $topRows = $db->fetchAll(
                "SELECT COALESCE(NULLIF(origen_departamento,''), '(sin departamento)') AS departamento,
                        COALESCE(NULLIF(TRIM(objeto_trazable), ''), '(sin producto)') AS producto,
                        SUM(cantidad) AS cantidad
                   FROM sag_trazaragro_movimientos
                  WHERE id_proyecto = ?
                    AND tipo_movimiento_id = 111
                    AND guiasa_no IS NOT NULL AND TRIM(guiasa_no) <> ''
                    AND origen_establecimiento IS NOT NULL AND origen_establecimiento <> ''
               GROUP BY departamento, producto
               ORDER BY departamento, cantidad DESC",
                [$pid]
            );

            $topByDepto = [];
            foreach ($topRows as $r) {
                $depto = (string)$r['departamento'];
                $topByDepto[$depto][] = [
                    'objeto'   => (string)$r['producto'],
                    'cantidad' => (float)$r['cantidad'],
                ];
            }

            foreach ($rows as &$r) {
                $r['inventario_registrado'] = (float)$r['inventario_registrado'];
                $r['cantidad_entregada']    = (float)$r['cantidad_entregada'];
                $r['saldo_inventario']      = $r['inventario_registrado'] - $r['cantidad_entregada'];
                $r['avance_pct']            = $r['inventario_registrado'] > 0
                    ? round(($r['cantidad_entregada'] / $r['inventario_registrado']) * 100, 1)
                    : 0;
                $r['bodegas']               = (int)$r['bodegas'];
                $r['municipios']            = $r['bodegas'];
                $r['entregados']            = (int)$r['movimientos'];
                $r['pendientes']            = 0;
                $r['top_objetos']           = $topByDepto[(string)$r['departamento']] ?? [];
            }
            unset($r);

            return $rows;
        } catch (\Throwable $e) {
            error_log('inventarioOirsaPorDepartamento: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Reporte por productor paginado (AJAX). Agrupa en SQL por destino_dni
     * (o hash del nombre si no hay DNI) y sólo materializa los movimientos
     * de los productores de la página solicitada.
     */
    public function datosProductores(): void
    {
        $this->requireCsrf();
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();

            $porPagina = min(60, max(10, (int)$this->getPost('per_page', 30)));
            $pagina    = max(1, (int)$this->getPost('page', 1));

            $keyExpr = "COALESCE(NULLIF(TRIM(destino_dni), ''),
                        CONCAT('(sin-dni)-', MD5(COALESCE(destino_persona, ''))))";

            $hayPadron = (bool)$db->fetchOne(
                "SELECT 1 FROM sag_beneficiarios
                  WHERE id_proyecto = ? AND dni IS NOT NULL AND dni <> '' LIMIT 1",
                [$pid]
            );

            $where  = ['id_proyecto = ?', 'tipo_movimiento_id = 111'];
            $params = [$pid];

            $depto = (string)$this->getPost('depto', '');
            if ($depto !== '') {
                $where[]  = 'destino_departamento = ?';
                $params[] = $depto;
            }

            // Un productor coincide con la búsqueda si ALGUNO de sus movimientos coincide
            $busca = trim((string)$this->getPost('busca', ''));
            if ($busca !== '') {
                $like = '%' . addcslashes($busca, '%_\\') . '%';
                $cols = ['destino_nombre', 'destino_persona', 'destino_dni', 'destino_departamento',
                         'destino_municipio', 'destino_establecimiento', 'objeto_trazable',
                         'guiasa_no', 'codigo_trazabilidad'];
                $where[] = '(' . implode(' OR ', array_map(fn($c) => "{$c} LIKE ?", $cols)) . ')';
                foreach ($cols as $c) {
                    $params[] = $like;
                }
            }

            $padronSub = "SELECT TRIM(dni) FROM sag_beneficiarios
                           WHERE id_proyecto = ? AND dni IS NOT NULL AND dni <> ''";
            switch ((string)$this->getPost('padron', '')) {
                case 'sin_dni':
                    $where[] = "TRIM(COALESCE(destino_dni, '')) = ''";
                    break;
                case 'en_padron':
                    if (!$hayPadron) { $where[] = '1 = 0'; break; }
                    $where[]  = "TRIM(destino_dni) IN ({$padronSub})";
                    $params[] = $pid;
                    break;
                case 'no_padron':
                    // Con padrón vacío el estado es 'padron_vacio', no 'no_padron'
                    if (!$hayPadron) { $where[] = '1 = 0'; break; }
                    $where[]  = "(TRIM(COALESCE(destino_dni, '')) <> '' AND TRIM(destino_dni) NOT IN ({$padronSub}))";
                    $params[] = $pid;
                    break;
            }

            $whereSql = implode(' AND ', $where);

            $total = (int)($db->fetchOne(
                "SELECT COUNT(DISTINCT {$keyExpr}) AS c
                   FROM sag_trazaragro_movimientos WHERE {$whereSql}",
                $params
            )['c'] ?? 0);

            $offset = ($pagina - 1) * $porPagina;
            $grupos = $offset < $total
                ? $db->fetchAll(
                    "SELECT {$keyExpr} AS pkey,
                            MAX(TRIM(COALESCE(destino_dni, ''))) AS dni,
                            MAX(COALESCE(NULLIF(destino_nombre, ''), NULLIF(destino_persona, ''))) AS nombre,
                            MAX(COALESCE(destino_departamento, ''))    AS departamento,
                            MAX(COALESCE(destino_municipio, ''))       AS municipio,
                            MAX(COALESCE(destino_establecimiento, '')) AS establecimiento,
                            COUNT(*) AS num_objetos,
                            COUNT(DISTINCT CASE WHEN guiasa_no IS NOT NULL AND guiasa_no <> '' THEN guiasa_no END) AS num_manifiestos,
                            SUM(CASE WHEN estado_local = 'entregado' THEN 1 ELSE 0 END) AS entregados,
                            SUM(CASE WHEN estado_local = 'entregado' THEN 0 ELSE 1 END) AS pendientes
                       FROM sag_trazaragro_movimientos
                      WHERE {$whereSql}
                   GROUP BY pkey
                   ORDER BY num_objetos DESC, pkey ASC
                      LIMIT {$porPagina} OFFSET {$offset}",
                    $params
                )
                : [];

            $productores = $grupos ? $this->armarPaginaProductores($grupos, $keyExpr, $hayPadron) : [];

            $this->success('OK', [
                'total'    => $total,
                'page'     => $pagina,
                'per_page' => $porPagina,
                'rows'     => $productores,
            ]);
        } catch (\Throwable $e) {
            error_log('EntregasController::datosProductores — ' . $e->getMessage());
            $this->error('No se pudo cargar el reporte por productor.');
        }
    }

    /**
     * Completa los grupos de una página del reporte por productor:
     * objetos recibidos, validación de padrón y alertas de la tarjeta
     * (mismos criterios que escanearMovimientos, evaluados sólo sobre
     * los movimientos de los productores de la página).
     */
    private function armarPaginaProductores(array $grupos, string $keyExpr, bool $hayPadron): array
    {
        $db  = Database::programa();
        $pid = Database::proyectoId();

        $keys = array_column($grupos, 'pkey');
        $ph   = implode(',', array_fill(0, count($keys), '?'));
        $objRows = $db->fetchAll(
            "SELECT {$keyExpr} AS pkey, objeto_trazable, codigo_trazabilidad, guiasa_no,
                    fecha_autorizacion, cantidad, unidad, estado_local, autorizado_por, movement_id
               FROM sag_trazaragro_movimientos
              WHERE id_proyecto = ? AND tipo_movimiento_id = 111 AND {$keyExpr} IN ({$ph})
           ORDER BY fecha_autorizacion DESC, id DESC",
            array_merge([$pid], $keys)
        );
        $objetosPorProd = [];
        foreach ($objRows as $o) {
            $objetosPorProd[$o['pkey']][] = $o;
        }
        unset($objRows);

        // Estado en padrón sólo de los DNI de esta página
        $padronPagina = [];
        $dnis = array_values(array_filter(array_map('strval', array_column($grupos, 'dni'))));
        if ($hayPadron && $dnis) {
            $phDni = implode(',', array_fill(0, count($dnis), '?'));
            $rows  = $db->fetchAll(
                "SELECT TRIM(dni) AS dni, estado FROM sag_beneficiarios
                  WHERE id_proyecto = ? AND TRIM(dni) IN ({$phDni})",
                array_merge([$pid], $dnis)
            );
            foreach ($rows as $r) {
                $padronPagina[$r['dni']] = strtolower((string)($r['estado'] ?? ''));
            }
        }

        $progId      = $_SESSION['programa']['id'] ?? '';
        $detectarDup = (bool)(PROGRAMAS[$progId]['detectar_dup_dni_objeto'] ?? true);

        $out = [];
        foreach ($grupos as $g) {
            $dni  = (string)$g['dni'];
            $movs = $objetosPorProd[$g['pkey']] ?? [];

            if ($dni === '') {
                $validacion = 'sin_dni';
            } elseif (isset($padronPagina[$dni])) {
                $validacion = 'en_padron';
            } else {
                $validacion = $hayPadron ? 'no_padron' : 'padron_vacio';
            }

            $tieneAlerta = $dni === '' || $validacion === 'no_padron';
            if (!$tieneAlerta && $validacion === 'en_padron') {
                $estado      = $padronPagina[$dni];
                $tieneAlerta = $estado !== '' && !in_array($estado, ['activo', 'activa', '1', 'vigente'], true);
            }

            $porObjeto = [];
            $objetos   = [];
            foreach ($movs as $m) {
                $obj = trim((string)($m['objeto_trazable'] ?? ''));
                if ($obj !== '') $porObjeto[$obj] = ($porObjeto[$obj] ?? 0) + 1;
                if ((float)($m['cantidad'] ?? 0) <= 0) $tieneAlerta = true;
                if (($m['estado_local'] ?? '') === 'entregado' && $obj === '') $tieneAlerta = true;
                if (count($objetos) < self::MAX_OBJETOS_POR_PRODUCTOR) {
                    $objetos[] = [
                        'objeto'       => $obj !== '' ? $obj : '(sin nombre)',
                        'codigo_traza' => (string)($m['codigo_trazabilidad'] ?? ''),
                        'guiasa'       => (string)($m['guiasa_no'] ?? ''),
                        'fecha'        => (string)($m['fecha_autorizacion'] ?? ''),
                        'cantidad'     => (float)($m['cantidad'] ?? 0),
                        'unidad'       => (string)($m['unidad'] ?? ''),
                        'estado'       => (string)(($m['estado_local'] ?? '') !== '' ? $m['estado_local'] : 'pendiente'),
                        'autoriza'     => (string)($m['autorizado_por'] ?? ''),
                        'movement_id'  => $m['movement_id'] ?? 0,
                    ];
                }
            }
            if (!$tieneAlerta && $detectarDup && $dni !== '' && $porObjeto && max($porObjeto) > 1) {
                $tieneAlerta = true;
            }

            $out[] = [
                'dni'              => $dni !== '' ? $dni : '(sin DNI)',
                'nombre'           => ($g['nombre'] ?? '') !== '' && $g['nombre'] !== null ? $g['nombre'] : '(sin nombre)',
                'departamento'     => (string)$g['departamento'],
                'municipio'        => (string)$g['municipio'],
                'establecimiento'  => (string)$g['establecimiento'],
                'validacion'       => $validacion,
                'num_objetos'      => (int)$g['num_objetos'],
                'num_manifiestos'  => (int)$g['num_manifiestos'],
                'entregados'       => (int)$g['entregados'],
                'pendientes'       => (int)$g['pendientes'],
                'tiene_alerta'     => $tieneAlerta,
                'objetos'          => $objetos,
                'objetos_omitidos' => max(0, count($movs) - count($objetos)),
                'acta_url'         => $dni !== '' ? BASE_URL . '/entregas/acta?dni=' . urlencode($dni) : '',
            ];
        }
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
    //  PROYECCIÓN DE MOVIMIENTOS OIRSA AL KARDEX
    // ════════════════════════════════════════════════════════════

    /**
     * Proyecta entradas, salidas y traslados confirmados al kardex.
     * La referencia externa impide duplicar movimientos al resincronizar.
     */
    private function prepararDescuentoInventario(array $movs): array
    {
        require_once ROOT_PATH . '/core/InventarioOirsaService.php';
        $resumen = InventarioOirsaService::sincronizar(
            $movs,
            (int) ($_SESSION['user']['id_usuario'] ?? 0) ?: null
        );

        if (!$this->resumenInventarioSync) {
            $this->resumenInventarioSync = $resumen;
        } else {
            foreach ([
                'insertados', 'actualizados', 'existentes', 'anulados',
                'omitidos', 'sin_bodega', 'sin_producto',
                'saldos_negativos', 'errores',
            ] as $campo) {
                $this->resumenInventarioSync[$campo] =
                    (int) ($this->resumenInventarioSync[$campo] ?? 0)
                    + (int) ($resumen[$campo] ?? 0);
            }
            $this->resumenInventarioSync['configurado'] =
                !empty($this->resumenInventarioSync['configurado'])
                && !empty($resumen['configurado']);
            $this->resumenInventarioSync['detalles'] = array_values(array_unique(array_merge(
                $this->resumenInventarioSync['detalles'] ?? [],
                $resumen['detalles'] ?? []
            )));
        }

        if (empty($resumen['configurado'])
            || ($resumen['insertados'] ?? 0) > 0
            || ($resumen['actualizados'] ?? 0) > 0
            || ($resumen['anulados'] ?? 0) > 0
            || ($resumen['sin_bodega'] ?? 0) > 0
            || ($resumen['sin_producto'] ?? 0) > 0) {
            $this->logAction(
                'SYNC_INVENTARIO_OIRSA',
                'inventarios',
                json_encode($resumen, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }
        return $resumen;
    }

    // ════════════════════════════════════════════════════════════
    //  SINCRONIZACIÓN (botón "Sincronizar con Trazaragro")
    // ════════════════════════════════════════════════════════════
    public function sincronizarTrazaragro(): void
    {
        try {
            $this->resumenInventarioSync = [];
            // ── Seguridad: CSRF + autorización por rol ──
            // Solo Super Admin, Coord Nacional y Coord PIP pueden disparar sync,
            // porque la operación trae PII de productores (DNI, nombres) desde OIRSA.
            $this->requireCsrf();
            $this->requireRole(['super_admin', 'coord_nacional', 'coord_pip', 'admin', 'administrador', 'coordinador']);

            // El sync con OIRSA puede tardar varios minutos. Subimos los límites
            // SOLO para esta petición; no afectan al resto del sitio.
            // 60 min con paginación 10k = margen amplio para histórico completo
            // de cada programa (~80k movimientos por programa = 5-10 min reales).
            @set_time_limit(3600);                 // 60 minutos máx (era 1800/30 min)
            @ini_set('memory_limit', '2048M');     // 2 GB — antes 1 GB se agotaba con >50K filas
            @ignore_user_abort(true);

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

            // Solo Super Admin / Coord Nacional pueden limpiar la tabla antes del sync
            $limpiarSolicitado = (int)$this->getPost('limpiar', 0) === 1;
            $puedeLimpiar      = $this->hasRole(['super_admin', 'coord_nacional', 'admin', 'administrador']);
            $limpiar           = $limpiarSolicitado && $puedeLimpiar;

            // ── Modos de sincronización (FASE 2 — junio 2026) ──────────
            // Modos válidos:
            //   · incremental → desde MAX(synced_at) - 1 día (default)
            //   · historico   → desde OIRSA_FECHA_BASE hasta hoy
            //   · rango       → desde/hasta del POST (rango específico)
            //   · guiasa      → filtra por RegistrationCode = guiasa_no del POST
            //   · bodega      → filtra por CUE (origen o destino) = bodega_cue del POST
            // Si el modo es 'guiasa' o 'bodega', no aplicamos limpieza ni filtro de rubro
            // (el filtro específico es más restrictivo que el rubro).
            $modosValidos = ['incremental', 'historico', 'rango', 'guiasa', 'bodega', 'departamento'];
            $modo = strtolower(trim((string)$this->getPost('modo', 'incremental')));
            if (!in_array($modo, $modosValidos, true)) $modo = 'incremental';
            $guiasaNo     = trim((string)$this->getPost('guiasa_no', ''));
            $bodegaCue    = trim((string)$this->getPost('bodega_cue', ''));
            $departamento = trim((string)$this->getPost('departamento', ''));
            // Validaciones de modo: si falta el input requerido, degradar a incremental
            if ($modo === 'guiasa'       && $guiasaNo     === '') $modo = 'incremental';
            if ($modo === 'bodega'       && $bodegaCue    === '') $modo = 'incremental';
            if ($modo === 'departamento' && $departamento === '') $modo = 'incremental';
            // 'historico' es equivalente a Shift+Clic siempre que tenga permiso
            if ($modo === 'historico' && $puedeLimpiar) $limpiar = true;

            // ── Validación de inputs ──
            // Estrategia de fechas (nueva — junio 2026):
            //   · Limpieza (Shift+Clic): histórico completo desde OIRSA_FECHA_BASE.
            //   · Incremental (Clic normal): desde MAX(fecha_autorizacion) en BD menos 1 día,
            //     o desde OIRSA_FECHA_BASE si la tabla está vacía.
            // El "menos 1 día" da overlap defensivo por si OIRSA tiene movimientos
            // que se autorizaron en las últimas horas pero ya estaban en proceso.
            $fechaBase = defined('OIRSA_FECHA_BASE') ? OIRSA_FECHA_BASE : '2026-04-01';

            if ($limpiar) {
                $desdeDefault = $fechaBase;
            } else {
                $ultimaFecha = $this->ultimaFechaAutorizacionDelPrograma();
                if ($ultimaFecha) {
                    $desdeDefault = date('Y-m-d', strtotime($ultimaFecha . ' -1 day'));
                    // Nunca antes de la fecha base
                    if ($desdeDefault < $fechaBase) $desdeDefault = $fechaBase;
                } else {
                    $desdeDefault = $fechaBase;
                }
            }
            $topDefault = $limpiar ? 100000 : 50000;
            $desdeRaw   = (string)$this->getPost('desde', $desdeDefault);
            $hastaRaw   = (string)$this->getPost('hasta', date('Y-m-d'));
            $desde      = preg_match('/^\d{4}-\d{2}-\d{2}$/', $desdeRaw) ? $desdeRaw : $desdeDefault;
            $hasta      = preg_match('/^\d{4}-\d{2}-\d{2}$/', $hastaRaw) ? $hastaRaw : date('Y-m-d');
            // Tope mínimo: jamás traer cosas anteriores a OIRSA_FECHA_BASE.
            if ($desde < $fechaBase) $desde = $fechaBase;
            // Tamaño del lote. Para limpieza se pide más historial por tipo; si OIRSA
            // tiene más de este límite, puede repetirse el sync sin duplicar filas.
            $top      = max(1, min(500000, (int)$this->getPost('top', $topDefault)));

            // Filtro OIRSA: preferimos ID numérico si está validado para el programa,
            // si no usamos texto (substringof) como fallback.
            $progId   = $_SESSION['programa']['id'] ?? '';
            $rubroId  = (int)(PROGRAMAS[$progId]['trazaragro_rubro_id'] ?? 0);
            $rubroIds = PROGRAMAS[$progId]['trazaragro_rubro_ids'] ?? null;
            $rubro    = PROGRAMAS[$progId]['trazaragro_rubro']    ?? null;

            $fetchArgsBase = [
                'top'   => $top,
                'desde' => $desde,
                'hasta' => $hasta,
            ];

            // ── Filtros específicos por modo ───────────────────────────
            // Si el sync es por GUIASA o por bodega, ignoramos el filtro de rubro
            // (queremos TODOS los movimientos de esa guía/bodega, sin importar rubro).
            if ($modo === 'guiasa') {
                $fetchArgsBase['regCode'] = $guiasaNo;
                $filtroAplicado = "GUIASA={$guiasaNo} (sin filtro de rubro)";
                // Una guía suele tener pocos movimientos; bajamos top y subimos rango
                $fetchArgsBase['desde'] = $fechaBase;
                $fetchArgsBase['hasta'] = date('Y-m-d');
            } elseif ($modo === 'bodega') {
                $fetchArgsBase['cue'] = $bodegaCue;
                $filtroAplicado = "BodegaCUE={$bodegaCue} (sin filtro de rubro)";
                $fetchArgsBase['desde'] = $fechaBase;
                $fetchArgsBase['hasta'] = date('Y-m-d');
            } else {
                // Modos incremental / historico / rango / departamento usan el filtro de rubro habitual.
                // Orden de precedencia: lista de IDs > ID único > texto
                if (is_array($rubroIds) && !empty($rubroIds)) {
                    $fetchArgsBase['rubroIds'] = $rubroIds;
                    $filtroAplicado = 'rubroIds=[' . implode(',', $rubroIds) . ']';
                } elseif ($rubroId > 0) {
                    $fetchArgsBase['rubroId'] = $rubroId;
                    $filtroAplicado = "rubroId={$rubroId}";
                } elseif (!empty($rubro)) {
                    $fetchArgsBase['rubro'] = $rubro;
                    $filtroAplicado = "rubro='{$rubro}' (texto)";
                } else {
                    $filtroAplicado = 'sin filtro de rubro';
                }
                // Sumar filtro por departamento si el modo lo pide.
                // Mantiene el rubro: queremos SOLO movimientos del programa actual
                // (PIPC=café, PIPA=agrícola) que toquen ese depto.
                if ($modo === 'departamento') {
                    $fetchArgsBase['departamento'] = $departamento;
                    $filtroAplicado .= " · departamento='{$departamento}'";
                    // Para depto traemos histórico completo del depto (chico de todos modos)
                    $fetchArgsBase['desde'] = $fechaBase;
                    $fetchArgsBase['hasta'] = date('Y-m-d');
                }
            }
            error_log("sincronizarTrazaragro: programa={$progId} · modo={$modo} · {$filtroAplicado} · rango={$fetchArgsBase['desde']}/{$fetchArgsBase['hasta']} · top={$top}");

            // Consultar cada tipo por separado. Antes se enviaba tipoMovId=0 y
            // los tres tipos competían por el mismo TOP; si entregas/recepciones
            // llenaban el lote, los traslados (112) nunca llegaban a persistirse.
            $catalogoTipos = defined('OIRSA_TIPOS_MOVIMIENTO')
                ? OIRSA_TIPOS_MOVIMIENTO
                : [
                    111 => ['nombre' => 'Bodega a Productor'],
                    112 => ['nombre' => 'Bodega a Bodega'],
                    113 => ['nombre' => 'Proveedor a Bodega'],
                ];
            $tiposConfigurados = array_map('intval', array_keys($catalogoTipos));
            // Este campo puede llegar como array (select multiple / jQuery) o
            // como CSV. No usar getPost(): el helper aplica trim() y falla con arrays.
            $tiposSolicitados = $_POST['tipos_movimiento'] ?? $tiposConfigurados;
            if (!is_array($tiposSolicitados)) {
                $tiposSolicitados = preg_split('/\s*,\s*/', (string)$tiposSolicitados, -1, PREG_SPLIT_NO_EMPTY);
            }
            $tiposSolicitados = array_values(array_intersect(
                $tiposConfigurados,
                array_unique(array_map('intval', $tiposSolicitados))
            ));
            if (empty($tiposSolicitados)) {
                $tiposSolicitados = $tiposConfigurados;
            }

            $stats = [
                'recibidos' => 0, 'insertados' => 0, 'actualizados' => 0,
                'errores' => 0, 'errores_det' => [],
            ];
            $porTipo = [];
            $movimientosPendientes = [];
            foreach ($tiposSolicitados as $tipoMovId) {
                $fetchArgs = $fetchArgsBase;
                $fetchArgs['tipoMovId'] = $tipoMovId;
                $movimientosTipo = $cli->fetchEntregas($fetchArgs);

                $nombreTipo = $catalogoTipos[$tipoMovId]['nombre'] ?? ('Tipo ' . $tipoMovId);
                $porTipo[(string)$tipoMovId] = [
                    'nombre' => $nombreTipo,
                    'recibidos' => count($movimientosTipo),
                    'insertados' => 0,
                    'actualizados' => 0,
                    'errores' => 0,
                ];
                $stats['recibidos'] += count($movimientosTipo);

                if ($limpiar) {
                    $movimientosPendientes[(string)$tipoMovId] = $movimientosTipo;
                } else {
                    $statsTipo = $this->persistirMovimientos($movimientosTipo);
                    $this->prepararDescuentoInventario($movimientosTipo);

                    foreach (['insertados', 'actualizados', 'errores'] as $campo) {
                        $porTipo[(string)$tipoMovId][$campo] = $statsTipo[$campo];
                        $stats[$campo] += $statsTipo[$campo];
                    }
                    if (!empty($statsTipo['errores_det'])) {
                        $stats['errores_det'] = array_merge($stats['errores_det'], $statsTipo['errores_det']);
                    }
                }
            }

            // Si OIRSA no devuelve nada, dar un mensaje útil al usuario en vez
            // de un genérico "0 sincronizados".
            if ($stats['recibidos'] === 0) {
                $msg = "OIRSA no devolvió movimientos para {$progId} con {$filtroAplicado} "
                     . "entre {$desde} y {$hasta}. Posibles causas: "
                     . "(1) este programa aún no registra entregas en producción, "
                     . "(2) el rango de fechas no cubre movimientos existentes, "
                     . "(3) el filtro de rubro no corresponde al ambiente actual.";
                $this->logAction('SYNC_TRAZARAGRO_VACIO', 'entregas', $msg);
                $this->success($msg, [
                    'rubro_filtrado'   => $rubro,
                    'rubro_id'         => $rubroId,
                    'filtro_aplicado'  => $filtroAplicado,
                    'recibidos'        => 0,
                    'insertados'       => 0,
                    'actualizados'     => 0,
                    'errores'          => 0,
                    'por_tipo'         => $porTipo,
                    'modo'             => $ping['mode'],
                    'rango'            => ['desde' => $desde, 'hasta' => $hasta],
                ]);
                return;
            }

            if ($limpiar) {
                $recibidos111 = $porTipo['111']['recibidos'] ?? 0;
                $recibidos113 = $porTipo['113']['recibidos'] ?? 0;
                if (in_array(113, $tiposSolicitados, true) && $recibidos111 > 0 && $recibidos113 === 0) {
                    $msg = "OIRSA devolvio entregas 111 pero ninguna recepcion 113 para {$progId}. "
                         . "No se borro la base local para evitar dejar inventario incompleto.";
                    $this->logAction('SYNC_TRAZARAGRO_ABORTADO', 'entregas', $msg);
                    $this->error($msg);
                    return;
                }

                try {
                    $db  = Database::programa();
                    $pid = Database::proyectoId();
                    $db->beginTransaction();
                    $borradas = $db->execute(
                        "DELETE FROM sag_trazaragro_movimientos WHERE id_proyecto = ?",
                        [$pid]
                    );

                    foreach ($movimientosPendientes as $tipoMovId => $movimientosTipo) {
                        $statsTipo = $this->persistirMovimientos($movimientosTipo);
                        $this->prepararDescuentoInventario($movimientosTipo);

                        foreach (['insertados', 'actualizados', 'errores'] as $campo) {
                            $porTipo[$tipoMovId][$campo] = $statsTipo[$campo];
                            $stats[$campo] += $statsTipo[$campo];
                        }
                        if (!empty($statsTipo['errores_det'])) {
                            $stats['errores_det'] = array_merge($stats['errores_det'], $statsTipo['errores_det']);
                        }
                    }

                    if ($stats['errores'] > 0) {
                        $db->rollback();
                        $detalleErrores = !empty($stats['errores_det'])
                            ? ' Detalle: ' . implode(' | ', array_slice($stats['errores_det'], 0, 3))
                            : '';
                        $this->error('La re-sincronizacion tuvo errores; se conservaron los datos anteriores.' . $detalleErrores);
                        return;
                    }

                    $db->commit();
                    $this->logAction('SYNC_TRAZARAGRO_LIMPIEZA', 'entregas',
                        "programa={$progId} · filas reemplazadas={$borradas}");
                } catch (\Throwable $e) {
                    if (isset($db)) {
                        try { $db->rollback(); } catch (\Throwable $ignored) {}
                    }
                    error_log('sincronizarTrazaragro limpieza segura EX: ' . $e->getMessage());
                    $this->error('No se pudo reemplazar la base local; se conservaron los datos anteriores.');
                    return;
                }
            }

            $conteosBd = $this->conteosTrazaragroPorTipo();
            $datosIncompletos = ($conteosBd['111'] ?? 0) > 0 && ($conteosBd['113'] ?? 0) === 0;

            // Resumen amigable
            $resumen = "Trazaragro ({$ping['mode']}): {$stats['recibidos']} movimientos sincronizados";
            if ($stats['insertados'])   $resumen .= " · {$stats['insertados']} nuevos";
            if ($stats['actualizados']) $resumen .= " · {$stats['actualizados']} actualizados";
            if ($stats['errores'])      $resumen .= " · {$stats['errores']} con error";
            $conteosTipo = [];
            foreach ($porTipo as $tipoId => $detalleTipo) {
                $conteosTipo[] = $tipoId . '=' . $detalleTipo['recibidos'];
            }
            if ($conteosTipo) $resumen .= ' · por tipo: ' . implode(', ', $conteosTipo);
            if ($datosIncompletos) {
                $resumen .= ' · ADVERTENCIA: hay salidas 111 sin recepciones 113 en BD';
            }
            if ($this->resumenInventarioSync) {
                if (empty($this->resumenInventarioSync['configurado'])) {
                    $resumen .= ' · Inventario pendiente de migración 022';
                } else {
                    $movInv = (int) ($this->resumenInventarioSync['insertados'] ?? 0)
                            + (int) ($this->resumenInventarioSync['actualizados'] ?? 0);
                    if ($movInv > 0) {
                        $resumen .= " · {$movInv} movimientos aplicados al inventario";
                    }
                    $sinMapeo = (int) ($this->resumenInventarioSync['sin_bodega'] ?? 0)
                              + (int) ($this->resumenInventarioSync['sin_producto'] ?? 0);
                    if ($sinMapeo > 0) {
                        $resumen .= " · {$sinMapeo} movimientos sin mapeo de inventario";
                    }
                }
            }

            $this->logAction('SYNC_TRAZARAGRO', 'entregas',
                "filtro=({$filtroAplicado}) · {$resumen}");

            $this->success($resumen, [
                'rubro_filtrado'   => $rubro,
                'rubro_id'         => $rubroId,
                'filtro_aplicado'  => $filtroAplicado,
                'recibidos'        => $stats['recibidos'],
                'insertados'       => $stats['insertados'],
                'actualizados'     => $stats['actualizados'],
                'errores'          => $stats['errores'],
                'errores_det'      => $stats['errores_det'] ?? [],
                'por_tipo'         => $porTipo,
                'conteos_bd'       => $conteosBd,
                'datos_incompletos'=> $datosIncompletos,
                'inventario'       => $this->resumenInventarioSync,
                'modo'             => $ping['mode'],
                'rango'            => ['desde' => $desde, 'hasta' => $hasta],
            ]);
        } catch (\Throwable $e) {
            error_log('sincronizarTrazaragro EX: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $this->error('Error interno al sincronizar con Trazaragro. Revise el log del servidor.');
        }
    }

    /**
     * Auto-descubre los ProductActivityId reales del ambiente OIRSA activo.
     *
     * Los IDs cambian entre pruebas y producción. En lugar de hardcodearlos,
     * esta acción consulta los últimos N movimientos y agrupa por
     * ProductActivityId+Name. La salida deja al admin mapear visualmente
     * qué ID corresponde a cada programa SAG.
     *
     * Solo para administradores. No expone PII (solo IDs y nombres de rubros).
     */
    public function descubrirRubrosOirsa(): void
    {
        try {
            $this->requireRole(['super_admin', 'coord_nacional', 'admin', 'administrador']);

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

            $muestra = max(100, min(5000, (int)$this->getQuery('muestra', 1000)));

            // Pedimos solo los campos clasificatorios — sin PII, sin nombres,
            // sin DNI. Esto evita cargar datos sensibles al hacer descubrimiento.
            $movs = $cli->fetchEntregas([
                'top'         => $muestra,
                'extraFilter' => '', // sin filtro de rubro
            ]);

            $rubros = [];
            foreach ($movs as $m) {
                $id = (int)($m['rubro_id'] ?? 0);
                $nm = (string)($m['rubro']    ?? '');
                $k  = $id . '|' . $nm;
                if (!isset($rubros[$k])) {
                    $rubros[$k] = ['id' => $id, 'nombre' => $nm, 'conteo' => 0];
                }
                $rubros[$k]['conteo']++;
            }
            uasort($rubros, fn($a, $b) => $b['conteo'] <=> $a['conteo']);

            $this->logAction('DESCUBRIR_RUBROS_OIRSA', 'entregas',
                'muestra=' . $muestra . ' · distintos=' . count($rubros));

            $this->success('Rubros OIRSA detectados en la muestra.', [
                'ambiente'        => stripos(TRAZARAGRO['base_url'], 'pruebas') === false ? 'producción' : 'pruebas',
                'tipo_movimiento' => 111, // Bodega → Productor
                'muestra'         => $muestra,
                'rubros'          => array_values($rubros),
            ]);
        } catch (\Throwable $e) {
            error_log('descubrirRubrosOirsa EX: ' . $e->getMessage());
            $this->error('Error interno descubriendo rubros OIRSA.');
        }
    }

    /**
     * Diagnóstico: lista los tipos de movimiento (MovementTypeId / MovementTypeName)
     * que existen en OIRSA para el rubro del programa activo.
     *
     * Útil para descubrir el ID correcto de "Proveedor → Bodega" (entradas a
     * bodega SAG), que se usará luego en el módulo de Inventarios OIRSA.
     *
     * Endpoint: GET /entregas/descubrirTiposMovimientos?muestra=2000
     * Solo administradores. No persiste nada, solo lee y agrupa.
     */
    public function descubrirTiposMovimientos(): void
    {
        try {
            $this->requireRole(['super_admin', 'coord_nacional', 'admin', 'administrador']);

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

            $muestra = max(100, min(10000, (int)$this->getQuery('muestra', 2000)));

            // Filtrar por rubro del programa activo (si está definido), para
            // que el descubrimiento sea relevante al PIP. Si el programa no
            // tiene rubro configurado, traemos cualquier rubro.
            $progId   = $_SESSION['programa']['id'] ?? '';
            $rubroId  = (int)(PROGRAMAS[$progId]['trazaragro_rubro_id'] ?? 0);
            $rubroIds = PROGRAMAS[$progId]['trazaragro_rubro_ids'] ?? null;

            $fetchArgs = [
                'top'       => $muestra,
                'tipoMovId' => 0, // 0 = sin filtro de tipo (lo nuevo, ver cliente)
            ];
            if (is_array($rubroIds) && !empty($rubroIds)) {
                $fetchArgs['rubroIds'] = $rubroIds;
            } elseif ($rubroId > 0) {
                $fetchArgs['rubroId'] = $rubroId;
            }

            $movs = $cli->fetchEntregas($fetchArgs);

            // Agrupar por TipoMovimientoId + Nombre, contar ocurrencias
            $tipos = [];
            foreach ($movs as $m) {
                $id = (int)($m['tipo_movimiento_id'] ?? 0);
                $nm = (string)($m['tipo_movimiento']    ?? '');
                $k  = $id . '|' . $nm;
                if (!isset($tipos[$k])) {
                    $tipos[$k] = [
                        'id'     => $id,
                        'nombre' => $nm,
                        'conteo' => 0,
                        'ejemplo_origen'  => $m['origen_establecimiento'] ?? '',
                        'ejemplo_destino' => $m['destino_establecimiento'] ?? '',
                    ];
                }
                $tipos[$k]['conteo']++;
            }
            uasort($tipos, fn($a, $b) => $b['conteo'] <=> $a['conteo']);

            $this->logAction('DESCUBRIR_TIPOS_MOV_OIRSA', 'entregas',
                'programa=' . $progId . ' · muestra=' . $muestra . ' · distintos=' . count($tipos));

            $this->success('Tipos de movimiento OIRSA detectados en la muestra.', [
                'ambiente'         => stripos(TRAZARAGRO['base_url'], 'pruebas') === false ? 'producción' : 'pruebas',
                'programa'         => $progId,
                'rubro_id'         => $rubroId,
                'rubro_ids'        => $rubroIds,
                'muestra_solicitada' => $muestra,
                'muestra_obtenida'   => count($movs),
                'tipos'            => array_values($tipos),
                'nota'             => 'El que dice "Bodega → Productor" (o equivalente, ID 111 en producción) es el que YA se sincroniza. Buscá el que sugiere "Proveedor → Bodega" o "Entrada" o "Recepción".',
            ]);
        } catch (\Throwable $e) {
            error_log('descubrirTiposMovimientos EX: ' . $e->getMessage());
            $this->error('Error interno descubriendo tipos de movimiento OIRSA.');
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

        // Patrón SAG_DEMO: id_proyecto identifica al programa SAG (PIPC=1,
        // PIPG=2, PIPA=3, FPROG=4). UNIQUE compuesta (id_proyecto, movement_id,
        // codigo_trazabilidad) porque OIRSA repite MovementId por cada línea
        // de item de un movimiento.
        $sql = "INSERT INTO sag_trazaragro_movimientos (
            id_proyecto, movement_id, rubro, rubro_id, tipo_movimiento, tipo_movimiento_id, actividad_id,
            objeto_trazable, objeto_trazable_codigo, codigo_trazabilidad,
            guiasa_no, codigo_autorizacion,
            fecha_registro, fecha_autorizacion, fecha_expiracion,
            origen_persona, origen_establecimiento, origen_cue, origen_departamento, origen_municipio,
            destino_persona, destino_dni, destino_nombre, destino_establecimiento, destino_cue, destino_departamento, destino_municipio,
            cantidad, unidad, transportista, vehiculo, condicion, proposito,
            autorizado_por, creado_por, status_oirsa, status_id, event_stage, is_completed,
            estado_local, raw_json, synced_at
        ) VALUES (
            :id_proyecto, :movement_id, :rubro, :rubro_id, :tipo_movimiento, :tipo_movimiento_id, :actividad_id,
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
            rubro_id = VALUES(rubro_id),
            tipo_movimiento = VALUES(tipo_movimiento),
            tipo_movimiento_id = VALUES(tipo_movimiento_id),
            actividad_id = VALUES(actividad_id),
            objeto_trazable = VALUES(objeto_trazable),
            objeto_trazable_codigo = VALUES(objeto_trazable_codigo),
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

                $params = [
                    ':id_proyecto'           => Database::proyectoId(),
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

                // MySQL ON DUPLICATE KEY UPDATE: rowCount()=1 → INSERT, >=2 → UPDATE, 0 → sin cambio
                $affected = $db->execute($sql, $params);
                if ($affected === 1) $stats['insertados']++;
                else                 $stats['actualizados']++;
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
            $db  = Database::programa();
            $pid = Database::proyectoId();
            // Aislamiento crítico: el botón "limpiar y re-sincronizar" SÓLO borra
            // las entregas del PIP activo. Antes borraba la tabla entera y se
            // perdían entregas de otros programas.
            return $db->execute(
                "DELETE FROM sag_trazaragro_movimientos WHERE id_proyecto = ?",
                [$pid]
            );
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function conteosTrazaragroPorTipo(): array
    {
        $conteos = ['111' => 0, '112' => 0, '113' => 0];
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();
            $rows = $db->fetchAll(
                "SELECT tipo_movimiento_id AS tipo, COUNT(*) AS total
                   FROM sag_trazaragro_movimientos
                  WHERE id_proyecto = ?
                    AND tipo_movimiento_id IN (111, 112, 113)
               GROUP BY tipo_movimiento_id",
                [$pid]
            );
            foreach ($rows as $row) {
                $tipo = (string)($row['tipo'] ?? '');
                if (array_key_exists($tipo, $conteos)) {
                    $conteos[$tipo] = (int)($row['total'] ?? 0);
                }
            }
        } catch (\Throwable $e) {
            error_log('conteosTrazaragroPorTipo: ' . $e->getMessage());
        }
        return $conteos;
    }

    /**
     * Devuelve la fecha de autorización OIRSA más reciente ya guardada.
     * El endpoint OIRSA filtra por AuthorizationDate, así que el incremental
     * debe usar la misma referencia temporal para no saltarse movimientos.
     *
     * @return string|null  'YYYY-MM-DD HH:MM:SS' o null si no hay filas.
     */
    private function ultimaFechaAutorizacionDelPrograma(): ?string
    {
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();
            if (!$db->tablaExiste('sag_trazaragro_movimientos')) return null;
            $row = $db->fetchOne(
                "SELECT MAX(fecha_autorizacion) AS u
                   FROM sag_trazaragro_movimientos
                  WHERE id_proyecto = ?",
                [$pid]
            );
            $valor = $row['u'] ?? null;
            return $valor ? (string)$valor : null;
        } catch (\Throwable $e) {
            error_log('ultimaFechaAutorizacionDelPrograma: ' . $e->getMessage());
            return null;
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
            error_log('EntregasController::exportar — ' . $e->getMessage());
            http_response_code(500);
            echo 'Error de conexión con la base de datos.';
            exit;
        }

        // Aislamiento: el CSV exporta SOLO movimientos del PIP activo
        // y SOLO entregas a productor (tipo 111). Las recepciones (tipo 113)
        // se exportan desde el módulo Inventario OIRSA.
        $pid = Database::proyectoId();

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

        // Exportación por lotes (keyset sobre la PK `id`): el dataset completo
        // con SELECT * (incluía el blob raw_json) agotaba la memoria de PHP.
        $ultimoId = PHP_INT_MAX;
        do {
            $rows = $db->fetchAll(
                "SELECT id, rubro, tipo_movimiento, objeto_trazable,
                        codigo_trazabilidad, guiasa_no, codigo_autorizacion, fecha_autorizacion,
                        origen_persona, origen_establecimiento, origen_departamento,
                        destino_persona, destino_dni, destino_establecimiento, destino_cue,
                        destino_departamento, destino_municipio,
                        cantidad, unidad, autorizado_por, estado_local
                   FROM sag_trazaragro_movimientos
                  WHERE id_proyecto = ?
                    AND tipo_movimiento_id = 111
                    AND id < ?
               ORDER BY id DESC
                  LIMIT 2000",
                [$pid, $ultimoId]
            );
            foreach ($rows as $r) {
                $ultimoId = (int)$r['id'];
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
            $seguir = count($rows) === 2000;
            unset($rows);
        } while ($seguir);
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
            $db  = Database::programa();
            $pid = Database::proyectoId();
        } catch (\Throwable $e) {
            error_log('EntregasController::acta — ' . $e->getMessage());
            http_response_code(500);
            echo 'Error de conexión con la base de datos.';
            exit;
        }

        // Aislamiento por proyecto: el acta sólo agrega entregas del PIP activo
        // y SOLO entregas a productor (tipo 111), no recepciones.
        $movs = $db->fetchAll(
            "SELECT * FROM sag_trazaragro_movimientos
             WHERE destino_dni = ? AND id_proyecto = ?
               AND tipo_movimiento_id = 111
             ORDER BY fecha_autorizacion ASC, guiasa_no ASC",
            [$dni, $pid]
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
            $pid = Database::proyectoId();
            // Aislamiento: el movimiento debe pertenecer al PIP activo
            $row = $db->fetchOne(
                "SELECT * FROM sag_trazaragro_movimientos
                  WHERE movement_id = ? AND id_proyecto = ?",
                [$id, $pid]
            );
            if (!$row) { $this->error('Movimiento no encontrado en su proyecto.'); return; }
            // Decodificar el raw_json para enviarlo como objeto
            if (!empty($row['raw_json'])) {
                $row['raw'] = json_decode($row['raw_json'], true);
            }
            $this->success('OK', ['movimiento' => $row]);
        } catch (\Throwable $e) {
            error_log('EntregasController::detalle — ' . $e->getMessage());
            $this->error('Error al cargar el detalle del movimiento.');
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
            'app/controllers/InventariosController.php'     => $base . '/app/controllers/InventariosController.php',
            'app/views/inventarios/index.php'               => $base . '/app/views/inventarios/index.php',
            'public/assets/js/modules/inventarios.js'       => $base . '/public/assets/js/modules/inventarios.js',
            'sql/migracion_004_trazaragro_movimientos.sql'  => $base . '/sql/migracion_004_trazaragro_movimientos.sql',
            'sql/bd_mddesarr_sag.sql'                       => $base . '/sql/bd_mddesarr_sag.sql',
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
            $db  = Database::programa();
            $pid = Database::proyectoId();
            $r   = $db->fetchOne("SHOW TABLES LIKE 'sag_trazaragro_movimientos'");
            $tablaExiste = (bool)$r;
            if ($tablaExiste) {
                // Aislamiento: el conteo de diagnóstico es por proyecto
                $r2 = $db->fetchOne(
                    "SELECT COUNT(*) AS c FROM sag_trazaragro_movimientos WHERE id_proyecto = ?",
                    [$pid]
                );
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
    //  DIAGNÓSTICO API OIRSA — Probar conexión en vivo
    // ════════════════════════════════════════════════════════════

    /**
     * Vista HTML del diagnóstico de conexión API.
     * Carga la página /entregas/diagnosticoApi y ejecuta el endpoint JSON vía AJAX.
     */
    public function diagnosticoApi(): void
    {
        $this->view('entregas/diag_api');
    }

    /**
     * Endpoint JSON: prueba la conexión a OIRSA en 4 pasos.
     * Devuelve por cada paso: ok/fail, detalle y tiempo de respuesta.
     */
    public function probarApi(): void
    {
        @set_time_limit(60);
        $pasos = [];

        // Paso 1 — Verificar config en .env
        $cfgOk = defined('TRAZARAGRO')
              && !empty(TRAZARAGRO['base_url'])
              && !empty(TRAZARAGRO['username'])
              && !empty(TRAZARAGRO['password']);
        $cfgDetalle = $cfgOk
            ? 'URL=' . TRAZARAGRO['base_url'] . ' · usuario=' . TRAZARAGRO['username']
            : 'Falta uno o más de: TRAZARAGRO_BASE_URL, TRAZARAGRO_USERNAME, TRAZARAGRO_PASSWORD en .env';
        $pasos[] = [
            'paso'    => 'Configuración en .env',
            'ok'      => $cfgOk,
            'detalle' => $cfgDetalle,
            'tiempo'  => 0,
        ];

        // Paso 2 — Servidor OIRSA accesible (ping HTTP HEAD)
        $tStart    = microtime(true);
        $reachable = false;
        $msgReach  = 'No probado (falta config)';
        if ($cfgOk) {
            $ch = curl_init(TRAZARAGRO['base_url']);
            curl_setopt_array($ch, [
                CURLOPT_NOBODY         => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);
            $reachable = ($code > 0);
            $msgReach  = $reachable
                ? "Servidor responde HTTP {$code}"
                : "Error de red: " . ($err ?: 'sin detalle');
        }
        $pasos[] = [
            'paso'    => 'Servidor OIRSA accesible',
            'ok'      => $reachable,
            'detalle' => $msgReach,
            'tiempo'  => round((microtime(true) - $tStart) * 1000, 1),
        ];

        // Paso 3 — Autenticación OAuth2 (obtener token)
        $authOk = false;
        $msgAuth = 'No probado';
        if ($cfgOk && $reachable && class_exists('TrazaragroClient')) {
            $tStart = microtime(true);
            try {
                $cli  = new TrazaragroClient();
                $ping = $cli->ping();
                $authOk  = !empty($ping['ok']);
                $msgAuth = $authOk
                    ? 'Token OAuth2 obtenido correctamente (' . ($ping['mode'] ?? 'live') . ')'
                    : 'Falló autenticación: ' . ($ping['msg'] ?? 'sin detalle');
                $tAuth = round((microtime(true) - $tStart) * 1000, 1);
            } catch (\Throwable $e) {
                $msgAuth = 'Excepción: ' . substr($e->getMessage(), 0, 200);
                $tAuth = round((microtime(true) - $tStart) * 1000, 1);
            }
        } else {
            $tAuth = 0;
        }
        $pasos[] = [
            'paso'    => 'Autenticación OAuth2',
            'ok'      => $authOk,
            'detalle' => $msgAuth,
            'tiempo'  => $tAuth,
        ];

        // Paso 4 — Consulta OData de prueba (top=1)
        $queryOk = false;
        $msgQuery = 'No probado';
        $tQuery = 0;
        if ($authOk && isset($cli)) {
            $tStart = microtime(true);
            try {
                $movs = $cli->fetchEntregas([
                    'top'       => 1,
                    'desde'     => date('Y-m-d', strtotime('-30 days')),
                    'hasta'     => date('Y-m-d'),
                    'tipoMovId' => 0,
                ]);
                $queryOk  = true;
                $msgQuery = 'OData OK — devolvió ' . count($movs) . ' movimiento' . (count($movs) !== 1 ? 's' : '') . ' (prueba)';
                $tQuery = round((microtime(true) - $tStart) * 1000, 1);
            } catch (\Throwable $e) {
                $msgQuery = 'Excepción: ' . substr($e->getMessage(), 0, 200);
                $tQuery = round((microtime(true) - $tStart) * 1000, 1);
            }
        }
        $pasos[] = [
            'paso'    => 'Consulta OData de prueba',
            'ok'      => $queryOk,
            'detalle' => $msgQuery,
            'tiempo'  => $tQuery,
        ];

        // Resumen + config técnica
        $todoOk = true;
        foreach ($pasos as $p) { if (!$p['ok']) { $todoOk = false; break; } }

        $this->success('Diagnóstico API OIRSA', [
            'todo_ok' => $todoOk,
            'pasos'   => $pasos,
            'config'  => [
                'base_url'     => defined('TRAZARAGRO') ? (TRAZARAGRO['base_url']  ?? '') : '',
                'username'     => defined('TRAZARAGRO') ? (TRAZARAGRO['username']  ?: '(vacío)') : '',
                'has_password' => defined('TRAZARAGRO') ? !empty(TRAZARAGRO['password']) : false,
                'client_id'    => defined('TRAZARAGRO') ? (TRAZARAGRO['client_id'] ?? '') : '',
                'instance'     => defined('TRAZARAGRO') ? (TRAZARAGRO['instance']  ?? '') : '',
                'timeout_s'    => defined('TRAZARAGRO') ? (TRAZARAGRO['timeout']   ?? 30) : 0,
                'php_version'  => PHP_VERSION,
                'curl_version' => function_exists('curl_version') ? (curl_version()['version'] ?? '?') : 'sin cURL',
            ],
        ]);
    }

    public function aprobar(): void
    {
        // No aplica directamente en la vista OIRSA. Reservado para futura
        // funcionalidad de marcar revisión local sobre un movimiento.
        $id  = (int) $this->getPost('id', 0);
        $obs = $this->getPost('observacion', '');
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();
            // Aislamiento: sólo afecta movimientos del PIP activo
            $afect = $db->execute(
                "UPDATE sag_trazaragro_movimientos
                 SET estado_local='entregado', observaciones_local=?, revisado_por_local=?, fecha_revision_local=NOW()
                 WHERE movement_id=? AND id_proyecto=?",
                [$obs, $_SESSION['user']['email'] ?? 'sistema', $id, $pid]
            );
            if ($afect === 0) { $this->error('Movimiento no encontrado en su proyecto.'); return; }
            $this->logAction('APROBAR_MOV', 'entregas', "#{$id}");
            $this->success("Movimiento #{$id} marcado como entregado.", ['id' => $id]);
        } catch (\Throwable $e) {
            error_log('EntregasController::aprobar — ' . $e->getMessage());
            $this->error('Error al marcar el movimiento como entregado.');
        }
    }

    public function rechazar(): void
    {
        $id  = (int) $this->getPost('id', 0);
        $obs = $this->getPost('observacion', '');
        if (!$obs) { $this->error('Debe indicar el motivo del rechazo.'); return; }
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();
            // Aislamiento: sólo afecta movimientos del PIP activo
            $afect = $db->execute(
                "UPDATE sag_trazaragro_movimientos
                 SET estado_local='observado', observaciones_local=?, revisado_por_local=?, fecha_revision_local=NOW()
                 WHERE movement_id=? AND id_proyecto=?",
                [$obs, $_SESSION['user']['email'] ?? 'sistema', $id, $pid]
            );
            if ($afect === 0) { $this->error('Movimiento no encontrado en su proyecto.'); return; }
            $this->logAction('OBSERVAR_MOV', 'entregas', "#{$id} obs={$obs}");
            $this->success("Movimiento #{$id} observado.", ['id' => $id]);
        } catch (\Throwable $e) {
            error_log('EntregasController::rechazar — ' . $e->getMessage());
            $this->error('Error al observar el movimiento.');
        }
    }
}
