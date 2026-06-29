<?php
require_once __DIR__ . '/_test_guard.php';
/**
 * Migración 009 — Roles, privilegios en BD y acceso multi-proyecto.
 * Aplica SOLO en sag_main.
 * URL: http://localhost/SAG_DEMO/migrar_009.php?modo=ejecutar
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL); ini_set('display_errors', '1');
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config/app.php';

$sqlRaw = file_get_contents(ROOT_PATH . '/sql/migracion_023_roles_privilegios.sql');
$modo   = $_GET['modo'] ?? 'verificar';

echo "═════════════════════════════════════════════\n";
echo "  MIGRACIÓN 023 — Roles, privilegios y multi-proyecto\n";
echo "  (aplica solo en sag_main)\n";
echo "═════════════════════════════════════════════\n\n";

/**
 * Divide el SQL en sentencias respetando que las sentencias comentadas se
 * descartan. Mantiene el orden (las TEMPORARY TABLE viven en la conexión).
 */
function split_sql(string $sql): array {
    // Quitar comentarios de línea (-- ...) sin tocar el resto.
    $sql = preg_replace('/^\s*--[^\n]*$/m', '', $sql);
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

// ── Estado previo ──
$tablas = [];
foreach ($pdo->query("SHOW TABLES") as $r) $tablas[array_values($r)[0]] = true;
echo "Tablas nuevas:\n";
foreach (['sag_modulos','sag_acciones','sag_rol_privilegio','sag_usuario_proyecto'] as $t) {
    echo "  · " . str_pad($t, 22) . (isset($tablas[$t]) ? '✓ existe' : '✗ se creará') . "\n";
}
$cols = [];
foreach ($pdo->query("SHOW COLUMNS FROM sag_usuarios") as $r) $cols[$r['Field']] = true;
echo "  · sag_usuarios.todos_proyectos " . (isset($cols['todos_proyectos']) ? '✓ existe' : '✗ se creará') . "\n\n";

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
        $msg = $e->getMessage();
        // 'exists'/'duplicate' son ignorables en re-ejecuciones idempotentes.
        if (stripos($msg, 'duplicate') === false && stripos($msg, 'exists') === false) {
            $err++;
            echo "✗ #$i: " . substr($msg, 0, 200) . "\n    " . substr($s, 0, 120) . "...\n";
        }
    }
}
echo "→ $ok sentencias OK, $err errores reales\n\n";

// ── Verificación final ──
echo "PRIVILEGIOS POR ROL:\n";
$q = "SELECT r.slug, COUNT(rp.id_accion) AS n
      FROM sag_roles r
      LEFT JOIN sag_rol_privilegio rp ON rp.id_rol = r.id_rol
      GROUP BY r.id_rol ORDER BY r.slug";
foreach ($pdo->query($q) as $r) {
    echo "  · " . str_pad($r['slug'], 22) . " — {$r['n']} privilegios\n";
}

echo "\nACCESO POR USUARIO:\n";
$q = "SELECT u.username, u.todos_proyectos,
             GROUP_CONCAT(p.codigo ORDER BY p.codigo) AS proyectos
      FROM sag_usuarios u
      LEFT JOIN sag_usuario_proyecto up ON up.id_usuario = u.id_usuario
      LEFT JOIN sag_proyectos p ON p.id_proyecto = up.id_proyecto
      GROUP BY u.id_usuario ORDER BY u.username";
foreach ($pdo->query($q) as $r) {
    $alcance = $r['todos_proyectos'] ? 'TODOS' : ($r['proyectos'] ?: '(ninguno)');
    echo "  · " . str_pad($r['username'], 22) . " — {$alcance}\n";
}

echo "\n═════════════════════════════════════════════\n  FIN\n═════════════════════════════════════════════\n";
echo "\nSiguiente paso (lo implementamos en el código tras validar esta migración):\n";
echo "  1. core/Permisos.php leerá la matriz desde sag_rol_privilegio.\n";
echo "  2. La UI de Mantenimiento administrará roles, privilegios y proyectos.\n";
