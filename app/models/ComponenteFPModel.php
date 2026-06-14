<?php
/**
 * ComponenteFPModel — Componentes del programa FPROG 2026.
 *
 * Tabla: sag_fp_componentes
 * Aislamiento: id_proyecto (Model base scoped=true sella INSERT/UPDATE/DELETE).
 *
 * Cada componente es la unidad estructural del programa
 * "Fortalecimiento de Programas y Proyectos SAG 2026":
 * presupuesto, meta física, avance y responsable.
 */
class ComponenteFPModel extends Model
{
    protected string $table      = 'sag_fp_componentes';
    protected string $primaryKey = 'id_componente';

    public const CATEGORIAS = ['agricola', 'pecuario', 'transversal', 'infraestructura', 'otro'];
    public const ESTADOS    = ['planificado', 'en_ejecucion', 'completado', 'suspendido', 'cancelado'];

    /**
     * Listado completo del proyecto activo con filtros opcionales.
     */
    public function getListado(array $filtros = []): array
    {
        $where  = ['id_proyecto = ?', 'activo = 1'];
        $params = [Database::proyectoId()];

        if (!empty($filtros['estado']) && in_array($filtros['estado'], self::ESTADOS, true)) {
            $where[]  = 'estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['categoria']) && in_array($filtros['categoria'], self::CATEGORIAS, true)) {
            $where[]  = 'categoria = ?';
            $params[] = $filtros['categoria'];
        }
        if (!empty($filtros['buscar'])) {
            $where[] = '(nombre LIKE ? OR numero_romano LIKE ? OR codigo LIKE ? OR descripcion LIKE ?)';
            $like    = '%' . $filtros['buscar'] . '%';
            array_push($params, $like, $like, $like, $like);
        }

        return $this->db->fetchAll(
            "SELECT *
             FROM sag_fp_componentes
             WHERE " . implode(' AND ', $where) . "
             ORDER BY
                 -- Orden romano: primero los que tienen 'I', 'II'... y luego sin número
                 CASE numero_romano
                     WHEN 'I'    THEN 1
                     WHEN 'II'   THEN 2
                     WHEN 'III'  THEN 3
                     WHEN 'IV'   THEN 4
                     WHEN 'V'    THEN 5
                     WHEN 'VI'   THEN 6
                     WHEN 'VII'  THEN 7
                     WHEN 'VIII' THEN 8
                     WHEN 'IX'   THEN 9
                     WHEN 'X'    THEN 10
                     ELSE 99
                 END,
                 id_componente",
            $params
        );
    }

    public function getDetalle(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM sag_fp_componentes
              WHERE id_componente = ? AND id_proyecto = ? AND activo = 1",
            [$id, Database::proyectoId()]
        ) ?: false;
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
     * Devuelve totales financieros y físicos del programa.
     */
    public function getResumen(): array
    {
        $r = $this->db->fetchOne(
            "SELECT
                COUNT(*)                                AS total,
                SUM(estado = 'planificado')             AS planificados,
                SUM(estado = 'en_ejecucion')            AS en_ejecucion,
                SUM(estado = 'completado')              AS completados,
                SUM(estado = 'suspendido')              AS suspendidos,
                SUM(estado = 'cancelado')               AS cancelados,
                COALESCE(SUM(presupuesto_asignado), 0)  AS presupuesto_total,
                COALESCE(SUM(presupuesto_ejecutado), 0) AS ejecutado_total
             FROM sag_fp_componentes
             WHERE id_proyecto = ? AND activo = 1",
            [Database::proyectoId()]
        );

        $pres   = (float)($r['presupuesto_total'] ?? 0);
        $ejec   = (float)($r['ejecutado_total']   ?? 0);
        $pctEje = $pres > 0 ? round(($ejec / $pres) * 100, 2) : 0;

        return [
            'total'             => (int) ($r['total']             ?? 0),
            'planificados'      => (int) ($r['planificados']      ?? 0),
            'en_ejecucion'      => (int) ($r['en_ejecucion']      ?? 0),
            'completados'       => (int) ($r['completados']       ?? 0),
            'suspendidos'       => (int) ($r['suspendidos']       ?? 0),
            'cancelados'        => (int) ($r['cancelados']        ?? 0),
            'presupuesto_total' => $pres,
            'ejecutado_total'   => $ejec,
            'pct_ejecucion'     => $pctEje,
        ];
    }

    /**
     * Verifica si ya existe un componente con ese numero_romano en el
     * proyecto activo (excluyendo opcionalmente uno por id, para editar).
     */
    public function numeroRomanoExiste(string $numero, int $excludeId = 0): bool
    {
        $sql    = "SELECT id_componente FROM sag_fp_componentes
                    WHERE id_proyecto = ? AND numero_romano = ? AND activo = 1";
        $params = [Database::proyectoId(), $numero];
        if ($excludeId > 0) {
            $sql      .= " AND id_componente <> ?";
            $params[] = $excludeId;
        }
        return (bool) $this->db->fetchOne($sql, $params);
    }
}
