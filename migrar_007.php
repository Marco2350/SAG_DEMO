<?php
require_once __DIR__ . '/_test_guard.php';
/**
 * Migración 007 — es_externo + id_beneficiario en sag_capacitaciones_participantes.
 * URL: http://localhost/sag_programas/migrar_007.php?modo=ejecutar
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL); ini_set('display_errors', '1');
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config/app.php';

$sqlRaw = file_get_contents(ROOT_PATH . '/sql/migracion_007_participantes_externos.sql');
$modo   = $_GET['modo'] ?? 'verificar';
echo "═════════════════════════════════════════════\n  MIGRACIÓN 007 — Participantes externos\n═════════════════════════════════════════════\n\n";

function split_sql(string $sql): array {
    $sql = preg_replace('/--[^\n]*/', '', $sql);
    return array_values(array_filter(array_map('trim', explode(';', $sql))));
}
$st = split_sql($sqlRaw);

foreach (PROGRAMAS as $progId => $prog) {
    echo "─── " . $prog['sigla'] . " · " . $prog['db'] . " ───\n";
    try {
        $dsn = "mysql:host=" . DB_MAIN['host'] . ";port=" . DB_MAIN['port']
             . ";dbname=" . $prog['db'] . ";charset=" . DB_MAIN['charset'];
        $pdo = new PDO($dsn, DB_MAIN['username'], DB_MAIN['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (\Throwable $e) { echo "  ✗ " . $e->getMessage() . "\n\n"; continue; }

    $cols = [];
    foreach ($pdo->query("SHOW COLUMNS FROM sag_capacitaciones_participantes") as $r) $cols[$r['Field']] = true;
    $faltan = array_filter(['es_externo','id_beneficiario'], fn($c) => !isset($cols[$c]));

    if (empty($faltan)) { echo "  ✓ Ya está al día\n\n"; continue; }
    echo "  Faltan: " . implode(', ', $faltan) . "\n";
    if ($modo !== 'ejecutar') { echo "  ⚠ Usar ?modo=ejecutar\n\n"; continue; }

    $ok = 0; $err = 0;
    foreach ($st as $i => $s) {
        try { $pdo->exec($s); $ok++; }
        catch (\Throwable $e) {
            $err++;
            if (stripos($e->getMessage(),'duplicate')===false && stripos($e->getMessage(),'exists')===false)
                echo "  ✗ #$i: " . substr($e->getMessage(),0,160) . "\n";
        }
    }
    echo "  → $ok OK, $err errores (ignorables si dicen 'already exists')\n\n";
}
echo "═════════════════════════════════════════════\n  FIN\n═════════════════════════════════════════════\n";
