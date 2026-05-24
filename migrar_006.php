<?php
/**
 * Migración 006 — Agrega columnas de evidencia a sag_capacitaciones y sag_asistencias_tecnicas.
 * URL: http://localhost/sag_programas/migrar_006.php?modo=ejecutar
 * Borrar después de aplicar.
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL); ini_set('display_errors', '1');
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config/app.php';

$sqlRaw = file_get_contents(ROOT_PATH . '/sql/migracion_006_evidencias.sql');
$modo   = $_GET['modo'] ?? 'verificar';

echo "═════════════════════════════════════════════\n";
echo "  MIGRACIÓN 006 — Evidencias documentales\n";
echo "═════════════════════════════════════════════\n\n";
echo "Modo: $modo\n\n";

function split_sql(string $sql): array {
    $sql = preg_replace('/--[^\n]*/', '', $sql);
    return array_values(array_filter(array_map('trim', explode(';', $sql))));
}
$statements = split_sql($sqlRaw);

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

    // Verificar columnas
    $checkTabla = function($tabla) use ($pdo) {
        $cols = [];
        foreach ($pdo->query("SHOW COLUMNS FROM $tabla") as $r) $cols[$r['Field']] = true;
        $req = ['evidencia_archivo','evidencia_nombre_original','evidencia_mime','evidencia_tamano',
                'evidencia_subida_at','evidencia_subida_por','evidencia_estado','evidencia_observaciones'];
        return array_filter($req, fn($c) => !isset($cols[$c]));
    };

    $faltCap = $checkTabla('sag_capacitaciones');
    $faltAt  = $checkTabla('sag_asistencias_tecnicas');

    echo "  sag_capacitaciones — faltan: " . (empty($faltCap) ? '(ninguna)' : implode(', ', $faltCap)) . "\n";
    echo "  sag_asistencias_tecnicas — faltan: " . (empty($faltAt) ? '(ninguna)' : implode(', ', $faltAt)) . "\n";

    if (empty($faltCap) && empty($faltAt)) {
        echo "  ✓ Ya está al día\n\n"; continue;
    }
    if ($modo !== 'ejecutar') {
        echo "  ⚠ Agrega ?modo=ejecutar para crear\n\n"; continue;
    }

    $ok = 0; $err = 0;
    foreach ($statements as $i => $s) {
        try { $pdo->exec($s); $ok++; }
        catch (\Throwable $e) {
            $err++;
            $m = $e->getMessage();
            if (stripos($m, 'duplicate') === false && stripos($m, 'exists') === false) {
                echo "  ✗ #$i: " . substr($m, 0, 200) . "\n";
            }
        }
    }
    echo "  → $ok OK, $err errores (ignorables si dicen 'already exists')\n\n";
}

// Crear carpeta uploads/evidencias si no existe
$dir = ROOT_PATH . '/uploads/evidencias';
if (!is_dir($dir)) {
    if (@mkdir($dir, 0775, true)) echo "✓ Carpeta creada: uploads/evidencias\n";
    else                          echo "✗ No se pudo crear uploads/evidencias — créala manualmente\n";
} else {
    echo "✓ uploads/evidencias ya existe\n";
}

echo "\n═════════════════════════════════════════════\n  FIN\n═════════════════════════════════════════════\n";
