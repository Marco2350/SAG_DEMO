<?php
/**
 * Diagnóstico de permisos: muestra el rol del usuario actual y qué puede hacer.
 * URL: http://localhost/sag_programas/diag_permisos.php
 * Borrar tras pruebas.
 */
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL); ini_set('display_errors', '1');
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/permisos.php';
require_once ROOT_PATH . '/core/Permisos.php';

session_name(SESSION_NAME);
session_start();

echo "═══════════════════════════════════════════════════════════════\n";
echo "  DIAGNÓSTICO DE PERMISOS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if (empty($_SESSION['user'])) {
    echo "⚠ Sin sesión activa. Inicia sesión primero.\n"; exit;
}

echo "USUARIO:\n";
echo "  Nombre:            " . ($_SESSION['user']['nombre'] ?? '?') . " " . ($_SESSION['user']['apellido'] ?? '') . "\n";
echo "  Email:             " . ($_SESSION['user']['email']  ?? '?') . "\n";
echo "  Rol slug:          " . ($_SESSION['user']['rol_slug']    ?? '(ninguno)') . "\n";
echo "  Rol nombre:        " . ($_SESSION['user']['rol_nombre']  ?? '(ninguno)') . "\n";
echo "  Programa asignado: " . ($_SESSION['user']['programa_asignado'] ?? '(todos)') . "\n";
echo "  PIP activo:        " . ($_SESSION['programa']['id']      ?? '(ninguno)') . "\n";

$info = Permisos::rolInfo();
echo "\nROL EN MATRIZ:\n";
echo "  Nombre:       " . $info['nombre'] . "\n";
echo "  Descripción:  " . $info['descripcion'] . "\n";
echo "  Alcance PIP:  " . $info['alcance_pip'] . "\n";
echo "  Es admin:     " . ($info['es_admin'] ? 'sí' : 'no') . "\n";

$modulos = ['dashboard','organizaciones','beneficiarios','asistencia','capacitaciones','entregas','inventarios','estadisticas','exportar','presupuesto','mantenimiento'];
$acciones = [ACC_VER, ACC_CREAR, ACC_EDITAR, ACC_ELIMINAR, ACC_APROBAR, ACC_EXPORTAR, ACC_CARGAR];

echo "\nMATRIZ DE PERMISOS (✓ = permitido, · = no):\n\n";
echo str_pad("MÓDULO", 18);
foreach ($acciones as $a) echo str_pad(strtoupper($a), 12);
echo "\n";
echo str_repeat('─', 18 + count($acciones)*12) . "\n";

foreach ($modulos as $m) {
    echo str_pad($m, 18);
    foreach ($acciones as $a) {
        echo str_pad(Permisos::puede($m, $a) ? '   ✓' : '   ·', 12);
    }
    echo "\n";
}

echo "\nOperar PIP activo: " . (Permisos::puedeOperarPipActivo() ? '✓ sí' : '✗ no') . "\n";
echo "\nMÓDULOS VISIBLES EN SIDEBAR: " . implode(', ', Permisos::modulosVisibles()) . "\n";
