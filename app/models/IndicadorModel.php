<?php
/**
 * IndicadorModel — Indicadores por programa SAG.
 *
 * Tabla: sag_indicadores
 * Aislamiento: id_proyecto (Model base scoped=true).
 * Puede vincularse opcionalmente a una sag_metas.id_meta.
 */
class IndicadorModel extends Model
{
    protected string $table      = 'sag_indicadores';
    protected string $primaryKey = 'id_indicador';

    public const TIPOS    = ['producto', 'resultado', 'impacto', 'proceso', 'otro'];
    public const ESTADOS  = ['activo', 'suspendido', 'reformulado', 'retirado'];
    public const FRECUENCIAS = ['diaria', 'semanal', 'mensual', 'trimestral', 'semestral', 'anual'];

    /**
     * Listado completo del proyecto activo con filtros opcionales.
     */
    public function getListado(array $filtros = []): array
    {
        $where  = ['i.id_proyecto = ?', 'i.activo = 1'];
        $params = [Database::proyectoId()];

        if (!empty($filtros['tipo']) && in_array($filtros['tipo'], self::TIPOS, true)) {
            $where[]  = 'i.tipo = ?';
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['estado']) && in_array($filtros['estado'], self::ESTADOS, true)) {
            $where[]  = 'i.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['id_meta'])) {
            $where[]  = 'i.id_meta = ?';
            $params[] = (int)$filtros['id_meta'];
        }
        if (!empty($filtros['id_componente'])) {
            $where[]  = 'i.id_componente = ?';
            $params[] = (int)$filtros['id_componente'];
        }
        if (!empty($filtros['buscar'])) {
            $where[] = '(i.nombre LIKE ? OR i.codigo LIKE ? OR i.descripcion LIKE ?)';
            $like    = '%' . $filtros['buscar'] . '%';
            array_push($params, $like, $like, $like);
        }

        return $this->db->fetchAll(
            "SELECT i.*,
                    m.nombre AS meta_nombre, m.codigo AS meta_codigo,
                    c.numero_romano AS componente_numero, c.nombre AS componente_nombre
             FROM sag_indicadores i
             LEFT JOIN sag_metas m
                    ON m.id_meta = i.id_meta
                   AND m.id_proyecto = i.id_proyecto
             LEFT JOIN sag_fp_componentes c
                    ON c.id_componente = i.id_componente
                   AND c.id_proyecto   = i.id_proyecto
             WHERE " . implode(' AND ', $where) . "
             ORDER BY i.id_indicador DESC",
            $params
        );
    }

    public function getDetalle(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT i.*,
                    m.nombre AS meta_nombre,
                    c.numero_romano AS componente_numero, c.nombre AS componente_nombre
             FROM sag_indicadores i
             LEFT JOIN sag_metas m
                    ON m.id_meta = i.id_meta
                   AND m.id_proyecto = i.id_proyecto
             LEFT JOIN sag_fp_componentes c
                    ON c.id_componente = i.id_componente
                   AND c.id_proyecto   = i.id_proyecto
             WHERE i.id_indicador = ? AND i.id_proyecto = ? AND i.activo = 1",
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
     * Verifica que una meta pertenezca al proyecto activo.
     * Defensa contra IDs enviados por cliente que apunten a otro proyecto.
     */
    public function metaPerteneceProyecto(int $idMeta): bool
    {
        return (bool) $this->db->fetchOne(
            "SELECT id_meta FROM sag_metas
              WHERE id_meta = ? AND id_proyecto = ? AND activo = 1",
            [$idMeta, Database::proyectoId()]
        );
    }

    public function getResumen(): array
    {
        $r = $this->db->fetchOne(
            "SELECT
                COUNT(*)                       AS total,
                SUM(tipo = 'producto')         AS producto,
                SUM(tipo = 'resultado')        AS resultado,
                SUM(tipo = 'impacto')          AS impacto,
                SUM(tipo = 'proceso')          AS proceso,
                SUM(estado = 'activo')         AS activos,
                SUM(estado = 'suspendido')     AS suspendidos
             FROM sag_indicadores
             WHERE id_proyecto = ? AND activo = 1",
            [Database::proyectoId()]
        );

        return [
            'total'       => (int) ($r['total']       ?? 0),
            'producto'    => (int) ($r['producto']    ?? 0),
            'resultado'   => (int) ($r['resultado']   ?? 0),
            'impacto'     => (int) ($r['impacto']     ?? 0),
            'proceso'     => (int) ($r['proceso']     ?? 0),
            'activos'     => (int) ($r['activos']     ?? 0),
            'suspendidos' => (int) ($r['suspendidos'] ?? 0),
        ];
    }
}
