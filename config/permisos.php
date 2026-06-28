<?php
/**
 * Matriz central de autorización SAG_DEMO.
 *
 * Los roles heredados se mantienen para no bloquear instalaciones existentes.
 * Los permisos siempre se combinan con el programa asignado al usuario.
 */

defined('ACC_VER')      || define('ACC_VER', 'ver');
defined('ACC_CREAR')    || define('ACC_CREAR', 'crear');
defined('ACC_EDITAR')   || define('ACC_EDITAR', 'editar');
defined('ACC_ELIMINAR') || define('ACC_ELIMINAR', 'eliminar');
defined('ACC_APROBAR')  || define('ACC_APROBAR', 'aprobar');
defined('ACC_EXPORTAR') || define('ACC_EXPORTAR', 'exportar');
defined('ACC_CARGAR')   || define('ACC_CARGAR', 'cargar');

define('ROLES', [
    'super_admin' => [
        'nombre' => 'Super Administrador',
        'descripcion' => 'Acceso total al sistema.',
        'alcance_pip' => 'todos',
        'es_admin' => true,
    ],
    'admin' => [
        'nombre' => 'Administrador',
        'descripcion' => 'Rol administrador heredado con acceso total.',
        'alcance_pip' => 'todos',
        'es_admin' => true,
    ],
    'administrador' => [
        'nombre' => 'Administrador',
        'descripcion' => 'Alias heredado del rol administrador.',
        'alcance_pip' => 'todos',
        'es_admin' => true,
    ],
    'coord_nacional' => [
        'nombre' => 'Coordinador Nacional',
        'descripcion' => 'Supervisión y operación nacional de los programas.',
        'alcance_pip' => 'todos',
        'es_admin' => false,
    ],
    'coordinador' => [
        'nombre' => 'Coordinador',
        'descripcion' => 'Rol coordinador heredado.',
        'alcance_pip' => 'todos',
        'es_admin' => false,
    ],
    'coord_pip' => [
        'nombre' => 'Coordinador de PIP',
        'descripcion' => 'Gestión integral del programa asignado.',
        'alcance_pip' => 'asignado',
        'es_admin' => false,
    ],
    'tecnico_campo' => [
        'nombre' => 'Técnico de Campo',
        'descripcion' => 'Captura operativa de asistencia y capacitaciones.',
        'alcance_pip' => 'asignado',
        'es_admin' => false,
    ],
    'tecnico' => [
        'nombre' => 'Técnico',
        'descripcion' => 'Rol técnico heredado.',
        'alcance_pip' => 'asignado',
        'es_admin' => false,
    ],
    'admin_bodega' => [
        'nombre' => 'Administrador de Bodega',
        'descripcion' => 'Gestión de inventario y recepción de insumos.',
        'alcance_pip' => 'asignado',
        'es_admin' => false,
    ],
    'admin_presupuesto' => [
        'nombre' => 'Administrador de Programa',
        'descripcion' => 'Gestión presupuestaria del programa asignado.',
        'alcance_pip' => 'asignado',
        'es_admin' => false,
    ],
    'jefe' => [
        'nombre' => 'Jefatura',
        'descripcion' => 'Rol heredado para revisión y visto bueno.',
        'alcance_pip' => 'asignado',
        'es_admin' => false,
    ],
]);

$todosLosModulos = [
    'dashboard', 'organizaciones', 'beneficiarios', 'asistencia',
    'capacitaciones', 'entregas', 'inventarios', 'movilizaciones',
    'fprog', 'estadisticas', 'exportar', 'presupuesto', 'flota',
    'catalogos', 'mantenimiento', 'auditoria',
];
$accesoTotal = array_fill_keys($todosLosModulos, ['*']);
$operacionCompleta = [
    ACC_VER, ACC_CREAR, ACC_EDITAR, ACC_ELIMINAR,
    ACC_APROBAR, ACC_EXPORTAR, ACC_CARGAR,
];
$soloLectura = [ACC_VER];
$lecturaExportacion = [ACC_VER, ACC_EXPORTAR];

define('PERMISOS', [
    'super_admin' => $accesoTotal,
    'admin' => $accesoTotal,
    'administrador' => $accesoTotal,

    'coord_nacional' => [
        'dashboard' => $soloLectura,
        'organizaciones' => $operacionCompleta,
        'beneficiarios' => $operacionCompleta,
        'asistencia' => $operacionCompleta,
        'capacitaciones' => $operacionCompleta,
        'entregas' => $operacionCompleta,
        'inventarios' => $operacionCompleta,
        'movilizaciones' => $lecturaExportacion,
        'fprog' => $operacionCompleta,
        'estadisticas' => $soloLectura,
        'exportar' => $lecturaExportacion,
        'presupuesto' => $operacionCompleta,
        'flota' => $operacionCompleta,
        'auditoria' => $soloLectura,
    ],
    'coordinador' => [
        'dashboard' => $soloLectura,
        'organizaciones' => $operacionCompleta,
        'beneficiarios' => $operacionCompleta,
        'asistencia' => $operacionCompleta,
        'capacitaciones' => $operacionCompleta,
        'entregas' => $operacionCompleta,
        'inventarios' => $operacionCompleta,
        'movilizaciones' => $lecturaExportacion,
        'fprog' => $operacionCompleta,
        'estadisticas' => $soloLectura,
        'exportar' => $lecturaExportacion,
        'presupuesto' => $operacionCompleta,
        'flota' => $operacionCompleta,
        'catalogos' => $operacionCompleta,
        'mantenimiento' => $operacionCompleta,
    ],
    'coord_pip' => [
        'dashboard' => $soloLectura,
        'organizaciones' => $operacionCompleta,
        'beneficiarios' => $operacionCompleta,
        'asistencia' => $operacionCompleta,
        'capacitaciones' => $operacionCompleta,
        'entregas' => $operacionCompleta,
        'inventarios' => $operacionCompleta,
        'movilizaciones' => $lecturaExportacion,
        'fprog' => $operacionCompleta,
        'estadisticas' => $soloLectura,
        'exportar' => $lecturaExportacion,
        'presupuesto' => $operacionCompleta,
        'flota' => $operacionCompleta,
        'catalogos' => $operacionCompleta,
        'mantenimiento' => $operacionCompleta,
    ],
    'tecnico_campo' => [
        'dashboard' => $soloLectura,
        'organizaciones' => $soloLectura,
        'beneficiarios' => [ACC_VER, ACC_CREAR, ACC_EDITAR],
        'asistencia' => [ACC_VER, ACC_CREAR, ACC_EDITAR, ACC_CARGAR],
        'capacitaciones' => [ACC_VER, ACC_CREAR, ACC_EDITAR, ACC_CARGAR],
        'entregas' => $soloLectura,
        'inventarios' => $soloLectura,
        'movilizaciones' => $soloLectura,
        'estadisticas' => $soloLectura,
        'exportar' => $lecturaExportacion,
        'presupuesto' => [ACC_VER, ACC_CREAR, ACC_EDITAR],
        'flota' => $soloLectura,
    ],
    'tecnico' => [
        'dashboard' => $soloLectura,
        'organizaciones' => $soloLectura,
        'beneficiarios' => [ACC_VER, ACC_CREAR, ACC_EDITAR],
        'asistencia' => [ACC_VER, ACC_CREAR, ACC_EDITAR, ACC_CARGAR],
        'capacitaciones' => [ACC_VER, ACC_CREAR, ACC_EDITAR, ACC_CARGAR],
        'entregas' => $soloLectura,
        'inventarios' => $soloLectura,
        'movilizaciones' => $soloLectura,
        'estadisticas' => $soloLectura,
        'exportar' => $lecturaExportacion,
        'presupuesto' => [ACC_VER, ACC_CREAR, ACC_EDITAR],
        'flota' => $soloLectura,
    ],
    'admin_bodega' => [
        'dashboard' => $soloLectura,
        'organizaciones' => $soloLectura,
        'beneficiarios' => $soloLectura,
        'entregas' => [ACC_VER, ACC_CARGAR],
        'inventarios' => $operacionCompleta,
        'movilizaciones' => $lecturaExportacion,
        'estadisticas' => $soloLectura,
        'exportar' => $lecturaExportacion,
        'presupuesto' => [ACC_VER, ACC_CREAR, ACC_EDITAR],
        'flota' => $soloLectura,
    ],
    'admin_presupuesto' => [
        'dashboard' => $soloLectura,
        'estadisticas' => $soloLectura,
        'exportar' => $lecturaExportacion,
        'presupuesto' => $operacionCompleta,
        'flota' => $soloLectura,
    ],
    'jefe' => [
        'dashboard' => $soloLectura,
        'organizaciones' => $soloLectura,
        'beneficiarios' => $soloLectura,
        'asistencia' => $soloLectura,
        'capacitaciones' => $soloLectura,
        'estadisticas' => $soloLectura,
        'exportar' => $lecturaExportacion,
        'presupuesto' => [ACC_VER, ACC_CREAR, ACC_EDITAR, ACC_APROBAR],
        'flota' => $soloLectura,
    ],
]);

define('MODULOS_RUTA', [
    'dashboard' => 'dashboard',
    'organizaciones' => 'organizaciones',
    'beneficiarios' => 'beneficiarios',
    'asistencia' => 'asistencia',
    'capacitaciones' => 'capacitaciones',
    'entregas' => 'entregas',
    'inventarios' => 'inventarios',
    'movilizaciones' => 'movilizaciones',
    'fortalecimiento' => 'fprog',
    'metas' => 'fprog',
    'indicadores' => 'fprog',
    'componentes_fp' => 'fprog',
    'equipo_fp' => 'fprog',
    'cronograma_fp' => 'fprog',
    'estadisticas' => 'estadisticas',
    'exportar' => 'exportar',
    'presupuesto' => 'presupuesto',
    'flota' => 'flota',
    'catalogos' => 'catalogos',
    'mantenimiento' => 'mantenimiento',
    'auditoria' => 'auditoria',
]);

unset($todosLosModulos, $accesoTotal, $operacionCompleta, $soloLectura, $lecturaExportacion);
