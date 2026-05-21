<?php
/**
 * InventariosController — Módulo de Inventarios de Incentivos
 *
 * ⚠️ VERSIÓN MOCK (mayo 2026)
 * Esta versión NO está conectada a las tablas reales todavía. Los datos
 * son ficticios para validar UX. Las tablas viven en:
 *   · sag_proveedores
 *   · sag_bodegas
 *   · sag_inventario_productos
 *   · sag_inventario_cronogramas (+ sag_inventario_lineas)
 *   · sag_inventario_movimientos (kardex)
 *
 * Pendiente:
 *   - Reemplazar mock_* por queries reales contra Database::current()
 *   - Implementar guardado real de cronogramas con líneas
 *   - Cron para detectar líneas atrasadas (estado='atrasada')
 *   - Cuando una entrega es aprobada, descontar stock vía
 *     EntregasController -> InventariosController::registrarSalida()
 */
class InventariosController extends Controller
{
    public function __construct()
    {
        $this->requirePrograma();
    }

    // ════════════════════════════════════════════════════════════
    //  VISTA PRINCIPAL
    // ════════════════════════════════════════════════════════════
    public function index(): void
    {
        $pageTitle = 'Inventarios de Incentivos — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;

        $cronogramas = $this->mockCronogramas();
        $stock       = $this->mockStockPorBodega();
        $kardex      = $this->mockKardex();
        $kpis        = $this->calcularKpis($cronogramas, $stock);

        $proveedores = $this->mockProveedores();
        $bodegas     = $this->mockBodegas();
        $productos   = $this->mockProductos();

        $esAdmin = in_array($this->rolSlug(), ['admin', 'coordinador']);

        $this->view('inventarios/index', compact(
            'pageTitle', 'cronogramas', 'stock', 'kardex',
            'kpis', 'proveedores', 'bodegas', 'productos', 'esAdmin'
        ));
    }

    // ════════════════════════════════════════════════════════════
    //  AJAX endpoints
    // ════════════════════════════════════════════════════════════
    public function listarCronogramas(): void
    {
        $this->success('OK', ['cronogramas' => $this->mockCronogramas()]);
    }

    public function getCronograma(): void
    {
        $id = (int) $this->getPost('id', 0);
        foreach ($this->mockCronogramas() as $c) {
            if ($c['id_cronograma'] == $id) {
                $this->success('OK', ['cronograma' => $c]);
                return;
            }
        }
        $this->error('Cronograma no encontrado.');
    }

    public function saveCronograma(): void
    {
        $this->requireRole(['admin', 'coordinador']);

        $datos  = json_decode($this->getPost('datos', '[]'), true) ?: [];
        $lineas = json_decode($this->getPost('lineas', '[]'), true) ?: [];

        // Validaciones mínimas
        if (empty($datos['id_proveedor']))    { $this->error('Debe seleccionar el proveedor.');     return; }
        if (empty($datos['num_contrato']) && empty($datos['num_orden_compra'])) {
            $this->error('Debe indicar al menos número de contrato u orden de compra.');
            return;
        }
        if (count($lineas) === 0) { $this->error('Debe agregar al menos una línea al cronograma.'); return; }

        foreach ($lineas as $i => $l) {
            if (empty($l['id_producto']) || empty($l['id_bodega']) || empty($l['cantidad_programada']) || empty($l['fecha_programada'])) {
                $this->error('La línea #' . ($i + 1) . ' está incompleta.');
                return;
            }
        }

        // MOCK: aquí se haría INSERT a sag_inventario_cronogramas + lineas
        $id = rand(1000, 9999);
        $this->logAction('CREAR_CRONOGRAMA', 'inventarios', "Cronograma mock #{$id} con " . count($lineas) . ' líneas');
        $this->success("Cronograma creado (MOCK). En producción se guardarán " . count($lineas) . " líneas.", [
            'id_cronograma' => $id,
            'codigo'        => 'CRON-' . strtoupper($_SESSION['programa']['sigla'] ?? 'XXX') . '-' . date('Y') . '-' . str_pad($id, 4, '0', STR_PAD_LEFT),
        ]);
    }

    public function recibirLinea(): void
    {
        $this->requireRole(['admin', 'coordinador', 'tecnico']);

        $idLinea  = (int) $this->getPost('id_linea', 0);
        $cantidad = (float) $this->getPost('cantidad', 0);
        $fecha    = $this->getPost('fecha_recibida', date('Y-m-d'));
        $resp     = $this->getPost('responsable', $_SESSION['user']['nombre'] ?? '');

        if ($idLinea <= 0)    { $this->error('Línea inválida.');                 return; }
        if ($cantidad <= 0)   { $this->error('La cantidad debe ser mayor a 0.'); return; }

        // MOCK: aquí se haría:
        //   UPDATE sag_inventario_lineas SET cantidad_recibida = cantidad_recibida + ?, ...
        //   INSERT INTO sag_inventario_movimientos (tipo='entrada', ...) con saldo recalculado
        $this->logAction('RECIBIR_LINEA', 'inventarios', "Línea #{$idLinea} +{$cantidad} ({$resp})");
        $this->success("Se registró la recepción de {$cantidad} unidades (MOCK).", [
            'id_linea'  => $idLinea,
            'cantidad'  => $cantidad,
            'fecha'     => $fecha,
            'responsable' => $resp,
        ]);
    }

    /**
     * Elimina (o cancela) un cronograma completo.
     *  · Solo admin/coordinador
     *  · Si tiene movimientos de recepción ya registrados, lo deja en estado 'cancelado'
     *    en lugar de borrarlo físicamente (preserva trazabilidad del kardex).
     *  · Si no tiene recepciones, lo borra de verdad (DELETE cascada a líneas).
     */
    public function deleteCronograma(): void
    {
        $this->requireRole(['admin', 'coordinador']);

        $id = (int) $this->getPost('id', 0);
        if ($id <= 0) { $this->error('Cronograma inválido.'); return; }

        // MOCK: buscar en datos ficticios
        $cronograma = null;
        foreach ($this->mockCronogramas() as $c) {
            if ($c['id_cronograma'] == $id) { $cronograma = $c; break; }
        }
        if (!$cronograma) { $this->error('Cronograma no encontrado.'); return; }

        // Revisar si tiene líneas ya recibidas (parcial o total)
        $tieneRecepciones = false;
        foreach ($cronograma['lineas'] as $l) {
            if ((float)$l['cantidad_recibida'] > 0) { $tieneRecepciones = true; break; }
        }

        if ($tieneRecepciones) {
            // En producción:
            //   UPDATE sag_inventario_cronogramas SET estado='cancelado',
            //          observaciones = CONCAT(observaciones, '\nCancelado por <user> el <fecha>')
            //   WHERE id_cronograma = ?
            $this->logAction('CANCELAR_CRONOGRAMA', 'inventarios',
                "#{$id} ({$cronograma['codigo']}) — tenía recepciones, marcado como cancelado");
            $this->success(
                "Cronograma {$cronograma['codigo']} marcado como CANCELADO (no se eliminó porque tenía recepciones registradas en el kardex).",
                ['id_cronograma' => $id, 'accion' => 'cancelado']
            );
            return;
        }

        // En producción:
        //   DELETE FROM sag_inventario_cronogramas WHERE id_cronograma = ?
        //   (las líneas se borran en cascada por la FK ON DELETE CASCADE)
        $this->logAction('ELIMINAR_CRONOGRAMA', 'inventarios',
            "#{$id} ({$cronograma['codigo']}) — eliminado físicamente, sin recepciones");
        $this->success(
            "Cronograma {$cronograma['codigo']} eliminado correctamente.",
            ['id_cronograma' => $id, 'accion' => 'eliminado']
        );
    }

    public function kardex(): void
    {
        $idProducto = (int) $this->getPost('id_producto', 0);
        $idBodega   = (int) $this->getPost('id_bodega', 0);
        $movs = $this->mockKardex();
        if ($idProducto) $movs = array_filter($movs, fn($m) => $m['id_producto'] == $idProducto);
        if ($idBodega)   $movs = array_filter($movs, fn($m) => $m['id_bodega']   == $idBodega);
        $this->success('OK', ['movimientos' => array_values($movs)]);
    }

    public function stockPorBodega(): void
    {
        $this->success('OK', ['stock' => $this->mockStockPorBodega()]);
    }

    /**
     * Punto de entrada que EntregasController llama cuando aprueba una entrega.
     * Registra una salida en el kardex y descuenta del stock disponible.
     *
     * @param int   $idEntrega
     * @param int   $idBodega
     * @param array $productos  [ ['id_producto'=>X, 'cantidad'=>Y], ... ]
     * @return array  ['ok'=>bool,'msg'=>string,'movimientos'=>int[]]
     */
    public static function registrarSalida(int $idEntrega, int $idBodega, array $productos): array
    {
        // MOCK: en producción aquí se validaría stock y se haría INSERT al kardex
        $ids = [];
        foreach ($productos as $p) {
            $ids[] = rand(100000, 999999);
        }
        return [
            'ok'          => true,
            'msg'         => count($productos) . " salidas de stock registradas (MOCK) por entrega #{$idEntrega}",
            'movimientos' => $ids,
        ];
    }

    // ════════════════════════════════════════════════════════════
    //  KPIs
    // ════════════════════════════════════════════════════════════
    private function calcularKpis(array $cronogramas, array $stock): array
    {
        $totalRecibido = 0; $totalProgramado = 0; $cronActivos = 0;
        foreach ($cronogramas as $c) {
            if (in_array($c['estado'], ['vigente','borrador'])) $cronActivos++;
            foreach ($c['lineas'] as $l) {
                $totalProgramado += (float) $l['cantidad_programada'];
                $totalRecibido   += (float) $l['cantidad_recibida'];
            }
        }
        $stockTotal = 0;
        foreach ($stock as $s) { $stockTotal += (float) $s['saldo']; }

        return [
            'cronogramas_activos' => $cronActivos,
            'total_programado'    => $totalProgramado,
            'total_recibido'      => $totalRecibido,
            'stock_disponible'    => $stockTotal,
            'avance_pct'          => $totalProgramado > 0 ? round(($totalRecibido / $totalProgramado) * 100, 1) : 0,
        ];
    }

    // ════════════════════════════════════════════════════════════
    //  MOCK DATA
    // ════════════════════════════════════════════════════════════
    private function mockProveedores(): array
    {
        return [
            ['id_proveedor' => 1, 'nombre' => 'Agroinsumos del Pacífico S. de R.L.', 'rtn' => '08019007654321'],
            ['id_proveedor' => 2, 'nombre' => 'Distribuidora Granos del Norte',      'rtn' => '05019007891234'],
            ['id_proveedor' => 3, 'nombre' => 'Fertilizantes Honduras S.A.',          'rtn' => '08019005566778'],
            ['id_proveedor' => 4, 'nombre' => 'Suministros Veterinarios Centroamericanos', 'rtn' => '15019003344556'],
        ];
    }

    private function mockBodegas(): array
    {
        return [
            ['id_bodega' => 1, 'codigo' => 'FM01', 'nombre' => 'Bodega Central FM-01',     'departamento' => 'Francisco Morazán'],
            ['id_bodega' => 2, 'codigo' => 'FM02', 'nombre' => 'Bodega Valle FM-02',       'departamento' => 'Francisco Morazán'],
            ['id_bodega' => 3, 'codigo' => 'CP01', 'nombre' => 'Bodega Santa Rosa CP-01',  'departamento' => 'Copán'],
            ['id_bodega' => 4, 'codigo' => 'CP02', 'nombre' => 'Bodega La Entrada CP-02',  'departamento' => 'Copán'],
            ['id_bodega' => 5, 'codigo' => 'OL01', 'nombre' => 'Bodega Juticalpa OL-01',  'departamento' => 'Olancho'],
            ['id_bodega' => 6, 'codigo' => 'OL02', 'nombre' => 'Bodega Campamento OL-02', 'departamento' => 'Olancho'],
        ];
    }

    private function mockProductos(): array
    {
        return [
            ['id_producto' => 1, 'codigo' => 'INS-FERT-001', 'nombre' => 'Fertilizante 18-46-0',    'unidad' => 'saco',   'presentacion' => 'saco 100 lb'],
            ['id_producto' => 2, 'codigo' => 'INS-FERT-002', 'nombre' => 'Urea 46%',                 'unidad' => 'saco',   'presentacion' => 'saco 100 lb'],
            ['id_producto' => 3, 'codigo' => 'INS-SEM-001',  'nombre' => 'Semilla de maíz híbrido', 'unidad' => 'bolsa',  'presentacion' => 'bolsa 20 kg'],
            ['id_producto' => 4, 'codigo' => 'INS-SEM-002',  'nombre' => 'Semilla de frijol rojo',  'unidad' => 'quintal','presentacion' => 'quintal 100 lb'],
            ['id_producto' => 5, 'codigo' => 'INS-VET-001',  'nombre' => 'Vacuna bovina triple',     'unidad' => 'dosis',  'presentacion' => 'frasco 50 dosis'],
        ];
    }

    private function mockCronogramas(): array
    {
        return [
            [
                'id_cronograma'    => 1,
                'codigo'           => 'CRON-PIPC-2026-0001',
                'proveedor'        => 'Agroinsumos del Pacífico S. de R.L.',
                'id_proveedor'     => 1,
                'num_contrato'     => 'CT-2026-PIPC-014',
                'num_orden_compra' => 'OC-2026-00125',
                'monto_total'      => 1850000.00,
                'fecha_firma'      => '2026-03-15',
                'fecha_inicio'     => '2026-04-01',
                'fecha_fin'        => '2026-08-30',
                'estado'           => 'vigente',
                'descripcion'      => 'Suministro de fertilizantes para temporada de cafetales 2026',
                'lineas' => [
                    ['id_linea'=>1, 'producto'=>'Fertilizante 18-46-0', 'id_producto'=>1, 'unidad'=>'saco', 'bodega'=>'Bodega Central FM-01', 'id_bodega'=>1,
                     'cantidad_programada'=>500, 'cantidad_recibida'=>500, 'fecha_programada'=>'2026-04-15', 'fecha_recibida'=>'2026-04-15', 'estado'=>'recibida'],
                    ['id_linea'=>2, 'producto'=>'Fertilizante 18-46-0', 'id_producto'=>1, 'unidad'=>'saco', 'bodega'=>'Bodega Santa Rosa CP-01', 'id_bodega'=>3,
                     'cantidad_programada'=>800, 'cantidad_recibida'=>800, 'fecha_programada'=>'2026-04-22', 'fecha_recibida'=>'2026-04-23', 'estado'=>'recibida'],
                    ['id_linea'=>3, 'producto'=>'Urea 46%',              'id_producto'=>2, 'unidad'=>'saco', 'bodega'=>'Bodega Santa Rosa CP-01', 'id_bodega'=>3,
                     'cantidad_programada'=>600, 'cantidad_recibida'=>350, 'fecha_programada'=>'2026-05-10', 'fecha_recibida'=>'2026-05-12', 'estado'=>'parcial'],
                    ['id_linea'=>4, 'producto'=>'Urea 46%',              'id_producto'=>2, 'unidad'=>'saco', 'bodega'=>'Bodega Juticalpa OL-01', 'id_bodega'=>5,
                     'cantidad_programada'=>400, 'cantidad_recibida'=>0,   'fecha_programada'=>'2026-06-05', 'fecha_recibida'=>null,       'estado'=>'pendiente'],
                ],
            ],
            [
                'id_cronograma'    => 2,
                'codigo'           => 'CRON-PIPC-2026-0002',
                'proveedor'        => 'Distribuidora Granos del Norte',
                'id_proveedor'     => 2,
                'num_contrato'     => 'CT-2026-PIPC-018',
                'num_orden_compra' => 'OC-2026-00147',
                'monto_total'      => 920000.00,
                'fecha_firma'      => '2026-04-02',
                'fecha_inicio'     => '2026-04-20',
                'fecha_fin'        => '2026-07-15',
                'estado'           => 'vigente',
                'descripcion'      => 'Semilla certificada para siembra de primera',
                'lineas' => [
                    ['id_linea'=>5, 'producto'=>'Semilla de maíz híbrido', 'id_producto'=>3, 'unidad'=>'bolsa', 'bodega'=>'Bodega Valle FM-02', 'id_bodega'=>2,
                     'cantidad_programada'=>300, 'cantidad_recibida'=>300, 'fecha_programada'=>'2026-04-28', 'fecha_recibida'=>'2026-04-30', 'estado'=>'recibida'],
                    ['id_linea'=>6, 'producto'=>'Semilla de frijol rojo',  'id_producto'=>4, 'unidad'=>'quintal','bodega'=>'Bodega La Entrada CP-02', 'id_bodega'=>4,
                     'cantidad_programada'=>180, 'cantidad_recibida'=>0,   'fecha_programada'=>'2026-05-15', 'fecha_recibida'=>null,       'estado'=>'atrasada'],
                ],
            ],
            [
                'id_cronograma'    => 3,
                'codigo'           => 'CRON-PIPC-2026-0003',
                'proveedor'        => 'Fertilizantes Honduras S.A.',
                'id_proveedor'     => 3,
                'num_contrato'     => null,
                'num_orden_compra' => 'OC-2026-00198',
                'monto_total'      => 425000.00,
                'fecha_firma'      => '2026-05-05',
                'fecha_inicio'     => '2026-05-20',
                'fecha_fin'        => '2026-06-30',
                'estado'           => 'borrador',
                'descripcion'      => 'Refuerzo de inventario bodegas Olancho',
                'lineas' => [
                    ['id_linea'=>7, 'producto'=>'Fertilizante 18-46-0', 'id_producto'=>1, 'unidad'=>'saco', 'bodega'=>'Bodega Campamento OL-02', 'id_bodega'=>6,
                     'cantidad_programada'=>250, 'cantidad_recibida'=>0, 'fecha_programada'=>'2026-05-25', 'fecha_recibida'=>null, 'estado'=>'pendiente'],
                ],
            ],
        ];
    }

    private function mockStockPorBodega(): array
    {
        // saldo actual por (bodega, producto) — calculado del kardex en producción
        return [
            ['id_bodega'=>1, 'bodega'=>'Bodega Central FM-01',    'id_producto'=>1, 'producto'=>'Fertilizante 18-46-0',    'unidad'=>'saco',    'saldo'=>460],
            ['id_bodega'=>2, 'bodega'=>'Bodega Valle FM-02',      'id_producto'=>3, 'producto'=>'Semilla de maíz híbrido', 'unidad'=>'bolsa',   'saldo'=>280],
            ['id_bodega'=>3, 'bodega'=>'Bodega Santa Rosa CP-01', 'id_producto'=>1, 'producto'=>'Fertilizante 18-46-0',    'unidad'=>'saco',    'saldo'=>720],
            ['id_bodega'=>3, 'bodega'=>'Bodega Santa Rosa CP-01', 'id_producto'=>2, 'producto'=>'Urea 46%',                 'unidad'=>'saco',    'saldo'=>290],
            ['id_bodega'=>4, 'bodega'=>'Bodega La Entrada CP-02', 'id_producto'=>4, 'producto'=>'Semilla de frijol rojo',  'unidad'=>'quintal', 'saldo'=>0],
            ['id_bodega'=>5, 'bodega'=>'Bodega Juticalpa OL-01', 'id_producto'=>2, 'producto'=>'Urea 46%',                 'unidad'=>'saco',    'saldo'=>0],
            ['id_bodega'=>6, 'bodega'=>'Bodega Campamento OL-02','id_producto'=>1, 'producto'=>'Fertilizante 18-46-0',    'unidad'=>'saco',    'saldo'=>0],
        ];
    }

    private function mockKardex(): array
    {
        return [
            ['id_movimiento'=>1, 'fecha'=>'2026-04-15 09:20:00', 'tipo'=>'entrada', 'id_producto'=>1, 'producto'=>'Fertilizante 18-46-0', 'id_bodega'=>1, 'bodega'=>'Bodega Central FM-01', 'cantidad'=>500, 'saldo'=>500, 'origen'=>'cronograma', 'id_origen'=>1, 'descripcion'=>'Recepción CRON-PIPC-2026-0001 línea 1'],
            ['id_movimiento'=>2, 'fecha'=>'2026-04-23 14:45:00', 'tipo'=>'entrada', 'id_producto'=>1, 'producto'=>'Fertilizante 18-46-0', 'id_bodega'=>3, 'bodega'=>'Bodega Santa Rosa CP-01','cantidad'=>800, 'saldo'=>800, 'origen'=>'cronograma', 'id_origen'=>2, 'descripcion'=>'Recepción CRON-PIPC-2026-0001 línea 2'],
            ['id_movimiento'=>3, 'fecha'=>'2026-04-30 08:10:00', 'tipo'=>'entrada', 'id_producto'=>3, 'producto'=>'Semilla maíz híbrido',  'id_bodega'=>2, 'bodega'=>'Bodega Valle FM-02',      'cantidad'=>300, 'saldo'=>300, 'origen'=>'cronograma', 'id_origen'=>5, 'descripcion'=>'Recepción CRON-PIPC-2026-0002 línea 1'],
            ['id_movimiento'=>4, 'fecha'=>'2026-05-08 10:30:00', 'tipo'=>'salida',  'id_producto'=>1, 'producto'=>'Fertilizante 18-46-0', 'id_bodega'=>1, 'bodega'=>'Bodega Central FM-01', 'cantidad'=>4,   'saldo'=>496, 'origen'=>'entrega',    'id_origen'=>1001, 'descripcion'=>'Entrega ENT-1001 (María C. López)'],
            ['id_movimiento'=>5, 'fecha'=>'2026-05-08 11:00:00', 'tipo'=>'salida',  'id_producto'=>1, 'producto'=>'Fertilizante 18-46-0', 'id_bodega'=>1, 'bodega'=>'Bodega Central FM-01', 'cantidad'=>4,   'saldo'=>492, 'origen'=>'entrega',    'id_origen'=>1002, 'descripcion'=>'Entrega ENT-1002 (Juan C. Hernández, parcial)'],
            ['id_movimiento'=>6, 'fecha'=>'2026-05-12 09:00:00', 'tipo'=>'entrada', 'id_producto'=>2, 'producto'=>'Urea 46%',              'id_bodega'=>3, 'bodega'=>'Bodega Santa Rosa CP-01','cantidad'=>350, 'saldo'=>350, 'origen'=>'cronograma', 'id_origen'=>3, 'descripcion'=>'Recepción parcial CRON-PIPC-2026-0001 línea 3'],
            ['id_movimiento'=>7, 'fecha'=>'2026-05-08 09:00:00', 'tipo'=>'salida',  'id_producto'=>1, 'producto'=>'Fertilizante 18-46-0', 'id_bodega'=>3, 'bodega'=>'Bodega Santa Rosa CP-01','cantidad'=>8,   'saldo'=>792, 'origen'=>'entrega',    'id_origen'=>1005, 'descripcion'=>'Entrega ENT-1005 (Ana Lucía Vásquez)'],
            ['id_movimiento'=>8, 'fecha'=>'2026-05-08 11:30:00', 'tipo'=>'salida',  'id_producto'=>1, 'producto'=>'Fertilizante 18-46-0', 'id_bodega'=>3, 'bodega'=>'Bodega Santa Rosa CP-01','cantidad'=>4,   'saldo'=>788, 'origen'=>'entrega',    'id_origen'=>1006, 'descripcion'=>'Entrega ENT-1006 (Manuel J. Ramos)'],
        ];
    }
}
