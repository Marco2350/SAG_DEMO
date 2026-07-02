<?php
abstract class Controller
{
    protected function view(string $name, array $data = []): void
    {
        extract($data);
        require ROOT_PATH . "/app/views/{$name}.php";
    }

    protected function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function success(string $message, mixed $data = null): void
    {
        $this->json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    protected function error(string $message, int $code = 200): void
    {
        $this->json(['success' => false, 'message' => $message], $code);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . $path);
        exit;
    }

    /** Requiere autenticación (usuario logueado) */
    protected function requireAuth(): void
    {
        if (empty($_SESSION['user'])) {
            if ($this->isAjax()) {
                $this->error('Sesión expirada.', 401);
            }
            $this->redirect('/auth/login');
        }
    }

    /**
     * Devuelve el token CSRF actual de la sesión.
     * Usar en formularios: <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
     */
    public static function csrfToken(): string
    {
        return $_SESSION['_csrf'] ?? '';
    }

    /**
     * Valida el token CSRF en peticiones POST.
     * Llamar al inicio de cualquier acción que modifique estado.
     * Acepta el token en $_POST['_csrf'] o en el header X-CSRF-Token.
     */
    protected function requireCsrf(): void
    {
        if (!$this->isPost()) return;
        $token = $_POST['_csrf']
              ?? $_SERVER['HTTP_X_CSRF_TOKEN']
              ?? '';
        $expected = $_SESSION['_csrf'] ?? '';
        if (!$expected || !is_string($token) || !hash_equals($expected, $token)) {
            if ($this->isAjax()) {
                $this->error('Token de seguridad inválido. Recargue la página.', 419);
            }
            http_response_code(419);
            die('Token de seguridad inválido. Recargue la página.');
        }
    }

    /** Requiere que haya un programa activo en sesión */
    protected function requirePrograma(): void
    {
        $this->requireAuth();
        if (empty($_SESSION['programa'])) {
            if ($this->isAjax()) {
                $this->error('No hay programa activo.', 403);
            }
            $this->redirect('/programas');
        }

        $programaId = (string) ($_SESSION['programa']['id'] ?? '');
        if (!isset(PROGRAMAS[$programaId]) || !$this->programaPermitido($programaId)) {
            unset($_SESSION['programa']);
            if ($this->isAjax()) {
                $this->error('No tiene autorización para operar este programa.', 403);
            }
            $this->redirect('/programas');
        }

        $this->requireModuleAccess();
    }

    /**
     * Devuelve el programa asignado o NULL cuando el usuario tiene alcance global.
     * Sincroniza las sesiones creadas antes de que este control fuera desplegado.
     */
    protected function programaAsignado(): ?string
    {
        if (!array_key_exists('programa_asignado', $_SESSION['user'] ?? [])) {
            $idUsuario = (int) ($_SESSION['user']['id_usuario'] ?? 0);
            if (!$idUsuario) {
                return '__sin_autorizacion__';
            }

            try {
                $row = Database::main()->fetchOne(
                    "SELECT programa_asignado
                     FROM sag_usuarios
                     WHERE id_usuario = ? AND activo = 1",
                    [$idUsuario]
                );
                if (!$row) {
                    return '__sin_autorizacion__';
                }

                $valor = trim((string) ($row['programa_asignado'] ?? ''));
                $_SESSION['user']['programa_asignado'] = $valor !== ''
                    ? strtolower($valor)
                    : null;
            } catch (\Throwable $e) {
                error_log('Controller::programaAsignado — ' . $e->getMessage());
                return '__sin_autorizacion__';
            }
        }

        $asignado = $_SESSION['user']['programa_asignado'];
        if ($asignado === null || $asignado === '') {
            return null;
        }
        return strtolower(trim((string) $asignado));
    }

    /** Verifica si el usuario puede operar el programa indicado. */
    protected function programaPermitido(string $programaId): bool
    {
        // Alcance multi-proyecto: roles admin o todos_proyectos => todos;
        // en otro caso, solo los proyectos asignados en sag_usuario_proyecto.
        return Permisos::puedeOperarPip($programaId);
    }

    /** Bloquea el acceso directo a módulos que el rol no puede consultar. */
    private function requireModuleAccess(): void
    {
        Permisos::init();

        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $basePath = rtrim((string) parse_url(BASE_URL, PHP_URL_PATH), '/');
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        $segmento = explode('/', trim($path, '/'))[0] ?? '';
        $modulo = MODULOS_RUTA[$segmento] ?? null;

        // API y rutas internas sin módulo propio conservan la autorización
        // específica de su controlador.
        if ($modulo === null || Permisos::puedeEn($modulo, ACC_VER)) {
            return;
        }

        $this->logAction('ACCESO_MODULO_DENEGADO', $modulo, 'Ruta: ' . mb_substr($path, 0, 150));
        if ($this->isAjax()) {
            $this->error('No tiene permisos para acceder a este módulo.', 403);
        }
        http_response_code(403);
        echo 'No tiene permisos para acceder a este módulo.';
        exit;
    }

    /** Autoriza una acción concreta usando la matriz central. */
    protected function requirePermission(string $modulo, string $accion = ACC_VER): void
    {
        $this->requirePrograma();
        if (Permisos::puedeEn($modulo, $accion)) {
            return;
        }

        $this->logAction(
            'ACCION_DENEGADA',
            $modulo,
            'Acción: ' . mb_substr($accion, 0, 30)
        );
        if ($this->isAjax()) {
            $this->error('No tiene permisos para realizar esta acción.', 403);
        }
        http_response_code(403);
        echo 'No tiene permisos para realizar esta acción.';
        exit;
    }

    /**
     * Middleware invocado por Router para aplicar permisos de acción a todas
     * las rutas registradas y también a las resueltas dinámicamente.
     */
    public function authorizeRouteAction(string $action, string $method): void
    {
        if (empty($_SESSION['programa'])) {
            return;
        }

        Permisos::init();
        $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $basePath = rtrim((string) parse_url(BASE_URL, PHP_URL_PATH), '/');
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        $segmento = explode('/', trim($path, '/'))[0] ?? '';
        $modulo = MODULOS_RUTA[$segmento] ?? null;
        if ($modulo === null) {
            return;
        }

        $accion = $this->permissionForAction($action, strtoupper($method));
        if (Permisos::puedeEn($modulo, $accion)) {
            return;
        }

        $this->logAction(
            'ACCION_DENEGADA',
            $modulo,
            'Acción: ' . mb_substr($action, 0, 60) . ' · Permiso: ' . $accion
        );
        if ($this->isAjax()) {
            $this->error('No tiene permisos para realizar esta acción.', 403);
        }
        http_response_code(403);
        echo 'No tiene permisos para realizar esta acción.';
        exit;
    }

    /** Traduce una acción de controlador al permiso funcional correspondiente. */
    private function permissionForAction(string $action, string $method): string
    {
        if ($method === 'GET') {
            if (in_array($action, [
                'diag', 'diagnosticoApi', 'probarApi',
                'descubrirTiposMovimientos', 'descubrirRubrosOirsa',
            ], true)) {
                return ACC_CARGAR;
            }
            return in_array($action, ['exportar', 'generar'], true)
                ? ACC_EXPORTAR
                : ACC_VER;
        }

        $lectura = [
            'listar', 'get', 'checkNombre', 'miembros', 'buscarPorDNI',
            'contar', 'datos', 'detalle', 'getPresupuesto', 'listarLineas',
            'listarModificaciones', 'listarCompras', 'getCompra',
            'listarViaticos', 'getViatico', 'listarGastos', 'getGasto',
            'listarDocumentos', 'apiLineas', 'listarCronogramas', 'datosProductores',
            'getCronograma', 'kardex', 'stockPorBodega', 'apiLista',
        ];
        if (in_array($action, $lectura, true)) {
            return ACC_VER;
        }

        if (str_contains(strtolower($action), 'delete')
            || str_contains(strtolower($action), 'eliminar')) {
            return ACC_ELIMINAR;
        }

        $aprobacion = [
            'estado', 'finalizar', 'validarEvidencia', 'autorizarPresupuesto',
            'estadoCompra', 'vistoBoeno', 'aprobarViatico', 'liquidarViatico',
            'estadoGasto', 'aprobar', 'rechazar', 'cerrarViaje',
        ];
        if (in_array($action, $aprobacion, true)) {
            return ACC_APROBAR;
        }

        $carga = [
            'masivo', 'subirEvidencia', 'importarExcel', 'recibirLinea',
            'sincronizar', 'sincronizarTrazaragro',
        ];
        if (in_array($action, $carga, true)) {
            return ACC_CARGAR;
        }

        if (in_array($action, ['generar', 'exportar'], true)) {
            return ACC_EXPORTAR;
        }

        // Las acciones POST desconocidas fallan de forma segura exigiendo
        // edición en lugar de quedar abiertas por omisión.
        return ACC_EDITAR;
    }

    /** Devuelve el slug del rol del usuario logueado */
    protected function rolSlug(): string
    {
        return $_SESSION['user']['rol_slug'] ?? '';
    }

    /** Verifica si el usuario tiene alguno de los roles indicados */
    protected function hasRole(array $roles): bool
    {
        return in_array($this->rolSlug(), $roles, true);
    }

    /** Bloquea el acceso si el usuario no tiene uno de los roles indicados */
    protected function requireRole(array $roles): void
    {
        $this->requireAuth();
        if (!$this->hasRole($roles)) {
            if ($this->isAjax()) {
                $this->error('No tiene permisos para esta acción.', 403);
            }
            http_response_code(403);
            $this->redirect('/dashboard');
        }
    }

    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function getPost(string $key, mixed $default = ''): mixed
    {
        return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
    }

    protected function getQuery(string $key, mixed $default = ''): mixed
    {
        return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
    }

    protected function logAction(string $accion, string $modulo = '', string $detalle = ''): void
    {
        try {
            $db = Database::main();
            $db->execute(
                "INSERT INTO sag_logs (id_usuario, programa, accion, modulo, detalle, ip)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [
                    $_SESSION['user']['id_usuario'] ?? null,
                    $_SESSION['programa']['id']     ?? null,
                    $accion,
                    $modulo,
                    $detalle,
                    $_SERVER['REMOTE_ADDR'] ?? '',
                ]
            );
        } catch (Exception $e) {
            error_log('logAction error: ' . $e->getMessage());
        }
    }
}
