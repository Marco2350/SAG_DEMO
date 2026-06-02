<?php
/**
 * Permisos — Helper para verificar acceso por rol/módulo/acción/PIP.
 * Consume la matriz definida en config/permisos.php
 */
class Permisos
{
    /** Carga la matriz al primer uso */
    private static ?array $cache = null;

    public static function init(): void
    {
        if (self::$cache !== null) return;
        if (!defined('PERMISOS')) {
            require_once defined('ROOT_PATH') ? ROOT_PATH . '/config/permisos.php'
                                              : dirname(__DIR__) . '/config/permisos.php';
        }
        self::$cache = ['roles' => ROLES, 'permisos' => PERMISOS];
    }

    /** Devuelve el slug de rol del usuario actual */
    public static function rolActual(): string
    {
        return (string)($_SESSION['user']['rol_slug'] ?? '');
    }

    /** Devuelve el id del programa activo en sesión, o null */
    public static function pipActual(): ?string
    {
        return $_SESSION['programa']['id'] ?? null;
    }

    /** Devuelve el PIP asignado al usuario (de la BD), o null */
    public static function pipAsignado(): ?string
    {
        return $_SESSION['user']['programa_asignado'] ?? null;
    }

    /**
     * Verifica si el usuario actual tiene un permiso específico.
     *
     * @param string $modulo      Ej. 'organizaciones', 'entregas'
     * @param string $accion      Ej. ACC_VER, ACC_ELIMINAR
     * @return bool
     */
    public static function puede(string $modulo, string $accion = ACC_VER): bool
    {
        self::init();
        $rol = self::rolActual();
        if ($rol === '') return false;

        // Wildcard de rol: super_admin con '*' siempre puede
        $rolPermisos = self::$cache['permisos'][$rol] ?? null;
        if (!$rolPermisos) return false;

        $modPerms = $rolPermisos[$modulo] ?? null;
        if ($modPerms === null) return false; // sin acceso al módulo

        // Wildcard de acción
        if (in_array('*', $modPerms, true)) return true;

        return in_array($accion, $modPerms, true);
    }

    /**
     * Verifica si el PIP activo coincide con el alcance permitido del usuario.
     * - Si el rol tiene alcance 'todos': siempre ok
     * - Si tiene alcance 'asignado': el PIP activo debe ser el asignado al usuario
     */
    public static function puedeOperarPipActivo(): bool
    {
        self::init();
        $rol = self::rolActual();
        $cfg = self::$cache['roles'][$rol] ?? null;
        if (!$cfg) return false;

        if (($cfg['alcance_pip'] ?? 'asignado') === 'todos') return true;

        $pipActivo  = self::pipActual();
        $pipAsignado = self::pipAsignado();
        // Si no hay PIP asignado, lo dejamos pasar (usuario sin restricción)
        if (!$pipAsignado) return true;
        return $pipActivo === $pipAsignado;
    }

    /**
     * Combina permiso de acción + alcance de PIP. Útil en controllers.
     */
    public static function puedeEn(string $modulo, string $accion = ACC_VER): bool
    {
        return self::puede($modulo, $accion) && self::puedeOperarPipActivo();
    }

    /** Devuelve la lista de módulos visibles para el usuario actual (para el sidebar) */
    public static function modulosVisibles(): array
    {
        self::init();
        $rol = self::rolActual();
        $perms = self::$cache['permisos'][$rol] ?? [];
        return array_keys($perms);
    }

    /** Devuelve metadata del rol actual */
    public static function rolInfo(): array
    {
        self::init();
        $rol = self::rolActual();
        return self::$cache['roles'][$rol] ?? [
            'nombre' => '(sin rol)', 'descripcion' => '', 'alcance_pip' => 'asignado', 'es_admin' => false,
        ];
    }

    /** Lista todos los roles definidos */
    public static function todosLosRoles(): array
    {
        self::init();
        return self::$cache['roles'];
    }
}
