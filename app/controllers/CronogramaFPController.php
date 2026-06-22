<?php
/**
 * CronogramaFPController — Cronograma de actividades FPROG 2026 (Jun–Dic).
 *
 * Cada actividad tiene marcas booleanas por mes y opcionalmente
 * se vincula a un componente del programa.
 */
class CronogramaFPController extends Controller
{
    private CronogramaFPModel $model;

    private const ROLES_EDICION = [
        'super_admin', 'coord_nacional', 'coord_pip',
        'admin', 'administrador', 'coordinador',
    ];

    public function __construct()
    {
        $this->requirePrograma();
        $this->requireFprog();
        $this->model = new CronogramaFPModel();
    }

    private function requireFprog(): void
    {
        if (($_SESSION['programa']['id'] ?? '') !== 'fprog') {
            if ($this->isAjax()) {
                $this->error('Cronograma sólo aplica al programa FPROG.', 403);
            }
            http_response_code(403);
            $this->redirect('/dashboard');
        }
    }

    public function index(): void
    {
        if (!Database::main()->tablaExiste('sag_fp_cronograma')) {
            $tituloModulo = 'Cronograma';
            $tablasFalta  = ['sag_fp_cronograma'];
            $migraciones  = ['migracion_015_fp_riesgos_equipo_cronograma.sql'];
            $pageTitle    = 'Cronograma — inicialización pendiente · ' . APP_NAME;
            $this->view('_partial/migracion_pendiente',
                compact('tituloModulo', 'tablasFalta', 'migraciones', 'pageTitle'));
            return;
        }

        $resumen = $this->model->getResumen();

        // Componentes del programa para el select
        $componentes = [];
        try {
            $componentes = Database::programa()->fetchAll(
                "SELECT id_componente, numero_romano, nombre
                   FROM sag_fp_componentes
                  WHERE id_proyecto = ? AND activo = 1
                  ORDER BY CASE numero_romano
                    WHEN 'I' THEN 1 WHEN 'II' THEN 2 WHEN 'III' THEN 3
                    WHEN 'IV' THEN 4 WHEN 'V' THEN 5 WHEN 'VI' THEN 6
                    WHEN 'VII' THEN 7 ELSE 99 END, id_componente",
                [Database::proyectoId()]
            );
        } catch (\Throwable $e) {
            error_log('CronogramaFPController::index componentes — ' . $e->getMessage());
        }

        $pageTitle = 'Cronograma — ' . ($_SESSION['programa']['sigla'] ?? 'FPROG') . ' · ' . APP_NAME;
        $this->view('cronograma_fp/index', compact('resumen', 'componentes', 'pageTitle'));
    }

    public function listar(): void
    {
        try {
            if (!Database::main()->tablaExiste('sag_fp_cronograma')) {
                $this->json(['data' => [], 'error' => 'Módulo no inicializado.']);
                return;
            }
            $filtros = [
                'estado'        => (string)$this->getPost('estado', ''),
                'id_componente' => (int)$this->getPost('id_componente', 0),
                'buscar'        => (string)$this->getPost('buscar', ''),
            ];
            $rows = $this->model->getListado($filtros);
            $data = array_map(function ($a) {
                $componente = '—';
                if (!empty($a['componente_nombre'])) {
                    $num = $a['componente_numero'] ? $a['componente_numero'] . '. ' : '';
                    $componente = $num . $a['componente_nombre'];
                }
                return [
                    'id_actividad' => (int)$a['id_actividad'],
                    'numero_orden' => (int)$a['numero_orden'],
                    'actividad'    => htmlspecialchars((string)$a['actividad'], ENT_QUOTES),
                    'componente'   => htmlspecialchars($componente, ENT_QUOTES),
                    'mes_jun'      => (int)$a['mes_jun'],
                    'mes_jul'      => (int)$a['mes_jul'],
                    'mes_ago'      => (int)$a['mes_ago'],
                    'mes_sep'      => (int)$a['mes_sep'],
                    'mes_oct'      => (int)$a['mes_oct'],
                    'mes_nov'      => (int)$a['mes_nov'],
                    'mes_dic'      => (int)$a['mes_dic'],
                    'responsable'  => htmlspecialchars((string)($a['responsable'] ?? ''), ENT_QUOTES),
                    'estado'       => htmlspecialchars((string)$a['estado'], ENT_QUOTES),
                ];
            }, $rows);
            $this->json(['data' => $data, 'resumen' => $this->model->getResumen()]);
        } catch (\Throwable $e) {
            error_log('CronogramaFPController::listar — ' . $e->getMessage());
            $this->json(['data' => [], 'error' => 'Error al cargar el listado.']);
        }
    }

    public function get(): void
    {
        $id = (int)$this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }
        $a = $this->model->getDetalle($id);
        if (!$a) { $this->error('Actividad no encontrada.', 404); return; }
        $this->success('OK', ['actividad' => $a]);
    }

    public function save(): void
    {
        $this->requireCsrf();
        $this->requireRole(self::ROLES_EDICION);

        $id           = (int)$this->getPost('id_actividad', 0);
        $actividad    = trim((string)$this->getPost('actividad', ''));
        $orden        = max(0, (int)$this->getPost('numero_orden', 0));
        $idComponente = (int)$this->getPost('id_componente', 0);

        if ($actividad === '') { $this->error('La actividad es obligatoria.'); return; }
        if (mb_strlen($actividad) > 300) {
            $this->error('Actividad demasiado larga (máx. 300 caracteres).'); return;
        }
        if ($idComponente > 0 && !$this->model->componentePerteneceProyecto($idComponente)) {
            $this->error('El componente seleccionado no pertenece a su proyecto.'); return;
        }

        $data = [
            'id_componente' => $idComponente > 0 ? $idComponente : null,
            'numero_orden'  => $orden,
            'actividad'     => $actividad,
            'descripcion'   => $this->getPost('descripcion') ?: null,
            'responsable'   => $this->getPost('responsable') ?: null,
            'observaciones' => $this->getPost('observaciones') ?: null,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];
        // Marcas mensuales: TINYINT(1) — sanea cada uno
        foreach (CronogramaFPModel::MESES as $mes) {
            $data[$mes] = (int)((string)$this->getPost($mes, '0') === '1');
        }

        if ($id === 0) {
            $data['estado']     = 'pendiente';
            $data['created_by'] = $_SESSION['user']['id_usuario'] ?? null;
        }

        try {
            if ($id > 0 && !$this->model->getDetalle($id)) {
                $this->error('Actividad no encontrada en su proyecto.', 404); return;
            }
            $newId = $this->model->guardar($data, $id);
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'cronograma_fp', "ID:{$newId} — {$actividad}");
            $this->success($id ? 'Actividad actualizada.' : 'Actividad registrada.', ['id' => $newId]);
        } catch (\Throwable $e) {
            error_log('CronogramaFPController::save — ' . $e->getMessage());
            $this->error('Error al guardar la actividad.');
        }
    }

    public function estado(): void
    {
        $this->requireCsrf();
        $this->requireRole(self::ROLES_EDICION);

        $id     = (int)$this->getPost('id', 0);
        $estado = (string)$this->getPost('estado', '');
        if (!$id) { $this->error('ID no válido.'); return; }
        if (!in_array($estado, CronogramaFPModel::ESTADOS, true)) {
            $this->error('Estado no válido.'); return;
        }

        $a = $this->model->getDetalle($id);
        if (!$a) { $this->error('Actividad no encontrada en su proyecto.', 404); return; }

        try {
            $this->model->guardar(['estado' => $estado, 'updated_at' => date('Y-m-d H:i:s')], $id);
            $this->logAction('ESTADO_' . strtoupper($estado), 'cronograma_fp', "ID:{$id}");
            $this->success("Estado actualizado a: {$estado}");
        } catch (\Throwable $e) {
            error_log('CronogramaFPController::estado — ' . $e->getMessage());
            $this->error('Error al cambiar de estado.');
        }
    }

    public function delete(): void
    {
        $this->requireCsrf();
        $this->requireRole(self::ROLES_EDICION);

        $id = (int)$this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }

        $a = $this->model->getDetalle($id);
        if (!$a) { $this->error('Actividad no encontrada en su proyecto.', 404); return; }

        try {
            $this->model->softDelete($id);
            $this->logAction('ELIMINAR', 'cronograma_fp', "ID:{$id} — " . ($a['actividad'] ?? ''));
            $this->success('Actividad eliminada.');
        } catch (\Throwable $e) {
            error_log('CronogramaFPController::delete — ' . $e->getMessage());
            $this->error('Error al eliminar.');
        }
    }
}
