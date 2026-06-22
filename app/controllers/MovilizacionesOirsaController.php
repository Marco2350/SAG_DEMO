<?php
/**
 * MovilizacionesOirsaController — Reportes consolidados de OIRSA Trazaragro.
 *
 * Lee de sag_trazaragro_movimientos (poblada por el sync del módulo Entregas)
 * y muestra TODOS los tipos de movilización del programa activo:
 *   • Tipo 113 — Recepción de insumos (Proveedor → Bodega)
 *   • Tipo 111 — Entrega de insumos (Bodega → Productor)
 *
 * Es un módulo de SOLO LECTURA. No genera movimientos propios; refleja lo
 * sincronizado por el módulo Entregas. Cada PIP solo ve sus propios datos.
 */
class MovilizacionesOirsaController extends Controller
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
        $pageTitle = 'Movilizaciones OIRSA — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;

        // KPIs precalculados para el primer paint
        $kpis = $this->kpisGlobales();

        // Catálogos para los selectores de filtro
        $filtros = $this->catalogosFiltros();

        $this->view('movilizaciones_oirsa/index', compact('pageTitle', 'kpis', 'filtros'));
    }

    // ════════════════════════════════════════════════════════════
    //  KPIs GLOBALES (Recepciones + Entregas)
    // ════════════════════════════════════════════════════════════
    private function kpisGlobales(): array
    {
        $vacio = [
            'recep_total'    => 0, 'recep_cantidad'   => 0,
            'ent_total'      => 0, 'ent_cantidad'     => 0,
            'proveedores'    => 0, 'bodegas'          => 0,
            'productos'      => 0, 'manifiestos'      => 0,
            'primera_fecha'  => '', 'ultima_fecha'    => '',
        ];

        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();

            if (!$db->tablaExiste('sag_trazaragro_movimientos')) {
                return $vacio;
            }

            $row = $db->fetchOne(
                "SELECT
                    SUM(CASE WHEN tipo_movimiento_id = 113 THEN 1 ELSE 0 END)              AS recep_total,
                    SUM(CASE WHEN tipo_movimiento_id = 113 THEN cantidad ELSE 0 END)       AS recep_cantidad,
                    SUM(CASE WHEN tipo_movimiento_id = 111 THEN 1 ELSE 0 END)              AS ent_total,
                    SUM(CASE WHEN tipo_movimiento_id = 111 THEN cantidad ELSE 0 END)       AS ent_cantidad,
                    COUNT(DISTINCT CASE WHEN tipo_movimiento_id = 113
                                        THEN NULLIF(origen_establecimiento,'')
                                        END)                                                AS proveedores,
                    COUNT(DISTINCT CASE WHEN tipo_movimiento_id IN (111,113)
                                        THEN NULLIF(IF(tipo_movimiento_id = 113, destino_establecimiento, origen_establecimiento),'')
                                        END)                                                AS bodegas,
                    COUNT(DISTINCT NULLIF(objeto_trazable,''))                             AS productos,
                    COUNT(DISTINCT NULLIF(guiasa_no,''))                                   AS manifiestos,
                    MIN(fecha_autorizacion)                                                AS primera_fecha,
                    MAX(fecha_autorizacion)                                                AS ultima_fecha
                 FROM sag_trazaragro_movimientos
                 WHERE id_proyecto = ? AND tipo_movimiento_id IN (111, 113)",
                [$pid]
            );

            if (!$row) return $vacio;

            return [
                'recep_total'   => (int)($row['recep_total']    ?? 0),
                'recep_cantidad'=> (float)($row['recep_cantidad'] ?? 0),
                'ent_total'     => (int)($row['ent_total']      ?? 0),
                'ent_cantidad'  => (float)($row['ent_cantidad']  ?? 0),
                'proveedores'   => (int)($row['proveedores']    ?? 0),
                'bodegas'       => (int)($row['bodegas']        ?? 0),
                'productos'     => (int)($row['productos']      ?? 0),
                'manifiestos'   => (int)($row['manifiestos']    ?? 0),
                'primera_fecha' => (string)($row['primera_fecha'] ?? ''),
                'ultima_fecha'  => (string)($row['ultima_fecha']  ?? ''),
            ];
        } catch (\Throwable $e) {
            error_log('MovilizacionesOirsa::kpisGlobales — ' . $e->getMessage());
            return $vacio;
        }
    }

    // ════════════════════════════════════════════════════════════
    //  CATÁLOGOS PARA SELECT DE FILTROS
    //  (valores DISTINCT presentes en los movimientos OIRSA)
    // ════════════════════════════════════════════════════════════
    private function catalogosFiltros(): array
    {
        $vacio = ['proveedores' => [], 'bodegas' => [], 'productos' => []];
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();
            if (!$db->tablaExiste('sag_trazaragro_movimientos')) return $vacio;

            // Proveedores: origen_establecimiento en recepciones (tipo 113)
            $rowsP = $db->fetchAll(
                "SELECT DISTINCT origen_establecimiento AS nombre
                   FROM sag_trazaragro_movimientos
                  WHERE id_proyecto = ? AND tipo_movimiento_id = 113
                    AND origen_establecimiento IS NOT NULL AND origen_establecimiento <> ''
               ORDER BY nombre",
                [$pid]
            );
            // Bodegas: destino en recepciones + origen en entregas
            $rowsB = $db->fetchAll(
                "SELECT DISTINCT estab AS nombre FROM (
                    SELECT destino_establecimiento AS estab FROM sag_trazaragro_movimientos
                     WHERE id_proyecto = ? AND tipo_movimiento_id = 113
                       AND destino_establecimiento IS NOT NULL AND destino_establecimiento <> ''
                    UNION
                    SELECT origen_establecimiento AS estab FROM sag_trazaragro_movimientos
                     WHERE id_proyecto = ? AND tipo_movimiento_id = 111
                       AND origen_establecimiento IS NOT NULL AND origen_establecimiento <> ''
                 ) AS u
                 ORDER BY nombre",
                [$pid, $pid]
            );
            $rowsProd = $db->fetchAll(
                "SELECT DISTINCT objeto_trazable AS nombre
                   FROM sag_trazaragro_movimientos
                  WHERE id_proyecto = ? AND tipo_movimiento_id IN (111, 113)
                    AND objeto_trazable IS NOT NULL AND objeto_trazable <> ''
               ORDER BY nombre",
                [$pid]
            );

            return [
                'proveedores' => array_column($rowsP,    'nombre'),
                'bodegas'     => array_column($rowsB,    'nombre'),
                'productos'   => array_column($rowsProd, 'nombre'),
            ];
        } catch (\Throwable $e) {
            error_log('MovilizacionesOirsa::catalogosFiltros — ' . $e->getMessage());
            return $vacio;
        }
    }

    // ════════════════════════════════════════════════════════════
    //  AJAX: listado paginado server-side
    //  GET /movilizaciones/listar?draw=N&start=0&length=25
    //                            &f_tipo=&f_proveedor=&f_bodega=&f_producto=
    //                            &f_desde=&f_hasta=&f_guiasa=
    //
    //  Respuesta JSON estándar DataTables: { draw, recordsTotal,
    //  recordsFiltered, data: [...] }. Escala a millones porque hace LIMIT
    //  OFFSET + COUNT(*) sobre los índices ya existentes.
    // ════════════════════════════════════════════════════════════
    public function listar(): void
    {
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();

            if (!$db->tablaExiste('sag_trazaragro_movimientos')) {
                $this->json(['draw' => (int)($_GET['draw'] ?? 0),
                             'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
                return;
            }

            $draw   = max(0, (int)($_GET['draw'] ?? 0));
            $start  = max(0, (int)($_GET['start'] ?? 0));
            $length = (int)($_GET['length'] ?? 25);
            if ($length < 1 || $length > 200) $length = 25;
            $search = trim((string)($_GET['search']['value'] ?? ''));

            $columnsOrder = [
                0 => 'fecha_autorizacion',
                1 => 'tipo_movimiento_id',
                2 => 'origen_establecimiento',
                3 => 'destino_establecimiento',
                4 => 'objeto_trazable',
                5 => 'guiasa_no',
                6 => 'cantidad',
            ];
            $orderCol  = (int)($_GET['order'][0]['column'] ?? 0);
            $orderDir  = strtolower((string)($_GET['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
            $orderExpr = ($columnsOrder[$orderCol] ?? 'fecha_autorizacion') . ' ' . $orderDir . ', movement_id ' . $orderDir;

            // Filtros
            $where  = ['id_proyecto = ?', 'tipo_movimiento_id IN (111, 113)'];
            $params = [$pid];

            $tipo = (string)($_GET['f_tipo'] ?? '');
            if ($tipo === 'recepcion')   { $where[] = 'tipo_movimiento_id = 113'; }
            elseif ($tipo === 'entrega') { $where[] = 'tipo_movimiento_id = 111'; }

            $prov = (string)($_GET['f_proveedor'] ?? '');
            if ($prov !== '') { $where[] = '(origen_establecimiento = ? AND tipo_movimiento_id = 113)'; $params[] = $prov; }

            $bod = (string)($_GET['f_bodega'] ?? '');
            if ($bod !== '') {
                $where[] = '((tipo_movimiento_id = 113 AND destino_establecimiento = ?) OR (tipo_movimiento_id = 111 AND origen_establecimiento = ?))';
                $params[] = $bod;
                $params[] = $bod;
            }

            $prod = (string)($_GET['f_producto'] ?? '');
            if ($prod !== '') { $where[] = 'objeto_trazable = ?'; $params[] = $prod; }

            $desde = (string)($_GET['f_desde'] ?? '');
            $hasta = (string)($_GET['f_hasta'] ?? '');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) { $where[] = 'fecha_autorizacion >= ?'; $params[] = $desde . ' 00:00:00'; }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) { $where[] = 'fecha_autorizacion <= ?'; $params[] = $hasta . ' 23:59:59'; }

            $guiasa = trim((string)($_GET['f_guiasa'] ?? ''));
            if ($guiasa !== '') { $where[] = 'guiasa_no = ?'; $params[] = $guiasa; }

            if ($search !== '') {
                $where[] = '(guiasa_no = ? OR codigo_trazabilidad = ? OR codigo_autorizacion = ? OR objeto_trazable LIKE ?)';
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
                $params[] = '%' . $search . '%';
            }

            $whereSql = 'WHERE ' . implode(' AND ', $where);

            // recordsTotal — total del proyecto (ambos tipos) sin filtros adicionales
            $rowT = $db->fetchOne(
                "SELECT COUNT(*) AS c FROM sag_trazaragro_movimientos
                  WHERE id_proyecto = ? AND tipo_movimiento_id IN (111, 113)",
                [$pid]
            );
            $recordsTotal = (int)($rowT['c'] ?? 0);

            // recordsFiltered
            $rowF = $db->fetchOne(
                "SELECT COUNT(*) AS c FROM sag_trazaragro_movimientos $whereSql",
                $params
            );
            $recordsFiltered = (int)($rowF['c'] ?? 0);

            // Página
            $rows = $db->fetchAll(
                "SELECT movement_id, tipo_movimiento_id, tipo_movimiento,
                        fecha_autorizacion, rubro, objeto_trazable,
                        codigo_trazabilidad, guiasa_no, codigo_autorizacion,
                        origen_establecimiento, origen_departamento, origen_municipio, origen_persona,
                        destino_establecimiento, destino_departamento, destino_municipio,
                        destino_persona, destino_dni, destino_nombre,
                        cantidad, unidad, status_oirsa
                   FROM sag_trazaragro_movimientos
                   $whereSql
                   ORDER BY $orderExpr
                   LIMIT $start, $length",
                $params
            );

            $this->json([
                'draw'            => $draw,
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $rows,
            ]);
        } catch (\Throwable $e) {
            error_log('MovilizacionesOirsa::listar — ' . $e->getMessage());
            $this->json([
                'draw'            => (int)($_GET['draw'] ?? 0),
                'recordsTotal'    => 0, 'recordsFiltered' => 0, 'data' => [],
                'error'           => 'Error interno.',
            ], 500);
        }
    }

    // ════════════════════════════════════════════════════════════
    //  AJAX: agregados por bodega / proveedor / producto
    //  GET /movilizaciones/resumen?agrupacion=bodega|proveedor|producto
    // ════════════════════════════════════════════════════════════
    public function resumen(): void
    {
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();
            $agr = (string)($_GET['agrupacion'] ?? 'bodega');

            if (!$db->tablaExiste('sag_trazaragro_movimientos')) {
                $this->json(['data' => []]);
                return;
            }

            switch ($agr) {
                case 'proveedor':
                    // Proveedores solo aparecen en recepciones (tipo 113)
                    $rows = $db->fetchAll(
                        "SELECT origen_establecimiento               AS nombre,
                                MAX(origen_departamento)              AS departamento,
                                COUNT(*)                              AS movimientos,
                                COALESCE(SUM(cantidad), 0)            AS cantidad,
                                COUNT(DISTINCT objeto_trazable)        AS productos,
                                COUNT(DISTINCT destino_establecimiento) AS bodegas_entregadas,
                                MIN(fecha_autorizacion)               AS primera,
                                MAX(fecha_autorizacion)               AS ultima
                           FROM sag_trazaragro_movimientos
                          WHERE id_proyecto = ? AND tipo_movimiento_id = 113
                            AND origen_establecimiento IS NOT NULL AND origen_establecimiento <> ''
                       GROUP BY origen_establecimiento
                       ORDER BY cantidad DESC
                          LIMIT 200",
                        [$pid]
                    );
                    break;

                case 'producto':
                    $rows = $db->fetchAll(
                        "SELECT objeto_trazable                       AS nombre,
                                MAX(unidad)                            AS unidad,
                                SUM(CASE WHEN tipo_movimiento_id = 113 THEN cantidad ELSE 0 END) AS recibido,
                                SUM(CASE WHEN tipo_movimiento_id = 111 THEN cantidad ELSE 0 END) AS entregado,
                                SUM(CASE WHEN tipo_movimiento_id = 113 THEN 1 ELSE 0 END)        AS num_recepciones,
                                SUM(CASE WHEN tipo_movimiento_id = 111 THEN 1 ELSE 0 END)        AS num_entregas
                           FROM sag_trazaragro_movimientos
                          WHERE id_proyecto = ? AND tipo_movimiento_id IN (111, 113)
                            AND objeto_trazable IS NOT NULL AND objeto_trazable <> ''
                       GROUP BY objeto_trazable
                       ORDER BY recibido DESC
                          LIMIT 200",
                        [$pid]
                    );
                    foreach ($rows as &$r) {
                        $r['stock_estimado'] = (float)$r['recibido'] - (float)$r['entregado'];
                    }
                    unset($r);
                    break;

                case 'bodega':
                default:
                    // Bodegas: aparecen como destino en recepciones (113) y como origen en entregas (111)
                    $rows = $db->fetchAll(
                        "SELECT nombre, MAX(departamento) AS departamento,
                                SUM(recibido) AS recibido, SUM(entregado) AS entregado,
                                SUM(num_recep) AS num_recep, SUM(num_ent) AS num_ent
                           FROM (
                              SELECT destino_establecimiento AS nombre,
                                     MAX(destino_departamento) AS departamento,
                                     SUM(cantidad) AS recibido, 0 AS entregado,
                                     COUNT(*) AS num_recep, 0 AS num_ent
                                FROM sag_trazaragro_movimientos
                               WHERE id_proyecto = ? AND tipo_movimiento_id = 113
                                 AND destino_establecimiento IS NOT NULL AND destino_establecimiento <> ''
                            GROUP BY destino_establecimiento
                              UNION ALL
                              SELECT origen_establecimiento AS nombre,
                                     MAX(origen_departamento) AS departamento,
                                     0 AS recibido, SUM(cantidad) AS entregado,
                                     0 AS num_recep, COUNT(*) AS num_ent
                                FROM sag_trazaragro_movimientos
                               WHERE id_proyecto = ? AND tipo_movimiento_id = 111
                                 AND origen_establecimiento IS NOT NULL AND origen_establecimiento <> ''
                            GROUP BY origen_establecimiento
                           ) AS u
                       GROUP BY nombre
                       ORDER BY recibido DESC
                          LIMIT 200",
                        [$pid, $pid]
                    );
                    foreach ($rows as &$r) {
                        $r['stock_estimado'] = (float)$r['recibido'] - (float)$r['entregado'];
                    }
                    unset($r);
                    break;
            }

            $this->json(['agrupacion' => $agr, 'data' => $rows]);
        } catch (\Throwable $e) {
            error_log('MovilizacionesOirsa::resumen — ' . $e->getMessage());
            $this->json(['data' => [], 'error' => 'Error interno.'], 500);
        }
    }
}
