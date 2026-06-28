<?php
require_once __DIR__ . '/_test_guard.php';
/**
 * Migración 006 — Evidencias documentales (R-027 / R-028)
 * Agrega las columnas evidencia_* a sag_capacitaciones y sag_asistencias_tecnicas.
 *
 * Reescrito tras la unificación de BD (todo vive en mddesarr_sag; la versión
 * anterior iteraba PROGRAMAS con la clave 'db' que ya no existe).
 * Idempotente y compatible con MySQL 5.7: consulta INFORMATION_SCHEMA y
 * agrega solo lo que falta (el .sql original usa ADD COLUMN IF NOT EXISTS,
 * sintaxis MariaDB que el servidor de producción no soporta).
 *
 * Navegador: http://localhost/PROYECTOS-PHP/SAG/migrar_006.php?modo=ejecutar
 * CLI:       php migrar_006.php ejecutar
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL); ini_set('display_errors', '1');
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config/app.php';

$modo = $_GET['modo'] ?? ($argv[1] ?? 'verificar');

$columnas = [
    'evidencia_archivo'         => "VARCHAR(255) NULL",
    'evidencia_nombre_original' => "VARCHAR(255) NULL",
    'evidencia_mime'            => "VARCHAR(80) NULL",
    'evidencia_tamano'          => "INT NULL",
    'evidencia_subida_at'       => "DATETIME NULL",
    'evidencia_subida_por'      => "INT NULL",
    'evidencia_estado'          => "ENUM('pendiente','cargada','validada','rechazada') NOT NULL DEFAULT 'pendiente'",
    'evidencia_observaciones'   => "TEXT NULL",
];
$tablas = [
    'sag_capacitaciones'       => 'idx_ev_estado_cap',
    'sag_asistencias_tecnicas' => 'idx_ev_estado_at',
];

echo "═════════════════════════════════════════════\n";
echo "  MIGRACIÓN 006 — Evidencias (R-027 / R-028)\n";
echo "═════════════════════════════════════════════\n\n";
echo "Modo: {$modo}\n\n";

try {
    $dsn = "mysql:host=" . DB_MAIN['host'] . ";port=" . DB_MAIN['port']
         . ";dbname=" . DB_MAIN['database'] . ";charset=" . DB_MAIN['charset'];
    $pdo = new PDO($dsn, DB_MAIN['username'], DB_MAIN['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "✓ Conectado a " . DB_MAIN['database'] . " (" . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . ")\n\n";
} catch (\Throwable $e) {
    echo "✗ Conexión: " . $e->getMessage() . "\n"; exit(1);
}

$pendientes = []; // [ [sql, descripcion], ... ]

foreach ($tablas as $tabla => $indice) {
    echo "── {$tabla} ──\n";
    $q = $pdo->prepare(
        "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?"
    );
    $q->execute([$tabla]);
    $existentes = array_flip($q->fetchAll(PDO::FETCH_COLUMN));

    foreach ($columnas as $col => $def) {
        if (isset($existentes[$col])) {
            echo "  ✓ {$col} ya existe\n";
        } else {
            echo "  ✗ {$col} FALTA\n";
            $pendientes[] = ["ALTER TABLE {$tabla} ADD COLUMN {$col} {$def}", "{$tabla}.{$col}"];
        }
    }

    $qi = $pdo->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?"
    );
    $qi->execute([$tabla, $indice]);
    if ((int) $qi->fetchColumn() > 0) {
        echo "  ✓ índice {$indice} ya existe\n";
    } else {
        echo "  ✗ índice {$indice} FALTA\n";
        $pendientes[] = ["CREATE INDEX {$indice} ON {$tabla} (evidencia_estado)", "índice {$indice}"];
    }
    echo "\n";
}

// Carpeta de evidencias (la usa EvidenciaService)
$dir = ROOT_PATH . '/uploads/evidencias';
if (is_dir($dir)) {
    echo "✓ uploads/evidencias ya existe\n\n";
} elseif ($modo === 'ejecutar') {
    echo (@mkdir($dir, 0775, true)
        ? "✓ Carpeta creada: uploads/evidencias\n\n"
        : "✗ No se pudo crear uploads/evidencias — créala manualmente\n\n");
} else {
    echo "✗ uploads/evidencias FALTA (se creará al ejecutar)\n\n";
}

if (empty($pendientes)) {
    echo "✓ Nada pendiente: la migración 006 ya está aplicada.\n";
    exit(0);
}

echo count($pendientes) . " cambio(s) de BD pendiente(s).\n\n";
if ($modo !== 'ejecutar') {
    echo "⚠ Modo verificación. Agrega ?modo=ejecutar (navegador) o `php migrar_006.php ejecutar` (CLI) para aplicar.\n";
    exit(0);
}

$ok = 0; $err = 0;
foreach ($pendientes as [$sql, $desc]) {
    try {
        $pdo->exec($sql);
        echo "  ✓ {$desc}\n";
        $ok++;
    } catch (\Throwable $e) {
        echo "  ✗ {$desc}: " . substr($e->getMessage(), 0, 200) . "\n";
        $err++;
    }
}
echo "\n→ {$ok} aplicados, {$err} errores\n";
echo "\n═════════════════════════════════════════════\n  FIN\n═════════════════════════════════════════════\n";
