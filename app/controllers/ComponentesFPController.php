<?php
/**
 * ComponentesFPController — Componentes del programa FPROG 2026.
 *
 * Seguridad (manifiesto SAG_DEMO):
 *   - requirePrograma()   sesión + programa activo.
 *   - requireCsrf()       en TODAS las acciones de mutación.
 *   - requireRole()       roles autorizados; ocultar botones no es autorización.
 *   - id_proyecto         filtrado/sellado en servidor por ComponenteFPModel.
 *   - logAction()         crear, editar, cambiar estado, eliminar.
 *
 * Restricción de programa: este módulo es exclusivo de FPROG. Si el
 * programa activo no es 'fprog' se redirige.
 */
class ComponentesFPController extends Controller
{
    private ComponenteFPModel $model;

    private const ROLES_EDICION = [
        'super_admin', 'coord_nacional', 'coord_pip',
        'admin', 'administrador', 'coordinador',
    ];

    public function __construct()
    {
        $this->requirePrograma();
        $this->requireFprog();
        $this->model = new ComponenteFPModel();
    }

    /**
     * Bloqueo de programa: componentes sólo aplican a FPROG.
     */
    private function requireFprog(): void
    {
        if (($_SESSION['programa']['id'] ?? '') !== 'fprog') {
            if ($this->isAjax()) {
                $this->error('Componentes sólo aplica al programa FPROG.', 403);
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
        // Si la migración 013 no fue aplicada, renderiza mensaje amigable
        // en vez de propagar una excepción PDO al navegador (manifiesto).
        if (!Database::main()->tablaExiste('sag_fp_componentes')) {
            $tituloModulo = 'Componentes';
            $tablasFalta  = ['sag_fp_componentes'];
            $migraciones  = ['migracion_013_fp_componentes.sql'];
            $pageTitle    = 'Componentes — inicialización pendiente · ' . APP_NAME;
            $this->view('_partial/migracion_pendiente',
                compact('tituloModulo', 'tablasFalta', 'migraciones', 'pageTitle'));
            return;
        }

        $resumen   = $this->model->getResumen();
        $pageTitle = 'Componentes — ' . ($_SESSION['programa']['sigla'] ?? 'FPROG') . ' · ' . APP_NAME;
        $this->view('componentes_fp/index', compact('resumen', 'pageTitle'));
    }

    // ──────────────────────────────────────────────────────────────
    //  LISTADO AJAX
    // ──────────────────────────────────────────────────────────────
    public function listar(): void
    {
        try {
            // Defensa: si la tabla no existe (migración pendiente), no propagar PDO
            if (!Database::main()->tablaExiste('sag_fp_componentes')) {
                $this->json(['data' => [], 'error' => 'Módulo no inicializado.']);
                return;
            }
            $filtros = [
                'estado'    => (string)$this->getPost('estado',    ''),
                'categoria' => (string)$this->getPost('categoria', ''),
                'buscar'    => (string)$this->getPost('buscar',    ''),
            ];
            $rows = $this->model->getListado($filtros);

            $data = array_map(function ($c) {
                $pres = (float)$c['presupuesto_asignado'];
                $ejec = (float)$c['presupuesto_ejecutado'];
                $pctP = $pres > 0 ? round(($ejec / $pres) * 100, 1) : 0;

                $meta    = (float)$c['meta_valor'];
                $avance  = (float)$c['avance_valor'];
                $pctM    = $meta > 0 ? round(($avance / $meta) * 100, 1) : 0;

                return [
                    'id_componente'         => (int)$c['id_componente'],
                    'numero_romano'         => htmlspecialchars((string)($c['numero_romano'] ?? '—'), ENT_QUOTES),
                    'nombre'                => htmlspecialchars((string)$c['nombre'], ENT_QUOTES),
                    'categoria'             => htmlspecialchars((string)$c['categoria'], ENT_QUOTES),
                    'presupuesto_asignado'  => $pres,
                    'presupuesto_ejecutado' => $ejec,
                    'pct_ejecucion'         => $pctP,
                    'meta_unidad'           => htmlspecialchars((string)($c['meta_unidad'] ?? ''), ENT_QUOTES),
                    'meta_valor'            => $meta,
                    'avance_valor'          => $avance,
                    'pct_avance'            => $pctM,
                    'estado'                => htmlspecialchars((string)$c['estado'], ENT_QUOTES),
                    'fecha_inicio'          => $c['fecha_inicio'],
                    'fecha_fin'             => $c['fecha_fin'],
                    'responsable'           => htmlspecialchars((string)($c['responsable'] ?? ''), ENT_QUOTES),
                ];
            }, $rows);

            $this->json(['data' => $data, 'resumen' => $this->model->getResumen()]);
        } catch (\Throwable $e) {
            error_log('ComponentesFPController::listar — ' . $e->getMessage());
            $this->json(['data' => [], 'error' => 'Error al cargar el listado.']);
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  OBTENER UN COMPONENTE
    // ──────────────────────────────────────────────────────────────
    public function get(): void
    {
        $id = (int)$this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }
        $c = $this->model->getDetalle($id);
        if (!$c) { $this->error('Componente no encontrado.', 404); return; }
        $this->success('OK', ['componente' => $c]);
    }

    // ──────────────────────────────────────────────────────────────
    //  GUARDAR (crear o actualizar)
    // ──────────────────────────────────────────────────────────────
    public function save(): void
    {
        $this->requireCsrf();
        $this->requireRole(self::ROLES_EDICION);

        $id        = (int)$this->getPost('id_componente', 0);
        $nombre    = trim((string)$this->getPost('nombre', ''));
        $numero    = trim((string)$this->getPost('numero_romano', ''));
        $categoria = (string)$this->getPost('categoria', 'otro');
        $presAsig  = (string)$this->getPost('presupuesto_asignado', '0');
        $presEjec  = (string)$this->getPost('presupuesto_ejecutado', '0');
        $metaVal   = (string)$this->getPost('meta_valor', '0');
        $avanceVal = (string)$this->getPost('avance_valor', '0');
        $fechaIni  = (string)$this->getPost('fecha_inicio', '');
        $fechaFin  = (string)$this->getPost('fecha_fin', '');

        // ── Validaciones ──
        if ($nombre === '') { $this->error('El nombre es obligatorio.'); return; }
        if (mb_strlen($nombre) > 200) {
            $this->error('Nombre demasiado largo (máx. 200 caracteres).'); return;
        }
        if ($numero !== '' && mb_strlen($numero) > 8) {
            $this->error('El número romano no puede exceder 8 caracteres.'); return;
        }
        // Si vino un número romano, validar formato y unicidad por proyecto
        if ($numero !== '' && !preg_match('/^[IVXLCDM]+$/i', $numero)) {
            $this->error('Número romano inválido (use I, II, III, IV, V, etc).'); return;
        }
        if ($numero !== '' && $this->model->numeroRomanoExiste(strtoupper($numero), $id)) {
            $this->error("Ya existe un componente con el número {$numero} en este programa."); return;
        }
        if (!in_array($categoria, ComponenteFPModel::CATEGORIAS, true)) {
            $this->error('Categoría no válida.'); return;
        }
        if (!is_numeric($presAsig) || (float)$presAsig < 0) {
            $this->error('Presupuesto asignado debe ser un número >= 0.'); return;
        }
        if ($presEjec !== '' && (!is_numeric($presEjec) || (float)$presEjec < 0)) {
            $this->error('Presupuesto ejecutado debe ser un número >= 0.'); return;
        }
        if ($metaVal !== '' && (!is_numeric($metaVal) || (float)$metaVal < 0)) {
            $this->error('Meta debe ser un número >= 0.'); return;
        }
        if ($avanceVal !== '' && (!is_numeric($avanceVal) || (float)$avanceVal < 0)) {
            $this->error('Avance debe ser un número >= 0.'); return;
        }
        // Fechas
        foreach (['fecha_inicio' => $fechaIni, 'fecha_fin' => $fechaFin] as $k => $v) {
            if ($v !== '') {
                $dt = \DateTime::createFromFormat('Y-m-d', $v);
                if (!$dt || $dt->format('Y-m-d') !== $v) {
                    $this->error("La {$k} no es válida."); return;
                }
            }
        }
        if ($fechaIni !== '' && $fechaFin !== '' && $fechaFin < $fechaIni) {
            $this->error('La fecha de fin no puede ser anterior al inicio.'); return;
        }

        $data = [
            'numero_romano'         => $numero !== '' ? strtoupper($numero) : null,
            'codigo'                => $this->getPost('codigo') ?: null,
            'nombre'                => $nombre,
            'descripcion'           => $this->getPost('descripcion') ?: null,
            'categoria'             => $categoria,
            'presupuesto_asignado'  => (float)$presAsig,
            'presupuesto_ejecutado' => (float)$presEjec,
            'moneda'                => $this->getPost('moneda', 'HNL') ?: 'HNL',
            'meta_unidad'           => $this->getPost('meta_unidad') ?: null,
            'meta_valor'            => (float)$metaVal,
            'avance_valor'          => (float)$avanceVal,
            'medio_verificacion'    => $this->getPost('medio_verificacion') ?: null,
            'fecha_inicio'          => $fechaIni ?: null,
            'fecha_fin'             => $fechaFin ?: null,
            'responsable'           => $this->getPost('responsable') ?: null,
            'observaciones'         => $this->getPost('observaciones') ?: null,
            'updated_at'            => date('Y-m-d H:i:s'),
        ];

        if ($id === 0) {
            $data['estado']     = 'planificado';
            $data['created_by'] = $_SESSION['user']['id_usuario'] ?? null;
        }

        try {
            // Si edita, verifica que el componente sea del proyecto activo (defensa explícita)
            if ($id > 0 && !$this->model->getDetalle($id)) {
                $this->error('Componente no encontrado en su proyecto.', 404); return;
            }
            $newId = $this->model->guardar($data, $id);
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'componentes_fp', "ID:{$newId} — {$nombre}");
            $this->success($id ? 'Componente actualizado.' : 'Componente registrado.', ['id' => $newId]);
        } catch (\Throwable $e) {
            error_log('ComponentesFPController::save — ' . $e->getMessage());
            $this->error('Error al guardar el componente.');
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  CAMBIO DE ESTADO
    // ──────────────────────────────────────────────────────────────
    public function estado(): void
    {
        $this->requireCsrf();
        $this->requireRole(self::ROLES_EDICION);

        $id     = (int)$this->getPost('id', 0);
        $estado = (string)$this->getPost('estado', '');
        if (!$id) { $this->error('ID no válido.'); return; }
        if (!in_array($estado, ComponenteFPModel::ESTADOS, true)) {
            $this->error('Estado no válido.'); return;
        }

        $c = $this->model->getDetalle($id);
        if (!$c) { $this->error('Componente no encontrado en su proyecto.', 404); return; }

        try {
            $this->model->guardar(['estado' => $estado, 'updated_at' => date('Y-m-d H:i:s')], $id);
            $this->logAction('ESTADO_' . strtoupper($estado), 'componentes_fp', "ID:{$id}");
            $this->success("Estado actualizado a: {$estado}");
        } catch (\Throwable $e) {
            error_log('ComponentesFPController::estado — ' . $e->getMessage());
            $this->error('Error al cambiar de estado.');
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  SOFT DELETE
    // ──────────────────────────────────────────────────────────────
    public function delete(): void
    {
        $this->requireCsrf();
        $this->requireRole(self::ROLES_EDICION);

        $id = (int)$this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }

        $c = $this->model->getDetalle($id);
        if (!$c) { $this->error('Componente no encontrado en su proyecto.', 404); return; }

        try {
            $this->model->softDelete($id);
            $this->logAction('ELIMINAR', 'componentes_fp', "ID:{$id} — " . ($c['nombre'] ?? ''));
            $this->success('Componente eliminado.');
        } catch (\Throwable $e) {
            error_log('ComponentesFPController::delete — ' . $e->getMessage());
            $this->error('Error al eliminar.');
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  API: lista corta para select (usado por Metas e Indicadores)
    // ──────────────────────────────────────────────────────────────
    public function apiLista(): void
    {
        try {
            $rows = $this->model->getListado([]);
            $out  = array_map(fn($c) => [
                'id_componente' => (int)$c['id_componente'],
                'numero_romano' => $c['numero_romano'] ?? '',
                'nombre'        => $c['nombre'],
            ], $rows);
            $this->json(['data' => $out]);
        } catch (\Throwable $e) {
            error_log('ComponentesFPController::apiLista — ' . $e->getMessage());
            $this->json(['data' => []]);
        }
    }
}
