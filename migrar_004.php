<?php
require_once __DIR__ . '/_test_guard.php';
/**
 * Migración 004 — Crea sag_trazaragro_movimientos en cada BD de programa.
 * URL: http://localhost/sag_programas/migrar_004.php?modo=ejecutar
 *
 * Borrar este archivo una vez aplicada la migración.
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config/app.php';

$sqlFile = ROOT_PATH . '/sql/migracion_004_trazaragro_movimientos.sql';

echo "═══════════════════════════════════════════════════════════════\n";
echo "  MIGRACIÓN 004 — sag_trazaragro_movimientos\n";
echo "  Archivo: $sqlFile\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if (!is_file($sqlFile)) {
    echo "✗ No se encontró el archivo SQL.\n";
    exit;
}
$sqlRaw = file_get_contents($sqlFile);

$modo = $_GET['modo'] ?? 'verificar';
echo "Modo: $modo  (cámbialo con ?modo=verificar o ?modo=ejecutar)\n\n";

function splitSqlStatements(string $sql): array {
    // Quitar TODOS los comentarios -- (línea-inicio y también inline hasta fin de línea)
    // Importante: hay que hacerlo antes de explode(';') porque los comentarios pueden contener ';'.
    $sql = preg_replace('/--[^\n]*/', '', $sql);
    // Colapsar líneas en blanco
    $sql = preg_replace('/\n\s*\n/', "\n", $sql);
    // Separar por ;
    $parts = array_filter(array_map('trim', explode(';', $sql)));
    return array_values($parts);
}

$statements = splitSqlStatements($sqlRaw);
echo "Sentencias SQL: " . count($statements) . "\n\n";

foreach (PROGRAMAS as $progId => $prog) {
    $dbName = $prog['db'];
    echo "──────────────────────────────────────────\n";
    echo "  Programa: " . $prog['sigla'] . " · BD: $dbName\n";
    echo "──────────────────────────────────────────\n";

    try {
        $dsn = "mysql:host=" . DB_MAIN['host'] . ";port=" . DB_MAIN['port']
             . ";dbname=$dbName;charset=" . DB_MAIN['charset'];
        $pdo = new PDO($dsn, DB_MAIN['username'], DB_MAIN['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (\Throwable $e) {
        echo "  ✗ No se pudo conectar: " . $e->getMessage() . "\n\n";
        continue;
    }

    $stmt = $pdo->query("SHOW TABLES LIKE 'sag_trazaragro_movimientos'");
    $exists = (bool) $stmt->fetch();
    echo "  sag_trazaragro_movimientos: " . ($exists ? "✓ ya existe" : "✗ falta") . "\n";

    if ($exists) {
        $r = $pdo->query("SELECT COUNT(*) AS c FROM sag_trazaragro_movimientos")->fetch(PDO::FETCH_ASSOC);
        echo "  Filas actuales: " . (int)$r['c'] . "\n\n";
        continue;
    }

    if ($modo !== 'ejecutar') {
        echo "  ⚠ Para crear la tabla, agrega ?modo=ejecutar a la URL\n\n";
        continue;
    }

    $ok = 0; $err = 0;
    foreach ($statements as $i => $stmt) {
        if (trim($stmt) === '') continue;
        try {
            $pdo->exec($stmt);
            $ok++;
        } catch (\Throwable $e) {
            $err++;
            $msg = $e->getMessage();
            if (stripos($msg, 'already exists') === false) {
                echo "    ✗ Sentencia #$i falló: " . substr($msg, 0, 200) . "\n";
            }
        }
    }
    echo "  → Ejecutado: $ok OK, $err errores\n";

    $stmt = $pdo->query("SHOW TABLES LIKE 'sag_trazaragro_movimientos'");
    echo "  Estado final: " . ($stmt->fetch() ? "✓ creada" : "✗ no creada") . "\n\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "  FIN\n";
echo "═══════════════════════════════════════════════════════════════\n";
