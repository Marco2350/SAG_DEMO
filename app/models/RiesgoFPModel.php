<?php
/**
 * RiesgoFPModel — Riesgos del programa FPROG 2026.
 *
 * Tabla: sag_fp_riesgos
 * Aislamiento: id_proyecto (Model base scoped=true).
 */
class RiesgoFPModel extends Model
{
    protected string $table      = 'sag_fp_riesgos';
    protected string $primaryKey = 'id_riesgo';

    public const CATEGORIAS    = ['operativo','financiero','tecnico','politico','legal','ambiental','otro'];
    public const PROBABILIDADES = ['baja','media','alta'];
    public const IMPACTOS      = ['bajo','medio','alto'];
    public const ESTADOS       = ['identificado','mitigacion','materializado','superado','cerrado'];

    public function getListado(array $filtros = []): array
    {
        $where  = ['id_proyecto = ?', 'activo = 1'];
        $params = [Database::proyectoId()];

        if (!empty($filtros['categoria']) && in_array($filtros['categoria'], self::CATEGORIAS, true)) {
            $where[]  = 'categoria = ?';
            $params[] = $filtros['categoria'];
        }
        if (!empty($filtros['estado']) && in_array($filtros['estado'], self::ESTADOS, true)) {
            $where[]  = 'estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['probabilidad']) && in_array($filtros['probabilidad'], self::PROBABILIDADES, true)) {
            $where[]  = 'probabilidad = ?';
            $params[] = $filtros['probabilidad'];
        }
        if (!empty($filtros['impacto']) && in_array($filtros['impacto'], self::IMPACTOS, true)) {
            $where[]  = 'impacto = ?';
            $params[] = $filtros['impacto'];
        }
        if (!empty($filtros['buscar'])) {
            $where[] = '(descripcion LIKE ? OR medida_mitigacion LIKE ? OR responsable LIKE ?)';
            $like    = '%' . $filtros['buscar'] . '%';
            array_push($params, $like, $like, $like);
        }

        return $this->db->fetchAll(
            "SELECT * FROM sag_fp_riesgos
              WHERE " . implode(' AND ', $where) . "
              ORDER BY
                  -- Riesgos críticos primero (alta×alto), luego ordenados
                  CASE WHEN probabilidad='alta'  AND impacto='alto'  THEN 1
                       WHEN probabilidad='alta'  AND impacto='medio' THEN 2
                       WHEN probabilidad='media' AND impacto='alto'  THEN 2
                       ELSE 3 END,
                  id_riesgo DESC",
            $params
        );
    }

    public function getDetalle(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM sag_fp_riesgos
              WHERE id_riesgo = ? AND id_proyecto = ? AND activo = 1",
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
                COUNT(*)                                     AS total,
                SUM(estado = 'identificado')                 AS identificados,
                SUM(estado = 'mitigacion')                   AS en_mitigacion,
                SUM(estado = 'materializado')                AS materializados,
                SUM(estado = 'superado')                     AS superados,
                SUM(probabilidad='alta' AND impacto='alto')  AS criticos
             FROM sag_fp_riesgos
             WHERE id_proyecto = ? AND activo = 1",
            [Database::proyectoId()]
        );
        return [
            'total'           => (int) ($r['total']           ?? 0),
            'identificados'   => (int) ($r['identificados']   ?? 0),
            'en_mitigacion'   => (int) ($r['en_mitigacion']   ?? 0),
            'materializados'  => (int) ($r['materializados']  ?? 0),
            'superados'       => (int) ($r['superados']       ?? 0),
            'criticos'        => (int) ($r['criticos']        ?? 0),
        ];
    }
}
