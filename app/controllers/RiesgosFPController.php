<?php
/**
 * RiesgosFPController — Matriz de riesgos del programa FPROG 2026.
 *
 * Seguridad (manifiesto SAG_DEMO):
 *   - requirePrograma + requireFprog (exclusivo del programa)
 *   - requireCsrf en mutaciones
 *   - requireRole en mutaciones (no basta con ocultar botones)
 *   - id_proyecto filtrado/sellado por el modelo
 *   - logAction en crear / editar / cambiar estado / eliminar
 */
class RiesgosFPController extends Controller
{
    private RiesgoFPModel $model;

    private const ROLES_EDICION = [
        'super_admin', 'coord_nacional', 'coord_pip',
        'admin', 'administrador', 'coordinador',
    ];

    public function __construct()
    {
        $this->requirePrograma();
        $this->requireFprog();
        $this->model = new RiesgoFPModel();
    }

    private function requireFprog(): void
    {
        if (($_SESSION['programa']['id'] ?? '') !== 'fprog') {
            if ($this->isAjax()) {
                $this->error('Riesgos sólo aplica al programa FPROG.', 403);
            }
            http_response_code(403);
            $this->redirect('/dashboard');
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  VISTA PRINCIPAL
    // ──────────────────────────────────────────────────────────────
    public function index(): void
    {
        if (!Database::main()->tablaExiste('sag_fp_riesgos')) {
            $tituloModulo = 'Riesgos';
            $tablasFalta  = ['sag_fp_riesgos'];
            $migraciones  = ['migracion_015_fp_riesgos_equipo_cronograma.sql'];
            $pageTitle    = 'Riesgos — inicialización pendiente · ' . APP_NAME;
            $this->view('_partial/migracion_pendiente',
                compact('tituloModulo', 'tablasFalta', 'migraciones', 'pageTitle'));
            return;
        }
        $resumen   = $this->model->getResumen();
        $pageTitle = 'Riesgos — ' . ($_SESSION['programa']['sigla'] ?? 'FPROG') . ' · ' . APP_NAME;
        $this->view('riesgos_fp/index', compact('resumen', 'pageTitle'));
    }

    public function listar(): void
    {
        try {
            if (!Database::main()->tablaExiste('sag_fp_riesgos')) {
                $this->json(['data' => [], 'error' => 'Módulo no inicializado.']);
                return;
            }
            $filtros = [
                'categoria'    => (string)$this->getPost('categoria', ''),
                'estado'       => (string)$this->getPost('estado', ''),
                'probabilidad' => (string)$this->getPost('probabilidad', ''),
                'impacto'      => (string)$this->getPost('impacto', ''),
                'buscar'       => (string)$this->getPost('buscar', ''),
            ];
            $rows = $this->model->getListado($filtros);
            $data = array_map(function ($r) {
                // Nivel calculado del riesgo (alta×alto = crítico, etc.)
                $nivel = 'bajo';
                if ($r['probabilidad'] === 'alta' && $r['impacto'] === 'alto') $nivel = 'critico';
                elseif (($r['probabilidad'] === 'alta'  && $r['impacto'] === 'medio') ||
                        ($r['probabilidad'] === 'media' && $r['impacto'] === 'alto')) $nivel = 'alto';
                elseif (($r['probabilidad'] === 'media' && $r['impacto'] === 'medio') ||
                        ($r['probabilidad'] === 'alta'  && $r['impacto'] === 'bajo')  ||
                        ($r['probabilidad'] === 'baja'  && $r['impacto'] === 'alto')) $nivel = 'medio';

                return [
                    'id_riesgo'         => (int)$r['id_riesgo'],
                    'categoria'         => htmlspecialchars((string)$r['categoria'], ENT_QUOTES),
                    'descripcion'       => htmlspecialchars((string)$r['descripcion'], ENT_QUOTES),
                    'probabilidad'      => htmlspecialchars((string)$r['probabilidad'], ENT_QUOTES),
                    'impacto'           => htmlspecialchars((string)$r['impacto'], ENT_QUOTES),
                    'nivel'             => $nivel,
                    'medida_mitigacion' => htmlspecialchars((string)($r['medida_mitigacion'] ?? ''), ENT_QUOTES),
                    'responsable'       => htmlspecialchars((string)($r['responsable'] ?? ''), ENT_QUOTES),
                    'estado'            => htmlspecialchars((string)$r['estado'], ENT_QUOTES),
                ];
            }, $rows);
            $this->json(['data' => $data]);
        } catch (\Throwable $e) {
            error_log('RiesgosFPController::listar — ' . $e->getMessage());
            $this->json(['data' => [], 'error' => 'Error al cargar el listado.']);
        }
    }

    public function get(): void
    {
        $id = (int)$this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }
        $r = $this->model->getDetalle($id);
        if (!$r) { $this->error('Riesgo no encontrado.', 404); return; }
        $this->success('OK', ['riesgo' => $r]);
    }

    public function save(): void
    {
        $this->requireCsrf();
        $this->requireRole(self::ROLES_EDICION);

        $id          = (int)$this->getPost('id_riesgo', 0);
        $descripcion = trim((string)$this->getPost('descripcion', ''));
        $categoria   = (string)$this->getPost('categoria', 'operativo');
        $prob        = (string)$this->getPost('probabilidad', 'media');
        $impacto     = (string)$this->getPost('impacto', 'medio');

        if ($descripcion === '') { $this->error('La descripción del riesgo es obligatoria.'); return; }
        if (mb_strlen($descripcion) > 2000) {
            $this->error('Descripción demasiado larga (máx. 2000 caracteres).'); return;
        }
        if (!in_array($categoria, RiesgoFPModel::CATEGORIAS, true)) {
            $this->error('Categoría no válida.'); return;
        }
        if (!in_array($prob, RiesgoFPModel::PROBABILIDADES, true)) {
            $this->error('Probabilidad no válida.'); return;
        }
        if (!in_array($impacto, RiesgoFPModel::IMPACTOS, true)) {
            $this->error('Impacto no válido.'); return;
        }

        $data = [
            'categoria'         => $categoria,
            'descripcion'       => $descripcion,
            'probabilidad'      => $prob,
            'impacto'           => $impacto,
            'medida_mitigacion' => $this->getPost('medida_mitigacion') ?: null,
            'responsable'       => $this->getPost('responsable') ?: null,
            'observaciones'     => $this->getPost('observaciones') ?: null,
            'updated_at'        => date('Y-m-d H:i:s'),
        ];

        if ($id === 0) {
            $data['estado']     = 'identificado';
            $data['created_by'] = $_SESSION['user']['id_usuario'] ?? null;
        }

        try {
            if ($id > 0 && !$this->model->getDetalle($id)) {
                $this->error('Riesgo no encontrado en su proyecto.', 404); return;
            }
            $newId = $this->model->guardar($data, $id);
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'riesgos_fp', "ID:{$newId}");
            $this->success($id ? 'Riesgo actualizado.' : 'Riesgo registrado.', ['id' => $newId]);
        } catch (\Throwable $e) {
            error_log('RiesgosFPController::save — ' . $e->getMessage());
            $this->error('Error al guardar el riesgo.');
        }
    }

    public function estado(): void
    {
        $this->requireCsrf();
        $this->requireRole(self::ROLES_EDICION);

        $id     = (int)$this->getPost('id', 0);
        $estado = (string)$this->getPost('estado', '');
        if (!$id) { $this->error('ID no válido.'); return; }
        if (!in_array($estado, RiesgoFPModel::ESTADOS, true)) {
            $this->error('Estado no válido.'); return;
        }

        $r = $this->model->getDetalle($id);
        if (!$r) { $this->error('Riesgo no encontrado en su proyecto.', 404); return; }

        try {
            $this->model->guardar(['estado' => $estado, 'updated_at' => date('Y-m-d H:i:s')], $id);
            $this->logAction('ESTADO_' . strtoupper($estado), 'riesgos_fp', "ID:{$id}");
            $this->success("Estado actualizado a: {$estado}");
        } catch (\Throwable $e) {
            error_log('RiesgosFPController::estado — ' . $e->getMessage());
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
        if (!$r) { $this->error('Riesgo no encontrado en su proyecto.', 404); return; }

        try {
            $this->model->softDelete($id);
            $this->logAction('ELIMINAR', 'riesgos_fp', "ID:{$id}");
            $this->success('Riesgo eliminado.');
        } catch (\Throwable $e) {
            error_log('RiesgosFPController::delete — ' . $e->getMessage());
            $this->error('Error al eliminar.');
        }
    }
}
