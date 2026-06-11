<?php
/**
 * CatalogosController — Parametrización del programa
 * CRUD de las tablas parametrizables: técnicos, temas/subtemas,
 * cultivos y tipos de asistencia técnica.
 * Los endpoints de guardado/eliminación viven en MantenimientoController
 * (/mantenimiento/<catalogo>/save|delete); aquí solo se renderizan las vistas.
 */
class CatalogosController extends Controller
{
    // Debe coincidir con MantenimientoController::ROLES_CATALOGOS
    private const ROLES_CATALOGOS = ['admin', 'super_admin', 'coordinador', 'coord_nacional', 'coord_pip'];

    public function __construct()
    {
        $this->requirePrograma();
        $this->requireRole(self::ROLES_CATALOGOS);
    }

    public function tecnicos(): void
    {
        $db  = Database::programa();
        $pid = Database::proyectoId();
        $tecnicos = $db->fetchAll(
            "SELECT t.*, d.nombre AS departamento
             FROM sag_tecnicos t
             LEFT JOIN sag_departamentos d ON d.id_departamento = t.id_departamento
             WHERE t.id_proyecto = ?
             ORDER BY t.activo DESC, t.nombre_completo",
            [$pid]
        );
        $departamentos = $db->fetchAll(
            "SELECT id_departamento, nombre FROM sag_departamentos WHERE activo=1 AND id_proyecto=? ORDER BY nombre",
            [$pid]
        );
        $pageTitle = 'Técnicos — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;
        $this->view('catalogos/tecnicos', compact('tecnicos', 'departamentos', 'pageTitle'));
    }

    public function temas(): void
    {
        $db  = Database::programa();
        $pid = Database::proyectoId();
        $temas = $db->fetchAll(
            "SELECT t.*,
                    (SELECT COUNT(*) FROM sag_subtemas s WHERE s.id_tema = t.id_tema) AS num_subtemas
             FROM sag_temas t
             WHERE t.id_proyecto = ?
             ORDER BY t.activo DESC, t.nombre",
            [$pid]
        );
        $subtemas = $db->fetchAll(
            "SELECT s.*, t.nombre AS tema
             FROM sag_subtemas s
             INNER JOIN sag_temas t ON t.id_tema = s.id_tema
             WHERE s.id_proyecto = ?
             ORDER BY s.activo DESC, t.nombre, s.nombre",
            [$pid]
        );
        $pageTitle = 'Temas y Subtemas — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;
        $this->view('catalogos/temas', compact('temas', 'subtemas', 'pageTitle'));
    }

    public function cultivos(): void
    {
        $db  = Database::programa();
        $pid = Database::proyectoId();
        $cultivos = $db->fetchAll(
            "SELECT * FROM sag_cultivos WHERE id_proyecto=? ORDER BY activo DESC, tipo, nombre",
            [$pid]
        );
        $pageTitle = 'Cultivos y Rubros — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;
        $this->view('catalogos/cultivos', compact('cultivos', 'pageTitle'));
    }

    public function tiposat(): void
    {
        $db  = Database::programa();
        $pid = Database::proyectoId();
        $tiposAt = $db->fetchAll(
            "SELECT * FROM sag_tipo_at WHERE id_proyecto=? ORDER BY activo DESC, nombre",
            [$pid]
        );
        $pageTitle = 'Tipos de Asistencia — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;
        $this->view('catalogos/tiposat', compact('tiposAt', 'pageTitle'));
    }
}
