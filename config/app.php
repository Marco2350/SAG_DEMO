<?php
/**
 * Configuración principal — SAG Programas
 */

// ── Variables de entorno (.env) ───────────────────
// Carga el .env de la raíz del proyecto antes de definir constantes.
require_once __DIR__ . '/../core/Env.php';
Env::load(__DIR__ . '/../.env');

// ── URL base ──────────────────────────────────────
// Si BASE_URL no está definida en .env (caso típico al clonar el repo, donde
// .env no se versiona), se autodetecta desde el servidor. Así el proyecto
// funciona sin configurar nada, ya sea en una subcarpeta (localhost/.../SAG_DEMO)
// o en la raíz de un dominio (https://midominio.com). Sólo conviene fijarla en
// .env si hay un proxy/CDN que altere el host o el esquema.
if (!function_exists('sag_detectar_base_url')) {
    function sag_detectar_base_url(): string {
        // En CLI no hay request HTTP: usar default local de desarrollo.
        if (PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST'])) {
            return 'http://localhost';
        }

        $esHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
                || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
                || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);
        $scheme = $esHttps ? 'https' : 'http';

        $host = $_SERVER['HTTP_HOST'];

        // SCRIPT_NAME apunta a index.php; su carpeta es la base del proyecto.
        // En raíz de dominio dirname() devuelve '/' (o '\' en Windows) → se anula.
        $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $base = '/' . trim($base, '/');
        if ($base === '/') {
            $base = '';
        }

        return $scheme . '://' . $host . $base;
    }
}

$baseUrlEnv = trim((string) Env::get('BASE_URL', ''));
define('BASE_URL',   $baseUrlEnv !== '' ? rtrim($baseUrlEnv, '/') : sag_detectar_base_url());
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
        // Filtro OIRSA por texto (substringof sobre ProductActivityName).
        // Los IDs numéricos (ProductActivityId) deben descubrirse vía
        // EntregasController::descubrirRubrosOirsa() y persistirse en la BD,
        // NUNCA hardcodearse: cambian entre ambientes (pruebas vs producción).
        'trazaragro_rubro' => 'café',
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
        'trazaragro_rubro' => 'pecuario',
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
        // VALIDADO contra producción OIRSA 06/2026 vía _test_trazaragro_final.php
        'trazaragro_rubro_id' => 2375,           // Insumo/Incentivo agrícola (738 entregas)
        'trazaragro_rubro'    => 'agrícola',
    ],
    'fprog' => [
        'id'          => 'fprog',
        'id_proyecto' => 4,
        // Identidad oficial (perfil de programa SAG · ejercicio fiscal 2026)
        'nombre'      => 'Fortalecimiento de Programas y Proyectos SAG 2026',
        'nombre_largo'=> 'Fortalecimiento de Programas y Proyectos de la Secretaría de Estado en los Despachos de Agricultura y Ganadería',
        'sigla'       => 'FPROG',
        'icono'       => 'fa-chart-line',
        'color'       => '#0284c7',
        'gradient'    => 'linear-gradient(180deg, #7dd3fc 0%, #0369a1 100%)',
        'color_light' => '#f0f9ff',
        'descripcion' => 'Programa marco SAG 2026: 7 componentes de inversión productiva (riego, semilla, frutales, ganadería) con 5,500 beneficiarios directos.',
        'trazaragro_rubro' => '',
        // Marco institucional del programa (ver PPTX perfil 2026)
        'ejecutor'              => 'Secretaría de Agricultura y Ganadería (SAG)',
        'administrador_fondos'  => 'IICA (RCI 5%)',
        'marco_politica'        => 'Política de Estado del Sector Agroalimentario de Honduras 2023–2043 (PESAH)',
        'marco_legal'           => 'Decreto Legislativo No. 04-2025 — Eje: Seguridad Alimentaria y Soberanía Nacional',
        'responsable_tecnico'   => 'Sub-Coordinador de Programas',
        // Indicadores macro del programa
        'presupuesto_total'     => 116423410.97,           // Lempiras
        'pct_inversion_directa' => 0.85,                   // 85% en los 7 componentes
        'pct_recursos_humanos'  => 0.10,                   // 10% RH
        'pct_rci_iica'          => 0.05,                   // 5% RCI IICA
        'beneficiarios_meta'    => 5500,
        'periodo_inicio'        => '2026-06-01',
        'periodo_fin'           => '2026-12-31',
        'num_componentes'       => 7,
    ],
]);

// ── Catálogo OIRSA: tipos de movimiento (confirmados en producción 06/2026) ──
// IDs estables entre ambientes; los ProductActivityId NO lo son y se descubren
// dinámicamente (ver EntregasController::descubrirRubrosOirsa).
define('OIRSA_TIPOS_MOVIMIENTO', [
    111 => ['nombre' => 'Bodega a Productor', 'naturaleza' => 'Salida'],
    112 => ['nombre' => 'Bodega a Bodega',    'naturaleza' => 'Traslado'],
    113 => ['nombre' => 'Proveedor a Bodega', 'naturaleza' => 'Entrada'],
]);

// ── Catálogo de etnias reconocidas en Honduras ─────────────────
// Fuente: pueblos indígenas y afrohondureños reconocidos oficialmente
// + categorías generales (mestizo, ladino) + "otro" libre.
// Usado para validar entrada en Beneficiarios y carga masiva.
define('ETNIAS_HONDURAS', [
    'lenca'       => 'Lenca',
    'garifuna'    => 'Garífuna',
    'miskito'     => 'Miskito',
    'pech'        => 'Pech (Paya)',
    'tawahka'     => 'Tawahka (Sumo)',
    'tolupan'     => 'Tolupán (Jicaque)',
    'maya_chorti' => 'Maya Ch\'ortí',
    'nahua'       => 'Nahua',
    'negro_ingles'=> 'Negro de habla inglesa',
    'mestizo'     => 'Mestizo',
    'ladino'      => 'Ladino',
    'otro'        => 'Otro',
    'sin_dato'    => 'Sin dato / Prefiere no decir',
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
// Si username/password están vacíos, el cliente trabaja en MOCK.
// Ambiente de pruebas: 'https://pruebas-trazaragro.oirsa.org'
// Ambiente productivo: 'https://trazaragro.oirsa.org'
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
