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
        $pid = Database::proyectoId();
        $resumen = $this->model->getResumen();
        $departamentos = $db->fetchAll(
            "SELECT id_departamento, nombre FROM sag_departamentos WHERE activo=1 AND id_proyecto=? ORDER BY nombre",
            [$pid]
        );
        $temas = $db->fetchAll(
            "SELECT id_tema, nombre FROM sag_temas WHERE activo=1 AND tipo IN ('capacitacion','ambos') AND id_proyecto=? ORDER BY nombre",
            [$pid]
        );
        $tecnicos = $db->fetchAll(
            "SELECT id_tecnico, nombre_completo FROM sag_tecnicos WHERE activo=1 AND id_proyecto=? ORDER BY nombre_completo",
            [$pid]
        );
        $organizaciones = $db->fetchAll(
            "SELECT id_organizacion, nombre FROM sag_organizaciones WHERE estado='activa' AND id_proyecto=? ORDER BY nombre",
            [$pid]
        );
        $pageTitle = 'Capacitaciones — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;
        $this->view('capacitaciones/index',
            compact('resumen', 'departamentos', 'temas', 'tecnicos', 'organizaciones', 'pageTitle'));
    }

    public function listar(): void
    {
        try {
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
        } catch (Exception $e) {
            error_log('CapacitacionesController::listar — ' . $e->getMessage());
            $this->json(['data' => [], 'error' => 'Error al cargar el listado.']);
        }
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
        $fecha  = (string) $this->getPost('fecha_capacitacion', '');
        $aldea  = trim((string) $this->getPost('aldea', ''));
        $lugar  = trim((string) $this->getPost('lugar_especifico', ''));

        if (!$idDep)  { $this->error('Seleccione un departamento.');      return; }
        if (!$idMun)  { $this->error('Seleccione un municipio.');         return; }
        if (!$this->model->municipioValido($idMun, $idDep)) {
            $this->error('El municipio seleccionado no pertenece al departamento.'); return;
        }
        if (!$idTema) { $this->error('Seleccione el tema.');              return; }
        if (!$this->model->temaValido($idTema)) {
            $this->error('El tema seleccionado no es válido.'); return;
        }
        if (!$idTec)  { $this->error('Seleccione el técnico.');           return; }
        if (!$this->model->tecnicoValido($idTec)) {
            $this->error('El técnico seleccionado no es válido.'); return;
        }
        if (!$fecha)  { $this->error('Ingrese la fecha de capacitación.'); return; }
        $dt = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$dt || $dt->format('Y-m-d') !== $fecha) {
            $this->error('La fecha de capacitación no es válida.'); return;
        }
        if (mb_strlen($aldea) > 200) { $this->error('La aldea no puede exceder 200 caracteres.');  return; }
        if (mb_strlen($lugar) > 300) { $this->error('El lugar no puede exceder 300 caracteres.');  return; }

        $duracion = $this->getPost('duracion_horas', '');
        if ($duracion !== '' && (!is_numeric($duracion) || (float) $duracion <= 0 || (float) $duracion > 99)) {
            $this->error('La duración debe ser un número de horas entre 0 y 99.'); return;
        }
        $data = [
            'id_departamento'    => $idDep,
            'id_municipio'       => $idMun,
            'aldea'              => $aldea,
            'lugar_especifico'   => $lugar,
            'id_tema'            => $idTema,
            'id_subtema'         => ((int) $this->getPost('id_subtema', 0)) ?: null,
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
        if (!$this->model->getDetalle($id)) { $this->error('Capacitación no encontrada.', 404); return; }
        $this->model->finalizar($id);
        $this->logAction('FINALIZAR', 'capacitaciones', "ID:{$id}");
        $this->success('Capacitación finalizada correctamente.');
    }

    public function addParticipante(): void
    {
        $idCap  = (int) $this->getPost('id_capacitacion', 0);
        $nombre = trim((string) $this->getPost('nombre', ''));
        $sexo   = (string) $this->getPost('sexo', '');
        $edad   = (string) $this->getPost('edad', '');
        $idOrg  = (int) $this->getPost('id_organizacion', 0);

        if (!$idCap)  { $this->error('ID de capacitación no válido.'); return; }
        if (!$this->model->getDetalle($idCap)) {
            $this->error('Capacitación no encontrada.', 404); return;
        }
        if ($nombre === '') { $this->error('El nombre del participante es obligatorio.'); return; }
        if (mb_strlen($nombre) > 100) { $this->error('El nombre no puede exceder 100 caracteres.'); return; }
        if ($sexo !== '' && !in_array($sexo, ['M', 'F'], true)) {
            $this->error('Sexo no válido.'); return;
        }
        if ($edad !== '' && (!ctype_digit($edad) || (int) $edad < 1 || (int) $edad > 120)) {
            $this->error('La edad debe ser un número entre 1 y 120.'); return;
        }
        $dni = preg_replace('/\D/', '', (string) $this->getPost('dni', ''));
        if ($dni !== '' && strlen($dni) !== 13) {
            $this->error('El DNI debe tener 13 dígitos.'); return;
        }
        $telefono = preg_replace('/\D/', '', (string) $this->getPost('telefono', ''));
        if ($telefono !== '' && strlen($telefono) !== 8) {
            $this->error('El teléfono debe tener 8 dígitos (formato Honduras).'); return;
        }
        if ($idOrg && !$this->model->organizacionValida($idOrg)) {
            $this->error('La organización seleccionada no es válida.'); return;
        }

        $data = [
            'id_capacitacion' => $idCap,
            'nombre'          => $nombre,
            'apellido'        => trim((string) $this->getPost('apellido', '')),
            'dni'             => $dni ?: null,
            'edad'            => ($edad !== '' ? (int) $edad : null),
            'sexo'            => $sexo ?: null,
            'id_organizacion' => $idOrg ?: null,
            'telefono'        => $telefono !== '' ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : '',
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
            if (!$this->model->eliminarParticipante($id)) {
                $this->error('Participante no encontrado.', 404); return;
            }
            $this->logAction('DEL_PARTICIPANTE', 'capacitaciones', "PID:{$id}");
            $this->success('Participante eliminado.');
        } catch (Exception $e) {
            error_log('CapacitacionesController::deleteParticipante — ' . $e->getMessage());
            $this->error('Error al eliminar participante.');
        }
    }

    public function delete(): void
    {
        $id = (int) $this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }
        $cap = $this->model->getDetalle($id);
        if (!$cap) { $this->error('Capacitación no encontrada.', 404); return; }
        try {
            // El FK de participantes es ON DELETE CASCADE: se eliminan junto con la capacitación
            $db = Database::programa();
            $db->execute("DELETE FROM sag_capacitaciones WHERE id_capacitacion=? AND id_proyecto=?", [$id, Database::proyectoId()]);
            $this->logAction('ELIMINAR', 'capacitaciones', "ID:{$id} — {$cap['tema']} {$cap['fecha_capacitacion']} ({$cap['num_participantes']} participantes)");
            $this->success('Capacitación eliminada correctamente.');
        } catch (Exception $e) {
            error_log('CapacitacionesController::delete — ' . $e->getMessage());
            $this->error('Error al eliminar la capacitación.');
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
