<?php
/**
 * Diagnóstico del nuevo flujo OIRSA (sag_trazaragro_movimientos).
 * URL: http://localhost/sag_programas/test_entregas_db.php
 * Requiere sesión activa con un programa seleccionado.
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config/app.php';

spl_autoload_register(function (string $class): void {
    $paths = [
        ROOT_PATH . "/core/{$class}.php",
        ROOT_PATH . "/app/controllers/{$class}.php",
        ROOT_PATH . "/app/models/{$class}.php",
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) { require_once $path; return; }
    }
});

session_name(SESSION_NAME);
session_start();

echo "=== DIAGNÓSTICO OIRSA · sag_trazaragro_movimientos ===\n\n";

echo "[1] Sesión:\n";
echo "    user      = " . ($_SESSION['user']['email'] ?? '(no logueado)') . "\n";
echo "    programa  = " . ($_SESSION['programa']['sigla'] ?? '(ninguno)') . "\n";
echo "    db        = " . ($_SESSION['programa']['db'] ?? '(ninguna)') . "\n";

if (empty($_SESSION['user']) || empty($_SESSION['programa'])) {
    echo "\n⚠ Inicia sesión y selecciona programa primero.\n";
    exit;
}

$progId = $_SESSION['programa']['id'] ?? '';
$rubro  = PROGRAMAS[$progId]['trazaragro_rubro'] ?? null;
echo "    rubro     = " . ($rubro ?: '(no configurado)') . "\n\n";

try {
    $db = Database::programa();
    echo "    ✓ Conexión OK\n\n";
} catch (\Throwable $e) {
    echo "    ✗ FAIL conexión: " . $e->getMessage() . "\n";
    exit;
}

echo "[2] Verificar tabla sag_trazaragro_movimientos:\n";
try {
    $r = $db->fetchOne("SHOW TABLES LIKE 'sag_trazaragro_movimientos'");
    if ($r) {
        echo "    ✓ Tabla existe\n";
        $r2 = $db->fetchOne("SELECT COUNT(*) AS c FROM sag_trazaragro_movimientos");
        echo "    Filas actuales: " . (int)($r2['c'] ?? 0) . "\n";
    } else {
        echo "    ✗ NO EXISTE — corre primero la migración 004:\n";
        echo "        http://localhost/sag_programas/migrar_004.php?modo=ejecutar\n";
        exit;
    }
} catch (\Throwable $e) {
    echo "    ✗ ERROR: " . $e->getMessage() . "\n";
    exit;
}

echo "\n[3] Sincronización en vivo (limpia + re-sync con rubro={$rubro}):\n";
try {
    $cli = new TrazaragroClient();
    $ping = $cli->ping();
    if (!$ping['ok']) {
        echo "    ✗ FAIL ping a OIRSA: " . $ping['msg'] . "\n";
        exit;
    }
    echo "    ✓ Ping OK\n";

    $movs = $cli->fetchEntregas([
        'top'   => 50,
        'desde' => date('Y-m-d', strtotime('-30 days')),
        'hasta' => date('Y-m-d'),
        'rubro' => $rubro,
    ]);
    echo "    Trazaragro devolvió " . count($movs) . " movimientos\n";

    if (empty($movs)) {
        echo "    ⚠ La consulta devolvió 0. Posibles causas:\n";
        echo "       · El rubro '{$rubro}' no coincide con ningún ProductActivityName en OIRSA\n";
        echo "       · No hay movimientos en los últimos 30 días para ese rubro\n";
        echo "       · El usuario OIRSA no tiene permisos sobre esos manifiestos\n";
        echo "\n    Re-intentando SIN filtro de rubro:\n";
        $movsAll = $cli->fetchEntregas([
            'top'   => 5,
            'desde' => date('Y-m-d', strtotime('-30 days')),
            'hasta' => date('Y-m-d'),
        ]);
        echo "    Sin rubro: " . count($movsAll) . " movimientos\n";
        if (!empty($movsAll)) {
            echo "    Rubros disponibles en la muestra:\n";
            $rubros = [];
            foreach ($movsAll as $m) {
                $rubros[$m['rubro']] = ($rubros[$m['rubro']] ?? 0) + 1;
            }
            foreach ($rubros as $r => $c) echo "      · '{$r}' — {$c} movimientos\n";
        }
        exit;
    }

    // Persistir usando el método real del controlador
    $rc = new ReflectionClass('EntregasController');
    $ctl = $rc->newInstanceWithoutConstructor();
    $m = $rc->getMethod('persistirMovimientos');
    $m->setAccessible(true);
    $stats = $m->invoke($ctl, $movs);
    echo "    Resultado persistencia:\n";
    echo json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

    if (!empty($stats['errores_det'])) {
        echo "    ⚠ Detalle de errores:\n";
        foreach ($stats['errores_det'] as $e) echo "      - $e\n";
    }

    echo "\n[4] Verificación post-persistencia:\n";
    $r = $db->fetchOne("SELECT COUNT(*) AS c FROM sag_trazaragro_movimientos");
    echo "    Total filas ahora: " . (int)($r['c'] ?? 0) . "\n";

    $sample = $db->fetchOne(
        "SELECT movement_id, rubro, objeto_trazable, guiasa_no, destino_dni, destino_nombre, autorizado_por, codigo_trazabilidad, estado_local
         FROM sag_trazaragro_movimientos ORDER BY synced_at DESC LIMIT 1"
    );
    if ($sample) {
        echo "    Primer registro:\n";
        foreach ($sample as $k => $v) {
            echo "      {$k}: " . ($v ?? '(null)') . "\n";
        }
    }

} catch (\Throwable $e) {
    echo "    ✗ FAIL: " . $e->getMessage() . "\n";
    echo "       archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "       trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN ===\n";
