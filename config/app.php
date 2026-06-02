<?php
/**
 * Configuración principal — SAG Programas
 */

// ── Variables de entorno (.env) ───────────────────
// Carga el .env de la raíz del proyecto antes de definir constantes.
require_once __DIR__ . '/../core/Env.php';
Env::load(__DIR__ . '/../.env');

// ── URL base ──────────────────────────────────────
define('BASE_URL',   Env::get('BASE_URL', 'http://localhost/PROYECTOS-PHP/SAG'));
define('APP_NAME',   Env::get('APP_NAME', 'SAG Honduras Sin Hambre'));
define('APP_ENV',    Env::get('APP_ENV', 'development'));
define('APP_VERSION','2.0');

// ── Sesión ────────────────────────────────────────
define('SESSION_NAME',    'sag_programas_sess');
define('SESSION_TIMEOUT', 3600);   // 1 hora

// ── Zona horaria ──────────────────────────────────
date_default_timezone_set('America/Tegucigalpa');

// ── Programas disponibles ─────────────────────────
// Todos los programas comparten la base sag_main; se distinguen por
// id_proyecto (alineado con el seed de la tabla sag_proyectos en bd.sql).
define('PROGRAMAS', [
    'pipc' => [
        'id'          => 'pipc',
        'id_proyecto' => 1,
        'nombre'      => 'Programa de Incentivos para la Producción de Café',
        'sigla'       => 'PIPC',
        'icono'       => 'svg:bean',
        'color'       => '#7d6249',
        'gradient'    => 'linear-gradient(180deg, #ab9a8a 0%, #4f2a09 100%)',
        'color_light' => '#f5ede5',
        'descripcion' => 'Incentivos y asistencia para productores de café en Honduras',
    ],
    'pipg' => [
        'id'          => 'pipg',
        'id_proyecto' => 2,
        'nombre'      => 'Programa de Incentivos para la Producción Ganadera',
        'sigla'       => 'PIPG',
        'icono'       => 'fa-cow',
        'color'       => '#2563eb',
        'color_light' => '#eff6ff',
        'descripcion' => 'Incentivos y asistencia técnica para el sector ganadero',
    ],
    'pipa' => [
        'id'          => 'pipa',
        'id_proyecto' => 3,
        'nombre'      => 'Programa para la Producción Agrícola',
        'sigla'       => 'PIPA',
        'icono'       => 'fa-wheat-awn',
        'color'       => '#16a34a',
        'color_light' => '#f0fdf4',
        'descripcion' => 'Apoyo integral a la producción agrícola nacional',
    ],
]);

// ── Base de datos única (autenticación + todos los programas) ──
define('DB_MAIN', [
    'host'     => Env::get('DB_HOST', 'localhost'),
    'port'     => (int) Env::get('DB_PORT', 3306),
    'database' => Env::get('DB_NAME', 'mddesarr_sag'),
    'username' => Env::get('DB_USER', 'root'),
    'password' => Env::get('DB_PASS', ''),
    'charset'  => Env::get('DB_CHARSET', 'utf8mb4'),
]);

// ── Trazaragro (OIRSA) ─────────────────────────────
// API OData v3 con OAuth2 password grant.
// Mientras username/password estén vacíos, el cliente trabaja en MOCK.
// Cambia a 'https://trazaragro.oirsa.org' cuando estés en producción.
define('TRAZARAGRO', [
    'base_url'      => Env::get('TRAZARAGRO_BASE_URL', 'https://pruebas-trazaragro.oirsa.org'),
    'username'      => Env::get('TRAZARAGRO_USERNAME', ''),   // ← usuario otorgado por OIRSA
    'password'      => Env::get('TRAZARAGRO_PASSWORD', ''),   // ← contraseña
    'client_id'     => Env::get('TRAZARAGRO_CLIENT_ID', 'TZWEB'),
    'client_secret' => Env::get('TRAZARAGRO_CLIENT_SECRET', '44007759-8c91-4557-9347-53708a1bb5c5'),
    'instance'      => Env::get('TRAZARAGRO_INSTANCE', 'HN'),
    'timeout'       => (int) Env::get('TRAZARAGRO_TIMEOUT', 30),
]);

// ── Error reporting ───────────────────────────────
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
