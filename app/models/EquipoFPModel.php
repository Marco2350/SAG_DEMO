<?php
/**
 * EquipoFPModel — Estructura del equipo técnico del FPROG 2026.
 *
 * Tabla: sag_fp_equipo
 * Aislamiento: id_proyecto.
 */
class EquipoFPModel extends Model
{
    protected string $table      = 'sag_fp_equipo';
    protected string $primaryKey = 'id_equipo';

    public const ESTADOS = ['vacante','en_proceso','contratado','baja'];

    public function getListado(array $filtros = []): array
    {
        $where  = ['id_proyecto = ?', 'activo = 1'];
        $params = [Database::proyectoId()];

        if (!empty($filtros['estado']) && in_array($filtros['estado'], self::ESTADOS, true)) {
            $where[]  = 'estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['buscar'])) {
            $where[] = '(rol LIKE ? OR descripcion LIKE ? OR ambito LIKE ? OR responsable LIKE ?)';
            $like    = '%' . $filtros['buscar'] . '%';
            array_push($params, $like, $like, $like, $like);
        }

        return $this->db->fetchAll(
            "SELECT * FROM sag_fp_equipo
              WHERE " . implode(' AND ', $where) . "
              ORDER BY estado, rol, id_equipo",
            $params
        );
    }

    public function getDetalle(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM sag_fp_equipo
              WHERE id_equipo = ? AND id_proyecto = ? AND activo = 1",
            [$id, Database::proyectoId()]
        ) ?: false;
    }

    public function guardar(array $data, int $id = 0): int
    {
        if ($id > 0) { $this->update($id, $data); return $id; }
        return $this->insert($data);
    }

    public function getResumen(): array
    {
        $r = $this->db->fetchOne(
            "SELECT
                COUNT(*)                                  AS total_roles,
                COALESCE(SUM(cantidad), 0)                AS total_plazas,
                SUM(estado = 'vacante')                   AS vacantes,
                SUM(estado = 'en_proceso')                AS en_proceso,
                SUM(estado = 'contratado')                AS contratados,
                COALESCE(SUM(presupuesto_asignado), 0)    AS presupuesto_total
             FROM sag_fp_equipo
             WHERE id_proyecto = ? AND activo = 1",
            [Database::proyectoId()]
        );
        return [
            'total_roles'       => (int)   ($r['total_roles']       ?? 0),
            'total_plazas'      => (int)   ($r['total_plazas']      ?? 0),
            'vacantes'          => (int)   ($r['vacantes']          ?? 0),
            'en_proceso'        => (int)   ($r['en_proceso']        ?? 0),
            'contratados'       => (int)   ($r['contratados']       ?? 0),
            'presupuesto_total' => (float) ($r['presupuesto_total'] ?? 0),
        ];
    }
}
