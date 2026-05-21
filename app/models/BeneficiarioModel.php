<?php
class BeneficiarioModel extends Model
{
    protected string $table      = 'sag_beneficiarios';
    protected string $primaryKey = 'id_beneficiario';

    public function getListado(array $filtros = []): array
    {
        $where  = ['b.estado = "activo"'];
        $params = [];

        if (!empty($filtros['id_organizacion'])) {
            $where[]  = 'b.id_organizacion = ?';
            $params[] = $filtros['id_organizacion'];
        }
        if (!empty($filtros['id_departamento'])) {
            $where[]  = 'b.id_departamento = ?';
            $params[] = $filtros['id_departamento'];
        }
        if (!empty($filtros['sexo'])) {
            $where[]  = 'b.sexo = ?';
            $params[] = $filtros['sexo'];
        }

        return $this->db->fetchAll(
            "SELECT b.*,
                    d.nombre AS departamento,
                    m.nombre AS municipio,
                    o.nombre AS organizacion,
                    CONCAT(b.nombre,' ',b.apellido) AS nombre_completo,
                    TIMESTAMPDIFF(YEAR, b.fecha_nacimiento, CURDATE()) AS edad
             FROM sag_beneficiarios b
             INNER JOIN sag_departamentos  d ON d.id_departamento = b.id_departamento
             INNER JOIN sag_municipios     m ON m.id_municipio    = b.id_municipio
             LEFT  JOIN sag_organizaciones o ON o.id_organizacion = b.id_organizacion
             WHERE " . implode(' AND ', $where) . "
             ORDER BY b.nombre, b.apellido",
            $params
        );
    }

    public function getDetalle(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT b.*,
                    d.nombre AS departamento,
                    m.nombre AS municipio,
                    o.nombre AS organizacion
             FROM sag_beneficiarios b
             INNER JOIN sag_departamentos  d ON d.id_departamento = b.id_departamento
             INNER JOIN sag_municipios     m ON m.id_municipio    = b.id_municipio
             LEFT  JOIN sag_organizaciones o ON o.id_organizacion = b.id_organizacion
             WHERE b.id_beneficiario = ?",
            [$id]
        ) ?: false;
    }

    public function existeDNI(string $dni, int $excludeId = 0): bool
    {
        $sql    = "SELECT COUNT(*) AS t FROM sag_beneficiarios WHERE dni=? AND estado='activo'";
        $params = [$dni];
        if ($excludeId > 0) { $sql .= " AND id_beneficiario != ?"; $params[] = $excludeId; }
        return ((int) ($this->db->fetchOne($sql, $params)['t'] ?? 0)) > 0;
    }

    public function guardar(array $data, int $id = 0): int
    {
        if ($id > 0) { $this->update($id, $data); return $id; }
        return $this->insert($data);
    }

    public function insertarMasivo(array $registros, int $createdBy): array
    {
        $ok = 0; $errores = [];
        $this->db->beginTransaction();
        try {
            foreach ($registros as $i => $r) {
                $fila = $i + 2;
                if (empty($r['nombre']) || empty($r['apellido'])) {
                    $errores[] = "Fila {$fila}: nombre y apellido son obligatorios."; continue;
                }
                if (!empty($r['dni']) && $this->existeDNI($r['dni'])) {
                    $errores[] = "Fila {$fila}: DNI {$r['dni']} ya está registrado."; continue;
                }
                $this->insert([
                    'id_departamento' => (int) ($r['id_departamento'] ?? 0),
                    'id_municipio'    => (int) ($r['id_municipio']    ?? 0),
                    'id_organizacion' => ($r['id_organizacion'] ?: null),
                    'nombre'          => trim($r['nombre']),
                    'apellido'        => trim($r['apellido']),
                    'dni'             => $r['dni']              ?? null,
                    'fecha_nacimiento'=> $r['fecha_nacimiento'] ?? null,
                    'sexo'            => $r['sexo']             ?? 'M',
                    'telefono'        => $r['telefono']         ?? null,
                    'aldea'           => $r['aldea']            ?? null,
                    'estado'          => 'activo',
                    'created_by'      => $createdBy,
                ]);
                $ok++;
            }
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
        return ['ok' => $ok, 'errores' => $errores];
    }

    public function getResumen(): array
    {
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) AS t, SUM(sexo='M') AS hombres, SUM(sexo='F') AS mujeres
             FROM sag_beneficiarios WHERE estado='activo'"
        );
        $orgs = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT id_organizacion) AS t
             FROM sag_beneficiarios WHERE estado='activo' AND id_organizacion IS NOT NULL"
        );
        return [
            'total'   => (int) ($total['t']       ?? 0),
            'hombres' => (int) ($total['hombres']  ?? 0),
            'mujeres' => (int) ($total['mujeres']  ?? 0),
            'orgs'    => (int) ($orgs['t']         ?? 0),
        ];
    }
}
