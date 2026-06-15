<?php
class AsistenciaTecnicaController extends Controller
{
    private AsistenciaTecnicaModel $model;

    public function __construct()
    {
        $this->requirePrograma();
        $this->model = new AsistenciaTecnicaModel();
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
            "SELECT id_tema, nombre FROM sag_temas WHERE activo=1 AND tipo IN ('at','ambos') AND id_proyecto=? ORDER BY nombre",
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
        $tiposAT  = $this->model->getTiposAT();
        $cultivos = $this->model->getCultivos();

        $pageTitle = 'Asistencia Técnica — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;
        $this->view('asistencia_tecnica/index',
            compact('resumen', 'departamentos', 'temas', 'tecnicos',
                    'organizaciones', 'tiposAT', 'cultivos', 'pageTitle'));
    }

    public function listar(): void
    {
        try {
        $filtros = [
            'id_departamento' => (int)    $this->getPost('id_departamento', 0),
            'id_tecnico'      => (int)    $this->getPost('id_tecnico', 0),
            'id_tema'         => (int)    $this->getPost('id_tema', 0),
            'id_tipo_at'      => (int)    $this->getPost('id_tipo_at', 0),
            'estado'          => (string) $this->getPost('estado', ''),
            'fecha_desde'     => (string) $this->getPost('fecha_desde', ''),
            'fecha_hasta'     => (string) $this->getPost('fecha_hasta', ''),
        ];
        $rows = $this->model->getListado($filtros);

        $data = array_map(function ($a) {
            $estadoBadge = $a['estado'] === 'finalizado'
                ? '<span class="badge-activo">Finalizado</span>'
                : '<span class="badge-pendiente">Borrador</span>';
            $sexoIcon = $a['productor_sexo'] === 'M'
                ? '<i class="fas fa-mars" style="color:#2563eb;" title="Masculino"></i>'
                : ($a['productor_sexo'] === 'F'
                    ? '<i class="fas fa-venus" style="color:#db2777;" title="Femenino"></i>' : '');
            $productor = htmlspecialchars(trim($a['productor_nombre'] . ' ' . ($a['productor_apellido'] ?? '')));
            $acciones = '
                <button class="btn-outline btn-sm-icon btn-ver-at" data-id="' . $a['id_at'] . '" title="Ver">
                    <i class="fas fa-eye"></i></button>
                <button class="btn-outline btn-sm-icon btn-editar-at ms-1" data-id="' . $a['id_at'] . '" title="Editar">
                    <i class="fas fa-pen"></i></button>
                <button class="btn-danger-sm ms-1 btn-eliminar-at" data-id="' . $a['id_at'] . '" title="Eliminar">
                    <i class="fas fa-trash"></i></button>';
            return [
                'id_at'       => $a['id_at'],
                'fecha'       => $a['fecha_visita'],
                'tipo_at'     => '<i class="fas ' . htmlspecialchars($a['tipo_icono'] ?? 'fa-handshake') . ' me-1" style="color:var(--primario);"></i>' . htmlspecialchars($a['tipo_at']),
                'productor'   => $productor . ' ' . $sexoIcon,
                'tema'        => htmlspecialchars($a['tema']),
                'subtema'     => htmlspecialchars($a['subtema'] ?? '—'),
                'tecnico'     => htmlspecialchars($a['tecnico']),
                'ubicacion'   => htmlspecialchars($a['departamento'] . ' / ' . $a['municipio']),
                'cultivo'     => htmlspecialchars($a['cultivo'] ?? '—'),
                'prox_visita' => $a['prox_visita'] ?? '—',
                'estado'      => $estadoBadge,
                'acciones'    => $acciones,
            ];
        }, $rows);

        $this->json(['data' => $data]);
        } catch (Exception $e) {
            error_log('AsistenciaTecnicaController::listar — ' . $e->getMessage());
            $this->json(['data' => [], 'error' => 'Error al cargar el listado.']);
        }
    }

    public function get(): void
    {
        $id = (int) $this->getPost('id', 0);
        $at = $this->model->getDetalle($id);
        if (!$at) { $this->error('Asistencia técnica no encontrada.', 404); return; }
        $this->success('OK', $at);
    }

    public function save(): void
    {
        $id       = (int) $this->getPost('id_at', 0);
        $idTipoAT = (int) $this->getPost('id_tipo_at', 0);
        $idDep    = (int) $this->getPost('id_departamento', 0);
        $idMun    = (int) $this->getPost('id_municipio', 0);
        $idTema   = (int) $this->getPost('id_tema', 0);
        $idTec    = (int) $this->getPost('id_tecnico', 0);
        $idOrg    = (int) $this->getPost('id_organizacion', 0);
        $fecha    = (string) $this->getPost('fecha_visita', '');
        $productor= trim((string) $this->getPost('productor_nombre', ''));
        $sexo     = (string) $this->getPost('productor_sexo', '');
        $edad     = (string) $this->getPost('productor_edad', '');
        $area     = (string) $this->getPost('area_productiva', '');
        $proxVis  = (string) $this->getPost('prox_visita', '');

        if (!$idTipoAT)  { $this->error('Seleccione el tipo de asistencia.');     return; }
        if (!$this->model->tipoATValido($idTipoAT)) {
            $this->error('El tipo de asistencia seleccionado no es válido.'); return;
        }
        if (!$idDep)     { $this->error('Seleccione un departamento.');            return; }
        if (!$idMun)     { $this->error('Seleccione un municipio.');              return; }
        if (!$this->model->municipioValido($idMun, $idDep)) {
            $this->error('El municipio seleccionado no pertenece al departamento.'); return;
        }
        if (!$idTema)    { $this->error('Seleccione el tema técnico.');           return; }
        if (!$this->model->temaValido($idTema)) {
            $this->error('El tema seleccionado no es válido.'); return;
        }
        if (!$idTec)     { $this->error('Seleccione el técnico responsable.');    return; }
        if (!$this->model->tecnicoValido($idTec)) {
            $this->error('El técnico seleccionado no es válido.'); return;
        }
        if (!$fecha)     { $this->error('Ingrese la fecha de la visita.');        return; }
        $dt = DateTime::createFromFormat('Y-m-d', $fecha);
        if (!$dt || $dt->format('Y-m-d') !== $fecha) {
            $this->error('La fecha de la visita no es válida.'); return;
        }
        if ($proxVis !== '') {
            $dtProx = DateTime::createFromFormat('Y-m-d', $proxVis);
            if (!$dtProx || $dtProx->format('Y-m-d') !== $proxVis) {
                $this->error('La fecha de próxima visita no es válida.'); return;
            }
            if ($proxVis < $fecha) {
                $this->error('La próxima visita no puede ser anterior a la fecha de la visita.'); return;
            }
        }

        // ── Detectar modalidad: individual vs grupal ──
        // Lo decide el cliente con los campos enviados, pero validamos en server.
        $grupoTotal   = (int) $this->getPost('grupo_total', 0);
        $grupoHombres = (int) $this->getPost('grupo_hombres', 0);
        $grupoMujeres = (int) $this->getPost('grupo_mujeres', 0);
        $idOrgGrupo   = (int) $this->getPost('id_organizacion_grupal', 0);
        $esGrupal     = ($grupoTotal > 0 || $grupoHombres > 0 || $grupoMujeres > 0);

        if ($esGrupal) {
            // ── Validación GRUPAL ──
            if ($grupoTotal < 1 || $grupoTotal > 500) {
                $this->error('El total de asistentes debe estar entre 1 y 500.'); return;
            }
            if ($grupoHombres + $grupoMujeres > $grupoTotal) {
                $this->error('La suma de hombres y mujeres no puede superar el total.'); return;
            }
            if ($idOrgGrupo && !$this->model->organizacionValida($idOrgGrupo)) {
                $this->error('La organización seleccionada no es válida.'); return;
            }
            // En grupal, los campos individuales se ignoran
            $productor = 'Atención grupal · ' . $grupoTotal . ' asistentes';
            $sexo = ''; $edad = ''; $dni = ''; $telefono = ''; $area = '';
            $idOrg = $idOrgGrupo ?: 0;
        } else {
            // ── Validación INDIVIDUAL ──
            $dni = preg_replace('/\D/', '', (string) $this->getPost('productor_dni', ''));
            if (strlen($dni) !== 13) {
                $this->error('Debe ingresar y buscar el DNI del productor.'); return;
            }
            try {
                $identidad = ProductorLookup::buscar($dni);
                $persona   = $identidad['persona'] ?? null;
                if ($persona) {
                    $productor = trim((string) ($persona['nombre'] ?? $persona['nombres'] ?? $productor));
                    $_POST['productor_apellido'] = trim((string) ($persona['apellido'] ?? $persona['apellidos'] ?? $this->getPost('productor_apellido', '')));
                    if (!empty($persona['edad'])) $edad = (string) $persona['edad'];
                    if (!empty($persona['sexo'])) $sexo = (string) $persona['sexo'];
                }
            } catch (Throwable $e) {
                error_log('AsistenciaTecnicaController identidad - ' . $e->getMessage());
                $this->error('No fue posible verificar la identidad. Intente nuevamente.'); return;
            }
            if ($productor === '') { $this->error('El nombre del productor es obligatorio.'); return; }
            if (mb_strlen($productor) > 200) { $this->error('El nombre del productor no puede exceder 200 caracteres.'); return; }
            if ($sexo !== '' && !in_array($sexo, ['M', 'F'], true)) {
                $this->error('Sexo no válido.'); return;
            }
            if ($edad !== '' && (!ctype_digit($edad) || (int) $edad < 1 || (int) $edad > 120)) {
                $this->error('La edad debe ser un número entre 1 y 120.'); return;
            }
            $telefono = preg_replace('/\D/', '', (string) $this->getPost('productor_telefono', ''));
            if ($telefono !== '' && strlen($telefono) !== 8) {
                $this->error('El teléfono debe tener 8 dígitos (formato Honduras).'); return;
            }
            if ($area !== '' && (!is_numeric($area) || (float) $area < 0)) {
                $this->error('El área productiva debe ser un número positivo.'); return;
            }
            if ($idOrg && !$this->model->organizacionValida($idOrg)) {
                $this->error('La organización seleccionada no es válida.'); return;
            }
        }

        $data = [
            'id_tipo_at'         => $idTipoAT,
            'id_departamento'    => $idDep,
            'id_municipio'       => $idMun,
            'aldea'              => trim((string) $this->getPost('aldea', '')),
            'id_tema'            => $idTema,
            'id_subtema'         => ((int) $this->getPost('id_subtema', 0)) ?: null,
            'id_cultivo'         => ((int) $this->getPost('id_cultivo', 0)) ?: null,
            'descripcion'        => $this->getPost('descripcion', ''),
            'fecha_visita'       => $fecha,
            'hora_visita'        => ($this->getPost('hora_visita') ?: null),
            'duracion'           => $this->getPost('duracion', ''),
            'id_tecnico'         => $idTec,
            'productor_nombre'   => $productor,
            'productor_apellido' => trim((string) $this->getPost('productor_apellido', '')),
            'productor_dni'      => $dni ?: null,
            'productor_edad'     => ($edad !== '' ? (int) $edad : null),
            'productor_sexo'     => $sexo ?: null,
            'id_organizacion'    => $idOrg ?: null,
            'productor_telefono' => $telefono !== '' ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : '',
            'area_productiva'    => ($area !== '' ? (float) $area : null),
            'prox_visita'        => $proxVis ?: null,
            'observaciones'      => $this->getPost('observaciones', ''),
            'updated_at'         => date('Y-m-d H:i:s'),
        ];
        if ($id === 0) {
            $data['estado']     = 'borrador';
            $data['created_by'] = $_SESSION['user']['id_usuario'];
        }

        try {
            $newId = $this->model->guardar($data, $id);
            $resultadosRaw = $this->getPost('resultados', '');
            if ($resultadosRaw) {
                $lista = array_filter(array_map('trim', explode("\n", $resultadosRaw)));
                $this->model->guardarResultados($newId, $lista);
            }
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'asistencias_tecnicas', "ID:{$newId} — {$productor}");
            $this->success(
                $id ? 'Asistencia técnica actualizada.' : 'Visita de asistencia técnica registrada.',
                ['id' => $newId]
            );
        } catch (Exception $e) {
            error_log('AsistenciaTecnicaController::save — ' . $e->getMessage());
            $this->error('Error al guardar. Intente nuevamente.');
        }
    }

    public function finalizar(): void
    {
        $id = (int) $this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }
        if (!$this->model->getDetalle($id)) { $this->error('Asistencia técnica no encontrada.', 404); return; }
        $this->model->finalizar($id);
        $this->logAction('FINALIZAR', 'asistencias_tecnicas', "ID:{$id}");
        $this->success('Asistencia técnica finalizada correctamente.');
    }

    public function delete(): void
    {
        $id = (int) $this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }
        $at = $this->model->getDetalle($id);
        if (!$at) { $this->error('Asistencia técnica no encontrada.', 404); return; }
        try {
            $db = Database::programa();
            $db->execute("DELETE FROM sag_asistencias_tecnicas WHERE id_at=? AND id_proyecto=?", [$id, Database::proyectoId()]);
            $this->logAction('ELIMINAR', 'asistencias_tecnicas', "ID:{$id} — {$at['productor_nombre']} {$at['fecha_visita']}");
            $this->success('Asistencia técnica eliminada correctamente.');
        } catch (Exception $e) {
            error_log('AsistenciaTecnicaController::delete — ' . $e->getMessage());
            $this->error('Error al eliminar la asistencia técnica.');
        }
    }

    // ════════════════════════════════════════════════════════════
    //  EVIDENCIA DOCUMENTAL (R-027)
    // ════════════════════════════════════════════════════════════

    public function subirEvidencia(): void
    {
        require_once ROOT_PATH . '/core/EvidenciaService.php';
        $id = (int)($_POST['id_at'] ?? 0);
        if (!$id) { $this->error('Asistencia técnica no especificada.'); return; }
        if (empty($_FILES['archivo'])) { $this->error('No se recibió archivo.'); return; }

        $r = EvidenciaService::guardarEvidencia(
            'sag_asistencias_tecnicas', $id, $_FILES['archivo'],
            $_SESSION['user']['id_usuario'] ?? null,
            $_POST['observaciones'] ?? null
        );
        $this->logAction($r['ok'] ? 'EVIDENCIA_OK' : 'EVIDENCIA_FAIL', 'asistencias_tecnicas', "ID:{$id} — {$r['msg']}");
        if ($r['ok']) $this->success($r['msg'], $r);
        else          $this->error($r['msg']);
    }

    public function validarEvidencia(): void
    {
        require_once ROOT_PATH . '/core/EvidenciaService.php';
        $id     = (int) $this->getPost('id_at', 0);
        $estado = $this->getPost('estado', '');
        $obs    = $this->getPost('observaciones', '');
        $r = EvidenciaService::cambiarEstado('sag_asistencias_tecnicas', $id, $estado, $obs);
        $this->logAction('VALIDAR_EVIDENCIA', 'asistencias_tecnicas', "ID:{$id} → {$estado}");
        if ($r['ok']) $this->success($r['msg']);
        else          $this->error($r['msg']);
    }

    public function evidencia(): void
    {
        require_once ROOT_PATH . '/core/EvidenciaService.php';
        $id = (int)($_GET['id'] ?? 0);
        EvidenciaService::servirArchivo('sag_asistencias_tecnicas', $id);
    }
}
