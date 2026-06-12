<?php
/**
 * Punto de entrada único — SAG Programas Honduras Sin Hambre v2
 */
define('ROOT_PATH', __DIR__);

require_once ROOT_PATH . '/config/app.php';

// Autoload
spl_autoload_register(function (string $class): void {
    $paths = [
        ROOT_PATH . "/core/{$class}.php",
        ROOT_PATH . "/app/controllers/{$class}.php",
        ROOT_PATH . "/app/models/{$class}.php",
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) { require_once $path; return; }
    }
});

// Sesión
session_name(SESSION_NAME);
session_start();

// Protección session fixation
if (!isset($_SESSION['_last_regen'])) {
    $_SESSION['_last_regen'] = time();
} elseif (time() - $_SESSION['_last_regen'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['_last_regen'] = time();
}

// Token CSRF — se genera una sola vez por sesión
if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}

// Timeout de sesión
if (isset($_SESSION['user'], $_SESSION['_last_activity'])) {
    if (time() - $_SESSION['_last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: ' . BASE_URL . '/auth/login?timeout=1');
        exit;
    }
}
if (isset($_SESSION['user'])) {
    $_SESSION['_last_activity'] = time();
}

// Core
require_once ROOT_PATH . '/core/Router.php';
require_once ROOT_PATH . '/core/Controller.php';
require_once ROOT_PATH . '/core/Model.php';
require_once ROOT_PATH . '/core/Database.php';

// ──────────────────────────────────────────────────────────────
//  Detección defensiva del paquete CSRF
//  El sistema CSRF requiere que Controller.php, main.js, header.php
//  y AuthController.php estén actualizados como un paquete completo.
//  Si Controller::csrfToken() no existe, asumimos que el sistema está
//  en modo legacy y desactivamos el middleware para no romper nada.
// ──────────────────────────────────────────────────────────────
require_once ROOT_PATH . '/core/Controller.php';
define('CSRF_ENABLED', method_exists('Controller', 'csrfToken'));

// Helper global para inyectar el token CSRF en las vistas
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        return CSRF_ENABLED ? Controller::csrfToken() : '';
    }
}
if (!function_exists('csrf_field')) {
    function csrf_field(): string {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
    }
}

/**
 * Helper para renderizar el icono del programa.
 * Soporta clases FontAwesome (ej: 'fa-cow') o iconos SVG personalizados
 * declarados como 'svg:<nombre>' (ej: 'svg:bean' para grano de café).
 *
 * @param array  $prog       Array del programa (ver config/app.php)
 * @param string $extraStyle Estilos CSS adicionales (font-size, margin, etc.)
 * @param bool   $useGradient Si true y el programa tiene 'gradient', lo aplica al SVG
 */
if (!function_exists('progIconHtml')) {
    function progIconHtml(array $prog, string $extraStyle = '', bool $useGradient = true): string {
        $color = $prog['color']    ?? '#54668E';
        $grad  = $useGradient ? ($prog['gradient'] ?? null) : null;
        $ico   = $prog['icono']    ?? 'fa-seedling';

        // Icono SVG personalizado
        if (is_string($ico) && str_starts_with($ico, 'svg:')) {
            $name = substr($ico, 4);
            $gid  = 'pgi_' . substr(md5($name . $color . ($grad ?? '')), 0, 8);

            // Parsear el linear-gradient para extraer los stops
            $fillRef = htmlspecialchars($color);
            $defs    = '';
            if ($grad && preg_match_all('/#[0-9a-fA-F]{3,8}/', $grad, $m)) {
                $stops = $m[0];
                if (count($stops) >= 2) {
                    $defs = '<defs><linearGradient id="' . $gid . '" x1="0" y1="0" x2="0" y2="1">'
                          . '<stop offset="0%" stop-color="' . htmlspecialchars($stops[0]) . '"/>'
                          . '<stop offset="100%" stop-color="' . htmlspecialchars($stops[count($stops)-1]) . '"/>'
                          . '</linearGradient></defs>';
                    $fillRef = 'url(#' . $gid . ')';
                }
            }

            $baseStyle = 'width:1em;height:1em;vertical-align:-0.125em;' . htmlspecialchars($extraStyle);

            if ($name === 'bean') {
                return '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="' . $baseStyle . '">'
                     . $defs
                     . '<g transform="rotate(22 12 12)">'
                     . '<ellipse cx="12" cy="12" rx="5.6" ry="9" fill="' . $fillRef . '"/>'
                     . '<path d="M12 4.2 C 10.3 8 10.3 16 12 19.8" stroke="rgba(255,255,255,.55)" stroke-width="1.3" fill="none" stroke-linecap="round"/>'
                     . '</g></svg>';
            }
            return '';
        }

        // FontAwesome (comportamiento original)
        return '<i class="fas ' . htmlspecialchars($ico) . '" style="color:' . htmlspecialchars($color) . ';' . htmlspecialchars($extraStyle) . '"></i>';
    }
}

/**
 * Devuelve el background del programa: gradiente si existe, sino el color con alpha opcional.
 *
 * @param array  $prog
 * @param string $alphaHex Sufijo hexadecimal de alpha si se usa color sólido (ej: '22', '15')
 */
if (!function_exists('progBackground')) {
    function progBackground(array $prog, string $alphaHex = ''): string {
        if (!empty($prog['gradient'])) return $prog['gradient'];
        return ($prog['color'] ?? '#54668E') . $alphaHex;
    }
}

/**
 * Helper para versionar assets locales y evitar problemas de caché.
 * Usa la fecha de modificación del archivo cuando es posible (auto-bust).
 * En producción cae en APP_VERSION como fallback.
 */
if (!function_exists('asset')) {
    function asset(string $relativePath): string {
        $absolute = ROOT_PATH . '/' . ltrim($relativePath, '/');
        $version  = is_file($absolute) ? filemtime($absolute) : APP_VERSION;
        return BASE_URL . '/' . ltrim($relativePath, '/') . '?v=' . $version;
    }
}

// ── Validación CSRF automática en TODOS los POST excepto login ──
// Solo se activa si el sistema tiene el paquete CSRF completo (Controller.php nuevo)
if (CSRF_ENABLED && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $reqUri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $basePath = parse_url(BASE_URL, PHP_URL_PATH);
    if ($basePath && str_starts_with($reqUri, $basePath)) {
        $reqUri = substr($reqUri, strlen($basePath));
    }
    $reqUri = '/' . trim($reqUri, '/');

    // Rutas exentas de CSRF (login y carga inicial de sesión)
    $csrfExentas = ['/auth/login'];

    if (!in_array($reqUri, $csrfExentas, true)) {
        $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!$token || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
            http_response_code(419);
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                   && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Token de seguridad inválido. Recargue la página (F5).'
                ]);
            } else {
                echo 'Token de seguridad inválido. Recargue la página (F5).';
            }
            exit;
        }
    }
}

$router = new Router();

// ── Auth ──────────────────────────────────────────
$router->get('/',                    'AuthController', 'login');
$router->get('/auth/login',          'AuthController', 'login');
$router->post('/auth/login',         'AuthController', 'doLogin');
$router->get('/auth/logout',         'AuthController', 'logout');

// ── Selector de Programas ─────────────────────────
$router->get('/programas',           'ProgramasController', 'index');
$router->post('/programas/seleccionar', 'ProgramasController', 'seleccionar');
$router->get('/programas/salir',     'ProgramasController', 'salir');

// ── Dashboard ─────────────────────────────────────
$router->get('/dashboard',           'DashboardController', 'index');
$router->get('/dashboard/stats',     'DashboardController', 'stats');
$router->get('/dashboard/mapa',      'DashboardController', 'mapa');

// ── Organizaciones ────────────────────────────────
$router->get('/organizaciones',              'OrganizacionesController', 'index');
$router->post('/organizaciones/listar',      'OrganizacionesController', 'listar');
$router->post('/organizaciones/get',         'OrganizacionesController', 'get');
$router->post('/organizaciones/save',        'OrganizacionesController', 'save');
$router->post('/organizaciones/checkNombre', 'OrganizacionesController', 'checkNombre');
$router->post('/organizaciones/estado',      'OrganizacionesController', 'estado');
$router->post('/organizaciones/delete',      'OrganizacionesController', 'delete');
$router->post('/organizaciones/miembros',    'OrganizacionesController', 'miembros');

// ── Beneficiarios ─────────────────────────────────
$router->get('/beneficiarios',               'BeneficiariosController', 'index');
$router->post('/beneficiarios/listar',       'BeneficiariosController', 'listar');
$router->post('/beneficiarios/get',          'BeneficiariosController', 'get');
$router->post('/beneficiarios/save',         'BeneficiariosController', 'save');
$router->post('/beneficiarios/masivo',       'BeneficiariosController', 'masivo');
$router->post('/beneficiarios/delete',       'BeneficiariosController', 'delete');

// ── Capacitaciones ────────────────────────────────
$router->get('/capacitaciones',                      'CapacitacionesController', 'index');
$router->post('/capacitaciones/listar',              'CapacitacionesController', 'listar');
$router->post('/capacitaciones/get',                 'CapacitacionesController', 'get');
$router->post('/capacitaciones/save',                'CapacitacionesController', 'save');
$router->post('/capacitaciones/finalizar',           'CapacitacionesController', 'finalizar');
$router->post('/capacitaciones/delete',              'CapacitacionesController', 'delete');
$router->post('/capacitaciones/participante/add',    'CapacitacionesController', 'addParticipante');
$router->post('/capacitaciones/participante/delete', 'CapacitacionesController', 'deleteParticipante');
// R-028: Evidencia documental
$router->post('/capacitaciones/evidencia/subir',     'CapacitacionesController', 'subirEvidencia');
$router->post('/capacitaciones/evidencia/validar',   'CapacitacionesController', 'validarEvidencia');
$router->get( '/capacitaciones/evidencia',           'CapacitacionesController', 'evidencia');

// ── Asistencia Técnica ────────────────────────────
$router->get('/asistencia',                  'AsistenciaTecnicaController', 'index');
$router->post('/asistencia/listar',          'AsistenciaTecnicaController', 'listar');
$router->post('/asistencia/get',             'AsistenciaTecnicaController', 'get');
$router->post('/asistencia/save',            'AsistenciaTecnicaController', 'save');
$router->post('/asistencia/finalizar',       'AsistenciaTecnicaController', 'finalizar');
$router->post('/asistencia/delete',          'AsistenciaTecnicaController', 'delete');
// R-027: Evidencia documental
$router->post('/asistencia/evidencia/subir',   'AsistenciaTecnicaController', 'subirEvidencia');
$router->post('/asistencia/evidencia/validar', 'AsistenciaTecnicaController', 'validarEvidencia');
$router->get( '/asistencia/evidencia',         'AsistenciaTecnicaController', 'evidencia');

// ── Estadísticas ──────────────────────────────────
$router->get('/estadisticas',                'EstadisticasController', 'index');
$router->get('/estadisticas/datos',          'EstadisticasController', 'datos');

// ── Exportar ──────────────────────────────────────
$router->get('/exportar',                    'ExportarController', 'index');
$router->post('/exportar/generar',           'ExportarController', 'generar');

// ── Mantenimiento ─────────────────────────────────
$router->get('/mantenimiento',               'MantenimientoController', 'index');
$router->post('/mantenimiento/usuarios/save','MantenimientoController', 'saveUsuario');
$router->post('/mantenimiento/usuarios/delete','MantenimientoController','deleteUsuario');
$router->post('/mantenimiento/tecnicos/save','MantenimientoController', 'saveTecnico');
$router->post('/mantenimiento/tecnicos/delete','MantenimientoController','deleteTecnico');
$router->post('/mantenimiento/temas/save',   'MantenimientoController', 'saveTema');
$router->post('/mantenimiento/temas/delete', 'MantenimientoController', 'deleteTema');
$router->post('/mantenimiento/subtemas/save','MantenimientoController', 'saveSubtema');
$router->post('/mantenimiento/subtemas/delete','MantenimientoController','deleteSubtema');
$router->post('/mantenimiento/cultivos/save','MantenimientoController', 'saveCultivo');
$router->post('/mantenimiento/cultivos/delete','MantenimientoController','deleteCultivo');
$router->post('/mantenimiento/tipoat/save',  'MantenimientoController', 'saveTipoAT');
$router->post('/mantenimiento/tipoat/delete','MantenimientoController', 'deleteTipoAT');

// ── Auditoría (bitácora del sistema) ─────────────
$router->get('/auditoria',                   'AuditoriaController', 'index');
$router->post('/auditoria/listar',           'AuditoriaController', 'listar');

// ── Parametrización (catálogos del programa) ─────
$router->get('/catalogos/tecnicos',          'CatalogosController', 'tecnicos');
$router->get('/catalogos/temas',             'CatalogosController', 'temas');
$router->get('/catalogos/cultivos',          'CatalogosController', 'cultivos');
$router->get('/catalogos/tiposat',           'CatalogosController', 'tiposat');

// ── Presupuesto / Ejecución Financiera ───────────
$router->get('/presupuesto',                     'PresupuestoController', 'index');
$router->post('/presupuesto/save',               'PresupuestoController', 'savePresupuesto');
$router->post('/presupuesto/autorizar',          'PresupuestoController', 'autorizarPresupuesto');
$router->post('/presupuesto/get',                'PresupuestoController', 'getPresupuesto');
$router->post('/presupuesto/lineas/listar',      'PresupuestoController', 'listarLineas');
$router->post('/presupuesto/lineas/save',        'PresupuestoController', 'saveLinea');
$router->post('/presupuesto/lineas/delete',      'PresupuestoController', 'deleteLinea');
$router->post('/presupuesto/modificaciones/listar','PresupuestoController','listarModificaciones');
$router->post('/presupuesto/compras/listar',     'PresupuestoController', 'listarCompras');
$router->post('/presupuesto/compras/get',        'PresupuestoController', 'getCompra');
$router->post('/presupuesto/compras/save',       'PresupuestoController', 'saveCompra');
$router->post('/presupuesto/compras/estado',     'PresupuestoController', 'estadoCompra');
$router->post('/presupuesto/viaticos/listar',    'PresupuestoController', 'listarViaticos');
$router->post('/presupuesto/viaticos/get',       'PresupuestoController', 'getViatico');
$router->post('/presupuesto/viaticos/save',      'PresupuestoController', 'saveViatico');
$router->post('/presupuesto/viaticos/visto',     'PresupuestoController', 'vistoBoeno');
$router->post('/presupuesto/viaticos/aprobar',   'PresupuestoController', 'aprobarViatico');
$router->post('/presupuesto/viaticos/liquidar',  'PresupuestoController', 'liquidarViatico');
$router->post('/presupuesto/gastos/listar',      'PresupuestoController', 'listarGastos');
$router->post('/presupuesto/gastos/get',         'PresupuestoController', 'getGasto');
$router->post('/presupuesto/gastos/save',        'PresupuestoController', 'saveGasto');
$router->post('/presupuesto/gastos/estado',      'PresupuestoController', 'estadoGasto');
$router->post('/presupuesto/documentos/listar',  'PresupuestoController', 'listarDocumentos');
$router->post('/presupuesto/documentos/save',    'PresupuestoController', 'saveDocumento');
$router->post('/presupuesto/documentos/delete',  'PresupuestoController', 'deleteDocumento');
$router->get('/presupuesto/documentos/ver',      'PresupuestoController', 'descargarDocumento');
$router->get('/presupuesto/api/lineas',          'PresupuestoController', 'apiLineas');

// ── Entregas de Incentivos (mock por ahora — Kobo + Trazaragro) ──────
$router->get('/entregas',                            'EntregasController', 'index');
$router->get('/entregas/diag',                       'EntregasController', 'diag');
$router->get('/entregas/exportar',                   'EntregasController', 'exportar');
$router->get('/entregas/acta',                       'EntregasController', 'acta');
$router->post('/entregas/sincronizar',               'EntregasController', 'sincronizar');
$router->post('/entregas/sincronizarTrazaragro',     'EntregasController', 'sincronizarTrazaragro');
$router->post('/entregas/aprobar',                   'EntregasController', 'aprobar');
$router->post('/entregas/rechazar',                  'EntregasController', 'rechazar');
$router->post('/entregas/detalle',                   'EntregasController', 'detalle');

// ── Inventarios de Incentivos (cronogramas + stock + kardex) ─────────
$router->get('/inventarios',                         'InventariosController', 'index');
$router->post('/inventarios/listarCronogramas',      'InventariosController', 'listarCronogramas');
$router->post('/inventarios/getCronograma',          'InventariosController', 'getCronograma');
$router->post('/inventarios/saveCronograma',         'InventariosController', 'saveCronograma');
$router->post('/inventarios/recibirLinea',           'InventariosController', 'recibirLinea');
$router->post('/inventarios/kardex',                 'InventariosController', 'kardex');
$router->post('/inventarios/stockPorBodega',         'InventariosController', 'stockPorBodega');

// ── API catálogos AJAX ────────────────────────────
$router->get('/api/municipios',              'ApiController', 'municipios');
$router->get('/api/subtemas',                'ApiController', 'subtemas');
$router->get('/api/tecnicos',                'ApiController', 'tecnicos');
$router->get('/api/organizaciones',          'ApiController', 'organizaciones');
$router->get('/api/departamentos',           'ApiController', 'departamentos');

$router->dispatch();
