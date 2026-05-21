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
                 WHERE id_departamento = ? ORDER BY nombre",
                [$deptoId]
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
                 WHERE id_tema = ? ORDER BY nombre",
                [$temaId]
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
                "SELECT id_tecnico, CONCAT(nombre, ' ', apellido) AS nombre_completo, especialidad
                 FROM sag_tecnicos WHERE activo = 1 ORDER BY nombre"
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
                 FROM sag_organizaciones WHERE estado = 'activa'
                 ORDER BY nombre"
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
                "SELECT id_departamento, nombre FROM sag_departamentos ORDER BY nombre"
            );
            $this->success('OK', $rows);
        } catch (Exception $e) {
            $this->error('Error al obtener departamentos.');
        }
    }
}
