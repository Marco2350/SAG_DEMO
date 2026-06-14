<?php
/**
 * EquipoFPController — Estructura del equipo técnico del FPROG 2026.
 */
class EquipoFPController extends Controller
{
    private EquipoFPModel $model;

    private const ROLES_EDICION = [
        'super_admin', 'coord_nacional', 'coord_pip',
        'admin', 'administrador', 'coordinador',
    ];

    public function __construct()
    {
        $this->requirePrograma();
        $this->requireFprog();
        $this->model = new EquipoFPModel();
    }

    private function requireFprog(): void
    {
        if (($_SESSION['programa']['id'] ?? '') !== 'fprog') {
            if ($this->isAjax()) {
                $this->error('Equipo sólo aplica al programa FPROG.', 403);
            }
            http_response_code(403);
            $this->redirect('/dashboard');
        }
    }

    public function index(): void
    {
        if (!Database::main()->tablaExiste('sag_fp_equipo')) {
            $tituloModulo = 'Equipo Técnico';
            $tablasFalta  = ['sag_fp_equipo'];
            $migraciones  = ['migracion_015_fp_riesgos_equipo_cronograma.sql'];
            $pageTitle    = 'Equipo — inicialización pendiente · ' . APP_NAME;
            $this->view('_partial/migracion_pendiente',
                compact('tituloModulo', 'tablasFalta', 'migraciones', 'pageTitle'));
            return;
        }
        $resumen   = $this->model->getResumen();
        $pageTitle = 'Equipo — ' . ($_SESSION['programa']['sigla'] ?? 'FPROG') . ' · ' . APP_NAME;
        $this->view('equipo_fp/index', compact('resumen', 'pageTitle'));
    }

    public function listar(): void
    {
        try {
            if (!Database::main()->tablaExiste('sag_fp_equipo')) {
                $this->json(['data' => [], 'error' => 'Módulo no inicializado.']);
                return;
            }
            $filtros = [
                'estado' => (string)$this->getPost('estado', ''),
                'buscar' => (string)$this->getPost('buscar', ''),
            ];
            $rows = $this->model->getListado($filtros);
            $data = array_map(fn($r) => [
                'id_equipo'             => (int)$r['id_equipo'],
                'rol'                   => htmlspecialchars((string)$r['rol'], ENT_QUOTES),
                'cantidad'              => (int)$r['cantidad'],
                'descripcion'           => htmlspecialchars((string)($r['descripcion'] ?? ''), ENT_QUOTES),
                'ambito'                => htmlspecialchars((string)($r['ambito'] ?? ''), ENT_QUOTES),
                'porcentaje_presupuesto'=> $r['porcentaje_presupuesto'] !== null ? (float)$r['porcentaje_presupuesto'] : null,
                'presupuesto_asignado'  => $r['presupuesto_asignado']   !== null ? (float)$r['presupuesto_asignado']   : null,
                'responsable'           => htmlspecialchars((string)($r['responsable'] ?? ''), ENT_QUOTES),
                'estado'                => htmlspecialchars((string)$r['estado'], ENT_QUOTES),
            ], $rows);
            $this->json(['data' => $data]);
        } catch (\Throwable $e) {
            error_log('EquipoFPController::listar — ' . $e->getMessage());
            $this->json(['data' => [], 'error' => 'Error al cargar el listado.']);
        }
    }

    public function get(): void
    {
        $id = (int)$this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }
        $r = $this->model->getDetalle($id);
        if (!$r) { $this->error('Rol no encontrado.', 404); return; }
        $this->success('OK', ['equipo' => $r]);
    }

    public function save(): void
    {
        $this->requireCsrf();
        $this->requireRole(self::ROLES_EDICION);

        $id        = (int)$this->getPost('id_equipo', 0);
        $rol       = trim((string)$this->getPost('rol', ''));
        $cantidad  = max(1, (int)$this->getPost('cantidad', 1));
        $pctPresup = (string)$this->getPost('porcentaje_presupuesto', '');
        $presup    = (string)$this->getPost('presupuesto_asignado', '');

        if ($rol === '') { $this->error('El rol es obligatorio.'); return; }
        if (mb_strlen($rol) > 200) {
            $this->error('Rol demasiado largo (máx. 200 caracteres).'); return;
        }
        if ($pctPresup !== '' && (!is_numeric($pctPresup) || (float)$pctPresup < 0 || (float)$pctPresup > 100)) {
            $this->error('El % de presupuesto debe estar entre 0 y 100.'); return;
        }
        if ($presup !== '' && (!is_numeric($presup) || (float)$presup < 0)) {
            $this->error('El presupuesto asignado debe ser un número >= 0.'); return;
        }

        $data = [
            'rol'                   => $rol,
            'cantidad'              => $cantidad,
            'descripcion'           => $this->getPost('descripcion') ?: null,
            'ambito'                => $this->getPost('ambito') ?: null,
            'porcentaje_presupuesto'=> $pctPresup !== '' ? (float)$pctPresup : null,
            'presupuesto_asignado'  => $presup    !== '' ? (float)$presup    : null,
            'responsable'           => $this->getPost('responsable') ?: null,
            'observaciones'         => $this->getPost('observaciones') ?: null,
            'updated_at'            => date('Y-m-d H:i:s'),
        ];

        if ($id === 0) {
            $data['estado']     = 'vacante';
            $data['created_by'] = $_SESSION['user']['id_usuario'] ?? null;
        }

        try {
            if ($id > 0 && !$this->model->getDetalle($id)) {
                $this->error('Rol no encontrado en su proyecto.', 404); return;
            }
            $newId = $this->model->guardar($data, $id);
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'equipo_fp', "ID:{$newId} — {$rol}");
            $this->success($id ? 'Rol actualizado.' : 'Rol registrado.', ['id' => $newId]);
        } catch (\Throwable $e) {
            error_log('EquipoFPController::save — ' . $e->getMessage());
            $this->error('Error al guardar el rol.');
        }
    }

    public function estado(): void
    {
        $this->requireCsrf();
        $this->requireRole(self::ROLES_EDICION);

        $id     = (int)$this->getPost('id', 0);
        $estado = (string)$this->getPost('estado', '');
        if (!$id) { $this->error('ID no válido.'); return; }
        if (!in_array($estado, EquipoFPModel::ESTADOS, true)) {
            $this->error('Estado no válido.'); return;
        }

        $r = $this->model->getDetalle($id);
        if (!$r) { $this->error('Rol no encontrado en su proyecto.', 404); return; }

        try {
            $this->model->guardar(['estado' => $estado, 'updated_at' => date('Y-m-d H:i:s')], $id);
            $this->logAction('ESTADO_' . strtoupper($estado), 'equipo_fp', "ID:{$id}");
            $this->success("Estado actualizado a: {$estado}");
        } catch (\Throwable $e) {
            error_log('EquipoFPController::estado — ' . $e->getMessage());
            $this->error('Error al cambiar de estado.');
        }
    }

    public function delete(): void
    {
        $this->requireCsrf();
        $this->requireRole(self::ROLES_EDICION);

        $id = (int)$this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }

        $r = $this->model->getDetalle($id);
        if (!$r) { $this->error('Rol no encontrado en su proyecto.', 404); return; }

        try {
            $this->model->softDelete($id);
            $this->logAction('ELIMINAR', 'equipo_fp', "ID:{$id} — " . ($r['rol'] ?? ''));
            $this->success('Rol eliminado.');
        } catch (\Throwable $e) {
            error_log('EquipoFPController::delete — ' . $e->getMessage());
            $this->error('Error al eliminar.');
        }
    }
}
