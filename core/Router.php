<?php
/**
 * Router — Sistema SAG Honduras Sin Hambre
 * Formato de URL: /controlador/accion/param1/param2
 */
class Router
{
    private array $routes = [];

    /**
     * Registra una ruta GET
     */
    public function get(string $path, string $controller, string $action): void
    {
        $this->routes['GET'][$path] = ['controller' => $controller, 'action' => $action];
    }

    /**
     * Registra una ruta POST
     */
    public function post(string $path, string $controller, string $action): void
    {
        $this->routes['POST'][$path] = ['controller' => $controller, 'action' => $action];
    }

    /**
     * Despacha la petición actual
     */
    public function dispatch(): void
    {
        $uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        // Remover el base path (la subcarpeta donde vive el proyecto) si existe.
        // Se deriva de SCRIPT_NAME —la ubicación real de index.php—, que es
        // confiable aunque .env no esté configurado o BASE_URL esté mal puesta.
        // BASE_URL se usa sólo como respaldo.
        $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $basePath = rtrim($basePath, '/');
        if ($basePath === '' || !str_starts_with($uri, $basePath)) {
            $basePath = rtrim((string) parse_url(BASE_URL, PHP_URL_PATH), '/');
        }
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        $uri = '/' . trim($uri, '/');

        // Intentar ruta exacta primero
        if (isset($this->routes[$method][$uri])) {
            $route = $this->routes[$method][$uri];
            $this->callAction($route['controller'], $route['action']);
            return;
        }

        // Parseo dinámico: /controlador/accion/...params
        $segments = explode('/', trim($uri, '/'));
        $controllerName = !empty($segments[0]) ? ucfirst(strtolower($segments[0])) . 'Controller' : 'AuthController';
        $actionName     = $segments[1] ?? 'index';
        $params         = array_slice($segments, 2);

        $this->callAction($controllerName, $actionName, $params);
    }

    private function callAction(string $controllerName, string $action, array $params = []): void
    {
        $file = ROOT_PATH . "/app/controllers/{$controllerName}.php";

        if (!file_exists($file)) {
            $this->notFound();
            return;
        }

        require_once $file;

        if (!class_exists($controllerName)) {
            $this->notFound();
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $action)) {
            $this->notFound();
            return;
        }

        $controller->authorizeRouteAction($action, $_SERVER['REQUEST_METHOD'] ?? 'GET');
        call_user_func_array([$controller, $action], $params);
    }

    private function notFound(): void
    {
        http_response_code(404);
        if (file_exists(ROOT_PATH . '/app/views/errors/404.php')) {
            require ROOT_PATH . '/app/views/errors/404.php';
        } else {
            echo '<h1>404 — Página no encontrada</h1>';
        }
    }
}
