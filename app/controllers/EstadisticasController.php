<?php
class EstadisticasController extends Controller
{
    public function __construct()
    {
        $this->requirePrograma();
    }

    public function index(): void
    {
        $pageTitle  = 'Estadísticas — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;
        $usaChartJs = true;
        $this->view('estadisticas/index', compact('pageTitle', 'usaChartJs'));
    }

    public function datos(): void
    {
        try {
            $db   = Database::programa();
            $anio = (int) $this->getQuery('anio', date('Y'));

            // ── Beneficiarios por sexo ─────────────────
            $sexo = $db->fetchAll(
                "SELECT sexo, COUNT(*) AS total FROM sag_beneficiarios WHERE estado='activo' GROUP BY sexo"
            );

            // ── Beneficiarios por departamento (top 10) ─
            $porDepto = $db->fetchAll(
                "SELECT d.nombre AS departamento, COUNT(b.id_beneficiario) AS total
                 FROM sag_beneficiarios b
                 INNER JOIN sag_departamentos d ON d.id_departamento = b.id_departamento
                 WHERE b.estado='activo'
                 GROUP BY b.id_departamento ORDER BY total DESC LIMIT 10"
            );

            // ── Organizaciones por tipo ────────────────
            $orgTipo = $db->fetchAll(
                "SELECT tipo, COUNT(*) AS total FROM sag_organizaciones GROUP BY tipo ORDER BY total DESC"
            );

            // ── Organizaciones por estado ──────────────
            $orgEstado = $db->fetchAll(
                "SELECT estado, COUNT(*) AS total FROM sag_organizaciones GROUP BY estado"
            );

            // ── Capacitaciones por mes (año seleccionado) ─
            $capMes = $db->fetchAll(
                "SELECT MONTH(fecha_capacitacion) AS mes,
                        COUNT(*) AS total_eventos,
                        SUM(num_participantes) AS total_participantes
                 FROM sag_capacitaciones
                 WHERE YEAR(fecha_capacitacion) = ? AND estado='finalizado'
                 GROUP BY mes ORDER BY mes",
                [$anio]
            );

            // ── AT por tipo ────────────────────────────
            $atTipo = $db->fetchAll(
                "SELECT t.nombre AS tipo, COUNT(a.id_at) AS total
                 FROM sag_tipo_at t
                 LEFT JOIN sag_asistencias_tecnicas a ON a.id_tipo_at = t.id_tipo_at
                    AND YEAR(a.fecha_visita) = ? AND a.estado='finalizado'
                 GROUP BY t.id_tipo_at ORDER BY total DESC",
                [$anio]
            );

            // ── AT por mes (año seleccionado) ──────────
            $atMes = $db->fetchAll(
                "SELECT MONTH(fecha_visita) AS mes, COUNT(*) AS total
                 FROM sag_asistencias_tecnicas
                 WHERE YEAR(fecha_visita) = ? AND estado='finalizado'
                 GROUP BY mes ORDER BY mes",
                [$anio]
            );

            // ── Resumen general ────────────────────────
            $resumen = $db->fetchOne(
                "SELECT
                    (SELECT COUNT(*) FROM sag_beneficiarios WHERE estado='activo') AS beneficiarios,
                    (SELECT COUNT(*) FROM sag_organizaciones WHERE estado='activa') AS organizaciones,
                    (SELECT COUNT(*) FROM sag_capacitaciones WHERE estado='finalizado') AS capacitaciones,
                    (SELECT COUNT(*) FROM sag_asistencias_tecnicas WHERE estado='finalizado') AS asistencias,
                    (SELECT SUM(num_participantes) FROM sag_capacitaciones WHERE estado='finalizado') AS participantes_cap"
            );

            // ── Años disponibles ───────────────────────
            $anios = $db->fetchAll(
                "SELECT DISTINCT YEAR(fecha_capacitacion) AS anio FROM sag_capacitaciones
                 UNION
                 SELECT DISTINCT YEAR(fecha_visita) FROM sag_asistencias_tecnicas
                 ORDER BY anio DESC"
            );

            $this->json([
                'success'  => true,
                'anio'     => $anio,
                'resumen'  => $resumen,
                'sexo'     => $sexo,
                'porDepto' => $porDepto,
                'orgTipo'  => $orgTipo,
                'orgEstado'=> $orgEstado,
                'capMes'   => $capMes,
                'atTipo'   => $atTipo,
                'atMes'    => $atMes,
                'anios'    => array_column($anios, 'anio'),
            ]);
        } catch (Exception $e) {
            error_log('EstadisticasController::datos — ' . $e->getMessage());
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
