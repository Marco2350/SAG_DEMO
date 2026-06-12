<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requirePrograma();

        $pageTitle = 'Dashboard — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;
        $usaLeaflet = true;
        $this->view('dashboard/index', compact('pageTitle', 'usaLeaflet'));
    }

    /** AJAX — KPIs del programa activo */
    public function stats(): void
    {
        $this->requirePrograma();

        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();

            // 6 queries individuales → 1 round-trip con subqueries escalares
            $r = $db->fetchOne(
                "SELECT
                    (SELECT COUNT(*) FROM sag_organizaciones       WHERE estado='activa'   AND id_proyecto=?) AS orgs,
                    (SELECT COUNT(*) FROM sag_organizaciones       WHERE estado='revision' AND id_proyecto=?) AS revision,
                    (SELECT COUNT(*) FROM sag_beneficiarios        WHERE estado='activo'   AND id_proyecto=?) AS benes,
                    (SELECT COUNT(*) FROM sag_capacitaciones       WHERE id_proyecto=?)                       AS caps,
                    (SELECT COUNT(*) FROM sag_asistencias_tecnicas WHERE id_proyecto=?)                       AS at,
                    (SELECT COUNT(DISTINCT d.id_departamento)
                     FROM sag_organizaciones o
                     INNER JOIN sag_municipios   m ON m.id_municipio    = o.id_municipio
                     INNER JOIN sag_departamentos d ON d.id_departamento = m.id_departamento
                     WHERE o.estado='activa' AND o.id_proyecto=?)                                             AS deptos",
                [$pid, $pid, $pid, $pid, $pid, $pid]
            );
            $orgs     = (int)($r['orgs']     ?? 0);
            $revision = (int)($r['revision'] ?? 0);
            $benes    = (int)($r['benes']    ?? 0);
            $caps     = (int)($r['caps']     ?? 0);
            $at       = (int)($r['at']       ?? 0);
            $deptos   = (int)($r['deptos']   ?? 0);

            $benIncentivo = 0;
            try {
                $tr = $db->fetchOne(
                    "SELECT COUNT(DISTINCT destino_dni) AS c
                     FROM sag_trazaragro_movimientos
                     WHERE destino_dni IS NOT NULL AND destino_dni <> ''"
                );
                $benIncentivo = (int)($tr['c'] ?? 0);
            } catch (\Throwable $e) { /* tabla opcional */ }

            // Últimas 5 organizaciones — JOIN en lugar de subquery correlacionada por fila
            $ultimas = $db->fetchAll(
                "SELECT o.nombre, o.representante, o.email,
                        d.nombre AS departamento,
                        COALESCE(bc.num_beneficiarios, 0) AS num_beneficiarios,
                        o.estado, o.fecha_registro
                 FROM sag_organizaciones o
                 LEFT JOIN sag_municipios   m  ON m.id_municipio    = o.id_municipio
                 LEFT JOIN sag_departamentos d  ON d.id_departamento = m.id_departamento
                 LEFT JOIN (
                     SELECT id_organizacion, COUNT(*) AS num_beneficiarios
                     FROM sag_beneficiarios WHERE estado='activo'
                     GROUP BY id_organizacion
                 ) bc ON bc.id_organizacion = o.id_organizacion
                 WHERE o.id_proyecto=?
                 ORDER BY o.id_organizacion DESC
                 LIMIT 5",
                [$pid]
            );

            $this->success('OK', [
                'kpis'    => compact('orgs', 'benes', 'caps', 'at', 'deptos', 'revision', 'benIncentivo'),
                'ultimas' => $ultimas,
            ]);

        } catch (Exception $e) {
            error_log('DashboardController::stats — ' . $e->getMessage());
            $this->success('OK', [
                'kpis'    => ['orgs' => 0, 'benes' => 0, 'caps' => 0, 'at' => 0, 'deptos' => 0, 'revision' => 0, 'benIncentivo' => 0],
                'ultimas' => [],
            ]);
        }
    }

    /** AJAX — Coordenadas de organizaciones para el mapa */
    public function mapa(): void
    {
        $this->requirePrograma();

        try {
            $db = Database::programa();

            // Subquery correlacionada por fila → LEFT JOIN con agregación
            $puntos = $db->fetchAll(
                "SELECT o.nombre, o.representante,
                        TRIM(SUBSTRING_INDEX(o.coordenadas, ',', 1))  AS latitud,
                        TRIM(SUBSTRING_INDEX(o.coordenadas, ',', -1)) AS longitud,
                        COALESCE(bc.num_beneficiarios, 0) AS num_beneficiarios,
                        o.estado,
                        d.nombre AS departamento
                 FROM sag_organizaciones o
                 LEFT JOIN sag_municipios   m  ON m.id_municipio    = o.id_municipio
                 LEFT JOIN sag_departamentos d  ON d.id_departamento = m.id_departamento
                 LEFT JOIN (
                     SELECT id_organizacion, COUNT(*) AS num_beneficiarios
                     FROM sag_beneficiarios WHERE estado='activo'
                     GROUP BY id_organizacion
                 ) bc ON bc.id_organizacion = o.id_organizacion
                 WHERE o.coordenadas IS NOT NULL AND o.coordenadas LIKE '%,%' AND o.id_proyecto=?",
                [Database::proyectoId()]
            );

            $this->success('OK', $puntos);

        } catch (Exception $e) {
            error_log('DashboardController::mapa — ' . $e->getMessage());
            $this->success('OK', []);
        }
    }
}
