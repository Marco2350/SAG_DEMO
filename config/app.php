<?php
/**
 * Configuración principal — SAG Programas
 */

// ── URL base ──────────────────────────────────────
define('BASE_URL',   'http://localhost/sag_programas');
define('APP_NAME',   'SAG Honduras Sin Hambre');
define('APP_ENV',    'development');
define('APP_VERSION','2.0');

// ── Sesión ────────────────────────────────────────
define('SESSION_NAME',    'sag_programas_sess');
define('SESSION_TIMEOUT', 3600);   // 1 hora

// ── Zona horaria ──────────────────────────────────
date_default_timezone_set('America/Tegucigalpa');

// ── Programas disponibles ─────────────────────────
define('PROGRAMAS', [
    'pipc' => [
        'id'          => 'pipc',
        'nombre'      => 'Programa de Incentivos para la Producción de Café',
        'sigla'       => 'PIPC',
        'db'          => 'sag_pipc',
        'icono'       => 'svg:bean',
        'color'       => '#7d6249',
        'gradient'    => 'linear-gradient(180deg, #ab9a8a 0%, #4f2a09 100%)',
        'color_light' => '#f5ede5',
        'descripcion' => 'Incentivos y asistencia para productores de café en Honduras',
        // Rubro de Trazaragro (ProductActivityName) a filtrar para este programa
        'trazaragro_rubro' => 'Insumo/Incentivo café',
    ],
    'pipg' => [
        'id'          => 'pipg',
        'nombre'      => 'Programa de Incentivos para la Producción Ganadera',
        'sigla'       => 'PIPG',
        'db'          => 'sag_pipg',
        'icono'       => 'fa-cow',
        'color'       => '#2563eb',
        'color_light' => '#eff6ff',
        'descripcion' => 'Incentivos y asistencia técnica para el sector ganadero',
        // Rubro Trazaragro: captura tanto pecuario como pesquero (substring "pecuario" o "pesquero")
        'trazaragro_rubro' => 'Insumo/Incentivo pecuario',
    ],
    'pipa' => [
        'id'          => 'pipa',
        'nombre'      => 'Programa para la Producción Agrícola',
        'sigla'       => 'PIPA',
        'db'          => 'sag_pipa',
        'icono'       => 'fa-wheat-awn',
        'color'       => '#16a34a',
        'color_light' => '#f0fdf4',
        'descripcion' => 'Apoyo integral a la producción agrícola nacional',
        'trazaragro_rubro' => 'Insumo/Incentivo agrícola',
    ],
]);

// ── Base de datos principal (autenticación) ────────
define('DB_MAIN', [
    'host'     => 'localhost',
    'port'     => 3306,
    'database' => 'sag_main',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
]);

// ── Trazaragro (OIRSA) ─────────────────────────────
// API OData v3 con OAuth2 password grant.
// Si username/password están vacíos, el cliente trabaja en MOCK.
// Ambiente de pruebas: 'https://pruebas-trazaragro.oirsa.org'
// Ambiente productivo: 'https://trazaragro.oirsa.org'
define('TRAZARAGRO', [
    'base_url'      => 'https://pruebas-trazaragro.oirsa.org',
    'username'      => 'joselrns@gmail.com',
    'password'      => 'Palacios',
    'client_id'     => 'TZWEB',
    'client_secret' => '44007759-8c91-4557-9347-53708a1bb5c5',
    'instance'      => 'HN',
    'timeout'       => 30,
]);

// ── Error reporting ───────────────────────────────
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
