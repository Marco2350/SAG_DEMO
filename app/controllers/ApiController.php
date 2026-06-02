<?php
/**
 * ApiController — Endpoints JSON de apoyo para selects dinámicos
 * Todos usan Database::programa() → base de datos del programa activo
 */
class ApiController extends Controller
{
    /** GET /api/municipios?depto_id=N */
    public function municipios(): void
    {
        $this->requirePrograma();
        $deptoId = (int) $this->getQuery('depto_id');
        if (!$deptoId) {
            $this->success('OK', []);
            return;
        }
        try {
            $db = Database::programa();
            $rows = $db->fetchAll(
                "SELECT id_municipio, nombre FROM sag_municipios
                 WHERE id_departamento = ? AND id_proyecto = ? ORDER BY nombre",
                [$deptoId, Database::proyectoId()]
            );
            $this->success('OK', $rows);
        } catch (Exception $e) {
            $this->error('Error al obtener municipios.');
        }
    }

    /** GET /api/subtemas?tema_id=N */
    public function subtemas(): void
    {
        $this->requirePrograma();
        $temaId = (int) $this->getQuery('tema_id');
        if (!$temaId) {
            $this->success('OK', []);
            return;
        }
        try {
            $db = Database::programa();
            $rows = $db->fetchAll(
                "SELECT id_subtema, nombre FROM sag_subtemas
                 WHERE id_tema = ? AND id_proyecto = ? ORDER BY nombre",
                [$temaId, Database::proyectoId()]
            );
            $this->success('OK', $rows);
        } catch (Exception $e) {
            $this->error('Error al obtener subtemas.');
        }
    }

    /** GET /api/tecnicos */
    public function tecnicos(): void
    {
        $this->requirePrograma();
        try {
            $db = Database::programa();
            $rows = $db->fetchAll(
                "SELECT id_tecnico, nombre_completo, especialidad
                 FROM sag_tecnicos WHERE activo = 1 AND id_proyecto = ? ORDER BY nombre_completo",
                [Database::proyectoId()]
            );
            $this->success('OK', $rows);
        } catch (Exception $e) {
            $this->error('Error al obtener técnicos.');
        }
    }

    /** GET /api/organizaciones — lista compacta para selects */
    public function organizaciones(): void
    {
        $this->requirePrograma();
        try {
            $db = Database::programa();
            $rows = $db->fetchAll(
                "SELECT id_organizacion, nombre, representante
                 FROM sag_organizaciones WHERE estado = 'activa' AND id_proyecto = ?
                 ORDER BY nombre",
                [Database::proyectoId()]
            );
            $this->success('OK', $rows);
        } catch (Exception $e) {
            $this->error('Error al obtener organizaciones.');
        }
    }

    /** GET /api/departamentos */
    public function departamentos(): void
    {
        $this->requirePrograma();
        try {
            $db = Database::programa();
            $rows = $db->fetchAll(
                "SELECT id_departamento, nombre FROM sag_departamentos WHERE id_proyecto = ? ORDER BY nombre",
                [Database::proyectoId()]
            );
            $this->success('OK', $rows);
        } catch (Exception $e) {
            $this->error('Error al obtener departamentos.');
        }
    }
}
