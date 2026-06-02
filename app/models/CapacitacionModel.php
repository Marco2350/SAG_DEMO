<?php
class CapacitacionModel extends Model
{
    protected string $table      = 'sag_capacitaciones';
    protected string $primaryKey = 'id_capacitacion';

    public function getListado(array $filtros = []): array
    {
        $where = ['c.id_proyecto = ?']; $params = [Database::proyectoId()];

        if (!empty($filtros['id_departamento'])) { $where[] = 'c.id_departamento=?'; $params[] = $filtros['id_departamento']; }
        if (!empty($filtros['id_tecnico']))       { $where[] = 'c.id_tecnico=?';      $params[] = $filtros['id_tecnico']; }
        if (!empty($filtros['id_tema']))          { $where[] = 'c.id_tema=?';         $params[] = $filtros['id_tema']; }
        if (!empty($filtros['estado']))           { $where[] = 'c.estado=?';          $params[] = $filtros['estado']; }
        if (!empty($filtros['fecha_desde']))      { $where[] = 'c.fecha_capacitacion>=?'; $params[] = $filtros['fecha_desde']; }
        if (!empty($filtros['fecha_hasta']))      { $where[] = 'c.fecha_capacitacion<=?'; $params[] = $filtros['fecha_hasta']; }

        return $this->db->fetchAll(
            "SELECT c.id_capacitacion, c.lugar_especifico, c.aldea,
                    c.fecha_capacitacion, c.duracion_horas, c.num_participantes, c.estado,
                    d.nombre AS departamento, m.nombre AS municipio,
                    t.nombre AS tema, s.nombre AS subtema,
                    tc.nombre_completo AS tecnico
             FROM sag_capacitaciones c
             INNER JOIN sag_departamentos d  ON d.id_departamento = c.id_departamento
             INNER JOIN sag_municipios    m  ON m.id_municipio    = c.id_municipio
             INNER JOIN sag_temas         t  ON t.id_tema         = c.id_tema
             LEFT  JOIN sag_subtemas      s  ON s.id_subtema      = c.id_subtema
             INNER JOIN sag_tecnicos      tc ON tc.id_tecnico     = c.id_tecnico
             WHERE " . implode(' AND ', $where) . "
             ORDER BY c.fecha_capacitacion DESC, c.id_capacitacion DESC",
            $params
        );
    }

    public function getDetalle(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT c.*,
                    d.nombre AS departamento, m.nombre AS municipio,
                    t.nombre AS tema, s.nombre AS subtema,
                    tc.nombre_completo AS tecnico
             FROM sag_capacitaciones c
             INNER JOIN sag_departamentos d  ON d.id_departamento = c.id_departamento
             INNER JOIN sag_municipios    m  ON m.id_municipio    = c.id_municipio
             INNER JOIN sag_temas         t  ON t.id_tema         = c.id_tema
             LEFT  JOIN sag_subtemas      s  ON s.id_subtema      = c.id_subtema
             INNER JOIN sag_tecnicos      tc ON tc.id_tecnico     = c.id_tecnico
             WHERE c.id_capacitacion = ? AND c.id_proyecto = ?",
            [$id, Database::proyectoId()]
        ) ?: false;
    }

    public function guardar(array $data, int $id = 0): int
    {
        if ($id > 0) { $this->update($id, $data); return $id; }
        return $this->insert($data);
    }

    public function getParticipantes(int $idCap): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, o.nombre AS organizacion
             FROM sag_cap_participantes p
             LEFT JOIN sag_organizaciones o ON o.id_organizacion = p.id_organizacion
             WHERE p.id_capacitacion = ?
             ORDER BY p.nombre, p.apellido",
            [$idCap]
        );
    }

    public function agregarParticipante(array $data): int
    {
        $this->db->execute(
            "INSERT INTO sag_cap_participantes
             (id_proyecto,id_capacitacion,nombre,apellido,dni,edad,sexo,id_organizacion,telefono)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [
                Database::proyectoId(),
                $data['id_capacitacion'], $data['nombre'],
                $data['apellido'] ?? null, $data['dni'] ?? null,
                $data['edad'] ?? null, $data['sexo'] ?? null,
                $data['id_organizacion'] ?: null, $data['telefono'] ?? null,
            ]
        );
        $newId = (int) $this->db->lastInsertId();
        $this->actualizarContador((int) $data['id_capacitacion']);
        return $newId;
    }

    public function eliminarParticipante(int $idPart): void
    {
        $p = $this->db->fetchOne(
            "SELECT id_capacitacion FROM sag_cap_participantes WHERE id_participante=?", [$idPart]
        );
        $this->db->execute("DELETE FROM sag_cap_participantes WHERE id_participante=?", [$idPart]);
        if ($p) $this->actualizarContador((int) $p['id_capacitacion']);
    }

    private function actualizarContador(int $idCap): void
    {
        $this->db->execute(
            "UPDATE sag_capacitaciones SET num_participantes=(
                SELECT COUNT(*) FROM sag_cap_participantes WHERE id_capacitacion=?)
             WHERE id_capacitacion=?",
            [$idCap, $idCap]
        );
    }

    public function finalizar(int $id): void
    {
        $this->update($id, ['estado' => 'finalizado', 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function getResumen(): array
    {
        $r = $this->db->fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(estado='finalizado') AS finalizadas,
                    SUM(estado='borrador')   AS borrador,
                    COALESCE(SUM(num_participantes),0) AS participantes
             FROM sag_capacitaciones WHERE id_proyecto=?",
            [Database::proyectoId()]
        );
        return [
            'total'         => (int) ($r['total']         ?? 0),
            'finalizadas'   => (int) ($r['finalizadas']   ?? 0),
            'borrador'      => (int) ($r['borrador']      ?? 0),
            'participantes' => (int) ($r['participantes'] ?? 0),
        ];
    }
}
