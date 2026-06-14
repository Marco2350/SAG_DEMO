<?php
/**
 * MetaModel — Metas por programa SAG.
 *
 * Tabla: sag_metas
 * Aislamiento: id_proyecto (Model base scoped=true sella INSERT/UPDATE/DELETE).
 */
class MetaModel extends Model
{
    protected string $table      = 'sag_metas';
    protected string $primaryKey = 'id_meta';

    public const PERIODOS = ['anual', 'semestral', 'trimestral', 'mensual'];
    public const ESTADOS  = ['planificada', 'en_progreso', 'cumplida', 'no_cumplida', 'reformulada'];

    /**
     * Listado completo del proyecto activo con filtros opcionales.
     * Devuelve también el nombre del componente vinculado (LEFT JOIN).
     */
    public function getListado(array $filtros = []): array
    {
        $where  = ['m.id_proyecto = ?', 'm.activo = 1'];
        $params = [Database::proyectoId()];

        if (!empty($filtros['estado']) && in_array($filtros['estado'], self::ESTADOS, true)) {
            $where[]  = 'm.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['periodo']) && in_array($filtros['periodo'], self::PERIODOS, true)) {
            $where[]  = 'm.periodo = ?';
            $params[] = $filtros['periodo'];
        }
        if (!empty($filtros['id_componente'])) {
            $where[]  = 'm.id_componente = ?';
            $params[] = (int)$filtros['id_componente'];
        }
        if (!empty($filtros['buscar'])) {
            $where[] = '(m.nombre LIKE ? OR m.codigo LIKE ? OR m.descripcion LIKE ?)';
            $like    = '%' . $filtros['buscar'] . '%';
            array_push($params, $like, $like, $like);
        }

        return $this->db->fetchAll(
            "SELECT m.*,
                    c.numero_romano AS componente_numero,
                    c.nombre        AS componente_nombre
             FROM sag_metas m
             LEFT JOIN sag_fp_componentes c
                    ON c.id_componente = m.id_componente
                   AND c.id_proyecto   = m.id_proyecto
             WHERE " . implode(' AND ', $where) . "
             ORDER BY m.fecha_inicio DESC, m.id_meta DESC",
            $params
        );
    }

    public function getDetalle(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT m.*,
                    c.numero_romano AS componente_numero,
                    c.nombre        AS componente_nombre
             FROM sag_metas m
             LEFT JOIN sag_fp_componentes c
                    ON c.id_componente = m.id_componente
                   AND c.id_proyecto   = m.id_proyecto
             WHERE m.id_meta = ? AND m.id_proyecto = ? AND m.activo = 1",
            [$id, Database::proyectoId()]
        ) ?: false;
    }

    /**
     * Verifica que un componente pertenezca al proyecto activo.
     * Defensa contra IDs enviados por cliente que apunten a otro proyecto.
     */
    public function componentePerteneceProyecto(int $idComponente): bool
    {
        return (bool) $this->db->fetchOne(
            "SELECT id_componente FROM sag_fp_componentes
              WHERE id_componente = ? AND id_proyecto = ? AND activo = 1",
            [$idComponente, Database::proyectoId()]
        );
    }

    public function guardar(array $data, int $id = 0): int
    {
        if ($id > 0) {
            $this->update($id, $data);
            return $id;
        }
        return $this->insert($data);
    }

    /**
     * Resumen para encabezado/dashboard del módulo.
     */
    public function getResumen(): array
    {
        $r = $this->db->fetchOne(
            "SELECT
                COUNT(*)                            AS total,
                SUM(estado = 'planificada')         AS planificadas,
                SUM(estado = 'en_progreso')         AS en_progreso,
                SUM(estado = 'cumplida')            AS cumplidas,
                SUM(estado = 'no_cumplida')         AS no_cumplidas,
                SUM(estado = 'reformulada')         AS reformuladas,
                ROUND(AVG(CASE WHEN valor_objetivo > 0 THEN (valor_actual / valor_objetivo) * 100 END), 2) AS avance_promedio
             FROM sag_metas
             WHERE id_proyecto = ? AND activo = 1",
            [Database::proyectoId()]
        );

        return [
            'total'           => (int)   ($r['total']           ?? 0),
            'planificadas'    => (int)   ($r['planificadas']    ?? 0),
            'en_progreso'     => (int)   ($r['en_progreso']     ?? 0),
            'cumplidas'       => (int)   ($r['cumplidas']       ?? 0),
            'no_cumplidas'    => (int)   ($r['no_cumplidas']    ?? 0),
            'reformuladas'    => (int)   ($r['reformuladas']    ?? 0),
            'avance_promedio' => (float) ($r['avance_promedio'] ?? 0),
        ];
    }
}
