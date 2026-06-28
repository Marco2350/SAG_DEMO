<?php
require_once __DIR__ . '/_test_guard.php';
/**
 * Migración 008 — Roles + programa_asignado (R-032 / R-033)
 * Esta migración aplica SOLO en sag_main (no en las BDs de PIP).
 * URL: http://localhost/sag_programas/migrar_008.php?modo=ejecutar
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL); ini_set('display_errors', '1');
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config/app.php';

$sqlRaw = file_get_contents(ROOT_PATH . '/sql/migracion_008_roles_y_pip.sql');
$modo   = $_GET['modo'] ?? 'verificar';

echo "═════════════════════════════════════════════\n";
echo "  MIGRACIÓN 008 — Roles + alcance PIP\n";
echo "  (aplica solo en sag_main)\n";
echo "═════════════════════════════════════════════\n\n";

function split_sql(string $sql): array {
    $sql = preg_replace('/--[^\n]*/', '', $sql);
    return array_values(array_filter(array_map('trim', explode(';', $sql))));
}
$st = split_sql($sqlRaw);

try {
    $dsn = "mysql:host=" . DB_MAIN['host'] . ";port=" . DB_MAIN['port']
         . ";dbname=" . DB_MAIN['database'] . ";charset=" . DB_MAIN['charset'];
    $pdo = new PDO($dsn, DB_MAIN['username'], DB_MAIN['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "✓ Conectado a " . DB_MAIN['database'] . "\n\n";
} catch (\Throwable $e) {
    echo "✗ Conexión: " . $e->getMessage() . "\n"; exit;
}

// Verificar columna
$cols = [];
foreach ($pdo->query("SHOW COLUMNS FROM sag_usuarios") as $r) $cols[$r['Field']] = true;
echo "Columna 'programa_asignado' en sag_usuarios: " . (isset($cols['programa_asignado']) ? '✓ existe' : '✗ falta') . "\n";

// Verificar roles existentes
$rolesActuales = [];
foreach ($pdo->query("SELECT slug, nombre FROM sag_roles") as $r) $rolesActuales[$r['slug']] = $r['nombre'];
echo "Roles actuales en sag_roles:\n";
foreach ($rolesActuales as $slug => $nombre) echo "  · {$slug} — {$nombre}\n";

$rolesNuevos = ['super_admin','coord_nacional','coord_pip','tecnico_campo','admin_bodega','admin_presupuesto'];
$faltan = array_diff($rolesNuevos, array_keys($rolesActuales));
echo "\nRoles que se van a crear/actualizar: " . (empty($faltan) ? '(ninguno faltante)' : implode(', ', $faltan)) . "\n\n";

if ($modo !== 'ejecutar') {
    echo "⚠ Modo verificación. Agrega ?modo=ejecutar para aplicar.\n";
    exit;
}

$ok = 0; $err = 0;
foreach ($st as $i => $s) {
    try {
        $pdo->exec($s);
        $ok++;
    } catch (\Throwable $e) {
        $err++;
        if (stripos($e->getMessage(),'duplicate')===false && stripos($e->getMessage(),'exists')===false)
            echo "✗ #$i: " . substr($e->getMessage(),0,200) . "\n";
    }
}
echo "→ $ok OK, $err errores (ignorables si dicen 'already exists')\n\n";

// Verificación final
echo "ESTADO FINAL:\n";
foreach ($pdo->query("SELECT slug, nombre, activo FROM sag_roles ORDER BY id_rol") as $r) {
    echo "  · " . str_pad($r['slug'], 22) . " — " . $r['nombre'] . ($r['activo'] ? '' : ' (INACTIVO)') . "\n";
}

echo "\n═════════════════════════════════════════════\n  FIN\n═════════════════════════════════════════════\n";
echo "\nSiguiente paso:\n";
echo "  1. Entra a Mantenimiento → Usuarios\n";
echo "  2. Asigna a cada usuario su rol nuevo y su programa_asignado (pipc/pipg/pipa o vacío para acceso a todos)\n";
echo "  3. Cierra sesión y vuelve a entrar para que el sistema cargue los permisos\n";
