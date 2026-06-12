<?php
class AccionFortalecimientoModel extends Model
{
    protected string $table      = 'sag_acciones_fortalecimiento';
    protected string $primaryKey = 'id_accion';

    private const ETIQUETAS_TIPO = [
        'consultoria'        => 'Consultoría',
        'taller'             => 'Taller',
        'reunion'            => 'Reunión',
        'estudio'            => 'Estudio',
        'asistencia_tecnica' => 'Asistencia Técnica',
        'capacitacion'       => 'Capacitación',
        'otro'               => 'Otro',
    ];

    // ──────────────────────────────────────────────────────────────
    //  LISTADO con filtros — respuesta para DataTable
    // ──────────────────────────────────────────────────────────────

    public function getListado(array $filtros = []): array
    {
        $where  = ['a.id_proyecto = ?', 'a.activo = 1'];
        $params = [Database::proyectoId()];

        if (!empty($filtros['tipo_accion'])) {
            $where[]  = 'a.tipo_accion = ?';
            $params[] = $filtros['tipo_accion'];
        }
        if (!empty($filtros['estado'])) {
            $where[]  = 'a.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['id_departamento'])) {
            $where[]  = 'a.id_departamento = ?';
            $params[] = (int) $filtros['id_departamento'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $where[]  = 'a.fecha_inicio >= ?';
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $where[]  = 'a.fecha_inicio <= ?';
            $params[] = $filtros['fecha_hasta'];
        }

        return $this->db->fetchAll(
            "SELECT
                a.id_accion, a.tipo_accion, a.titulo, a.alcance,
                a.fecha_inicio, a.fecha_fin, a.estado,
                a.num_participantes, a.avance, a.meta,
                a.presupuesto_asignado, a.presupuesto_ejecutado,
                d.nombre AS departamento,
                t.nombre_completo AS responsable
             FROM sag_acciones_fortalecimiento a
             LEFT JOIN sag_departamentos d ON d.id_departamento = a.id_departamento
             LEFT JOIN sag_tecnicos      t ON t.id_tecnico      = a.id_responsable
             WHERE " . implode(' AND ', $where) . "
             ORDER BY a.fecha_inicio DESC, a.id_accion DESC",
            $params
        );
    }

    // ──────────────────────────────────────────────────────────────
    //  DETALLE completo para modal de edición / visualización
    // ──────────────────────────────────────────────────────────────

    public function getDetalle(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT
                a.*,
                d.nombre AS departamento,
                t.nombre_completo AS responsable
             FROM sag_acciones_fortalecimiento a
             LEFT JOIN sag_departamentos d ON d.id_departamento = a.id_departamento
             LEFT JOIN sag_tecnicos      t ON t.id_tecnico      = a.id_responsable
             WHERE a.id_accion = ? AND a.id_proyecto = ? AND a.activo = 1",
            [$id, Database::proyectoId()]
        ) ?: false;
    }

    // ──────────────────────────────────────────────────────────────
    //  GUARDAR — insert o update unificado
    // ──────────────────────────────────────────────────────────────

    public function guardar(array $data, int $id = 0): int
    {
        if ($id > 0) {
            $this->update($id, $data);
            return $id;
        }
        return $this->insert($data);
    }

    // ──────────────────────────────────────────────────────────────
    //  ESTADO — transición controlada desde el controlador
    // ──────────────────────────────────────────────────────────────

    public function cambiarEstado(int $id, string $estado): void
    {
        $this->update($id, [
            'estado'     => $estado,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    //  PARTICIPANTES
    // ──────────────────────────────────────────────────────────────

    public function getParticipantes(int $idAccion): array
    {
        return $this->db->fetchAll(
            "SELECT id_participante, nombre, apellido, cargo, institucion, sexo
             FROM sag_fprog_participantes
             WHERE id_accion = ? AND activo = 1
             ORDER BY nombre, apellido",
            [$idAccion]
        );
    }

    public function agregarParticipante(array $data): int
    {
        $this->db->execute(
            "INSERT INTO sag_fprog_participantes
             (id_accion, id_proyecto, nombre, apellido, cargo, institucion, sexo)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['id_accion'],
                Database::proyectoId(),
                $data['nombre'],
                $data['apellido']    ?? null,
                $data['cargo']       ?? null,
                $data['institucion'] ?? null,
                $data['sexo']        ?? null,
            ]
        );
        $newId = (int) $this->db->lastInsertId();
        $this->actualizarContador((int) $data['id_accion']);
        return $newId;
    }

    public function softDeleteParticipante(int $idParticipante, int $idAccion): void
    {
        $this->db->execute(
            "UPDATE sag_fprog_participantes
             SET activo = 0
             WHERE id_participante = ? AND id_accion = ? AND id_proyecto = ?",
            [$idParticipante, $idAccion, Database::proyectoId()]
        );
        $this->actualizarContador($idAccion);
    }

    private function actualizarContador(int $idAccion): void
    {
        $this->db->execute(
            "UPDATE sag_acciones_fortalecimiento
             SET num_participantes = (
                 SELECT COUNT(*) FROM sag_fprog_participantes
                 WHERE id_accion = ? AND activo = 1
             )
             WHERE id_accion = ?",
            [$idAccion, $idAccion]
        );
    }

    // ──────────────────────────────────────────────────────────────
    //  RESUMEN — mini-stats para la cabecera del módulo
    // ──────────────────────────────────────────────────────────────

    public function getResumen(): array
    {
        $r = $this->db->fetchOne(
            "SELECT
                COUNT(*)                            AS total,
                SUM(estado = 'planificado')         AS planificado,
                SUM(estado = 'en_ejecucion')        AS en_ejecucion,
                SUM(estado = 'completado')          AS completado,
                SUM(estado = 'cancelado')           AS cancelado,
                COALESCE(SUM(num_participantes), 0) AS total_participantes
             FROM sag_acciones_fortalecimiento
             WHERE id_proyecto = ? AND activo = 1",
            [Database::proyectoId()]
        );

        return [
            'total'               => (int) ($r['total']               ?? 0),
            'planificado'         => (int) ($r['planificado']         ?? 0),
            'en_ejecucion'        => (int) ($r['en_ejecucion']        ?? 0),
            'completado'          => (int) ($r['completado']          ?? 0),
            'cancelado'           => (int) ($r['cancelado']           ?? 0),
            'total_participantes' => (int) ($r['total_participantes'] ?? 0),
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //  VALIDACIONES de integridad referencial
    // ──────────────────────────────────────────────────────────────

    public function responsableValido(int $id): bool
    {
        return (bool) $this->db->fetchOne(
            "SELECT id_tecnico FROM sag_tecnicos
             WHERE id_tecnico = ? AND id_proyecto = ? AND activo = 1",
            [$id, Database::proyectoId()]
        );
    }

    public function departamentoValido(int $id): bool
    {
        return (bool) $this->db->fetchOne(
            "SELECT id_departamento FROM sag_departamentos
             WHERE id_departamento = ? AND id_proyecto = ? AND activo = 1",
            [$id, Database::proyectoId()]
        );
    }

    public static function etiquetaTipo(string $tipo): string
    {
        return self::ETIQUETAS_TIPO[$tipo] ?? $tipo;
    }
}
