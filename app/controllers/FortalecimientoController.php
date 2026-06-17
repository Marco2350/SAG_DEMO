<?php
class FortalecimientoController extends Controller
{
    private AccionFortalecimientoModel $model;

    private const ESTADOS_VALIDOS = ['planificado', 'en_ejecucion', 'completado', 'cancelado'];
    private const TIPOS_VALIDOS   = [
        'consultoria', 'taller', 'reunion', 'estudio',
        'asistencia_tecnica', 'capacitacion', 'otro',
    ];

    // Reglas de transición: estado_actual → estados permitidos
    private const TRANSICIONES = [
        'planificado'  => ['en_ejecucion', 'cancelado'],
        'en_ejecucion' => ['completado', 'cancelado'],
        'completado'   => [],
        'cancelado'    => ['planificado'],
    ];

    public function __construct()
    {
        $this->requirePrograma();
        $this->model = new AccionFortalecimientoModel();
    }

    // ──────────────────────────────────────────────────────────────
    //  VISTA PRINCIPAL
    // ──────────────────────────────────────────────────────────────

    public function index(): void
    {
        $db  = Database::programa();
        $pid = Database::proyectoId();

        $resumen       = $this->model->getResumen();
        $departamentos = $db->fetchAll(
            "SELECT id_departamento, nombre FROM sag_departamentos
             WHERE id_proyecto = ? AND activo = 1 ORDER BY nombre",
            [$pid]
        );
        $tecnicos = $db->fetchAll(
            "SELECT id_tecnico, nombre_completo FROM sag_tecnicos
             WHERE id_proyecto = ? AND activo = 1 ORDER BY nombre_completo",
            [$pid]
        );

        $pageTitle = 'Acciones de Fortalecimiento — ' . ($_SESSION['programa']['sigla'] ?? '');
        $this->view('fortalecimiento/index', compact(
            'resumen', 'departamentos', 'tecnicos', 'pageTitle'
        ));
    }

    // ──────────────────────────────────────────────────────────────
    //  LISTADO AJAX — DataTable
    // ──────────────────────────────────────────────────────────────

    public function listar(): void
    {
        try {
            $filtros = [
                'tipo_accion'     => $this->getPost('tipo_accion'),
                'estado'          => $this->getPost('estado'),
                'id_departamento' => (int) $this->getPost('id_departamento', 0),
                'fecha_desde'     => $this->getPost('fecha_desde'),
                'fecha_hasta'     => $this->getPost('fecha_hasta'),
            ];

            $rows = $this->model->getListado($filtros);

            $data = array_map(function (array $r): array {
                $estadoBadge = match ($r['estado']) {
                    'planificado'  => '<span class="badge-pendiente">Planificado</span>',
                    'en_ejecucion' => '<span class="badge-proceso">En Ejecución</span>',
                    'completado'   => '<span class="badge-activo">Completado</span>',
                    'cancelado'    => '<span class="badge-inactivo">Cancelado</span>',
                    default        => htmlspecialchars($r['estado']),
                };

                $avancePct  = ($r['meta'] > 0)
                    ? min(100, round(($r['avance'] / $r['meta']) * 100))
                    : null;
                $avanceHtml = ($avancePct !== null)
                    ? '<div class="progress-micro"><div style="width:' . $avancePct . '%"></div></div>'
                      . '<small class="text-mute">' . $avancePct . '%</small>'
                    : '<span style="color:#bbb;">—</span>';

                return [
                    'id_accion'     => $r['id_accion'],
                    'tipo'          => htmlspecialchars(AccionFortalecimientoModel::etiquetaTipo($r['tipo_accion'])),
                    'titulo'        => htmlspecialchars($r['titulo']),
                    'alcance'       => $r['alcance'] === 'departamental'
                                       ? htmlspecialchars($r['departamento'] ?? '—')
                                       : 'Nacional',
                    'fecha_inicio'  => $r['fecha_inicio'],
                    'fecha_fin'     => $r['fecha_fin'] ?? '—',
                    'responsable'   => htmlspecialchars($r['responsable'] ?? '—'),
                    'participantes' => (int) $r['num_participantes'],
                    'avance'        => $avanceHtml,
                    'estado'        => $estadoBadge,
                    'acciones'      => '<button class="btn-sm-icon btn-outline btn-ver-accion" data-id="' . $r['id_accion'] . '" title="Ver"><i class="fas fa-eye"></i></button>'
                                     . ' <button class="btn-sm-icon btn-outline btn-editar-accion" data-id="' . $r['id_accion'] . '" title="Editar"><i class="fas fa-pen"></i></button>',
                ];
            }, $rows);

            $this->json(['data' => $data, 'resumen' => $this->model->getResumen()]);
        } catch (\Exception $e) {
            error_log('FortalecimientoController::listar — ' . $e->getMessage());
            $this->json(['data' => []]);
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  OBTENER registro completo (AJAX POST)
    // ──────────────────────────────────────────────────────────────

    public function get(): void
    {
        $id = (int) $this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }

        $accion = $this->model->getDetalle($id);
        if (!$accion) { $this->error('Acción no encontrada.', 404); return; }

        $this->success('OK', [
            'accion'        => $accion,
            'participantes' => $this->model->getParticipantes($id),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    //  GUARDAR (crear o actualizar)
    // ──────────────────────────────────────────────────────────────

    public function save(): void
    {
        $id          = (int)    $this->getPost('id_accion', 0);
        $tipoAccion  = (string) $this->getPost('tipo_accion');
        $titulo      = (string) $this->getPost('titulo');
        $alcance     = (string) $this->getPost('alcance', 'nacional');
        $idDept      = (int)    $this->getPost('id_departamento', 0);
        $fechaInicio = (string) $this->getPost('fecha_inicio');
        $fechaFin    = (string) $this->getPost('fecha_fin');
        $idResp      = (int)    $this->getPost('id_responsable', 0);
        $metaRaw     = (string) $this->getPost('meta');
        $avanceRaw   = (string) $this->getPost('avance');
        $presAsig    = (string) $this->getPost('presupuesto_asignado');
        $presEjec    = (string) $this->getPost('presupuesto_ejecutado');

        // ── Validaciones ──────────────────────────────────────────
        if (!in_array($tipoAccion, self::TIPOS_VALIDOS, true)) {
            $this->error('Tipo de acción no válido.'); return;
        }
        if ($titulo === '') {
            $this->error('El título es obligatorio.'); return;
        }
        if (mb_strlen($titulo) > 300) {
            $this->error('Título demasiado largo (máx. 300 caracteres).'); return;
        }
        if (!in_array($alcance, ['nacional', 'departamental'], true)) {
            $this->error('Alcance no válido.'); return;
        }
        if ($fechaInicio === '') {
            $this->error('La fecha de inicio es obligatoria.'); return;
        }

        $dtInicio = \DateTime::createFromFormat('Y-m-d', $fechaInicio);
        if (!$dtInicio || $dtInicio->format('Y-m-d') !== $fechaInicio) {
            $this->error('Fecha de inicio no válida.'); return;
        }

        $fechaFinNorm = null;
        if ($fechaFin !== '') {
            $dtFin = \DateTime::createFromFormat('Y-m-d', $fechaFin);
            if (!$dtFin || $dtFin->format('Y-m-d') !== $fechaFin) {
                $this->error('Fecha de fin no válida.'); return;
            }
            if ($dtFin < $dtInicio) {
                $this->error('La fecha de fin no puede ser anterior al inicio.'); return;
            }
            $fechaFinNorm = $fechaFin;
        }

        $idDeptNorm = null;
        if ($alcance === 'departamental') {
            if (!$idDept) {
                $this->error('Seleccione un departamento.'); return;
            }
            if (!$this->model->departamentoValido($idDept)) {
                $this->error('Departamento no válido.'); return;
            }
            $idDeptNorm = $idDept;
        }

        $idRespNorm = null;
        if ($idResp > 0) {
            if (!$this->model->responsableValido($idResp)) {
                $this->error('Responsable no válido.'); return;
            }
            $idRespNorm = $idResp;
        }

        $meta         = ($metaRaw  !== '' && is_numeric($metaRaw))  ? (float) $metaRaw  : null;
        $avance       = ($avanceRaw !== '' && is_numeric($avanceRaw)) ? (float) $avanceRaw : 0.0;
        $presAsigNorm = ($presAsig !== '' && is_numeric($presAsig) && (float) $presAsig >= 0) ? (float) $presAsig : null;
        $presEjecNorm = ($presEjec !== '' && is_numeric($presEjec) && (float) $presEjec >= 0) ? (float) $presEjec : 0.0;

        // ── Construir payload ─────────────────────────────────────
        $data = [
            'tipo_accion'           => $tipoAccion,
            'titulo'                => $titulo,
            'descripcion'           => $this->getPost('descripcion') ?: null,
            'objetivo'              => $this->getPost('objetivo') ?: null,
            'alcance'               => $alcance,
            'id_departamento'       => $idDeptNorm,
            'fecha_inicio'          => $fechaInicio,
            'fecha_fin'             => $fechaFinNorm,
            'id_responsable'        => $idRespNorm,
            'institucion_ejecutora' => $this->getPost('institucion_ejecutora') ?: null,
            'presupuesto_asignado'  => $presAsigNorm,
            'presupuesto_ejecutado' => $presEjecNorm,
            'indicador'             => $this->getPost('indicador') ?: null,
            'meta'                  => $meta,
            'avance'                => $avance,
            'observaciones'         => $this->getPost('observaciones') ?: null,
            'updated_at'            => date('Y-m-d H:i:s'),
        ];

        if ($id === 0) {
            $data['estado']     = 'planificado';
            $data['created_by'] = $_SESSION['user']['id_usuario'] ?? null;
        }

        try {
            $newId = $this->model->guardar($data, $id);
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'fortalecimiento', "ID:{$newId} — {$titulo}");
            $this->success(
                $id ? 'Acción actualizada.' : 'Acción registrada.',
                ['id' => $newId]
            );
        } catch (\Exception $e) {
            error_log('FortalecimientoController::save — ' . $e->getMessage());
            $this->error('Error al guardar. Intente nuevamente.');
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  SOFT DELETE
    // ──────────────────────────────────────────────────────────────

    public function delete(): void
    {
        $id = (int) $this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }

        $accion = $this->model->getDetalle($id);
        if (!$accion) { $this->error('Acción no encontrada.', 404); return; }
        if ($accion['estado'] === 'completado') {
            $this->error('No se puede eliminar una acción completada.'); return;
        }

        try {
            $this->model->softDelete($id);
            $this->logAction('ELIMINAR', 'fortalecimiento', "ID:{$id} — " . ($accion['titulo'] ?? ''));
            $this->success('Acción eliminada.');
        } catch (\Exception $e) {
            error_log('FortalecimientoController::delete — ' . $e->getMessage());
            $this->error('Error al eliminar.');
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  TRANSICIÓN DE ESTADO
    // ──────────────────────────────────────────────────────────────

    public function estado(): void
    {
        $id          = (int)    $this->getPost('id', 0);
        $nuevoEstado = (string) $this->getPost('estado');

        if (!$id) { $this->error('ID no válido.'); return; }
        if (!in_array($nuevoEstado, self::ESTADOS_VALIDOS, true)) {
            $this->error('Estado no válido.'); return;
        }

        $accion = $this->model->getDetalle($id);
        if (!$accion) { $this->error('Acción no encontrada.', 404); return; }

        $permitidos = self::TRANSICIONES[$accion['estado']] ?? [];
        if (!in_array($nuevoEstado, $permitidos, true)) {
            $this->error("No se puede pasar de '{$accion['estado']}' a '{$nuevoEstado}'."); return;
        }

        try {
            $this->model->cambiarEstado($id, $nuevoEstado);
            $this->logAction(
                'ESTADO_' . strtoupper($nuevoEstado),
                'fortalecimiento',
                "ID:{$id} — " . ($accion['titulo'] ?? '')
            );
            $etiquetas = [
                'en_ejecucion' => 'iniciada',
                'completado'   => 'completada',
                'cancelado'    => 'cancelada',
                'planificado'  => 'reactivada',
            ];
            $this->success('Acción ' . ($etiquetas[$nuevoEstado] ?? $nuevoEstado) . '.');
        } catch (\Exception $e) {
            error_log('FortalecimientoController::estado — ' . $e->getMessage());
            $this->error('Error al cambiar estado.');
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  PARTICIPANTES — agregar
    // ──────────────────────────────────────────────────────────────

    public function addParticipante(): void
    {
        $idAccion = (int)    $this->getPost('id_accion', 0);
        $nombre   = (string) $this->getPost('nombre');
        $sexo     = (string) $this->getPost('sexo');

        if (!$idAccion) { $this->error('Acción no especificada.'); return; }

        $accion = $this->model->getDetalle($idAccion);
        if (!$accion) { $this->error('Acción no encontrada.', 404); return; }
        if ($accion['estado'] === 'cancelado') {
            $this->error('No se pueden agregar participantes a una acción cancelada.'); return;
        }
        if ($nombre === '') { $this->error('El nombre es obligatorio.'); return; }
        if (mb_strlen($nombre) > 100) { $this->error('Nombre demasiado largo.'); return; }
        if ($sexo !== '' && !in_array($sexo, ['M', 'F'], true)) {
            $this->error('Sexo no válido.'); return;
        }

        try {
            $newId = $this->model->agregarParticipante([
                'id_accion'   => $idAccion,
                'nombre'      => $nombre,
                'apellido'    => $this->getPost('apellido') ?: null,
                'cargo'       => $this->getPost('cargo') ?: null,
                'institucion' => $this->getPost('institucion') ?: null,
                'sexo'        => $sexo ?: null,
            ]);
            $this->logAction('ADD_PARTICIPANTE', 'fortalecimiento', "Acción:{$idAccion} — {$nombre}");
            $this->success('Participante agregado.', ['id' => $newId]);
        } catch (\Exception $e) {
            error_log('FortalecimientoController::addParticipante — ' . $e->getMessage());
            $this->error('Error al agregar participante.');
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  PARTICIPANTES — soft delete
    // ──────────────────────────────────────────────────────────────

    public function deleteParticipante(): void
    {
        $idPart   = (int) $this->getPost('id_participante', 0);
        $idAccion = (int) $this->getPost('id_accion', 0);

        if (!$idPart || !$idAccion) { $this->error('Datos insuficientes.'); return; }

        try {
            $this->model->softDeleteParticipante($idPart, $idAccion);
            $this->logAction('DELETE_PARTICIPANTE', 'fortalecimiento', "Part:{$idPart} — Acción:{$idAccion}");
            $this->success('Participante eliminado.');
        } catch (\Exception $e) {
            error_log('FortalecimientoController::deleteParticipante — ' . $e->getMessage());
            $this->error('Error al eliminar participante.');
        }
    }
}
