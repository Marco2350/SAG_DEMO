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
            $db = Database::programa();

            $orgs  = (int) $db->fetchOne("SELECT COUNT(*) AS c FROM sag_organizaciones WHERE estado='activa'")['c'];
            $benes = (int) $db->fetchOne("SELECT COUNT(*) AS c FROM sag_beneficiarios WHERE estado='activo'")['c'];
            $caps  = (int) $db->fetchOne("SELECT COUNT(*) AS c FROM sag_capacitaciones")['c'];
            $at    = (int) $db->fetchOne("SELECT COUNT(*) AS c FROM sag_asistencias_tecnicas")['c'];
            $deptos = (int) $db->fetchOne(
                "SELECT COUNT(DISTINCT d.id_departamento) AS c
                 FROM sag_organizaciones o
                 INNER JOIN sag_municipios m ON m.id_municipio = o.id_municipio
                 INNER JOIN sag_departamentos d ON d.id_departamento = m.id_departamento
                 WHERE o.estado='activa'"
            )['c'];
            $revision = (int) $db->fetchOne("SELECT COUNT(*) AS c FROM sag_organizaciones WHERE estado='revision'")['c'];

            // R-005: Productores beneficiados con incentivos = DNIs únicos en movimientos Trazaragro
            $benIncentivo = 0;
            try {
                $r = $db->fetchOne(
                    "SELECT COUNT(DISTINCT destino_dni) AS c
                     FROM sag_trazaragro_movimientos
                     WHERE destino_dni IS NOT NULL AND destino_dni <> ''"
                );
                $benIncentivo = (int)($r['c'] ?? 0);
            } catch (\Throwable $e) {
                // Tabla aún no existe en esa BD — no es error grave
            }

            // Últimas 5 organizaciones
            $ultimas = $db->fetchAll(
                "SELECT o.nombre, o.representante, o.email,
                        d.nombre AS departamento,
                        o.num_beneficiarios, o.estado, o.fecha_registro
                 FROM sag_organizaciones o
                 LEFT JOIN sag_municipios m ON m.id_municipio = o.id_municipio
                 LEFT JOIN sag_departamentos d ON d.id_departamento = m.id_departamento
                 ORDER BY o.id_organizacion DESC
                 LIMIT 5"
            );

            $this->success('OK', [
                'kpis'   => compact('orgs', 'benes', 'caps', 'at', 'deptos', 'revision', 'benIncentivo'),
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

            $puntos = $db->fetchAll(
                "SELECT o.nombre, o.representante,
                        o.latitud, o.longitud,
                        o.num_beneficiarios, o.estado,
                        d.nombre AS departamento
                 FROM sag_organizaciones o
                 LEFT JOIN sag_municipios m ON m.id_municipio = o.id_municipio
                 LEFT JOIN sag_departamentos d ON d.id_departamento = m.id_departamento
                 WHERE o.latitud IS NOT NULL AND o.longitud IS NOT NULL"
            );

            $this->success('OK', $puntos);

        } catch (Exception $e) {
            error_log('DashboardController::mapa — ' . $e->getMessage());
            $this->success('OK', []);
        }
    }
}
