<?php
/**
 * InventariosController — Módulo de Inventarios de Incentivos
 *
 * Los cronogramas y sus líneas se almacenan en:
 *   · sag_proveedores
 *   · sag_bodegas
 *   · sag_inventario_productos
 *   · sag_inventario_cronogramas (+ sag_inventario_lineas)
 *   · sag_inventario_movimientos (kardex)
 *
 * Pendiente:
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

        $cronogramas = $this->getCronogramas();
        $stock       = $this->getStockPorBodega();
        $kardex      = $this->getKardex();
        $kpis        = $this->calcularKpis($cronogramas, $stock);

        $proveedores = $this->getProveedores();
        $bodegas     = $this->getBodegas();
        $productos   = $this->getProductos();

        $esAdmin = in_array($this->rolSlug(), ['admin', 'coordinador', 'admin_bodega'], true);

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
        $this->success('OK', ['cronogramas' => $this->getCronogramas()]);
    }

    public function getCronograma(): void
    {
        $id = (int) $this->getPost('id', 0);
        foreach ($this->getCronogramas() as $c) {
            if ($c['id_cronograma'] == $id) {
                $this->success('OK', ['cronograma' => $c]);
                return;
            }
        }
        $this->error('Cronograma no encontrado.');
    }

    public function saveCronograma(): void
    {
        $this->requireRole(['admin', 'coordinador', 'admin_bodega']);

        $datos  = json_decode($this->getPost('datos', '[]'), true) ?: [];
        $lineas = json_decode($this->getPost('lineas', '[]'), true) ?: [];

        $db  = Database::programa();
        $pid = Database::proyectoId();

        if (empty($datos['id_proveedor'])) { $this->error('Debe seleccionar el proveedor.'); return; }
        if (!$db->fetchOne("SELECT id_proveedor FROM sag_proveedores WHERE id_proveedor=? AND id_proyecto=? AND activo=1", [(int)$datos['id_proveedor'], $pid])) {
            $this->error('El proveedor seleccionado no pertenece al programa activo.'); return;
        }
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
            if (!$db->fetchOne("SELECT id_producto FROM sag_inventario_productos WHERE id_producto=? AND id_proyecto=? AND activo=1", [(int)$l['id_producto'], $pid])) {
                $this->error('El producto de la línea #' . ($i + 1) . ' no pertenece al programa.'); return;
            }
            if (!$db->fetchOne("SELECT id_bodega FROM sag_bodegas WHERE id_bodega=? AND id_proyecto=? AND activo=1", [(int)$l['id_bodega'], $pid])) {
                $this->error('La bodega de la línea #' . ($i + 1) . ' no pertenece al programa.'); return;
            }
        }

        try {
            $db->beginTransaction();
            $n = (int)($db->fetchOne("SELECT COUNT(*)+1 AS n FROM sag_inventario_cronogramas WHERE id_proyecto=?", [$pid])['n'] ?? 1);
            $codigo = 'CRON-' . strtoupper($_SESSION['programa']['sigla'] ?? 'XXX') . '-' . date('Y') . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
            $db->execute(
                "INSERT INTO sag_inventario_cronogramas
                 (id_proyecto,codigo,id_proveedor,num_contrato,num_orden_compra,descripcion,monto_total,fecha_inicio,fecha_fin,estado,created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,'borrador',?)",
                [$pid, $codigo, (int)$datos['id_proveedor'], ($datos['num_contrato'] ?? '') ?: null,
                 ($datos['num_orden_compra'] ?? '') ?: null, ($datos['descripcion'] ?? '') ?: null,
                 (float)($datos['monto_total'] ?? 0), ($datos['fecha_inicio'] ?? '') ?: null,
                 ($datos['fecha_fin'] ?? '') ?: null, $_SESSION['user']['id_usuario'] ?? null]
            );
            $id = (int)$db->lastInsertId();
            foreach ($lineas as $l) {
                $db->execute(
                    "INSERT INTO sag_inventario_lineas
                     (id_proyecto,id_cronograma,id_producto,id_bodega,cantidad_programada,fecha_programada,estado)
                     VALUES (?,?,?,?,?,?,'pendiente')",
                    [$pid, $id, (int)$l['id_producto'], (int)$l['id_bodega'],
                     (float)$l['cantidad_programada'], $l['fecha_programada']]
                );
            }
            $db->commit();
            $this->logAction('CREAR_CRONOGRAMA', 'inventarios', "#{$id} {$codigo} con " . count($lineas) . ' líneas');
            $this->success('Cronograma guardado correctamente.', ['id_cronograma' => $id, 'codigo' => $codigo]);
        } catch (Throwable $e) {
            $db->rollback();
            error_log('InventariosController::saveCronograma - ' . $e->getMessage());
            $this->error('No fue posible guardar el cronograma.');
        }
    }

    public function recibirLinea(): void
    {
        $this->requireRole(['admin', 'coordinador', 'admin_bodega', 'tecnico']);

        $idLinea  = (int) $this->getPost('id_linea', 0);
        $cantidad = (float) $this->getPost('cantidad', 0);
        $fecha    = $this->getPost('fecha_recibida', date('Y-m-d'));
        $resp     = $this->getPost('responsable', $_SESSION['user']['nombre'] ?? '');

        if ($idLinea <= 0)    { $this->error('Línea inválida.');                 return; }
        if ($cantidad <= 0)   { $this->error('La cantidad debe ser mayor a 0.'); return; }

        $db = Database::programa(); $pid = Database::proyectoId();
        $linea = $db->fetchOne("SELECT * FROM sag_inventario_lineas WHERE id_linea=? AND id_proyecto=?", [$idLinea, $pid]);
        if (!$linea) { $this->error('Línea no encontrada.'); return; }
        $pendiente = (float)$linea['cantidad_programada'] - (float)$linea['cantidad_recibida'];
        if ($cantidad > $pendiente) { $this->error('La cantidad supera lo pendiente por recibir.'); return; }
        try {
            $db->beginTransaction();
            $nuevoRecibido = (float)$linea['cantidad_recibida'] + $cantidad;
            $estado = $nuevoRecibido >= (float)$linea['cantidad_programada'] ? 'recibida' : 'parcial';
            $db->execute(
                "UPDATE sag_inventario_lineas SET cantidad_recibida=?,fecha_recibida=?,responsable_recepcion=?,estado=?
                  WHERE id_linea=? AND id_proyecto=?",
                [$nuevoRecibido, $fecha, $resp, $estado, $idLinea, $pid]
            );
            $saldo = (float)($db->fetchOne(
                "SELECT COALESCE(SUM(CASE WHEN tipo='entrada' THEN cantidad WHEN tipo='salida' THEN -cantidad ELSE cantidad END),0) AS saldo
                   FROM sag_inventario_movimientos WHERE id_proyecto=? AND id_producto=? AND id_bodega=?",
                [$pid, $linea['id_producto'], $linea['id_bodega']]
            )['saldo'] ?? 0) + $cantidad;
            $db->execute(
                "INSERT INTO sag_inventario_movimientos
                 (id_proyecto,fecha,tipo,id_producto,id_bodega,cantidad,saldo_resultante,origen,id_origen,descripcion,created_by)
                 VALUES (?,?,'entrada',?,?,?,?, 'cronograma',?,?,?)",
                [$pid, $fecha . ' ' . date('H:i:s'), $linea['id_producto'], $linea['id_bodega'], $cantidad, $saldo,
                 $idLinea, 'Recepción de línea de cronograma', $_SESSION['user']['id_usuario'] ?? null]
            );
            $db->commit();
            $this->logAction('RECIBIR_LINEA', 'inventarios', "Línea #{$idLinea} +{$cantidad} ({$resp})");
            $this->success("Se registró la recepción de {$cantidad} unidades.");
        } catch (Throwable $e) {
            $db->rollback();
            error_log('InventariosController::recibirLinea - ' . $e->getMessage());
            $this->error('No fue posible registrar la recepción.');
        }
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
        $this->requireRole(['admin', 'coordinador', 'admin_bodega']);

        $id = (int) $this->getPost('id', 0);
        if ($id <= 0) { $this->error('Cronograma inválido.'); return; }

        $db  = Database::programa();
        $pid = Database::proyectoId();
        $cronograma = $db->fetchOne(
            "SELECT * FROM sag_inventario_cronogramas WHERE id_cronograma=? AND id_proyecto=?",
            [$id, $pid]
        );
        if (!$cronograma) { $this->error('Cronograma no encontrado.'); return; }

        $tieneRecepciones = (bool)$db->fetchOne(
            "SELECT id_linea FROM sag_inventario_lineas
              WHERE id_cronograma=? AND id_proyecto=? AND cantidad_recibida>0 LIMIT 1",
            [$id, $pid]
        );

        if ($tieneRecepciones) {
            $db->execute(
                "UPDATE sag_inventario_cronogramas
                    SET estado='cancelado', observaciones=CONCAT(COALESCE(observaciones,''), ?)
                  WHERE id_cronograma=? AND id_proyecto=?",
                ["\nCancelado el " . date('d/m/Y H:i'), $id, $pid]
            );
            $db->execute(
                "UPDATE sag_inventario_lineas SET estado='cancelada'
                  WHERE id_cronograma=? AND id_proyecto=? AND cantidad_recibida=0",
                [$id, $pid]
            );
            $this->logAction('CANCELAR_CRONOGRAMA', 'inventarios',
                "#{$id} ({$cronograma['codigo']}) — tenía recepciones, marcado como cancelado");
            $this->success(
                "Cronograma {$cronograma['codigo']} marcado como CANCELADO (no se eliminó porque tenía recepciones registradas en el kardex).",
                ['id_cronograma' => $id, 'accion' => 'cancelado']
            );
            return;
        }

        $db->execute("DELETE FROM sag_inventario_cronogramas WHERE id_cronograma=? AND id_proyecto=?", [$id, $pid]);
        $this->logAction('ELIMINAR_CRONOGRAMA', 'inventarios',
            "#{$id} ({$cronograma['codigo']}) — eliminado físicamente, sin recepciones");
        $this->success(
            "Cronograma {$cronograma['codigo']} eliminado correctamente.",
            ['id_cronograma' => $id, 'accion' => 'eliminado']
        );
    }

    /** Lee un Excel/CSV y devuelve líneas validadas para el formulario. */
    public function importarExcel(): void
    {
        $this->requireRole(['admin', 'coordinador', 'admin_bodega']);
        if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $this->error('No se recibió el archivo.'); return;
        }
        if ($_FILES['archivo']['size'] > 5 * 1024 * 1024) {
            $this->error('El archivo no puede superar 5 MB.'); return;
        }
        $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
        try {
            $rows = $ext === 'csv'
                ? $this->parsearCsv($_FILES['archivo']['tmp_name'])
                : ($ext === 'xlsx' ? $this->parsearXlsx($_FILES['archivo']['tmp_name']) : []);
        } catch (Throwable $e) {
            error_log('InventariosController::importarExcel - ' . $e->getMessage());
            $this->error('No se pudo leer el archivo. Use la plantilla indicada.'); return;
        }
        if (!$rows) { $this->error('El archivo no contiene filas válidas.'); return; }

        $productos = []; foreach ($this->getProductos() as $p) $productos[strtoupper($p['codigo'])] = $p;
        $bodegas = []; foreach ($this->getBodegas() as $b) $bodegas[strtoupper($b['codigo'])] = $b;
        $lineas = []; $errores = [];
        foreach ($rows as $i => $r) {
            $fila = $i + 2;
            $codProducto = strtoupper(trim((string)($r['codigo_producto'] ?? '')));
            $codBodega   = strtoupper(trim((string)($r['codigo_bodega'] ?? '')));
            $fecha       = $this->normalizarFechaExcel((string)($r['fecha_programada'] ?? ''));
            $cantidad    = (float)str_replace(',', '', (string)($r['cantidad'] ?? '0'));
            if (!isset($productos[$codProducto])) { $errores[] = "Fila {$fila}: producto {$codProducto} no existe."; continue; }
            if (!isset($bodegas[$codBodega])) { $errores[] = "Fila {$fila}: bodega {$codBodega} no existe."; continue; }
            if (!$fecha) { $errores[] = "Fila {$fila}: fecha inválida."; continue; }
            if ($cantidad <= 0) { $errores[] = "Fila {$fila}: cantidad debe ser mayor a cero."; continue; }
            $lineas[] = [
                'id_producto' => $productos[$codProducto]['id_producto'],
                'producto' => $productos[$codProducto]['nombre'],
                'unidad' => $productos[$codProducto]['unidad'],
                'id_bodega' => $bodegas[$codBodega]['id_bodega'],
                'bodega' => $bodegas[$codBodega]['nombre'],
                'fecha_programada' => $fecha,
                'cantidad_programada' => $cantidad,
            ];
        }
        if (!$lineas) {
            $detalle = $errores ? ' ' . implode(' ', array_slice($errores, 0, 3)) : '';
            $this->error('Ninguna fila pudo importarse.' . $detalle);
            return;
        }
        $this->success(count($lineas) . ' líneas importadas.', ['lineas' => $lineas, 'errores' => $errores]);
    }

    public function kardex(): void
    {
        $idProducto = (int) $this->getPost('id_producto', 0);
        $idBodega   = (int) $this->getPost('id_bodega', 0);
        $movs = $this->getKardex();
        if ($idProducto) $movs = array_filter($movs, fn($m) => $m['id_producto'] == $idProducto);
        if ($idBodega)   $movs = array_filter($movs, fn($m) => $m['id_bodega']   == $idBodega);
        $this->success('OK', ['movimientos' => array_values($movs)]);
    }

    public function stockPorBodega(): void
    {
        $this->success('OK', ['stock' => $this->getStockPorBodega()]);
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

    private function getCronogramas(): array
    {
        $db = Database::programa(); $pid = Database::proyectoId();
        if (!$db->tablaExiste('sag_inventario_cronogramas')) return [];
        $rows = $db->fetchAll(
            "SELECT c.*, p.nombre AS proveedor
               FROM sag_inventario_cronogramas c
               JOIN sag_proveedores p ON p.id_proveedor=c.id_proveedor
              WHERE c.id_proyecto=? ORDER BY c.id_cronograma DESC",
            [$pid]
        );
        foreach ($rows as &$c) {
            $c['lineas'] = $db->fetchAll(
                "SELECT l.*, pr.nombre AS producto, pr.unidad, b.nombre AS bodega
                   FROM sag_inventario_lineas l
                   JOIN sag_inventario_productos pr ON pr.id_producto=l.id_producto
                   JOIN sag_bodegas b ON b.id_bodega=l.id_bodega
                  WHERE l.id_cronograma=? AND l.id_proyecto=?
                  ORDER BY l.fecha_programada, l.id_linea",
                [$c['id_cronograma'], $pid]
            );
        }
        return $rows;
    }

    private function getProveedores(): array
    {
        $db = Database::programa();
        return $db->tablaExiste('sag_proveedores') ? $db->fetchAll(
            "SELECT id_proveedor,nombre,rtn FROM sag_proveedores WHERE id_proyecto=? AND activo=1 ORDER BY nombre",
            [Database::proyectoId()]
        ) : [];
    }

    private function getBodegas(): array
    {
        $db = Database::programa();
        return $db->tablaExiste('sag_bodegas') ? $db->fetchAll(
            "SELECT b.id_bodega,b.codigo,b.nombre,d.nombre AS departamento
               FROM sag_bodegas b LEFT JOIN sag_departamentos d ON d.id_departamento=b.id_departamento
              WHERE b.id_proyecto=? AND b.activo=1 ORDER BY b.nombre",
            [Database::proyectoId()]
        ) : [];
    }

    private function getProductos(): array
    {
        $db = Database::programa();
        return $db->tablaExiste('sag_inventario_productos') ? $db->fetchAll(
            "SELECT id_producto,codigo,nombre,unidad,presentacion
               FROM sag_inventario_productos WHERE id_proyecto=? AND activo=1 ORDER BY nombre",
            [Database::proyectoId()]
        ) : [];
    }

    private function getStockPorBodega(): array
    {
        $db = Database::programa(); $pid = Database::proyectoId();
        if (!$db->tablaExiste('sag_inventario_movimientos')) return [];
        return $db->fetchAll(
            "SELECT m.id_bodega,b.nombre AS bodega,m.id_producto,p.nombre AS producto,p.unidad,
                    SUM(CASE WHEN m.tipo='entrada' THEN m.cantidad WHEN m.tipo='salida' THEN -m.cantidad ELSE m.cantidad END) AS saldo
               FROM sag_inventario_movimientos m
               JOIN sag_bodegas b ON b.id_bodega=m.id_bodega
               JOIN sag_inventario_productos p ON p.id_producto=m.id_producto
              WHERE m.id_proyecto=?
              GROUP BY m.id_bodega,b.nombre,m.id_producto,p.nombre,p.unidad
              ORDER BY b.nombre,p.nombre",
            [$pid]
        );
    }

    private function getKardex(): array
    {
        $db = Database::programa(); $pid = Database::proyectoId();
        if (!$db->tablaExiste('sag_inventario_movimientos')) return [];
        return $db->fetchAll(
            "SELECT m.id_movimiento,m.fecha,m.tipo,m.id_producto,p.nombre AS producto,m.id_bodega,b.nombre AS bodega,
                    m.cantidad,m.saldo_resultante AS saldo,m.origen,m.id_origen,m.descripcion
               FROM sag_inventario_movimientos m
               JOIN sag_bodegas b ON b.id_bodega=m.id_bodega
               JOIN sag_inventario_productos p ON p.id_producto=m.id_producto
              WHERE m.id_proyecto=? ORDER BY m.fecha DESC,m.id_movimiento DESC LIMIT 300",
            [$pid]
        );
    }

    private function parsearCsv(string $path): array
    {
        $h = fopen($path, 'r'); if (!$h) throw new RuntimeException('CSV inválido.');
        $header = fgetcsv($h, 2000, ',');
        if (!$header) { fclose($h); return []; }
        $header = array_map(
            fn($v) => strtolower(trim((string)$v, "\xEF\xBB\xBF \t\n\r\0\x0B")),
            $header
        );
        $rows = [];
        while (($row = fgetcsv($h, 2000, ',')) !== false) {
            $row = array_pad(array_slice($row, 0, count($header)), count($header), '');
            if (array_filter($row, fn($v) => trim((string)$v) !== '')) $rows[] = array_combine($header, $row);
        }
        fclose($h); return $rows;
    }

    private function parsearXlsx(string $path): array
    {
        if (!class_exists('ZipArchive')) throw new RuntimeException('Extensión ZIP no disponible.');
        $zip = new ZipArchive(); if ($zip->open($path) !== true) throw new RuntimeException('XLSX inválido.');
        $shared = [];
        if (($ss = $zip->getFromName('xl/sharedStrings.xml')) !== false && ($xml = @simplexml_load_string($ss))) {
            foreach ($xml->si as $si) {
                $v = isset($si->t) ? (string)$si->t : '';
                if (!$v && isset($si->r)) foreach ($si->r as $r) $v .= (string)$r->t;
                $shared[] = $v;
            }
        }
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml'); $zip->close();
        if ($sheet === false || !($xml = @simplexml_load_string($sheet))) throw new RuntimeException('Hoja inválida.');
        $filas = [];
        foreach ($xml->sheetData->row as $rowXml) {
            $fila = [];
            foreach ($rowXml->c as $c) {
                $col = preg_replace('/\d+/', '', (string)$c['r']); $type = (string)$c['t']; $v = '';
                if ($type === 's' && isset($c->v)) $v = $shared[(int)(string)$c->v] ?? '';
                elseif ($type === 'inlineStr' && isset($c->is->t)) $v = (string)$c->is->t;
                elseif (isset($c->v)) $v = (string)$c->v;
                $fila[$col] = trim($v);
            }
            if (array_filter($fila, fn($v) => $v !== '')) $filas[] = $fila;
        }
        if (!$filas) return [];
        $headerByColumn = [];
        foreach ($filas[0] as $col => $value) {
            $headerByColumn[$col] = strtolower(trim((string)$value));
        }
        $rows = [];
        foreach (array_slice($filas, 1) as $fila) {
            $row = [];
            foreach ($headerByColumn as $col => $header) {
                if ($header !== '') $row[$header] = $fila[$col] ?? '';
            }
            if (array_filter($row, fn($v) => trim((string)$v) !== '')) $rows[] = $row;
        }
        return $rows;
    }

    private function normalizarFechaExcel(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '') return null;
        if (is_numeric($valor)) return date('Y-m-d', strtotime('1899-12-30 +' . (int)$valor . ' days'));
        foreach (['Y-m-d', 'd/m/Y', 'm/d/Y'] as $formato) {
            $d = DateTime::createFromFormat($formato, $valor);
            if ($d && $d->format($formato) === $valor) return $d->format('Y-m-d');
        }
        return null;
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
