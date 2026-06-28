<?php
require_once __DIR__ . '/_test_guard.php';
/**
 * Migración 005 — Agrega columnas representante_dni, latitud y longitud a sag_organizaciones.
 * URL: http://localhost/sag_programas/migrar_005.php?modo=ejecutar
 * Borrar después de aplicar.
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config/app.php';

$sqlFile = ROOT_PATH . '/sql/migracion_005_organizaciones_campos_nuevos.sql';
echo "═════════════════════════════════════════════\n";
echo "  MIGRACIÓN 005 — Campos nuevos en sag_organizaciones\n";
echo "═════════════════════════════════════════════\n\n";

$sqlRaw = file_get_contents($sqlFile);
$modo   = $_GET['modo'] ?? 'verificar';
echo "Modo: $modo\n\n";

function splitSqlStatements(string $sql): array {
    $sql = preg_replace('/--[^\n]*/', '', $sql);
    $sql = preg_replace('/\n\s*\n/', "\n", $sql);
    return array_values(array_filter(array_map('trim', explode(';', $sql))));
}

$statements = splitSqlStatements($sqlRaw);
echo "Sentencias SQL: " . count($statements) . "\n\n";

foreach (PROGRAMAS as $progId => $prog) {
    echo "─── " . $prog['sigla'] . " · " . $prog['db'] . " ───\n";
    try {
        $dsn = "mysql:host=" . DB_MAIN['host'] . ";port=" . DB_MAIN['port']
             . ";dbname=" . $prog['db'] . ";charset=" . DB_MAIN['charset'];
        $pdo = new PDO($dsn, DB_MAIN['username'], DB_MAIN['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (\Throwable $e) {
        echo "  ✗ Conexión: " . $e->getMessage() . "\n\n"; continue;
    }

    // Verificar columnas existentes
    $cols = [];
    foreach ($pdo->query("SHOW COLUMNS FROM sag_organizaciones") as $r) {
        $cols[$r['Field']] = true;
    }
    $faltan = array_filter(['representante_dni', 'latitud', 'longitud'], fn($c) => !isset($cols[$c]));
    if (empty($faltan)) {
        echo "  ✓ Todas las columnas ya existen\n\n";
        continue;
    }
    echo "  Columnas faltantes: " . implode(', ', $faltan) . "\n";
    if ($modo !== 'ejecutar') {
        echo "  ⚠ Agrega ?modo=ejecutar para crear\n\n";
        continue;
    }
    $ok = 0; $err = 0;
    foreach ($statements as $i => $s) {
        try { $pdo->exec($s); $ok++; }
        catch (\Throwable $e) {
            $err++;
            if (stripos($e->getMessage(), 'duplicate') === false &&
                stripos($e->getMessage(), 'already exists') === false) {
                echo "  ✗ #$i: " . substr($e->getMessage(), 0, 160) . "\n";
            }
        }
    }
    echo "  → $ok OK, $err errores (ignorables si dicen 'already exists')\n\n";
}
echo "═════════════════════════════════════════════\n";
echo "  FIN\n";
echo "═════════════════════════════════════════════\n";
