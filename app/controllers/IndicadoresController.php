<?php
/**
 * IndicadoresController — Indicadores por programa SAG.
 *
 * Patrón idéntico al MetasController. Cuando se vincula a una meta,
 * se valida que la meta pertenezca al proyecto activo.
 */
class IndicadoresController extends Controller
{
    private IndicadorModel $model;

    private const ROLES_EDICION = [
        'super_admin', 'coord_nacional', 'coord_pip',
        'admin', 'administrador', 'coordinador',
    ];

    public function __construct()
    {
        $this->requirePrograma();
        $this->requireFprog();
        $this->model = new IndicadorModel();
    }

    /**
     * Indicadores son exclusivos del programa FPROG.
     * Defensa en profundidad: además del sidebar, bloquea URL directa.
     */
    private function requireFprog(): void
    {
        if (($_SESSION['programa']['id'] ?? '') !== 'fprog') {
            if ($this->isAjax()) {
                $this->error('Indicadores sólo aplica al programa FPROG.', 403);
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
        $resumen   = $this->model->getResumen();
        // Metas para el select (filtra por proyecto activo dentro del modelo)
        $metaModel = new MetaModel();
        $metas     = $metaModel->getListado([]);
        // Componentes del proyecto (sólo FPROG los tiene)
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
            error_log('IndicadoresController::index componentes — ' . $e->getMessage());
        }
        $pageTitle = 'Indicadores — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;
        $this->view('indicadores/index', compact('resumen', 'metas', 'componentes', 'pageTitle'));
    }

    // ──────────────────────────────────────────────────────────────
    //  LISTADO AJAX
    // ──────────────────────────────────────────────────────────────
    public function listar(): void
    {
        try {
            $filtros = [
                'tipo'          => (string)$this->getPost('tipo', ''),
                'estado'        => (string)$this->getPost('estado', ''),
                'id_meta'       => (int)$this->getPost('id_meta', 0),
                'id_componente' => (int)$this->getPost('id_componente', 0),
                'buscar'        => (string)$this->getPost('buscar', ''),
            ];
            $rows = $this->model->getListado($filtros);

            $data = array_map(function ($i) {
                $pct = ((float)$i['valor_objetivo']) > 0
                    ? round(((float)$i['valor_actual'] / (float)$i['valor_objetivo']) * 100, 1)
                    : 0;
                $componente = '—';
                if (!empty($i['componente_nombre'])) {
                    $num = $i['componente_numero'] ? $i['componente_numero'] . '. ' : '';
                    $componente = $num . $i['componente_nombre'];
                }
                return [
                    'id_indicador'   => (int)$i['id_indicador'],
                    'codigo'         => htmlspecialchars((string)($i['codigo'] ?? '—'), ENT_QUOTES),
                    'nombre'         => htmlspecialchars((string)$i['nombre'], ENT_QUOTES),
                    'tipo'           => htmlspecialchars((string)$i['tipo'], ENT_QUOTES),
                    'unidad_medida'  => htmlspecialchars((string)($i['unidad_medida'] ?? ''), ENT_QUOTES),
                    'linea_base'     => $i['linea_base'] !== null ? (float)$i['linea_base'] : null,
                    'valor_objetivo' => (float)$i['valor_objetivo'],
                    'valor_actual'   => (float)$i['valor_actual'],
                    'avance_pct'     => $pct,
                    'frecuencia'     => htmlspecialchars((string)$i['frecuencia_medicion'], ENT_QUOTES),
                    'ultima_medicion'=> $i['ultima_medicion'],
                    'estado'         => htmlspecialchars((string)$i['estado'], ENT_QUOTES),
                    'meta_nombre'    => htmlspecialchars((string)($i['meta_nombre']  ?? '—'), ENT_QUOTES),
                    'componente'     => htmlspecialchars($componente, ENT_QUOTES),
                    'responsable'    => htmlspecialchars((string)($i['responsable']  ?? ''), ENT_QUOTES),
                ];
            }, $rows);

            $this->json(['data' => $data]);
        } catch (\Throwable $e) {
            error_log('IndicadoresController::listar — ' . $e->getMessage());
            $this->json(['data' => [], 'error' => 'Error al cargar el listado.']);
        }
    }

    public function get(): void
    {
        $id = (int)$this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }

        $ind = $this->model->getDetalle($id);
        if (!$ind) { $this->error('Indicador no encontrado.', 404); return; }

        $this->success('OK', ['indicador' => $ind]);
    }

    public function save(): void
    {
        $this->requireCsrf();
        $this->requireRole(self::ROLES_EDICION);

        $id        = (int)$this->getPost('id_indicador', 0);
        $nombre       = trim((string)$this->getPost('nombre', ''));
        $tipo         = (string)$this->getPost('tipo', 'producto');
        $frecuencia   = (string)$this->getPost('frecuencia_medicion', 'mensual');
        $valorObj     = (string)$this->getPost('valor_objetivo', '');
        $valorAct     = (string)$this->getPost('valor_actual', '0');
        $lineaBase    = (string)$this->getPost('linea_base', '');
        $ultimaMed    = (string)$this->getPost('ultima_medicion', '');
        $idMeta       = (int)$this->getPost('id_meta', 0);
        $idComponente = (int)$this->getPost('id_componente', 0);

        if ($nombre === '') { $this->error('El nombre es obligatorio.'); return; }
        if (mb_strlen($nombre) > 200) {
            $this->error('Nombre demasiado largo (máx. 200 caracteres).'); return;
        }
        if (!in_array($tipo, IndicadorModel::TIPOS, true)) {
            $this->error('Tipo no válido.'); return;
        }
        if (!in_array($frecuencia, IndicadorModel::FRECUENCIAS, true)) {
            $this->error('Frecuencia no válida.'); return;
        }
        if ($valorObj === '' || !is_numeric($valorObj) || (float)$valorObj < 0) {
            $this->error('El valor objetivo debe ser un número >= 0.'); return;
        }
        if ($valorAct !== '' && (!is_numeric($valorAct) || (float)$valorAct < 0)) {
            $this->error('El valor actual debe ser un número >= 0.'); return;
        }
        if ($lineaBase !== '' && !is_numeric($lineaBase)) {
            $this->error('La línea base debe ser un número.'); return;
        }
        if ($ultimaMed !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d', $ultimaMed);
            if (!$dt || $dt->format('Y-m-d') !== $ultimaMed) {
                $this->error('Fecha de última medición no válida.'); return;
            }
        }

        // Validar pertenencia de la meta al proyecto activo si vino una
        if ($idMeta > 0 && !$this->model->metaPerteneceProyecto($idMeta)) {
            $this->error('La meta seleccionada no pertenece a su proyecto.'); return;
        }
        // Validar pertenencia del componente al proyecto activo si vino uno
        if ($idComponente > 0 && !$this->model->componentePerteneceProyecto($idComponente)) {
            $this->error('El componente seleccionado no pertenece a su proyecto.'); return;
        }

        $data = [
            'id_meta'             => $idMeta > 0 ? $idMeta : null,
            'id_componente'       => $idComponente > 0 ? $idComponente : null,
            'codigo'              => $this->getPost('codigo') ?: null,
            'nombre'              => $nombre,
            'descripcion'         => $this->getPost('descripcion') ?: null,
            'tipo'                => $tipo,
            'unidad_medida'       => $this->getPost('unidad_medida') ?: null,
            'formula'             => $this->getPost('formula') ?: null,
            'linea_base'          => $lineaBase !== '' ? (float)$lineaBase : null,
            'valor_objetivo'      => (float)$valorObj,
            'valor_actual'        => (float)$valorAct,
            'frecuencia_medicion' => $frecuencia,
            'ultima_medicion'     => $ultimaMed ?: null,
            'fuente_datos'        => $this->getPost('fuente_datos') ?: null,
            'responsable'         => $this->getPost('responsable') ?: null,
            'observaciones'       => $this->getPost('observaciones') ?: null,
            'updated_at'          => date('Y-m-d H:i:s'),
        ];

        if ($id === 0) {
            $data['estado']     = 'activo';
            $data['created_by'] = $_SESSION['user']['id_usuario'] ?? null;
        }

        try {
            if ($id > 0 && !$this->model->getDetalle($id)) {
                $this->error('Indicador no encontrado en su proyecto.', 404); return;
            }
            $newId = $this->model->guardar($data, $id);
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'indicadores', "ID:{$newId} — {$nombre}");
            $this->success($id ? 'Indicador actualizado.' : 'Indicador registrado.', ['id' => $newId]);
        } catch (\Throwable $e) {
            error_log('IndicadoresController::save — ' . $e->getMessage());
            $this->error('Error al guardar el indicador.');
        }
    }

    public function estado(): void
    {
        $this->requireCsrf();
        $this->requireRole(self::ROLES_EDICION);

        $id     = (int)$this->getPost('id', 0);
        $estado = (string)$this->getPost('estado', '');
        if (!$id) { $this->error('ID no válido.'); return; }
        if (!in_array($estado, IndicadorModel::ESTADOS, true)) {
            $this->error('Estado no válido.'); return;
        }

        $ind = $this->model->getDetalle($id);
        if (!$ind) { $this->error('Indicador no encontrado en su proyecto.', 404); return; }

        try {
            $this->model->guardar(['estado' => $estado, 'updated_at' => date('Y-m-d H:i:s')], $id);
            $this->logAction('ESTADO_' . strtoupper($estado), 'indicadores', "ID:{$id}");
            $this->success("Estado actualizado a: {$estado}");
        } catch (\Throwable $e) {
            error_log('IndicadoresController::estado — ' . $e->getMessage());
            $this->error('Error al cambiar de estado.');
        }
    }

    public function delete(): void
    {
        $this->requireCsrf();
        $this->requireRole(self::ROLES_EDICION);

        $id = (int)$this->getPost('id', 0);
        if (!$id) { $this->error('ID no válido.'); return; }

        $ind = $this->model->getDetalle($id);
        if (!$ind) { $this->error('Indicador no encontrado en su proyecto.', 404); return; }

        try {
            $this->model->softDelete($id);
            $this->logAction('ELIMINAR', 'indicadores', "ID:{$id} — " . ($ind['nombre'] ?? ''));
            $this->success('Indicador eliminado.');
        } catch (\Throwable $e) {
            error_log('IndicadoresController::delete — ' . $e->getMessage());
            $this->error('Error al eliminar.');
        }
    }
}
