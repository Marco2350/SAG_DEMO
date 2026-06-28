<?php
require_once __DIR__ . '/_test_guard.php';
/**
 * Test directo de TrazaragroClient — borrar después de diagnóstico.
 * URL: http://localhost/sag_programas/test_trazaragro.php
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "=== TEST TRAZARAGRO ===\n\n";

echo "PHP version: " . PHP_VERSION . "\n";
echo "ROOT_PATH (este archivo): " . __DIR__ . "\n\n";

// 1) Cargar config
echo "[1] Cargando config/app.php... ";
try {
    require_once __DIR__ . '/config/app.php';
    echo "OK\n";
} catch (\Throwable $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
    exit;
}

// 2) Verificar constante TRAZARAGRO
echo "[2] TRAZARAGRO definido: " . (defined('TRAZARAGRO') ? 'sí' : 'no') . "\n";
if (defined('TRAZARAGRO')) {
    $cfg = TRAZARAGRO;
    echo "    base_url:      " . ($cfg['base_url'] ?? '(vacío)') . "\n";
    echo "    username:      " . (empty($cfg['username']) ? '(vacío)' : 'configurado') . "\n";
    echo "    password:      " . (empty($cfg['password']) ? '(vacío)' : 'configurada') . "\n";
    echo "    client_secret: " . (empty($cfg['client_secret']) ? '(vacío)' : 'configurado') . "\n";
}
echo "\n";

// 3) Cargar la clase TrazaragroClient
echo "[3] Incluyendo core/TrazaragroClient.php... ";
try {
    require_once __DIR__ . '/core/TrazaragroClient.php';
    echo "OK\n";
} catch (\Throwable $e) {
    echo "FAIL (parse error o include): " . $e->getMessage() . "\n";
    echo "    archivo: " . $e->getFile() . " línea " . $e->getLine() . "\n";
    exit;
}

echo "[4] class_exists('TrazaragroClient'): " . (class_exists('TrazaragroClient') ? 'sí' : 'NO') . "\n\n";

// 5) Instanciar
echo "[5] Instanciando TrazaragroClient... ";
try {
    $cli = new TrazaragroClient();
    echo "OK\n";
} catch (\Throwable $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
    exit;
}

// 5b) Forzar limpieza del token cacheado del usuario anterior
echo "[5b] Limpiando token cacheado (por cambio de credenciales)... ";
$cli->clearToken();
echo "OK\n";

// 6) Ping (intenta obtener token)
echo "[6] Llamando ping() (intenta autenticar contra OIRSA)...\n";
try {
    $r = $cli->ping();
    echo json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
} catch (\Throwable $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
    echo "    archivo: " . $e->getFile() . " línea " . $e->getLine() . "\n";
    echo "    trace: " . $e->getTraceAsString() . "\n";
    exit;
}

// 7) Probar fetchEntregas (lo que hace el botón "Sincronizar con Trazaragro")
echo "\n[7] Llamando fetchEntregas() — la consulta OData real...\n";
try {
    $entregas = $cli->fetchEntregas([
        'top'   => 5,
        'desde' => date('Y-m-d', strtotime('-30 days')),
        'hasta' => date('Y-m-d'),
    ]);
    $diag = $cli->getLastDiag();
    echo "Resultado de la consulta OData:\n";
    echo json_encode([
        'cantidad_recibida' => count($entregas),
        'diag_ultima_http'  => $diag,
        'primera_entrega'   => $entregas[0] ?? null,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
} catch (\Throwable $e) {
    echo "FAIL EN fetchEntregas: " . $e->getMessage() . "\n";
    echo "    archivo: " . $e->getFile() . " línea " . $e->getLine() . "\n";
    echo "    trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN TEST ===\n";
