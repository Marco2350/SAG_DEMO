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
        $resumen = $this->model->getResumen();
        $departamentos = $db->fetchAll(
            "SELECT id_departamento, nombre FROM sag_departamentos WHERE activo=1 ORDER BY nombre"
        );
        $temas = $db->fetchAll(
            "SELECT id_tema, nombre FROM sag_temas WHERE activo=1 AND tipo IN ('at','ambos') ORDER BY nombre"
        );
        $tecnicos = $db->fetchAll(
            "SELECT id_tecnico, nombre_completo FROM sag_tecnicos WHERE activo=1 ORDER BY nombre_completo"
        );
        $organizaciones = $db->fetchAll(
            "SELECT id_organizacion, nombre FROM sag_organizaciones WHERE estado='activa' ORDER BY nombre"
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
        $fecha    = $this->getPost('fecha_visita', '');
        $productor= $this->getPost('productor_nombre', '');

        if (!$idTipoAT)  { $this->error('Seleccione el tipo de asistencia.');     return; }
        if (!$idDep)     { $this->error('Seleccione un departamento.');            return; }
        if (!$idMun)     { $this->error('Seleccione un municipio.');              return; }
        if (!$idTema)    { $this->error('Seleccione el tema técnico.');           return; }
        if (!$idTec)     { $this->error('Seleccione el técnico responsable.');    return; }
        if (!$fecha)     { $this->error('Ingrese la fecha de la visita.');        return; }
        if (!$productor) { $this->error('El nombre del productor es obligatorio.'); return; }

        $data = [
            'id_tipo_at'         => $idTipoAT,
            'id_departamento'    => $idDep,
            'id_municipio'       => $idMun,
            'aldea'              => $this->getPost('aldea', ''),
            'id_tema'            => $idTema,
            'id_subtema'         => ($this->getPost('id_subtema') ?: null),
            'id_cultivo'         => ($this->getPost('id_cultivo') ?: null),
            'descripcion'        => $this->getPost('descripcion', ''),
            'fecha_visita'       => $fecha,
            'hora_visita'        => ($this->getPost('hora_visita') ?: null),
            'duracion'           => $this->getPost('duracion', ''),
            'id_tecnico'         => $idTec,
            'productor_nombre'   => $productor,
            'productor_apellido' => $this->getPost('productor_apellido', ''),
            'productor_dni'      => ($this->getPost('productor_dni') ?: null),
            'productor_edad'     => ($this->getPost('productor_edad') ?: null),
            'productor_sexo'     => ($this->getPost('productor_sexo') ?: null),
            'id_organizacion'    => ($this->getPost('id_organizacion') ?: null),
            'productor_telefono' => $this->getPost('productor_telefono', ''),
            'area_productiva'    => ($this->getPost('area_productiva') ?: null),
            'prox_visita'        => ($this->getPost('prox_visita') ?: null),
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
        $this->model->finalizar($id);
        $this->logAction('FINALIZAR', 'asistencias_tecnicas', "ID:{$id}");
        $this->success('Asistencia técnica finalizada correctamente.');
    }

    public function delete(): void
    {
        $id = (int) $this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }
        try {
            $db = Database::programa();
            $db->execute("DELETE FROM sag_asistencias_tecnicas WHERE id_at=?", [$id]);
            $this->logAction('ELIMINAR', 'asistencias_tecnicas', "ID:{$id}");
            $this->success('Asistencia técnica eliminada correctamente.');
        } catch (Exception $e) {
            $this->error('Error al eliminar.');
        }
    }
}
