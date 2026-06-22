<?php
/**
 * MetasController — Metas por programa SAG.
 *
 * Seguridad (manifiesto SAG_DEMO):
 *   - requirePrograma()   asegura sesión + programa activo.
 *   - requireCsrf()       en TODAS las acciones de mutación.
 *   - requireRole()       roles autorizados; ocultar botones no es autorización.
 *   - id_proyecto         filtrado/sellado en servidor por MetaModel (Model base scoped).
 *   - logAction()         crear, editar, cambiar estado, eliminar.
 *
 * Patrón: copia del FortalecimientoController para mantener uniformidad.
 */
class MetasController extends Controller
{
    private MetaModel $model;

    // Roles que pueden gestionar metas. Lectura: cualquier rol con programa activo.
    private const ROLES_EDICION = [
        'super_admin', 'coord_nacional', 'coord_pip',
        'admin', 'administrador', 'coordinador',
    ];

    public function __construct()
    {
        $this->requirePrograma();
        $this->requireFprog();
        $this->model = new MetaModel();
    }

    /**
     * Metas son exclusivas del programa FPROG.
     * Bloquea acceso por URL desde PIPs (defensa en profundidad — el sidebar
     * tampoco las muestra, pero un usuario con la URL no debe poder entrar).
     */
    private function requireFprog(): void
    {
        if (($_SESSION['programa']['id'] ?? '') !== 'fprog') {
            if ($this->isAjax()) {
                $this->error('Metas sólo aplica al programa FPROG.', 403);
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
        // Si la migración 011 no fue aplicada, mostrar mensaje amigable
        // en vez de propagar excepción PDO al navegador.
        if (!Database::main()->tablaExiste('sag_metas')) {
            $tituloModulo = 'Metas';
            $tablasFalta  = ['sag_metas'];
            $migraciones  = ['migracion_011_metas_indicadores.sql'];
            $pageTitle    = 'Metas — inicialización pendiente · ' . APP_NAME;
            $this->view('_partial/migracion_pendiente',
                compact('tituloModulo', 'tablasFalta', 'migraciones', 'pageTitle'));
            return;
        }

        $resumen = $this->model->getResumen();
        // Componentes del proyecto activo, para llenar select del modal y filtro
        // (sólo aplica a FPROG; para PIPs viene vacío y la columna se oculta)
        $componentes = [];
        try {
            $componentes = Database::programa()->fetchAll(
                "SELECT id_componente, numero_romano, nombre
                   FROM sag_fp_componentes
                  WHERE id_proyecto = ? AND activo = 1
                  ORDER BY CASE numero_romano
                    WHEN 'I' THEN 1 WHEN 'II' THEN 2 WHEN 'III' THEN 3
                    WHEN 'IV' THEN 4 WHEN 'V' THEN 5 WHEN 'VI' THEN 6
                    WHEN 'VII' THEN 7 WHEN 'VIII' THEN 8 WHEN 'IX' THEN 9
                    WHEN 'X' THEN 10 ELSE 99 END, id_componente",
                [Database::proyectoId()]
            );
        } catch (\Throwable $e) {
            // tabla aún no existe (FPROG no migrado) → seguimos sin componentes
            error_log('MetasController::index componentes — ' . $e->getMessage());
        }
        $pageTitle = 'Metas — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;
        $this->view('metas/index', compact('resumen', 'componentes', 'pageTitle'));
    }

    // ──────────────────────────────────────────────────────────────
    //  LISTADO AJAX
    // ──────────────────────────────────────────────────────────────
    public function listar(): void
    {
        try {
            if (!Database::main()->tablaExiste('sag_metas')) {
                $this->json(['data' => [], 'error' => 'Módulo no inicializado.']);
                return;
            }
            $filtros = [
                'estado'        => (string)$this->getPost('estado', ''),
                'periodo'       => (string)$this->getPost('periodo', ''),
                'id_componente' => (int)$this->getPost('id_componente', 0),
                'buscar'        => (string)$this->getPost('buscar', ''),
            ];
            $rows = $this->model->getListado($filtros);

            $data = array_map(function ($m) {
                $pct = ((float)$m['valor_objetivo']) > 0
                    ? round(((float)$m['valor_actual'] / (float)$m['valor_objetivo']) * 100, 1)
                    : 0;
                // Etiqueta del componente vinculado (si existe)
                $componente = '—';
                if (!empty($m['componente_nombre'])) {
                    $num = $m['componente_numero'] ? $m['componente_numero'] . '. ' : '';
                    $componente = $num . $m['componente_nombre'];
                }
                return [
                    'id_meta'        => (int)$m['id_meta'],
                    'codigo'         => htmlspecialchars((string)($m['codigo'] ?? '—'), ENT_QUOTES),
                    'nombre'         => htmlspecialchars((string)$m['nombre'], ENT_QUOTES),
                    'periodo'        => htmlspecialchars((string)$m['periodo'], ENT_QUOTES),
                    'unidad_medida'  => htmlspecialchars((string)($m['unidad_medida'] ?? ''), ENT_QUOTES),
                    'valor_objetivo' => (float)$m['valor_objetivo'],
                    'valor_actual'   => (float)$m['valor_actual'],
                    'avance_pct'     => $pct,
                    'estado'         => htmlspecialchars((string)$m['estado'], ENT_QUOTES),
                    'fecha_inicio'   => $m['fecha_inicio'],
                    'fecha_fin'      => $m['fecha_fin'],
                    'responsable'    => htmlspecialchars((string)($m['responsable'] ?? ''), ENT_QUOTES),
                    'componente'     => htmlspecialchars($componente, ENT_QUOTES),
                ];
            }, $rows);

            $this->json(['data' => $data, 'resumen' => $this->model->getResumen()]);
        } catch (\Throwable $e) {
            error_log('MetasController::listar — ' . $e->getMessage());
            $this->json(['data' => [], 'error' => 'Error al cargar el listado.']);
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  OBTENER UNA META
    // ──────────────────────────────────────────────────────────────
    public function get(): void
    {
        $id = (int)$this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }

        $meta = $this->model->getDetalle($id);
        if (!$meta) { $this->error('Meta no encontrada.', 404); return; }

        $this->success('OK', ['meta' => $meta]);
    }

    // ──────────────────────────────────────────────────────────────
    //  GUARDAR (crear o actualizar)
    // ──────────────────────────────────────────────────────────────
    public function save(): void
    {
        $this->requireCsrf();
        $this->requireRole(self::ROLES_EDICION);

        $id           = (int)$this->getPost('id_meta', 0);
        $nombre       = trim((string)$this->getPost('nombre', ''));
        $periodo      = (string)$this->getPost('periodo', 'anual');
        $valorObj     = (string)$this->getPost('valor_objetivo', '');
        $valorAct     = (string)$this->getPost('valor_actual', '0');
        $fechaIni     = (string)$this->getPost('fecha_inicio', '');
        $fechaFin     = (string)$this->getPost('fecha_fin', '');
        $idComponente = (int)$this->getPost('id_componente', 0);

        // ── Validaciones ──
        if ($nombre === '') { $this->error('El nombre es obligatorio.'); return; }
        if (mb_strlen($nombre) > 200) {
            $this->error('Nombre demasiado largo (máx. 200 caracteres).'); return;
        }
        if (!in_array($periodo, MetaModel::PERIODOS, true)) {
            $this->error('Periodo no válido.'); return;
        }
        if ($valorObj === '' || !is_numeric($valorObj) || (float)$valorObj < 0) {
            $this->error('El valor objetivo debe ser un número >= 0.'); return;
        }
        if ($valorAct !== '' && (!is_numeric($valorAct) || (float)$valorAct < 0)) {
            $this->error('El valor actual debe ser un número >= 0.'); return;
        }
        if ($fechaIni !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d', $fechaIni);
            if (!$dt || $dt->format('Y-m-d') !== $fechaIni) {
                $this->error('Fecha de inicio no válida.'); return;
            }
        }
        if ($fechaFin !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d', $fechaFin);
            if (!$dt || $dt->format('Y-m-d') !== $fechaFin) {
                $this->error('Fecha de fin no válida.'); return;
            }
            if ($fechaIni !== '' && $fechaFin < $fechaIni) {
                $this->error('La fecha de fin no puede ser anterior al inicio.'); return;
            }
        }
        // Validar que el componente (si vino) pertenezca al proyecto activo
        if ($idComponente > 0 && !$this->model->componentePerteneceProyecto($idComponente)) {
            $this->error('El componente seleccionado no pertenece a su proyecto.'); return;
        }

        $data = [
            'id_componente'       => $idComponente > 0 ? $idComponente : null,
            'codigo'              => $this->getPost('codigo') ?: null,
            'nombre'              => $nombre,
            'descripcion'         => $this->getPost('descripcion') ?: null,
            'unidad_medida'       => $this->getPost('unidad_medida') ?: null,
            'valor_objetivo'      => (float)$valorObj,
            'valor_actual'        => (float)$valorAct,
            'periodo'             => $periodo,
            'fecha_inicio'        => $fechaIni ?: null,
            'fecha_fin'           => $fechaFin ?: null,
            'responsable'         => $this->getPost('responsable') ?: null,
            'fuente_verificacion' => $this->getPost('fuente_verificacion') ?: null,
            'observaciones'       => $this->getPost('observaciones') ?: null,
            'updated_at'          => date('Y-m-d H:i:s'),
        ];

        if ($id === 0) {
            $data['estado']     = 'planificada';
            $data['created_by'] = $_SESSION['user']['id_usuario'] ?? null;
        }

        try {
            // Si edita, verifica que la meta sea del proyecto activo (defensa explícita)
            if ($id > 0 && !$this->model->getDetalle($id)) {
                $this->error('Meta no encontrada en su proyecto.', 404); return;
            }
            $newId = $this->model->guardar($data, $id);
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'metas', "ID:{$newId} — {$nombre}");
            $this->success($id ? 'Meta actualizada.' : 'Meta registrada.', ['id' => $newId]);
        } catch (\Throwable $e) {
            error_log('MetasController::save — ' . $e->getMessage());
            $this->error('Error al guardar la meta.');
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
        if (!in_array($estado, MetaModel::ESTADOS, true)) {
            $this->error('Estado no válido.'); return;
        }

        $meta = $this->model->getDetalle($id);
        if (!$meta) { $this->error('Meta no encontrada en su proyecto.', 404); return; }

        try {
            $this->model->guardar(['estado' => $estado, 'updated_at' => date('Y-m-d H:i:s')], $id);
            $this->logAction('ESTADO_' . strtoupper($estado), 'metas', "ID:{$id}");
            $this->success("Estado actualizado a: {$estado}");
        } catch (\Throwable $e) {
            error_log('MetasController::estado — ' . $e->getMessage());
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

        $meta = $this->model->getDetalle($id);
        if (!$meta) { $this->error('Meta no encontrada en su proyecto.', 404); return; }

        try {
            $this->model->softDelete($id);
            $this->logAction('ELIMINAR', 'metas', "ID:{$id} — " . ($meta['nombre'] ?? ''));
            $this->success('Meta eliminada.');
        } catch (\Throwable $e) {
            error_log('MetasController::delete — ' . $e->getMessage());
            $this->error('Error al eliminar.');
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  API: lista corta para select (usado por Indicadores)
    // ──────────────────────────────────────────────────────────────
    public function apiLista(): void
    {
        try {
            $rows = $this->model->getListado([]);
            $out  = array_map(fn($m) => [
                'id_meta' => (int)$m['id_meta'],
                'codigo'  => $m['codigo'] ?? '',
                'nombre'  => $m['nombre'],
            ], $rows);
            $this->json(['data' => $out]);
        } catch (\Throwable $e) {
            error_log('MetasController::apiLista — ' . $e->getMessage());
            $this->json(['data' => []]);
        }
    }
}
