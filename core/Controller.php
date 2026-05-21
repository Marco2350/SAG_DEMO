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
