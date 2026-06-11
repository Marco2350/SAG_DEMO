<?php
class AsistenciaTecnicaModel extends Model
{
    protected string $table      = 'sag_asistencias_tecnicas';
    protected string $primaryKey = 'id_at';

    public function getListado(array $filtros = []): array
    {
        $where = ['a.id_proyecto = ?']; $params = [Database::proyectoId()];

        if (!empty($filtros['id_departamento'])) { $where[] = 'a.id_departamento=?'; $params[] = $filtros['id_departamento']; }
        if (!empty($filtros['id_tecnico']))       { $where[] = 'a.id_tecnico=?';      $params[] = $filtros['id_tecnico']; }
        if (!empty($filtros['id_tema']))          { $where[] = 'a.id_tema=?';         $params[] = $filtros['id_tema']; }
        if (!empty($filtros['id_tipo_at']))       { $where[] = 'a.id_tipo_at=?';      $params[] = $filtros['id_tipo_at']; }
        if (!empty($filtros['estado']))           { $where[] = 'a.estado=?';          $params[] = $filtros['estado']; }
        if (!empty($filtros['fecha_desde']))      { $where[] = 'a.fecha_visita>=?';   $params[] = $filtros['fecha_desde']; }
        if (!empty($filtros['fecha_hasta']))      { $where[] = 'a.fecha_visita<=?';   $params[] = $filtros['fecha_hasta']; }

        return $this->db->fetchAll(
            "SELECT a.id_at, a.fecha_visita, a.productor_nombre, a.productor_apellido,
                    a.productor_sexo, a.area_productiva, a.prox_visita, a.estado,
                    d.nombre AS departamento, m.nombre AS municipio,
                    t.nombre AS tema, s.nombre AS subtema,
                    tc.nombre_completo AS tecnico,
                    ta.nombre AS tipo_at, ta.icono AS tipo_icono,
                    cu.nombre AS cultivo
             FROM sag_asistencias_tecnicas a
             INNER JOIN sag_departamentos d  ON d.id_departamento = a.id_departamento
             INNER JOIN sag_municipios    m  ON m.id_municipio    = a.id_municipio
             INNER JOIN sag_temas         t  ON t.id_tema         = a.id_tema
             LEFT  JOIN sag_subtemas      s  ON s.id_subtema      = a.id_subtema
             INNER JOIN sag_tecnicos      tc ON tc.id_tecnico     = a.id_tecnico
             INNER JOIN sag_tipo_at       ta ON ta.id_tipo_at     = a.id_tipo_at
             LEFT  JOIN sag_cultivos      cu ON cu.id_cultivo     = a.id_cultivo
             WHERE " . implode(' AND ', $where) . "
             ORDER BY a.fecha_visita DESC, a.id_at DESC",
            $params
        );
    }

    public function getDetalle(int $id): array|false
    {
        $at = $this->db->fetchOne(
            "SELECT a.*,
                    d.nombre AS departamento, m.nombre AS municipio,
                    t.nombre AS tema, s.nombre AS subtema,
                    tc.nombre_completo AS tecnico,
                    ta.nombre AS tipo_at, ta.icono AS tipo_icono,
                    cu.nombre AS cultivo,
                    o.nombre  AS organizacion
             FROM sag_asistencias_tecnicas a
             INNER JOIN sag_departamentos d  ON d.id_departamento = a.id_departamento
             INNER JOIN sag_municipios    m  ON m.id_municipio    = a.id_municipio
             INNER JOIN sag_temas         t  ON t.id_tema         = a.id_tema
             LEFT  JOIN sag_subtemas      s  ON s.id_subtema      = a.id_subtema
             INNER JOIN sag_tecnicos      tc ON tc.id_tecnico     = a.id_tecnico
             INNER JOIN sag_tipo_at       ta ON ta.id_tipo_at     = a.id_tipo_at
             LEFT  JOIN sag_cultivos      cu ON cu.id_cultivo     = a.id_cultivo
             LEFT  JOIN sag_organizaciones o ON o.id_organizacion = a.id_organizacion
             WHERE a.id_at = ? AND a.id_proyecto = ?",
            [$id, Database::proyectoId()]
        );
        if (!$at) return false;

        $at['resultados'] = $this->db->fetchAll(
            "SELECT id_resultado, resultado FROM sag_at_resultados WHERE id_at=? ORDER BY id_resultado",
            [$id]
        );
        return $at;
    }

    public function guardar(array $data, int $id = 0): int
    {
        if ($id > 0) { $this->update($id, $data); return $id; }
        return $this->insert($data);
    }

    /** Valida que el tipo de asistencia exista, esté activo y pertenezca al programa activo. */
    public function tipoATValido(int $idTipoAT): bool
    {
        $r = $this->db->fetchOne(
            "SELECT id_tipo_at FROM sag_tipo_at WHERE id_tipo_at = ? AND id_proyecto = ? AND activo = 1",
            [$idTipoAT, Database::proyectoId()]
        );
        return (bool) $r;
    }

    /** Valida que el tema exista, esté activo y pertenezca al programa activo. */
    public function temaValido(int $idTema): bool
    {
        $r = $this->db->fetchOne(
            "SELECT id_tema FROM sag_temas WHERE id_tema = ? AND id_proyecto = ? AND activo = 1",
            [$idTema, Database::proyectoId()]
        );
        return (bool) $r;
    }

    /** Valida que el técnico exista, esté activo y pertenezca al programa activo. */
    public function tecnicoValido(int $idTecnico): bool
    {
        $r = $this->db->fetchOne(
            "SELECT id_tecnico FROM sag_tecnicos WHERE id_tecnico = ? AND id_proyecto = ? AND activo = 1",
            [$idTecnico, Database::proyectoId()]
        );
        return (bool) $r;
    }

    public function guardarResultados(int $idAt, array $resultados): void
    {
        $this->db->execute("DELETE FROM sag_at_resultados WHERE id_at=?", [$idAt]);
        foreach ($resultados as $r) {
            $r = trim($r);
            if ($r !== '') {
                $this->db->execute(
                    "INSERT INTO sag_at_resultados (id_proyecto, id_at, resultado) VALUES (?,?,?)",
                    [Database::proyectoId(), $idAt, $r]
                );
            }
        }
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
                    SUM(productor_sexo='M')  AS hombres,
                    SUM(productor_sexo='F')  AS mujeres
             FROM sag_asistencias_tecnicas WHERE id_proyecto=?",
            [Database::proyectoId()]
        );
        return [
            'total'       => (int) ($r['total']       ?? 0),
            'finalizadas' => (int) ($r['finalizadas'] ?? 0),
            'borrador'    => (int) ($r['borrador']    ?? 0),
            'hombres'     => (int) ($r['hombres']     ?? 0),
            'mujeres'     => (int) ($r['mujeres']     ?? 0),
        ];
    }

    public function getTiposAT(): array
    {
        return $this->db->fetchAll(
            "SELECT id_tipo_at, nombre, icono FROM sag_tipo_at WHERE activo=1 AND id_proyecto=? ORDER BY nombre",
            [Database::proyectoId()]
        );
    }

    public function getCultivos(): array
    {
        return $this->db->fetchAll(
            "SELECT id_cultivo, nombre, tipo FROM sag_cultivos WHERE activo=1 AND id_proyecto=? ORDER BY tipo, nombre",
            [Database::proyectoId()]
        );
    }
}
