<?php

/**
 * Pruebas rápidas sin base de datos ni efectos secundarios.
 *
 * Uso: C:\xampp\php\php.exe tests\smoke.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Esta prueba solo puede ejecutarse desde CLI.\n");
}

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/permisos.php';
require_once ROOT_PATH . '/core/Permisos.php';

$fallos = [];
$pruebas = 0;

function verificar(bool $condicion, string $mensaje): void
{
    global $fallos, $pruebas;
    $pruebas++;
    if (!$condicion) {
        $fallos[] = $mensaje;
    }
}

function sesionPrueba(string $rol, ?string $asignado, string $activo): void
{
    $_SESSION = [
        'user' => [
            'rol_slug' => $rol,
            'programa_asignado' => $asignado,
        ],
        'programa' => ['id' => $activo],
    ];
}

$_SESSION = [];
verificar(!Permisos::puede('dashboard'), 'Una sesión sin rol obtuvo acceso.');

sesionPrueba('super_admin', null, 'pipc');
verificar(Permisos::puedeEn('mantenimiento', ACC_ELIMINAR), 'Super administrador sin acceso total.');

sesionPrueba('tecnico_campo', 'pipc', 'pipc');
verificar(Permisos::puedeEn('beneficiarios', ACC_CREAR), 'Técnico sin permiso de captura.');
verificar(!Permisos::puedeEn('beneficiarios', ACC_ELIMINAR), 'Técnico obtuvo permiso de eliminación.');

sesionPrueba('tecnico_campo', 'pipc', 'pipg');
verificar(!Permisos::puedeEn('beneficiarios', ACC_CREAR), 'No se bloqueó el cruce entre PIP.');

sesionPrueba('admin_bodega', 'pipa', 'pipa');
verificar(Permisos::puedeEn('inventarios', ACC_CARGAR), 'Bodega sin permiso para inventario.');
verificar(!Permisos::puedeEn('mantenimiento', ACC_VER), 'Bodega obtuvo acceso a mantenimiento.');

$appConfig = file_get_contents(ROOT_PATH . '/config/app.php') ?: '';
verificar(
    !preg_match("/TRAZARAGRO_CLIENT_SECRET'\\s*,\\s*'[^']{8,}'/", $appConfig),
    'Hay un secreto OIRSA predeterminado dentro del código.'
);

$htaccess = file_get_contents(ROOT_PATH . '/.htaccess') ?: '';
verificar(
    str_contains($htaccess, 'public/uploads|uploads'),
    'Los directorios de archivos no están bloqueados para acceso directo.'
);

if ($fallos) {
    fwrite(STDERR, "FALLARON " . count($fallos) . " de {$pruebas} pruebas:\n");
    foreach ($fallos as $fallo) {
        fwrite(STDERR, " - {$fallo}\n");
    }
    exit(1);
}

echo "OK: {$pruebas} pruebas de seguridad y permisos.\n";
