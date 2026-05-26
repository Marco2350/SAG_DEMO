<?php
/**
 * Test de conexión a Trazaragro PRODUCCIÓN.
 * Limpia el token cacheado, intenta autenticar y prueba los endpoints.
 * URL: http://localhost/sag_programas/test_oirsa_prod.php
 * Borrar tras pruebas.
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/core/TrazaragroClient.php';

echo "═══════════════════════════════════════════════════════════════\n";
echo "  TEST CONEXIÓN OIRSA PRODUCCIÓN\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$cfg = TRAZARAGRO;
echo "Configuración actual:\n";
echo "  base_url:      " . $cfg['base_url'] . "\n";
echo "  username:      " . ($cfg['username'] ?: '(vacío)') . "\n";
echo "  password:      " . (empty($cfg['password']) ? '(vacío)' : '(configurada — ' . strlen($cfg['password']) . ' chars)') . "\n";
echo "  client_id:     " . $cfg['client_id'] . "\n";
echo "  instance:      " . $cfg['instance'] . "\n\n";

if (strpos($cfg['base_url'], 'pruebas') !== false) {
    echo "⚠ AVISO: el base_url todavía apunta a PRUEBAS, no a producción.\n\n";
}

// Paso 1: Limpiar el token cacheado (era de pruebas)
$cli = new TrazaragroClient();
$tokenFile = sys_get_temp_dir() . '/sag_trazaragro_token.json';
if (is_file($tokenFile)) {
    @unlink($tokenFile);
    echo "[1] ✓ Token cacheado borrado: $tokenFile\n\n";
} else {
    echo "[1] ✓ No había token cacheado previo\n\n";
}

// Paso 2: Pingear → fuerza autenticación nueva
echo "[2] Autenticando contra " . $cfg['base_url'] . "...\n";
$ping = $cli->ping();
echo json_encode($ping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

if (!$ping['ok']) {
    echo "✗ No se pudo autenticar. Detalle del diagnóstico arriba.\n";
    echo "\nPosibles causas:\n";
    echo "  1. Las credenciales productivas son distintas a las de pruebas — pídelas a OIRSA\n";
    echo "  2. El client_id/client_secret 'TZWEB' es de pruebas y producción usa otros\n";
    echo "  3. Tu usuario no tiene permisos habilitados en producción todavía\n";
    exit;
}
echo "✓ Autenticado correctamente con producción\n\n";

// Paso 3: Probar QueryMovementNationalPrograms (el que usa el sistema)
echo "[3] Probando endpoint QueryMovementNationalPrograms (el que usa el sistema)...\n";
try {
    $movs = $cli->fetchEntregas([
        'top'   => 3,
        'desde' => date('Y-m-d', strtotime('-30 days')),
        'hasta' => date('Y-m-d'),
    ]);
    echo "  ✓ Devolvió " . count($movs) . " movimientos\n";
    if (!empty($movs[0])) {
        $m = $movs[0];
        echo "\n  Primer movimiento de muestra:\n";
        echo "    MovementId:         " . ($m['trazaragro_id'] ?? '?') . "\n";
        echo "    Rubro:              " . ($m['rubro'] ?? '?') . "\n";
        echo "    Objeto trazable:    " . ($m['objeto_trazable'] ?? '(null)') . "\n";
        echo "    Código trazabilidad:" . ($m['codigo_trazabilidad'] ?? '(null)') . "\n";
        echo "    GUIASA:             " . ($m['registration_code'] ?? '?') . "\n";
        echo "    Cantidad:           " . ($m['cantidad'] ?? 0) . " " . ($m['unidad'] ?? '') . "\n";
        echo "    Beneficiario:       " . ($m['destino_nombre'] ?? '?') . " (DNI: " . ($m['destino_dni'] ?? '?') . ")\n";
        echo "    Autorizado por:     " . ($m['usuario_autoriza'] ?? '?') . "\n";

        $vacios = [];
        foreach (['objeto_trazable','codigo_trazabilidad','cantidad','usuario_autoriza','destino_nombre'] as $k) {
            if (empty($m[$k])) $vacios[] = $k;
        }
        if ($vacios) {
            echo "\n  ⚠ Campos vacíos detectados: " . implode(', ', $vacios) . "\n";
            echo "  → Si el endpoint en producción no devuelve estos campos, hay que cambiar el código\n";
            echo "    para usar QueryMovementProducts o agregar fallback.\n";
        } else {
            echo "\n  ✓ Todos los campos críticos están completos. La integración está lista.\n";
        }
    }
} catch (\Throwable $e) {
    echo "  ✗ Error al consultar OData: " . $e->getMessage() . "\n";
    echo "\n  Esto puede significar que el endpoint QueryMovementNationalPrograms no existe en producción.\n";
    echo "  Si pasa eso, debemos usar QueryMovementProducts (el viejo) como fallback.\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  FIN\n";
echo "═══════════════════════════════════════════════════════════════\n";
