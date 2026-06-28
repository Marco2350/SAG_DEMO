<?php
require_once __DIR__ . '/_test_guard.php';
/**
 * Test del endpoint /QueryMovementNationalPrograms — la pestaña "Programas nacionales" de OIRSA.
 * URL: http://localhost/sag_programas/test_oirsa_explore.php
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/core/TrazaragroClient.php';

$cli = new TrazaragroClient();
$ping = $cli->ping();
if (!$ping['ok']) { echo "✗ Ping FAIL: " . $ping['msg'] . "\n"; exit; }
echo "✓ Token obtenido\n\n";

$cfg = TRAZARAGRO;
$baseUrl = rtrim($cfg['base_url'], '/');
$token = $cli->getToken();

function findCaBundle(): ?string {
    foreach ([ini_get('curl.cainfo'), ini_get('openssl.cafile'),
              'C:/xampp/apache/bin/curl-ca-bundle.crt',
              'C:/xampp/php/extras/ssl/cacert.pem'] as $p) {
        if ($p && is_file($p)) return $p;
    }
    return null;
}

function httpCall(string $url, string $token, string $instance, ?string $caBundle): array {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'GET',
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'X-Local-Instance: ' . $instance,
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
    ];
    if ($caBundle) {
        $opts[CURLOPT_SSL_VERIFYPEER] = true;
        $opts[CURLOPT_CAINFO] = $caBundle;
    } else {
        $opts[CURLOPT_SSL_VERIFYPEER] = false;
        $opts[CURLOPT_SSL_VERIFYHOST] = 0;
    }
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status' => $status, 'body' => $raw];
}

$caBundle = findCaBundle();

// Pruebas dirigidas
$tests = [
    'QueryMovementNationalPrograms (sin filtro, 3 filas)' =>
        '/Services/odata/QueryMovementNationalPrograms?$top=3',
    'QueryMovementNationalPrograms con filtro PIPA + entregas' =>
        '/Services/odata/QueryMovementNationalPrograms?$top=3&$filter=' .
        urlencode("ActivityId eq 329 and MovementTypeId eq 141"),
    'QueryMovements (3 filas)' =>
        '/Services/odata/QueryMovements?$top=3',
    'QueryItemEntry (3 filas)' =>
        '/Services/odata/QueryItemEntry?$top=3',
];

foreach ($tests as $label => $path) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  [$label]\n";
    echo "  URL: " . $baseUrl . $path . "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    $r = httpCall($baseUrl . $path, $token, $cfg['instance'], $caBundle);
    echo "  Status: {$r['status']}\n\n";

    if ($r['status'] === 200) {
        $data = json_decode($r['body'], true);
        if (isset($data['value']) && count($data['value']) > 0) {
            $first = $data['value'][0];
            echo "  ESTRUCTURA DEL PRIMER REGISTRO (todas las columnas y sus valores):\n";
            foreach ($first as $k => $v) {
                $vs = is_array($v) ? json_encode($v) : (string)$v;
                if (mb_strlen($vs) > 80) $vs = mb_substr($vs, 0, 80) . '...';
                $emp = ($v === null || $v === '' || $v === 0 || $v === '0.000000');
                $marker = $emp ? '  ' : '★ ';   // ★ marca campos con datos reales
                echo "    {$marker}{$k}: {$vs}\n";
            }
            echo "\n  → Total devuelto en esta query: " . count($data['value']) . " filas\n";
        } else {
            echo "  ⚠ Sin resultados (value vacío)\n";
            echo "  Body: " . mb_substr($r['body'], 0, 500) . "\n";
        }
    } else {
        echo "  Body de error: " . mb_substr($r['body'] ?? '', 0, 800) . "\n";
    }
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "  FIN — los campos con ★ tienen datos reales (no NULL/0)\n";
echo "═══════════════════════════════════════════════════════════════\n";
