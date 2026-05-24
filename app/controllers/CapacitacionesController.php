<?php
class CapacitacionesController extends Controller
{
    private CapacitacionModel $model;

    public function __construct()
    {
        $this->requirePrograma();
        $this->model = new CapacitacionModel();
    }

    public function index(): void
    {
        $db = Database::programa();
        $resumen = $this->model->getResumen();
        $departamentos = $db->fetchAll(
            "SELECT id_departamento, nombre FROM sag_departamentos WHERE activo=1 ORDER BY nombre"
        );
        $temas = $db->fetchAll(
            "SELECT id_tema, nombre FROM sag_temas WHERE activo=1 AND tipo IN ('capacitacion','ambos') ORDER BY nombre"
        );
        $tecnicos = $db->fetchAll(
            "SELECT id_tecnico, nombre_completo FROM sag_tecnicos WHERE activo=1 ORDER BY nombre_completo"
        );
        $organizaciones = $db->fetchAll(
            "SELECT id_organizacion, nombre FROM sag_organizaciones WHERE estado='activa' ORDER BY nombre"
        );
        $pageTitle = 'Capacitaciones — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;
        $this->view('capacitaciones/index',
            compact('resumen', 'departamentos', 'temas', 'tecnicos', 'organizaciones', 'pageTitle'));
    }

    public function listar(): void
    {
        $filtros = [
            'id_departamento' => (int)    $this->getPost('id_departamento', 0),
            'id_tecnico'      => (int)    $this->getPost('id_tecnico', 0),
            'id_tema'         => (int)    $this->getPost('id_tema', 0),
            'estado'          => (string) $this->getPost('estado', ''),
            'fecha_desde'     => (string) $this->getPost('fecha_desde', ''),
            'fecha_hasta'     => (string) $this->getPost('fecha_hasta', ''),
        ];
        $rows = $this->model->getListado($filtros);

        $data = array_map(function ($c) {
            $estadoBadge = $c['estado'] === 'finalizado'
                ? '<span class="badge-activo">Finalizado</span>'
                : '<span class="badge-pendiente">Borrador</span>';
            $acciones = '
                <button class="btn-outline btn-sm-icon btn-ver-cap" data-id="' . $c['id_capacitacion'] . '" title="Ver / Participantes">
                    <i class="fas fa-eye"></i></button>
                <button class="btn-outline btn-sm-icon btn-editar-cap ms-1" data-id="' . $c['id_capacitacion'] . '" title="Editar">
                    <i class="fas fa-pen"></i></button>
                <button class="btn-danger-sm ms-1 btn-eliminar-cap" data-id="' . $c['id_capacitacion'] . '" title="Eliminar">
                    <i class="fas fa-trash"></i></button>';
            return [
                'id_capacitacion' => $c['id_capacitacion'],
                'fecha'           => $c['fecha_capacitacion'],
                'tema'            => htmlspecialchars($c['tema']),
                'subtema'         => htmlspecialchars($c['subtema'] ?? '—'),
                'tecnico'         => htmlspecialchars($c['tecnico']),
                'ubicacion'       => htmlspecialchars($c['departamento'] . ' / ' . $c['municipio']),
                'lugar'           => htmlspecialchars($c['lugar_especifico'] ?? '—'),
                'participantes'   => '<span class="badge-count">' . $c['num_participantes'] . '</span>',
                'duracion'        => $c['duracion_horas'] ? $c['duracion_horas'] . ' h' : '—',
                'estado'          => $estadoBadge,
                'acciones'        => $acciones,
            ];
        }, $rows);

        $this->json(['data' => $data]);
    }

    public function get(): void
    {
        $id = (int) $this->getPost('id', 0);
        $c  = $this->model->getDetalle($id);
        if (!$c) { $this->error('Capacitación no encontrada.', 404); return; }
        $participantes = $this->model->getParticipantes($id);
        $this->success('OK', ['capacitacion' => $c, 'participantes' => $participantes]);
    }

    public function save(): void
    {
        $id     = (int) $this->getPost('id_capacitacion', 0);
        $idDep  = (int) $this->getPost('id_departamento', 0);
        $idMun  = (int) $this->getPost('id_municipio', 0);
        $idTema = (int) $this->getPost('id_tema', 0);
        $idTec  = (int) $this->getPost('id_tecnico', 0);
        $fecha  = $this->getPost('fecha_capacitacion', '');

        if (!$idDep)  { $this->error('Seleccione un departamento.');      return; }
        if (!$idMun)  { $this->error('Seleccione un municipio.');         return; }
        if (!$idTema) { $this->error('Seleccione el tema.');              return; }
        if (!$idTec)  { $this->error('Seleccione el técnico.');           return; }
        if (!$fecha)  { $this->error('Ingrese la fecha de capacitación.'); return; }

        $duracion = $this->getPost('duracion_horas', '');
        $data = [
            'id_departamento'    => $idDep,
            'id_municipio'       => $idMun,
            'aldea'              => $this->getPost('aldea', ''),
            'lugar_especifico'   => $this->getPost('lugar_especifico', ''),
            'id_tema'            => $idTema,
            'id_subtema'         => ($this->getPost('id_subtema') ?: null),
            'descripcion'        => $this->getPost('descripcion', ''),
            'fecha_capacitacion' => $fecha,
            'duracion_horas'     => ($duracion !== '' ? (float) $duracion : null),
            'id_tecnico'         => $idTec,
            'updated_at'         => date('Y-m-d H:i:s'),
        ];
        if ($id === 0) {
            $data['estado']     = 'borrador';
            $data['created_by'] = $_SESSION['user']['id_usuario'];
        }

        try {
            $newId = $this->model->guardar($data, $id);
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'capacitaciones', "ID:{$newId}");
            $this->success(
                $id ? 'Capacitación actualizada.' : 'Capacitación registrada. Ahora puede agregar participantes.',
                ['id' => $newId]
            );
        } catch (Exception $e) {
            error_log('CapacitacionesController::save — ' . $e->getMessage());
            $this->error('Error al guardar. Intente nuevamente.');
        }
    }

    public function finalizar(): void
    {
        $id = (int) $this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }
        $this->model->finalizar($id);
        $this->logAction('FINALIZAR', 'capacitaciones', "ID:{$id}");
        $this->success('Capacitación finalizada correctamente.');
    }

    public function addParticipante(): void
    {
        $idCap  = (int) $this->getPost('id_capacitacion', 0);
        $nombre = $this->getPost('nombre', '');
        if (!$idCap)  { $this->error('ID de capacitación no válido.'); return; }
        if (!$nombre) { $this->error('El nombre del participante es obligatorio.'); return; }

        $data = [
            'id_capacitacion' => $idCap,
            'nombre'          => $nombre,
            'apellido'        => $this->getPost('apellido', ''),
            'dni'             => $this->getPost('dni', '') ?: null,
            'edad'            => ($this->getPost('edad') ?: null),
            'sexo'            => $this->getPost('sexo', '') ?: null,
            'id_organizacion' => ($this->getPost('id_organizacion') ?: null),
            'telefono'        => $this->getPost('telefono', ''),
        ];
        try {
            $newId = $this->model->agregarParticipante($data);
            $this->logAction('ADD_PARTICIPANTE', 'capacitaciones', "Cap:{$idCap} — {$nombre}");
            $this->success('Participante agregado.', ['id' => $newId]);
        } catch (Exception $e) {
            error_log('CapacitacionesController::addParticipante — ' . $e->getMessage());
            $this->error('Error al agregar participante.');
        }
    }

    public function deleteParticipante(): void
    {
        $id = (int) $this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }
        try {
            $this->model->eliminarParticipante($id);
            $this->logAction('DEL_PARTICIPANTE', 'capacitaciones', "PID:{$id}");
            $this->success('Participante eliminado.');
        } catch (Exception $e) {
            $this->error('Error al eliminar participante.');
        }
    }

    public function delete(): void
    {
        $id = (int) $this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }
        try {
            $db = Database::programa();
            $db->execute("DELETE FROM sag_capacitaciones WHERE id_capacitacion=?", [$id]);
            $this->logAction('ELIMINAR', 'capacitaciones', "ID:{$id}");
            $this->success('Capacitación eliminada correctamente.');
        } catch (Exception $e) {
            $this->error('Error al eliminar.');
        }
    }

    // ════════════════════════════════════════════════════════════
    //  EVIDENCIA DOCUMENTAL (R-028)
    // ════════════════════════════════════════════════════════════

    /** POST multipart — sube el archivo de evidencia */
    public function subirEvidencia(): void
    {
        require_once ROOT_PATH . '/core/EvidenciaService.php';
        $id = (int)($_POST['id_capacitacion'] ?? 0);
        if (!$id) { $this->error('Capacitación no especificada.'); return; }
        if (empty($_FILES['archivo'])) { $this->error('No se recibió archivo.'); return; }

        $r = EvidenciaService::guardarEvidencia(
            'sag_capacitaciones', $id, $_FILES['archivo'],
            $_SESSION['user']['id_usuario'] ?? null,
            $_POST['observaciones'] ?? null
        );
        $this->logAction($r['ok'] ? 'EVIDENCIA_OK' : 'EVIDENCIA_FAIL', 'capacitaciones', "ID:{$id} — {$r['msg']}");
        if ($r['ok']) $this->success($r['msg'], $r);
        else          $this->error($r['msg']);
    }

    /** POST — cambia el estado de validación */
    public function validarEvidencia(): void
    {
        require_once ROOT_PATH . '/core/EvidenciaService.php';
        $id     = (int) $this->getPost('id_capacitacion', 0);
        $estado = $this->getPost('estado', '');
        $obs    = $this->getPost('observaciones', '');
        $r = EvidenciaService::cambiarEstado('sag_capacitaciones', $id, $estado, $obs);
        $this->logAction('VALIDAR_EVIDENCIA', 'capacitaciones', "ID:{$id} → {$estado}");
        if ($r['ok']) $this->success($r['msg']);
        else          $this->error($r['msg']);
    }

    /** GET — descarga/preview del archivo */
    public function evidencia(): void
    {
        require_once ROOT_PATH . '/core/EvidenciaService.php';
        $id = (int)($_GET['id'] ?? 0);
        EvidenciaService::servirArchivo('sag_capacitaciones', $id);
    }
}
