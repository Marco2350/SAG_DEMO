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

            $orgs  = (int) $db->fetchOne("SELECT COUNT(*) AS c FROM sag_organizaciones WHERE estado='activa' AND id_proyecto=?", [$pid])['c'];
            $benes = (int) $db->fetchOne("SELECT COUNT(*) AS c FROM sag_beneficiarios WHERE estado='activo' AND id_proyecto=?", [$pid])['c'];
            $caps  = (int) $db->fetchOne("SELECT COUNT(*) AS c FROM sag_capacitaciones WHERE id_proyecto=?", [$pid])['c'];
            $at    = (int) $db->fetchOne("SELECT COUNT(*) AS c FROM sag_asistencias_tecnicas WHERE id_proyecto=?", [$pid])['c'];
            $deptos = (int) $db->fetchOne(
                "SELECT COUNT(DISTINCT d.id_departamento) AS c
                 FROM sag_organizaciones o
                 INNER JOIN sag_municipios m ON m.id_municipio = o.id_municipio
                 INNER JOIN sag_departamentos d ON d.id_departamento = m.id_departamento
                 WHERE o.estado='activa' AND o.id_proyecto=?",
                [$pid]
            )['c'];
            $revision = (int) $db->fetchOne("SELECT COUNT(*) AS c FROM sag_organizaciones WHERE estado='revision' AND id_proyecto=?", [$pid])['c'];

            // Últimas 5 organizaciones
            $ultimas = $db->fetchAll(
                "SELECT o.nombre, o.representante, o.email,
                        d.nombre AS departamento,
                        (SELECT COUNT(*) FROM sag_beneficiarios b
                         WHERE b.id_organizacion = o.id_organizacion AND b.estado='activo') AS num_beneficiarios,
                        o.estado, o.fecha_registro
                 FROM sag_organizaciones o
                 LEFT JOIN sag_municipios m ON m.id_municipio = o.id_municipio
                 LEFT JOIN sag_departamentos d ON d.id_departamento = m.id_departamento
                 WHERE o.id_proyecto=?
                 ORDER BY o.id_organizacion DESC
                 LIMIT 5",
                [$pid]
            );

            $this->success('OK', [
                'kpis'   => compact('orgs', 'benes', 'caps', 'at', 'deptos', 'revision'),
                'ultimas' => $ultimas,
            ]);

        } catch (Exception $e) {
            error_log('DashboardController::stats — ' . $e->getMessage());
            $this->success('OK', [
                'kpis'    => ['orgs' => 0, 'benes' => 0, 'caps' => 0, 'at' => 0, 'deptos' => 0, 'revision' => 0],
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

            $puntos = $db->fetchAll(
                "SELECT o.nombre, o.representante,
                        TRIM(SUBSTRING_INDEX(o.coordenadas, ',', 1))  AS latitud,
                        TRIM(SUBSTRING_INDEX(o.coordenadas, ',', -1)) AS longitud,
                        (SELECT COUNT(*) FROM sag_beneficiarios b
                         WHERE b.id_organizacion = o.id_organizacion AND b.estado='activo') AS num_beneficiarios,
                        o.estado,
                        d.nombre AS departamento
                 FROM sag_organizaciones o
                 LEFT JOIN sag_municipios m ON m.id_municipio = o.id_municipio
                 LEFT JOIN sag_departamentos d ON d.id_departamento = m.id_departamento
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
