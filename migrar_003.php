<?php
/**
 * Migración 003 — Verificación e instalación.
 * URL: http://localhost/sag_programas/migrar_003.php
 *
 * Borrar este archivo después de migrar (no es seguro dejarlo en producción).
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config/app.php';

// Tablas que esperamos que exista después de la migración 003
$tablasObjetivo = [
    'sag_proveedores',
    'sag_bodegas',
    'sag_inventario_productos',
    'sag_inventario_cronogramas',
    'sag_inventario_lineas',
    'sag_inventario_movimientos',
    'sag_entregas',
    'sag_entregas_lineas',
];

$sqlFile = ROOT_PATH . '/sql/migracion_003_entregas_inventario.sql';

echo "═══════════════════════════════════════════════════════════════\n";
echo "  MIGRACIÓN 003 — Entregas + Inventarios\n";
echo "  Archivo SQL: $sqlFile\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if (!is_file($sqlFile)) {
    echo "✗ No se encontró el archivo SQL.\n";
    exit;
}
$sqlRaw = file_get_contents($sqlFile);

// Modo: ?verificar (default) o ?ejecutar
$modo = $_GET['modo'] ?? 'verificar';
echo "Modo actual: $modo  (cámbialo con ?modo=verificar  o  ?modo=ejecutar)\n\n";

// Separar las sentencias SQL por ; (ignorando ; dentro de strings)
function splitSqlStatements(string $sql): array {
    // Quitar comentarios de línea
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    // Quitar líneas en blanco múltiples
    $sql = preg_replace('/\n\s*\n/', "\n", $sql);
    $parts = array_filter(array_map('trim', explode(';', $sql)));
    return array_values($parts);
}

$statements = splitSqlStatements($sqlRaw);
echo "Sentencias SQL detectadas: " . count($statements) . "\n\n";

foreach (PROGRAMAS as $progId => $prog) {
    $dbName = $prog['db'];
    echo "──────────────────────────────────────────\n";
    echo "  Programa: " . $prog['sigla'] . "  ·  BD: $dbName\n";
    echo "──────────────────────────────────────────\n";

    try {
        $dsn = "mysql:host=" . DB_MAIN['host']
             . ";port=" . DB_MAIN['port']
             . ";dbname=$dbName"
             . ";charset=" . DB_MAIN['charset'];
        $pdo = new PDO($dsn, DB_MAIN['username'], DB_MAIN['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (\Throwable $e) {
        echo "  ✗ No se pudo conectar: " . $e->getMessage() . "\n\n";
        continue;
    }

    // Verificar qué tablas existen
    echo "  Estado de tablas:\n";
    $faltantes = [];
    foreach ($tablasObjetivo as $t) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($t));
            $exists = (bool) $stmt->fetch();
            echo "    " . ($exists ? "✓" : "✗") . " $t" . ($exists ? "" : " (falta)") . "\n";
            if (!$exists) $faltantes[] = $t;
        } catch (\Throwable $e) {
            echo "    ? $t (error verificando: " . $e->getMessage() . ")\n";
        }
    }

    if (empty($faltantes)) {
        echo "  ✓ Migración ya aplicada en esta BD.\n\n";
        continue;
    }

    if ($modo !== 'ejecutar') {
        echo "  ⚠ Faltan " . count($faltantes) . " tabla(s). Para crearlas, vuelve a abrir esta URL con ?modo=ejecutar\n\n";
        continue;
    }

    // Ejecutar la migración
    echo "  → Ejecutando migración...\n";
    $ok = 0; $err = 0;
    foreach ($statements as $i => $stmt) {
        if (trim($stmt) === '') continue;
        try {
            $pdo->exec($stmt);
            $ok++;
        } catch (\Throwable $e) {
            $err++;
            $msg = $e->getMessage();
            // Filtrar "table already exists" — los CREATE usan IF NOT EXISTS pero
            // por si acaso reportamos solo errores reales
            if (stripos($msg, 'already exists') === false) {
                echo "    ✗ Sentencia #$i falló: " . substr($msg, 0, 200) . "\n";
                echo "       SQL: " . substr(trim($stmt), 0, 120) . "...\n";
            }
        }
    }
    echo "  Ejecución terminada: $ok OK, $err errores.\n";

    // Re-verificar
    echo "  Estado post-migración:\n";
    foreach ($tablasObjetivo as $t) {
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($t));
        $exists = (bool) $stmt->fetch();
        echo "    " . ($exists ? "✓" : "✗") . " $t\n";
    }
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "  FIN\n";
echo "═══════════════════════════════════════════════════════════════\n";
