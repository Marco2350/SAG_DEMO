<?php
/**
 * ApiController — Endpoints JSON de apoyo para selects dinámicos
 * Todos usan Database::programa() → base de datos del programa activo
 */
class ApiController extends Controller
{
    /** POST /api/productores/buscar-dni */
    public function buscarProductorPorDni(): void
    {
        $this->requirePrograma();
        try {
            $resultado = ProductorLookup::buscar((string) $this->getPost('dni', ''));
            $this->success($resultado['message'], $resultado);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
        } catch (Throwable $e) {
            error_log('ApiController::buscarProductorPorDni - ' . $e->getMessage());
            $this->error('No fue posible consultar la identidad.');
        }
    }

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
            // Devuelve también el 'codigo' (puede ser NULL si la migración 017
            // aún no se aplicó). El frontend lo usa para cargar aldeas.
            $rows = $db->fetchAll(
                "SELECT id_municipio, nombre,
                        COALESCE(codigo, '') AS codigo
                   FROM sag_municipios
                  WHERE id_departamento = ? AND id_proyecto = ?
                  ORDER BY nombre",
                [$deptoId, Database::proyectoId()]
            );
            $this->success('OK', $rows);
        } catch (Exception $e) {
            $this->error('Error al obtener municipios.');
        }
    }

    /**
     * GET /api/aldeas?codigo_municipio=XXXX
     * Devuelve aldeas oficiales del catálogo nacional (sag_aldeas) vinculadas
     * al municipio por código. Catálogo compartido entre programas — no se
     * filtra por id_proyecto. Si la migración 017 no se aplicó, devuelve vacío.
     */
    public function aldeas(): void
    {
        $this->requirePrograma();
        $codigoMuni = trim((string) $this->getQuery('codigo_municipio', ''));
        if ($codigoMuni === '') { $this->success('OK', []); return; }
        try {
            $db = Database::main();
            if (!$db->tablaExiste('sag_aldeas')) { $this->success('OK', []); return; }
            $rows = $db->fetchAll(
                "SELECT id_aldea, codigo, nombre, latitud, longitud
                   FROM sag_aldeas
                  WHERE codigo_municipio = ? AND activo = 1
                  ORDER BY nombre",
                [$codigoMuni]
            );
            $this->success('OK', $rows);
        } catch (Exception $e) {
            error_log('ApiController::aldeas — ' . $e->getMessage());
            $this->error('Error al obtener aldeas.');
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
