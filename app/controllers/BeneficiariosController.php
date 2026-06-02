<?php
class BeneficiariosController extends Controller
{
    private BeneficiarioModel $model;

    public function __construct()
    {
        $this->requirePrograma();
        $this->model = new BeneficiarioModel();
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
        $organizaciones = $db->fetchAll(
            "SELECT id_organizacion, nombre FROM sag_organizaciones WHERE estado='activa' AND id_proyecto=? ORDER BY nombre",
            [$pid]
        );
        $pageTitle = 'Beneficiarios — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;
        $this->view('beneficiarios/index', compact('resumen', 'departamentos', 'organizaciones', 'pageTitle'));
    }

    public function listar(): void
    {
        $filtros = [
            'id_organizacion' => (int) $this->getPost('id_organizacion', 0),
            'id_departamento' => (int) $this->getPost('id_departamento', 0),
            'sexo'            => $this->getPost('sexo', ''),
        ];
        $rows = $this->model->getListado($filtros);

        $data = array_map(function ($b) {
            $sexoIcon = $b['sexo'] === 'M'
                ? '<span style="color:#2563eb;"><i class="fas fa-mars"></i> Masculino</span>'
                : '<span style="color:#db2777;"><i class="fas fa-venus"></i> Femenino</span>';

            $acciones = '
                <button class="btn-outline btn-sm-icon btn-ver" data-id="' . $b['id_beneficiario'] . '" title="Ver">
                    <i class="fas fa-eye"></i></button>
                <button class="btn-outline btn-sm-icon btn-editar ms-1" data-id="' . $b['id_beneficiario'] . '" title="Editar">
                    <i class="fas fa-pen"></i></button>
                <button class="btn-danger-sm ms-1 btn-eliminar" data-id="' . $b['id_beneficiario'] . '" title="Eliminar">
                    <i class="fas fa-trash"></i></button>';

            return [
                'id_beneficiario' => $b['id_beneficiario'],
                'nombre_completo' => htmlspecialchars($b['nombre_completo']),
                'dni'             => $b['dni']  ?? '—',
                'edad'            => $b['edad'] ?? '—',
                'sexo'            => $sexoIcon,
                'organizacion'    => htmlspecialchars($b['organizacion'] ?? '—'),
                'ubicacion'       => htmlspecialchars($b['departamento'] . ' / ' . $b['municipio']),
                'telefono'        => $b['telefono'] ?? '—',
                'acciones'        => $acciones,
            ];
        }, $rows);

        $this->json(['data' => $data]);
    }

    public function get(): void
    {
        $id = (int) $this->getPost('id', 0);
        $b  = $this->model->getDetalle($id);
        if (!$b) { $this->error('Beneficiario no encontrado.', 404); return; }
        $this->success('OK', $b);
    }

    public function save(): void
    {
        $id       = (int) $this->getPost('id_beneficiario', 0);
        $nombre   = $this->getPost('nombre', '');
        $apellido = $this->getPost('apellido', '');
        $sexo     = $this->getPost('sexo', '');
        $idDep    = (int) $this->getPost('id_departamento', 0);
        $idMun    = (int) $this->getPost('id_municipio', 0);
        $dni      = $this->getPost('dni', '');

        if (empty($nombre))   { $this->error('El nombre es obligatorio.');   return; }
        if (empty($apellido)) { $this->error('El apellido es obligatorio.'); return; }
        if (empty($sexo))     { $this->error('Seleccione el sexo.');         return; }
        if (!$idDep)          { $this->error('Seleccione un departamento.'); return; }
        if (!$idMun)          { $this->error('Seleccione un municipio.');    return; }

        if (!empty($dni) && $this->model->existeDNI($dni, $id)) {
            $this->error("El DNI {$dni} ya está registrado."); return;
        }

        $data = [
            'nombre'           => $nombre,
            'apellido'         => $apellido,
            'dni'              => $dni ?: null,
            'fecha_nacimiento' => $this->getPost('fecha_nacimiento') ?: null,
            'sexo'             => $sexo,
            'id_departamento'  => $idDep,
            'id_municipio'     => $idMun,
            'aldea'            => $this->getPost('aldea', ''),
            'id_organizacion'  => ($this->getPost('id_organizacion') ?: null),
            'telefono'         => $this->getPost('telefono', ''),
            'updated_at'       => date('Y-m-d H:i:s'),
        ];
        if ($id === 0) {
            $data['estado']     = 'activo';
            $data['created_by'] = $_SESSION['user']['id_usuario'];
        }

        try {
            $newId = $this->model->guardar($data, $id);
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'beneficiarios', "ID:{$newId} — {$nombre} {$apellido}");
            $this->success($id ? 'Beneficiario actualizado.' : 'Beneficiario registrado correctamente.', ['id' => $newId]);
        } catch (Exception $e) {
            error_log('BeneficiariosController::save — ' . $e->getMessage());
            $this->error('Error al guardar. Intente nuevamente.');
        }
    }

    public function masivo(): void
    {
        if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $this->error('No se recibió archivo o hubo un error en la subida.'); return;
        }
        $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') { $this->error('Solo se permiten archivos CSV.'); return; }

        $handle = fopen($_FILES['archivo']['tmp_name'], 'r');
        if (!$handle) { $this->error('No se pudo leer el archivo.'); return; }

        $header    = array_map('trim', fgetcsv($handle, 1000, ','));
        $registros = [];
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (count($row) < 2) continue;
            $registros[] = array_combine($header, array_map('trim', $row));
        }
        fclose($handle);

        if (empty($registros)) { $this->error('El archivo no contiene registros válidos.'); return; }

        try {
            $result = $this->model->insertarMasivo($registros, $_SESSION['user']['id_usuario']);
            $this->logAction('CARGA_MASIVA', 'beneficiarios',
                "Insertados:{$result['ok']}, Errores:" . count($result['errores']));
            $this->success(
                "Carga completada: {$result['ok']} registros insertados.",
                ['ok' => $result['ok'], 'errores' => $result['errores']]
            );
        } catch (Exception $e) {
            error_log('BeneficiariosController::masivo — ' . $e->getMessage());
            $this->error('Error durante la carga masiva.');
        }
    }

    public function delete(): void
    {
        $id = (int) $this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }
        $this->model->update($id, ['estado' => 'inactivo', 'updated_at' => date('Y-m-d H:i:s')]);
        $this->logAction('ELIMINAR', 'beneficiarios', "ID:{$id}");
        $this->success('Beneficiario eliminado correctamente.');
    }
}
