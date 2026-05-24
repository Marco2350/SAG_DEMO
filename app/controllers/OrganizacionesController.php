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
            "SELECT id_departamento, nombre FROM sag_departamentos WHERE activo=1 ORDER BY nombre"
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
        ];

        $rows = $this->model->getListado($filtros);

        $data = array_map(function ($o) {
            $tipoLabel = ucfirst($o['tipo'] ?? '—');
            $badgeEstado = match ($o['estado']) {
                'activa'    => '<span class="badge-activo">Activa</span>',
                'inactiva'  => '<span class="badge-inactivo">Inactiva</span>',
                'pendiente' => '<span class="badge-pendiente">Pendiente</span>',
                default     => $o['estado'],
            };
            $n = (int)($o['num_beneficiarios'] ?? 0);
            $acciones = '
                <button class="btn-outline btn-sm-icon btn-agregar-productor" data-id="' . $o['id_organizacion'] . '" data-nombre="' . htmlspecialchars($o['nombre'], ENT_QUOTES) . '" title="Agregar productor a esta organización" style="background:#d1fae5;color:#15803d;border-color:#86efac;">
                    <i class="fas fa-user-plus"></i></button>
                <button class="btn-outline btn-sm-icon btn-miembros ms-1" data-id="' . $o['id_organizacion'] . '" data-nombre="' . htmlspecialchars($o['nombre'], ENT_QUOTES) . '" title="Ver productores miembros">
                    <i class="fas fa-users"></i></button>
                <button class="btn-outline btn-sm-icon btn-editar ms-1" data-id="' . $o['id_organizacion'] . '" title="Editar">
                    <i class="fas fa-pen"></i></button>
                <button class="btn-outline btn-sm-icon btn-estado ms-1" data-id="' . $o['id_organizacion'] . '" data-estado="' . $o['estado'] . '" title="Cambiar estado">
                    <i class="fas fa-toggle-on"></i></button>
                <button class="btn-danger-sm ms-1 btn-eliminar" data-id="' . $o['id_organizacion'] . '" title="Eliminar">
                    <i class="fas fa-trash"></i></button>';

            return [
                'id_organizacion'   => $o['id_organizacion'],
                'nombre'            => htmlspecialchars($o['nombre']),
                'tipo_organizacion' => $tipoLabel,
                'ubicacion'         => htmlspecialchars($o['departamento'] . ' / ' . $o['municipio']),
                'representante'     => htmlspecialchars($o['representante'] ?? '—'),
                'telefono'          => htmlspecialchars($o['telefono'] ?? '—'),
                'num_beneficiarios' => (int) ($o['num_beneficiarios'] ?? 0),
                'estado'            => $badgeEstado,
                'acciones'          => $acciones,
            ];
        }, $rows);

        $this->json(['data' => $data]);
        } catch (Exception $e) {
            error_log('OrganizacionesController::listar — ' . $e->getMessage());
            $this->json(['data' => [], 'error' => $e->getMessage()]);
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
        $nombre = trim((string)$this->getPost('nombre', ''));
        $idDep  = (int) $this->getPost('id_departamento', 0);
        $idMun  = (int) $this->getPost('id_municipio', 0);

        if (empty($nombre)) { $this->error('El nombre es obligatorio.');    return; }
        if (!$idDep)        { $this->error('Seleccione un departamento.');  return; }
        if (!$idMun)        { $this->error('Seleccione un municipio.');     return; }

        // R-012: Validar nombre único (ignora mayúsculas/acentos cuando se trate de la misma org en edición)
        $db = Database::programa();
        $dup = $db->fetchOne(
            "SELECT id_organizacion FROM sag_organizaciones
             WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?))
               AND id_organizacion <> ?
             LIMIT 1",
            [$nombre, $id]
        );
        if ($dup) {
            $this->error('Ya existe una organización registrada con ese nombre.');
            return;
        }

        // R-017: Coordenadas separadas (latitud / longitud), con fallback al campo combinado por compat
        $lat = $this->getPost('latitud', '');
        $lon = $this->getPost('longitud', '');
        $coord = $this->getPost('coordenadas', '');
        if ($lat === '' && $lon === '' && $coord !== '') {
            // Parsear "lat,lon" legacy
            $parts = explode(',', $coord);
            if (count($parts) === 2) {
                $lat = trim($parts[0]);
                $lon = trim($parts[1]);
            }
        }
        $latFloat = is_numeric($lat) ? (float)$lat : null;
        $lonFloat = is_numeric($lon) ? (float)$lon : null;

        // R-016: DNI representante (validar formato HN: 13 dígitos)
        $repDni = preg_replace('/\D/', '', (string)$this->getPost('representante_dni', ''));
        if ($repDni !== '' && strlen($repDni) !== 13) {
            $this->error('El DNI del representante debe tener 13 dígitos.');
            return;
        }

        $data = [
            'nombre'             => $nombre,
            'tipo'               => $this->getPost('tipo', 'cooperativa'),
            'id_departamento'    => $idDep,
            'id_municipio'       => $idMun,
            'aldea'              => $this->getPost('aldea', ''),
            'representante'      => $this->getPost('representante', ''),
            'representante_dni'  => $repDni ?: null,
            'telefono'           => $this->getPost('telefono', ''),
            'email'              => $this->getPost('email', ''),
            'estado'             => $this->getPost('estado', 'pendiente'),
            'fecha_registro'     => $this->getPost('fecha_registro') ?: null,
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
            $this->error('Error al guardar: ' . $e->getMessage());
        }
    }

    /**
     * R-012: AJAX para validar en vivo si el nombre ya existe (mientras escribe).
     */
    public function checkNombre(): void
    {
        $nombre = trim((string)$this->getPost('nombre', ''));
        $id     = (int) $this->getPost('id_organizacion', 0);
        if ($nombre === '') { $this->success('OK', ['existe' => false]); return; }

        try {
            $db = Database::programa();
            $r = $db->fetchOne(
                "SELECT id_organizacion FROM sag_organizaciones
                 WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?))
                   AND id_organizacion <> ?
                 LIMIT 1",
                [$nombre, $id]
            );
            $this->success('OK', ['existe' => (bool)$r]);
        } catch (\Throwable $e) {
            $this->success('OK', ['existe' => false]);
        }
    }

    public function estado(): void
    {
        $id     = (int) $this->getPost('id', 0);
        $estado = $this->getPost('estado', '');
        if (!in_array($estado, ['activa', 'inactiva', 'pendiente'])) { $this->error('Estado no válido.'); return; }
        $this->model->cambiarEstado($id, $estado);
        $this->logAction('ESTADO', 'organizaciones', "ID:{$id} → {$estado}");
        $this->success("Estado cambiado a: {$estado}");
    }

    public function delete(): void
    {
        $id = (int) $this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }

        $db    = Database::programa();
        $count = $db->fetchOne(
            "SELECT COUNT(*) AS t FROM sag_beneficiarios WHERE id_organizacion=? AND estado='activo'",
            [$id]
        );
        if (($count['t'] ?? 0) > 0) {
            $this->error('No se puede eliminar: la organización tiene beneficiarios activos.'); return;
        }
        $this->model->softDelete($id);
        $this->logAction('ELIMINAR', 'organizaciones', "ID:{$id}");
        $this->success('Organización eliminada correctamente.');
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
             WHERE b.id_organizacion = ?
             ORDER BY b.apellido, b.nombre",
            [$id]
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
                'dni'            => htmlspecialchars($b['dni'] ?? '—'),
                'sexo'           => $b['sexo'] === 'M' ? 'Masculino' : ($b['sexo'] === 'F' ? 'Femenino' : '—'),
                'ubicacion'      => htmlspecialchars(($b['departamento'] ?? '—') . ' / ' . ($b['municipio'] ?? '—')),
                'telefono'       => htmlspecialchars($b['telefono'] ?? '—'),
                'cultivo'        => htmlspecialchars($b['cultivo_principal'] ?? '—'),
                'estado'         => $badge,
            ];
        }, $rows);

        $this->json(['success' => true, 'data' => $data, 'total' => count($data)]);
    }
}
