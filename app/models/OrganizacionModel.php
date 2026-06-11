<?php
class OrganizacionModel extends Model
{
    protected string $table      = 'sag_organizaciones';
    protected string $primaryKey = 'id_organizacion';

    /** Etiquetas legibles por valor de ENUM tipo */
    public const TIPOS = [
        'cooperativa' => 'Cooperativa',
        'asociacion'  => 'Asociación',
        'grupo'       => 'Grupo',
        'empresa'     => 'Empresa',
        'caja_rural'  => 'Caja Rural',
        'otro'        => 'Otro',
    ];

    public const ESTADOS = ['activa', 'pendiente', 'inactiva'];

    public function getListado(array $filtros = []): array
    {
        $where  = ['o.id_proyecto = ?'];
        $params = [Database::proyectoId()];

        if (!empty($filtros['estado'])) {
            $where[]  = 'o.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['id_departamento'])) {
            $where[]  = 'o.id_departamento = ?';
            $params[] = $filtros['id_departamento'];
        }
        if (!empty($filtros['tipo'])) {
            $where[]  = 'o.tipo = ?';
            $params[] = $filtros['tipo'];
        }

        return $this->db->fetchAll(
            "SELECT o.id_organizacion, o.nombre, o.tipo, o.representante,
                    o.representante_dni, o.telefono, o.email, o.estado, o.fecha_registro,
                    o.aldea, o.id_departamento, o.id_municipio, o.latitud, o.longitud,
                    d.nombre AS departamento,
                    m.nombre AS municipio,
                    (SELECT COUNT(*) FROM sag_beneficiarios b
                     WHERE b.id_organizacion = o.id_organizacion AND b.estado='activo') AS num_beneficiarios
             FROM sag_organizaciones o
             INNER JOIN sag_departamentos d ON d.id_departamento = o.id_departamento
             INNER JOIN sag_municipios    m ON m.id_municipio    = o.id_municipio
             WHERE " . implode(' AND ', $where) . "
             ORDER BY o.nombre",
            $params
        );
    }

    public function getDetalle(int $id): array|false
    {
        $org = $this->db->fetchOne(
            "SELECT o.*,
                    d.nombre AS departamento,
                    m.nombre AS municipio,
                    (SELECT COUNT(*) FROM sag_beneficiarios b
                     WHERE b.id_organizacion = o.id_organizacion AND b.estado='activo') AS num_beneficiarios
             FROM sag_organizaciones o
             INNER JOIN sag_departamentos d ON d.id_departamento = o.id_departamento
             INNER JOIN sag_municipios    m ON m.id_municipio    = o.id_municipio
             WHERE o.id_organizacion = ? AND o.id_proyecto = ?",
            [$id, Database::proyectoId()]
        );
        return $org ?: false;
    }

    public function guardar(array $data, int $id = 0): int
    {
        if ($id > 0) { $this->update($id, $data); return $id; }
        return $this->insert($data);
    }

    /**
     * R-012: ¿Existe otra organización con el mismo nombre en el programa activo?
     * (case-insensitive; ignora la propia organización al editar)
     */
    public function nombreDuplicado(string $nombre, int $exceptId = 0): bool
    {
        $r = $this->db->fetchOne(
            "SELECT id_organizacion FROM sag_organizaciones
             WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?))
               AND id_proyecto = ?
               AND id_organizacion <> ?
             LIMIT 1",
            [$nombre, Database::proyectoId(), $exceptId]
        );
        return (bool) $r;
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $this->db->execute(
            "UPDATE sag_organizaciones SET estado=?, updated_at=NOW() WHERE id_organizacion=? AND id_proyecto=?",
            [$estado, $id, Database::proyectoId()]
        );
    }

    /**
     * Cuenta los registros de otras tablas que referencian a la organización
     * (FKs reales en BD: beneficiarios, participantes de capacitación y
     * asistencias técnicas). Devuelve [tabla => total] solo con totales > 0.
     */
    public function referencias(int $id): array
    {
        $checks = [
            'beneficiarios (activos e inactivos)' =>
                "SELECT COUNT(*) AS t FROM sag_beneficiarios WHERE id_organizacion = ?",
            'participantes de capacitaciones' =>
                "SELECT COUNT(*) AS t FROM sag_cap_participantes WHERE id_organizacion = ?",
            'asistencias técnicas' =>
                "SELECT COUNT(*) AS t FROM sag_asistencias_tecnicas WHERE id_organizacion = ?",
        ];
        $refs = [];
        foreach ($checks as $label => $sql) {
            $r = $this->db->fetchOne($sql, [$id]);
            if (($r['t'] ?? 0) > 0) $refs[$label] = (int) $r['t'];
        }
        return $refs;
    }

    /**
     * Borrado físico. La tabla no tiene columna `activo`, por lo que el
     * softDelete del Model base no aplica aquí. Solo debe llamarse después
     * de verificar referencias() — aun así el FK protege la integridad.
     */
    public function eliminar(int $id): bool
    {
        $n = $this->db->execute(
            "DELETE FROM sag_organizaciones WHERE id_organizacion = ? AND id_proyecto = ?",
            [$id, Database::proyectoId()]
        );
        return $n > 0;
    }

    public function getResumen(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT estado, COUNT(*) AS total FROM sag_organizaciones WHERE id_proyecto=? GROUP BY estado",
            [Database::proyectoId()]
        );
        $res = ['total' => 0, 'activa' => 0, 'inactiva' => 0, 'pendiente' => 0];
        foreach ($rows as $r) {
            $res[$r['estado']] = (int) $r['total'];
            $res['total']     += (int) $r['total'];
        }
        return $res;
    }

    /** Tipos de organización (ENUM) para el select */
    public function getTipos(): array
    {
        $out = [];
        foreach (self::TIPOS as $valor => $nombre) {
            $out[] = ['valor' => $valor, 'nombre' => $nombre];
        }
        return $out;
    }
}
