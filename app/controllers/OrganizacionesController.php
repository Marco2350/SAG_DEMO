<?php
class OrganizacionesController extends Controller
{
    private OrganizacionModel $model;

    public function __construct()
    {
        $this->requirePrograma();
        $this->model = new OrganizacionModel();
    }

    public function index(): void
    {
        $db  = Database::programa();
        $resumen = $this->model->getResumen();
        $tipos   = $this->model->getTipos();
        $departamentos = $db->fetchAll(
            "SELECT id_departamento, nombre FROM sag_departamentos WHERE activo=1 AND id_proyecto=? ORDER BY nombre",
            [Database::proyectoId()]
        );
        $pageTitle = 'Organizaciones — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;
        $this->view('organizaciones/index', compact('resumen', 'tipos', 'departamentos', 'pageTitle'));
    }

    public function listar(): void
    {
        try {
            $filtros = [
                'estado'          => $this->getPost('estado', ''),
                'id_departamento' => (int) $this->getPost('id_departamento', 0),
                'tipo'            => $this->getPost('tipo', ''),
            ];

            $rows = $this->model->getListado($filtros);

            $data = array_map(function ($o) {
                $tipoLabel = OrganizacionModel::TIPOS[$o['tipo']] ?? ucfirst((string) $o['tipo']);
                $badgeEstado = match ($o['estado']) {
                    'activa'    => '<span class="badge-activo">Activa</span>',
                    'inactiva'  => '<span class="badge-inactivo">Inactiva</span>',
                    'pendiente' => '<span class="badge-pendiente">Pendiente</span>',
                    default     => htmlspecialchars((string) $o['estado']),
                };
                $acciones = '
                    <button class="btn-outline btn-sm-icon btn-agregar-productor" data-id="' . $o['id_organizacion'] . '" data-nombre="' . htmlspecialchars($o['nombre'], ENT_QUOTES) . '" title="Agregar productor a esta organización" style="background:#d1fae5;color:#15803d;border-color:#86efac;">
                        <i class="fas fa-user-plus"></i></button>
                    <button class="btn-outline btn-sm-icon btn-miembros ms-1" data-id="' . $o['id_organizacion'] . '" data-nombre="' . htmlspecialchars($o['nombre'], ENT_QUOTES) . '" title="Ver productores miembros">
                        <i class="fas fa-users"></i></button>
                    <button class="btn-outline btn-sm-icon btn-editar ms-1" data-id="' . $o['id_organizacion'] . '" title="Editar">
                        <i class="fas fa-pen"></i></button>
                    <button class="btn-outline btn-sm-icon btn-estado ms-1" data-id="' . $o['id_organizacion'] . '" data-estado="' . $o['estado'] . '" title="Cambiar estado">
                        <i class="fas fa-toggle-on"></i></button>
                    <button class="btn-danger-sm ms-1 btn-eliminar" data-id="' . $o['id_organizacion'] . '" data-nombre="' . htmlspecialchars($o['nombre'], ENT_QUOTES) . '" title="Eliminar">
                        <i class="fas fa-trash"></i></button>';

                return [
                    'id_organizacion'   => $o['id_organizacion'],
                    'nombre'            => htmlspecialchars($o['nombre']),
                    'tipo_organizacion' => $tipoLabel,
                    'ubicacion'         => htmlspecialchars($o['departamento'] . ' / ' . $o['municipio']),
                    'representante'     => htmlspecialchars($o['representante'] ?: '—'),
                    'telefono'          => htmlspecialchars($o['telefono'] ?: '—'),
                    'num_beneficiarios' => (int) ($o['num_beneficiarios'] ?? 0),
                    'estado'            => $badgeEstado,
                    'acciones'          => $acciones,
                ];
            }, $rows);

            $this->json(['data' => $data, 'resumen' => $this->model->getResumen()]);
        } catch (Exception $e) {
            error_log('OrganizacionesController::listar — ' . $e->getMessage());
            $this->json(['data' => [], 'error' => 'Error al cargar el listado.']);
        }
    }

    public function get(): void
    {
        $id  = (int) $this->getPost('id', 0);
        $org = $this->model->getDetalle($id);
        if (!$org) { $this->error('Organización no encontrada.', 404); return; }
        $this->success('OK', $org);
    }

    public function save(): void
    {
        $id     = (int) $this->getPost('id_organizacion', 0);
        $nombre = trim((string) $this->getPost('nombre', ''));
        $tipo   = (string) $this->getPost('tipo', '');
        $idDep  = (int) $this->getPost('id_departamento', 0);
        $idMun  = (int) $this->getPost('id_municipio', 0);
        $estado = (string) $this->getPost('estado', 'pendiente');
        $rep    = trim((string) $this->getPost('representante', ''));
        $email  = trim((string) $this->getPost('email', ''));
        $aldea  = trim((string) $this->getPost('aldea', ''));

        // ── Identificación ──
        if ($nombre === '')           { $this->error('El nombre es obligatorio.'); return; }
        if (mb_strlen($nombre) < 3)   { $this->error('El nombre debe tener al menos 3 caracteres.'); return; }
        if (mb_strlen($nombre) > 200) { $this->error('El nombre no puede exceder 200 caracteres.'); return; }
        if (!isset(OrganizacionModel::TIPOS[$tipo])) {
            $this->error('Seleccione un tipo de organización válido.'); return;
        }
        if (!in_array($estado, OrganizacionModel::ESTADOS, true)) {
            $this->error('Estado no válido.'); return;
        }

        // ── Ubicación ──
        if (!$idDep) { $this->error('Seleccione un departamento.'); return; }
        if (!$idMun) { $this->error('Seleccione un municipio.');    return; }
        if (!$this->model->municipioValido($idMun, $idDep)) {
            $this->error('El municipio seleccionado no pertenece al departamento.'); return;
        }
        if (mb_strlen($aldea) > 200) { $this->error('La aldea no puede exceder 200 caracteres.'); return; }

        // R-012: nombre único dentro del programa activo
        if ($this->model->nombreDuplicado($nombre, $id)) {
            $this->error('Ya existe una organización registrada con ese nombre.'); return;
        }

        // R-017: Coordenadas separadas (latitud / longitud), con fallback al campo combinado por compat
        $lat = trim((string) $this->getPost('latitud', ''));
        $lon = trim((string) $this->getPost('longitud', ''));
        $coord = trim((string) $this->getPost('coordenadas', ''));
        if ($lat === '' && $lon === '' && $coord !== '') {
            $parts = explode(',', $coord);
            if (count($parts) === 2) {
                $lat = trim($parts[0]);
                $lon = trim($parts[1]);
            }
        }
        if (($lat === '') !== ($lon === '')) {
            $this->error('Ingrese latitud y longitud juntas, o deje ambas vacías.'); return;
        }
        $latFloat = null;
        $lonFloat = null;
        if ($lat !== '') {
            if (!is_numeric($lat) || !is_numeric($lon)) {
                $this->error('Las coordenadas deben ser numéricas.'); return;
            }
            $latFloat = (float) $lat;
            $lonFloat = (float) $lon;
            // Rango geográfico de Honduras
            if ($latFloat < 12.9 || $latFloat > 16.5) {
                $this->error('Latitud fuera del rango de Honduras (12.9 a 16.5).'); return;
            }
            if ($lonFloat < -89.4 || $lonFloat > -83.1) {
                $this->error('Longitud fuera del rango de Honduras (-89.4 a -83.1).'); return;
            }
        }

        // ── Representante legal (obligatorio, igual que en el formulario) ──
        if ($rep === '') { $this->error('Ingrese el nombre del representante legal.'); return; }
        if (mb_strlen($rep) > 200) { $this->error('El nombre del representante no puede exceder 200 caracteres.'); return; }

        // R-016: DNI representante (formato HN: 13 dígitos)
        $repDni = preg_replace('/\D/', '', (string) $this->getPost('representante_dni', ''));
        if ($repDni === '') { $this->error('Ingrese el DNI del representante.'); return; }
        if (strlen($repDni) !== 13) {
            $this->error('El DNI del representante debe tener 13 dígitos.'); return;
        }

        // ── Contacto ──
        $telefono = preg_replace('/[^\d]/', '', (string) $this->getPost('telefono', ''));
        if ($telefono !== '' && strlen($telefono) !== 8) {
            $this->error('El teléfono debe tener 8 dígitos (formato Honduras).'); return;
        }
        if ($email !== '') {
            if (mb_strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error('El correo electrónico no es válido.'); return;
            }
        }

        // ── Fecha de registro ──
        $fechaReg = (string) $this->getPost('fecha_registro', '');
        if ($fechaReg !== '') {
            $dt = DateTime::createFromFormat('Y-m-d', $fechaReg);
            if (!$dt || $dt->format('Y-m-d') !== $fechaReg) {
                $this->error('La fecha de registro no es válida.'); return;
            }
            if ($fechaReg > date('Y-m-d')) {
                $this->error('La fecha de registro no puede ser futura.'); return;
            }
        }

        $data = [
            'nombre'             => $nombre,
            'tipo'               => $tipo,
            'id_departamento'    => $idDep,
            'id_municipio'       => $idMun,
            'aldea'              => $aldea,
            'representante'      => $rep,
            'representante_dni'  => $repDni,
            'telefono'           => $telefono !== '' ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : '',
            'email'              => $email,
            'estado'             => $estado,
            'fecha_registro'     => $fechaReg ?: null,
            'latitud'            => $latFloat,
            'longitud'           => $lonFloat,
            'coordenadas'        => ($latFloat !== null && $lonFloat !== null) ? "{$latFloat},{$lonFloat}" : '',
            'updated_at'         => date('Y-m-d H:i:s'),
        ];
        if ($id === 0) $data['created_by'] = $_SESSION['user']['id_usuario'];

        try {
            $newId = $this->model->guardar($data, $id);
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'organizaciones', "ID:{$newId} — {$nombre}");
            $this->success($id ? 'Organización actualizada.' : 'Organización creada correctamente.', ['id' => $newId]);
        } catch (Exception $e) {
            error_log('OrganizacionesController::save — ' . $e->getMessage());
            $this->error('Error al guardar la organización. Revise los datos e intente de nuevo.');
        }
    }

    /**
     * R-012: AJAX para validar en vivo si el nombre ya existe (mientras escribe).
     */
    public function checkNombre(): void
    {
        $nombre = trim((string) $this->getPost('nombre', ''));
        $id     = (int) $this->getPost('id_organizacion', 0);
        if ($nombre === '') { $this->success('OK', ['existe' => false]); return; }

        try {
            $this->success('OK', ['existe' => $this->model->nombreDuplicado($nombre, $id)]);
        } catch (\Throwable $e) {
            $this->success('OK', ['existe' => false]);
        }
    }

    public function estado(): void
    {
        $id     = (int) $this->getPost('id', 0);
        $estado = $this->getPost('estado', '');
        if (!$id) { $this->error('ID no válido.'); return; }
        if (!in_array($estado, OrganizacionModel::ESTADOS, true)) { $this->error('Estado no válido.'); return; }
        if (!$this->model->getDetalle($id)) { $this->error('Organización no encontrada.', 404); return; }
        $this->model->cambiarEstado($id, $estado);
        $this->logAction('ESTADO', 'organizaciones', "ID:{$id} → {$estado}");
        $this->success("Estado cambiado a: {$estado}");
    }

    public function delete(): void
    {
        $id = (int) $this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }

        $org = $this->model->getDetalle($id);
        if (!$org) { $this->error('Organización no encontrada.', 404); return; }

        // Bloquear si otras tablas la referencian (FKs reales en la BD)
        $refs = $this->model->referencias($id);
        if ($refs) {
            $detalle = [];
            foreach ($refs as $tabla => $total) $detalle[] = "{$total} {$tabla}";
            $this->error('No se puede eliminar: la organización tiene registros asociados (' . implode(', ', $detalle) . '). Puede marcarla como Inactiva.');
            return;
        }

        try {
            $this->model->eliminar($id);
            $this->logAction('ELIMINAR', 'organizaciones', "ID:{$id} — {$org['nombre']}");
            $this->success('Organización eliminada correctamente.');
        } catch (Exception $e) {
            error_log('OrganizacionesController::delete — ' . $e->getMessage());
            $this->error('No se pudo eliminar: la organización tiene registros asociados en el sistema.');
        }
    }

    public function miembros(): void
    {
        $id = (int) $this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }

        $db   = Database::programa();
        $rows = $db->fetchAll(
            "SELECT b.id_beneficiario,
                    CONCAT(b.nombre,' ',b.apellido) AS nombre_completo,
                    b.dni, b.sexo,
                    d.nombre AS departamento,
                    m.nombre AS municipio,
                    b.telefono, b.cultivo_principal,
                    b.estado
             FROM sag_beneficiarios b
             LEFT JOIN sag_departamentos d ON d.id_departamento = b.id_departamento
             LEFT JOIN sag_municipios    m ON m.id_municipio    = b.id_municipio
             WHERE b.id_organizacion = ? AND b.id_proyecto = ?
             ORDER BY b.apellido, b.nombre",
            [$id, Database::proyectoId()]
        );

        $data = array_map(function ($b) {
            $badge = match ($b['estado']) {
                'activo'   => '<span class="badge-activo">Activo</span>',
                'inactivo' => '<span class="badge-inactivo">Inactivo</span>',
                default    => htmlspecialchars($b['estado']),
            };
            return [
                'id'             => $b['id_beneficiario'],
                'nombre'         => htmlspecialchars($b['nombre_completo']),
                'dni'            => htmlspecialchars($b['dni'] ?: '—'),
                'sexo'           => $b['sexo'] === 'M' ? 'Masculino' : ($b['sexo'] === 'F' ? 'Femenino' : '—'),
                'ubicacion'      => htmlspecialchars(($b['departamento'] ?? '—') . ' / ' . ($b['municipio'] ?? '—')),
                'telefono'       => htmlspecialchars($b['telefono'] ?: '—'),
                'cultivo'        => htmlspecialchars($b['cultivo_principal'] ?: '—'),
                'estado'         => $badge,
            ];
        }, $rows);

        $this->json(['success' => true, 'data' => $data, 'total' => count($data)]);
    }
}
