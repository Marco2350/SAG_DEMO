<?php
class OrganizacionModel extends Model
{
    protected string $table      = 'sag_organizaciones';
    protected string $primaryKey = 'id_organizacion';

    public function getListado(array $filtros = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['estado'])) {
            $where[]  = 'o.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['id_departamento'])) {
            $where[]  = 'o.id_departamento = ?';
            $params[] = $filtros['id_departamento'];
        }

        return $this->db->fetchAll(
            "SELECT o.id_organizacion, o.nombre, o.tipo, o.representante,
                    o.telefono, o.email, o.estado, o.fecha_registro,
                    o.aldea, o.id_departamento, o.id_municipio,
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
             WHERE o.id_organizacion = ?",
            [$id]
        );
        return $org ?: false;
    }

    public function guardar(array $data, int $id = 0): int
    {
        if ($id > 0) { $this->update($id, $data); return $id; }
        return $this->insert($data);
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $this->db->execute(
            "UPDATE sag_organizaciones SET estado=?, updated_at=NOW() WHERE id_organizacion=?",
            [$estado, $id]
        );
    }

    public function getResumen(): array
    {
        $rows = $this->db->fetchAll(
            "SELECT estado, COUNT(*) AS total FROM sag_organizaciones GROUP BY estado"
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
        return [
            ['valor' => 'cooperativa', 'nombre' => 'Cooperativa'],
            ['valor' => 'asociacion',  'nombre' => 'Asociación'],
            ['valor' => 'grupo',       'nombre' => 'Grupo'],
            ['valor' => 'empresa',     'nombre' => 'Empresa'],
            ['valor' => 'otro',        'nombre' => 'Otro'],
        ];
    }
}
