<?php
/**
 * CronogramaFPModel — Cronograma de actividades FPROG 2026 (Jun–Dic).
 *
 * Tabla: sag_fp_cronograma
 * Aislamiento: id_proyecto.
 * Vínculo opcional con sag_fp_componentes (id_componente).
 */
class CronogramaFPModel extends Model
{
    protected string $table      = 'sag_fp_cronograma';
    protected string $primaryKey = 'id_actividad';

    public const ESTADOS = ['pendiente','en_curso','completada','retrasada','cancelada'];
    public const MESES   = ['mes_jun','mes_jul','mes_ago','mes_sep','mes_oct','mes_nov','mes_dic'];

    public function getListado(array $filtros = []): array
    {
        $where  = ['a.id_proyecto = ?', 'a.activo = 1'];
        $params = [Database::proyectoId()];

        if (!empty($filtros['estado']) && in_array($filtros['estado'], self::ESTADOS, true)) {
            $where[]  = 'a.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['id_componente'])) {
            $where[]  = 'a.id_componente = ?';
            $params[] = (int)$filtros['id_componente'];
        }
        if (!empty($filtros['buscar'])) {
            $where[] = '(a.actividad LIKE ? OR a.descripcion LIKE ? OR a.responsable LIKE ?)';
            $like    = '%' . $filtros['buscar'] . '%';
            array_push($params, $like, $like, $like);
        }

        return $this->db->fetchAll(
            "SELECT a.*,
                    c.numero_romano AS componente_numero,
                    c.nombre        AS componente_nombre
             FROM sag_fp_cronograma a
             LEFT JOIN sag_fp_componentes c
                    ON c.id_componente = a.id_componente
                   AND c.id_proyecto   = a.id_proyecto
             WHERE " . implode(' AND ', $where) . "
             ORDER BY a.numero_orden, a.id_actividad",
            $params
        );
    }

    public function getDetalle(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT a.*,
                    c.numero_romano AS componente_numero,
                    c.nombre        AS componente_nombre
             FROM sag_fp_cronograma a
             LEFT JOIN sag_fp_componentes c
                    ON c.id_componente = a.id_componente
                   AND c.id_proyecto   = a.id_proyecto
             WHERE a.id_actividad = ? AND a.id_proyecto = ? AND a.activo = 1",
            [$id, Database::proyectoId()]
        ) ?: false;
    }

    public function guardar(array $data, int $id = 0): int
    {
        if ($id > 0) { $this->update($id, $data); return $id; }
        return $this->insert($data);
    }

    /**
     * Verifica que un componente pertenezca al proyecto activo
     * (defensa contra IDs enviados por cliente).
     */
    public function componentePerteneceProyecto(int $idComponente): bool
    {
        return (bool) $this->db->fetchOne(
            "SELECT id_componente FROM sag_fp_componentes
              WHERE id_componente = ? AND id_proyecto = ? AND activo = 1",
            [$idComponente, Database::proyectoId()]
        );
    }

    public function getResumen(): array
    {
        $r = $this->db->fetchOne(
            "SELECT
                COUNT(*)                                AS total,
                SUM(estado='pendiente')                 AS pendientes,
                SUM(estado='en_curso')                  AS en_curso,
                SUM(estado='completada')                AS completadas,
                SUM(estado='retrasada')                 AS retrasadas,
                SUM(estado='cancelada')                 AS canceladas
             FROM sag_fp_cronograma
             WHERE id_proyecto = ? AND activo = 1",
            [Database::proyectoId()]
        );
        return [
            'total'       => (int) ($r['total']       ?? 0),
            'pendientes'  => (int) ($r['pendientes']  ?? 0),
            'en_curso'    => (int) ($r['en_curso']    ?? 0),
            'completadas' => (int) ($r['completadas'] ?? 0),
            'retrasadas'  => (int) ($r['retrasadas']  ?? 0),
            'canceladas'  => (int) ($r['canceladas']  ?? 0),
        ];
    }
}
