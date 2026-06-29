<?php
/**
 * Permisos — Helper para verificar acceso por rol/módulo/acción/proyecto.
 *
 * Fuente de la matriz:
 *   1. Base de datos (sag_rol_privilegio + sag_roles + sag_modulos + sag_acciones).
 *   2. Si la BD no está disponible (CLI, smoke tests) o las tablas aún no existen,
 *      cae a la matriz estática de config/permisos.php (ROLES / PERMISOS).
 *
 * Alcance por proyecto (multi-proyecto, rol único):
 *   - Roles con es_admin => acceso a todos los proyectos.
 *   - Usuarios con sag_usuarios.todos_proyectos = 1 => todos los proyectos.
 *   - En otro caso, solo los proyectos listados en sag_usuario_proyecto.
 */
class Permisos
{
    /** Cache de la matriz: ['roles' => [...], 'permisos' => [...]] */
    private static ?array $cache = null;

    /** Carga la matriz al primer uso (BD con fallback a config). */
    public static function init(): void
    {
        if (self::$cache !== null) return;

        // Cargar SIEMPRE config/permisos.php: define las constantes ACC_*,
        // MODULOS_RUTA y la matriz de respaldo (ROLES / PERMISOS), que el
        // resto del sistema necesita aunque la matriz venga de la BD.
        if (!defined('MODULOS_RUTA')) {
            require_once defined('ROOT_PATH') ? ROOT_PATH . '/config/permisos.php'
                                              : dirname(__DIR__) . '/config/permisos.php';
        }

        if (self::cargarDesdeBD()) return;

        // Fallback estático (CLI/smoke o tablas no migradas todavía)
        self::$cache = ['roles' => ROLES, 'permisos' => PERMISOS];
    }

    /** Intenta poblar la cache desde la BD. Devuelve true si lo logró. */
    private static function cargarDesdeBD(): bool
    {
        if (!class_exists('Database')) return false;
        try {
            $db = Database::main();
            if (!$db->tablaExiste('sag_rol_privilegio')) return false;

            $roles = [];
            foreach ($db->fetchAll("SELECT slug, nombre, descripcion, es_admin, activo FROM sag_roles") as $r) {
                $roles[$r['slug']] = [
                    'nombre'      => $r['nombre'],
                    'descripcion' => $r['descripcion'] ?? '',
                    'es_admin'    => (bool) $r['es_admin'],
                    'activo'      => (bool) $r['activo'],
                    'alcance_pip' => ((bool) $r['es_admin']) ? 'todos' : 'asignado',
                ];
            }
            if (!$roles) return false;

            $permisos = [];
            $rows = $db->fetchAll(
                "SELECT r.slug AS rol, m.slug AS modulo, a.slug AS accion
                   FROM sag_rol_privilegio rp
                   JOIN sag_roles    r ON r.id_rol    = rp.id_rol
                   JOIN sag_modulos  m ON m.id_modulo = rp.id_modulo
                   JOIN sag_acciones a ON a.id_accion = rp.id_accion"
            );
            foreach ($rows as $row) {
                $permisos[$row['rol']][$row['modulo']][] = $row['accion'];
            }

            self::$cache = ['roles' => $roles, 'permisos' => $permisos];
            return true;
        } catch (\Throwable $e) {
            error_log('Permisos::cargarDesdeBD — ' . $e->getMessage());
            return false;
        }
    }

    /** Devuelve el slug de rol del usuario actual */
    public static function rolActual(): string
    {
        return (string) ($_SESSION['user']['rol_slug'] ?? '');
    }

    /** Devuelve el id del programa activo en sesión, o null */
    public static function pipActual(): ?string
    {
        return $_SESSION['programa']['id'] ?? null;
    }

    /** ¿El rol actual es administrador total? */
    public static function esAdmin(): bool
    {
        self::init();
        $cfg = self::$cache['roles'][self::rolActual()] ?? null;
        return (bool) ($cfg['es_admin'] ?? false);
    }

    /**
     * Alcance de proyectos del usuario actual.
     * @return array{todos: bool, codigos: string[]}
     */
    public static function proyectosPermitidos(): array
    {
        // Cache en sesión (se rehace al iniciar sesión).
        if (isset($_SESSION['user']['proyectos_scope']) && is_array($_SESSION['user']['proyectos_scope'])) {
            return $_SESSION['user']['proyectos_scope'];
        }

        $scope = ['todos' => false, 'codigos' => []];

        // 1) Admin total
        if (self::esAdmin()) {
            $scope['todos'] = true;
            return self::cachearScope($scope);
        }

        // 2) Sesión nueva con alcance explícito (poblado en el login)
        if (array_key_exists('todos_proyectos', $_SESSION['user'] ?? [])) {
            $scope['todos']   = (bool) $_SESSION['user']['todos_proyectos'];
            $scope['codigos'] = array_map('strtolower', (array) ($_SESSION['user']['proyectos'] ?? []));
            return self::cachearScope($scope);
        }

        // 3) Cargar desde la BD
        $idUsuario = (int) ($_SESSION['user']['id_usuario'] ?? 0);
        if ($idUsuario && class_exists('Database')) {
            try {
                $db = Database::main();
                if ($db->columnaExiste('sag_usuarios', 'todos_proyectos')) {
                    $u = $db->fetchOne("SELECT todos_proyectos FROM sag_usuarios WHERE id_usuario = ?", [$idUsuario]);
                    if ($u && (int) $u['todos_proyectos'] === 1) {
                        $scope['todos'] = true;
                        return self::cachearScope($scope);
                    }
                }
                if ($db->tablaExiste('sag_usuario_proyecto')) {
                    $rows = $db->fetchAll(
                        "SELECT p.codigo
                           FROM sag_usuario_proyecto up
                           JOIN sag_proyectos p ON p.id_proyecto = up.id_proyecto
                          WHERE up.id_usuario = ?",
                        [$idUsuario]
                    );
                    $scope['codigos'] = array_map(fn($r) => strtolower($r['codigo']), $rows);
                    return self::cachearScope($scope);
                }
            } catch (\Throwable $e) {
                error_log('Permisos::proyectosPermitidos — ' . $e->getMessage());
            }
        }

        // 4) Compatibilidad: alcance heredado de un solo PIP (programa_asignado).
        $asignado = $_SESSION['user']['programa_asignado'] ?? null;
        if ($asignado) {
            $scope['codigos'] = [strtolower((string) $asignado)];
        } else {
            // Sin información de restricción: no bloquear (comportamiento previo).
            $scope['todos'] = true;
        }
        return self::cachearScope($scope);
    }

    /** Guarda el alcance en sesión y lo devuelve. */
    private static function cachearScope(array $scope): array
    {
        if (isset($_SESSION['user']) && is_array($_SESSION['user'])) {
            $_SESSION['user']['proyectos_scope'] = $scope;
        }
        return $scope;
    }

    /** ¿El usuario puede operar el proyecto indicado? */
    public static function puedeOperarPip(string $programaId): bool
    {
        $scope = self::proyectosPermitidos();
        if ($scope['todos']) return true;
        return in_array(strtolower($programaId), $scope['codigos'], true);
    }

    /**
     * Verifica si el usuario actual tiene un permiso específico (acción/módulo).
     */
    public static function puede(string $modulo, string $accion = ACC_VER): bool
    {
        self::init();
        $rol = self::rolActual();
        if ($rol === '') return false;

        // Administrador total: acceso a todo.
        if (self::esAdmin()) return true;

        $rolPermisos = self::$cache['permisos'][$rol] ?? null;
        if (!$rolPermisos) return false;

        $modPerms = $rolPermisos[$modulo] ?? null;
        if ($modPerms === null) return false; // sin acceso al módulo

        if (in_array('*', $modPerms, true)) return true; // wildcard (config legacy)

        return in_array($accion, $modPerms, true);
    }

    /**
     * Verifica si el PIP activo está dentro del alcance permitido del usuario.
     */
    public static function puedeOperarPipActivo(): bool
    {
        self::init();
        if (self::esAdmin()) return true;

        $scope = self::proyectosPermitidos();
        if ($scope['todos']) return true;

        $pipActivo = self::pipActual();
        if ($pipActivo === null) return true; // aún sin programa activo (selector)
        return in_array(strtolower($pipActivo), $scope['codigos'], true);
    }

    /**
     * Combina permiso de acción + alcance de PIP. Útil en controllers.
     */
    public static function puedeEn(string $modulo, string $accion = ACC_VER): bool
    {
        return self::puede($modulo, $accion) && self::puedeOperarPipActivo();
    }

    /** Devuelve la lista de módulos visibles para el usuario actual (sidebar). */
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

    // ── Compatibilidad hacia atrás ─────────────────────────────────────

    /** @deprecated Usar proyectosPermitidos(). Devuelve el primer PIP asignado. */
    public static function pipAsignado(): ?string
    {
        $codigos = self::proyectosPermitidos()['codigos'] ?? [];
        return $codigos[0] ?? null;
    }
}
