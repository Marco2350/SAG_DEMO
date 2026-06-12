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
        try {
            // Protocolo DataTables server-side
            $draw   = (int) $this->getPost('draw', 1);
            $start  = max(0, (int) $this->getPost('start', 0));
            $length = (int) $this->getPost('length', 15);
            if ($length < 1 || $length > 200) $length = 15;

            $filtros = [
                'id_organizacion' => (int) $this->getPost('id_organizacion', 0),
                'id_departamento' => (int) $this->getPost('id_departamento', 0),
                'sexo'            => $this->getPost('sexo', ''),
                'buscar'          => trim((string) ($_POST['search']['value'] ?? '')),
            ];

            // Orden: solo columnas en lista blanca (índice DataTables → SQL)
            $ordenables = [
                1 => 'b.nombre, b.apellido',
                2 => 'b.dni',
                3 => 'b.fecha_nacimiento',
            ];
            $colIdx = (int) ($_POST['order'][0]['column'] ?? 1);
            $dir    = strtolower((string) ($_POST['order'][0]['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
            $orden  = ($ordenables[$colIdx] ?? $ordenables[1]) . ' ' . $dir;

            $total     = $this->model->contarListado(array_intersect_key($filtros, array_flip(['id_organizacion', 'id_departamento', 'sexo'])));
            $filtrados = $filtros['buscar'] !== '' ? $this->model->contarListado($filtros) : $total;
            $rows      = $this->model->getListado($filtros, $start, $length, $orden);

            $data = array_map(function ($b) {
                $sexoIcon = match ($b['sexo']) {
                    'M'     => '<span style="color:#2563eb;"><i class="fas fa-mars"></i> Masculino</span>',
                    'F'     => '<span style="color:#db2777;"><i class="fas fa-venus"></i> Femenino</span>',
                    default => '—',
                };

                $acciones = '
                    <button class="btn-outline btn-sm-icon btn-ver" data-id="' . $b['id_beneficiario'] . '" title="Ver">
                        <i class="fas fa-eye"></i></button>
                    <button class="btn-outline btn-sm-icon btn-editar ms-1" data-id="' . $b['id_beneficiario'] . '" title="Editar">
                        <i class="fas fa-pen"></i></button>
                    <button class="btn-danger-sm ms-1 btn-eliminar" data-id="' . $b['id_beneficiario'] . '" data-nombre="' . htmlspecialchars($b['nombre_completo'], ENT_QUOTES) . '" title="Eliminar">
                        <i class="fas fa-trash"></i></button>';

                return [
                    'id_beneficiario' => $b['id_beneficiario'],
                    'nombre_completo' => htmlspecialchars($b['nombre_completo']),
                    'dni'             => htmlspecialchars($b['dni'] ?: '—'),
                    'edad'            => $b['edad'] ?? '—',
                    'sexo'            => $sexoIcon,
                    'organizacion'    => htmlspecialchars($b['organizacion'] ?: '—'),
                    'ubicacion'       => htmlspecialchars($b['departamento'] . ' / ' . $b['municipio']),
                    'telefono'        => htmlspecialchars($b['telefono'] ?: '—'),
                    'acciones'        => $acciones,
                ];
            }, $rows);

            $this->json([
                'draw'            => $draw,
                'recordsTotal'    => $total,
                'recordsFiltered' => $filtrados,
                'data'            => $data,
            ]);
        } catch (Exception $e) {
            error_log('BeneficiariosController::listar — ' . $e->getMessage());
            $this->json([
                'draw'            => (int) $this->getPost('draw', 1),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => 'Error al cargar el listado.',
            ]);
        }
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
        $nombre   = trim((string) $this->getPost('nombre', ''));
        $apellido = trim((string) $this->getPost('apellido', ''));
        $sexo     = (string) $this->getPost('sexo', '');
        $idDep    = (int) $this->getPost('id_departamento', 0);
        $idMun    = (int) $this->getPost('id_municipio', 0);
        $idOrg    = (int) $this->getPost('id_organizacion', 0);
        $aldea    = trim((string) $this->getPost('aldea', ''));

        // ── Datos personales ──
        if ($nombre === '')             { $this->error('El nombre es obligatorio.'); return; }
        if (mb_strlen($nombre) > 100)   { $this->error('El nombre no puede exceder 100 caracteres.'); return; }
        if ($apellido === '')           { $this->error('El apellido es obligatorio.'); return; }
        if (mb_strlen($apellido) > 100) { $this->error('El apellido no puede exceder 100 caracteres.'); return; }
        if (!in_array($sexo, BeneficiarioModel::SEXOS, true)) {
            $this->error('Seleccione el sexo.'); return;
        }

        // DNI opcional, pero si viene debe tener 13 dígitos (formato HN) y ser único
        $dni = preg_replace('/\D/', '', (string) $this->getPost('dni', ''));
        if ($dni !== '' && strlen($dni) !== 13) {
            $this->error('El DNI debe tener 13 dígitos.'); return;
        }
        if ($dni !== '' && $this->model->existeDNI($dni, $id)) {
            $this->error("El DNI {$dni} ya está registrado."); return;
        }

        // Fecha de nacimiento opcional: válida, no futura, año razonable
        $fechaNac = (string) $this->getPost('fecha_nacimiento', '');
        if ($fechaNac !== '') {
            $dt = DateTime::createFromFormat('Y-m-d', $fechaNac);
            if (!$dt || $dt->format('Y-m-d') !== $fechaNac) {
                $this->error('La fecha de nacimiento no es válida.'); return;
            }
            if ($fechaNac > date('Y-m-d')) {
                $this->error('La fecha de nacimiento no puede ser futura.'); return;
            }
            if ((int) $dt->format('Y') < 1900) {
                $this->error('La fecha de nacimiento no es válida.'); return;
            }
        }

        // Teléfono opcional: 8 dígitos (formato HN)
        $telefono = preg_replace('/\D/', '', (string) $this->getPost('telefono', ''));
        if ($telefono !== '' && strlen($telefono) !== 8) {
            $this->error('El teléfono debe tener 8 dígitos (formato Honduras).'); return;
        }

        // ── Ubicación y organización ──
        if (!$idDep) { $this->error('Seleccione un departamento.'); return; }
        if (!$idMun) { $this->error('Seleccione un municipio.');    return; }
        if (!$this->model->municipioValido($idMun, $idDep)) {
            $this->error('El municipio seleccionado no pertenece al departamento.'); return;
        }
        if (mb_strlen($aldea) > 200) { $this->error('La aldea no puede exceder 200 caracteres.'); return; }
        if ($idOrg && !$this->model->organizacionValida($idOrg)) {
            $this->error('La organización seleccionada no es válida.'); return;
        }

        $data = [
            'nombre'           => $nombre,
            'apellido'         => $apellido,
            'dni'              => $dni ?: null,
            'fecha_nacimiento' => $fechaNac ?: null,
            'sexo'             => $sexo,
            'id_departamento'  => $idDep,
            'id_municipio'     => $idMun,
            'aldea'            => $aldea,
            'id_organizacion'  => $idOrg ?: null,
            'telefono'         => $telefono !== '' ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : '',
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

        $header = fgetcsv($handle, 1000, ',');
        if (!$header) { fclose($handle); $this->error('El archivo no tiene encabezado.'); return; }
        $header = array_map('trim', $header);

        $registros = [];
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (count($row) < 2) continue;
            // Normalizar filas con menos/más columnas que el encabezado
            $row = array_pad(array_slice($row, 0, count($header)), count($header), '');
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
        $b = $this->model->getDetalle($id);
        if (!$b) { $this->error('Beneficiario no encontrado.', 404); return; }
        $this->model->update($id, ['estado' => 'inactivo', 'updated_at' => date('Y-m-d H:i:s')]);
        $this->logAction('ELIMINAR', 'beneficiarios', "ID:{$id} — {$b['nombre']} {$b['apellido']}");
        $this->success('Beneficiario eliminado correctamente.');
    }
}
