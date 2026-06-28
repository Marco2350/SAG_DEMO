<?php
require_once __DIR__ . '/_test_guard.php';
/**
 * R-014: Verificar que los 298 municipios de Honduras estén disponibles
 * y correctamente relacionados con su departamento.
 *
 * URL: http://localhost/sag_programas/verificar_municipios.php
 *
 * Borrar este archivo después de verificar.
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config/app.php';

echo "═══════════════════════════════════════════════════════════════\n";
echo "  R-014 · Verificación del catálogo territorial (298 municipios)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

foreach (PROGRAMAS as $progId => $prog) {
    echo "──────────────────────────────────────────\n";
    echo "  Programa: " . $prog['sigla'] . " · BD: " . $prog['db'] . "\n";
    echo "──────────────────────────────────────────\n";

    try {
        $dsn = "mysql:host=" . DB_MAIN['host'] . ";port=" . DB_MAIN['port']
             . ";dbname=" . $prog['db'] . ";charset=" . DB_MAIN['charset'];
        $pdo = new PDO($dsn, DB_MAIN['username'], DB_MAIN['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (\Throwable $e) {
        echo "  ✗ Conexión: " . $e->getMessage() . "\n\n"; continue;
    }

    $totDep = (int)$pdo->query("SELECT COUNT(*) FROM sag_departamentos WHERE activo=1")->fetchColumn();
    $totMun = (int)$pdo->query("SELECT COUNT(*) FROM sag_municipios WHERE activo=1")->fetchColumn();
    $munOrf = (int)$pdo->query(
        "SELECT COUNT(*) FROM sag_municipios m
         LEFT JOIN sag_departamentos d ON d.id_departamento = m.id_departamento
         WHERE m.activo=1 AND d.id_departamento IS NULL"
    )->fetchColumn();

    echo "  Departamentos activos: {$totDep} (esperado: 18)\n";
    echo "  Municipios activos:    {$totMun} (esperado: 298)\n";
    echo "  Municipios huérfanos (sin dep válido): {$munOrf}\n";

    if ($totMun === 298 && $totDep === 18 && $munOrf === 0) {
        echo "  ✓ Catálogo territorial completo y consistente\n\n";
    } else {
        echo "  ⚠ Discrepancia detectada — revisar catálogo\n";
        // Mostrar departamentos con conteo de municipios
        echo "  Detalle por departamento:\n";
        $rows = $pdo->query(
            "SELECT d.nombre, COUNT(m.id_municipio) AS cant
             FROM sag_departamentos d
             LEFT JOIN sag_municipios m ON m.id_departamento = d.id_departamento AND m.activo=1
             WHERE d.activo=1
             GROUP BY d.id_departamento, d.nombre
             ORDER BY d.nombre"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            echo "    · " . str_pad($r['nombre'], 28) . " " . str_pad((string)$r['cant'], 4, ' ', STR_PAD_LEFT) . " municipios\n";
        }
        echo "\n";
    }
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "  FIN\n";
echo "═══════════════════════════════════════════════════════════════\n";
