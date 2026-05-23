<?php
class AuthController extends Controller
{
    public function login(): void
    {
        if (!empty($_SESSION['user'])) {
            $this->redirect(empty($_SESSION['programa']) ? '/programas' : '/dashboard');
        }
        $timeout = !empty($_GET['timeout']);
        $error   = $_GET['error'] ?? '';
        $pageTitle = 'Iniciar Sesión — ' . APP_NAME;
        $this->view('auth/login', compact('timeout', 'error', 'pageTitle'));
    }

    public function doLogin(): void
    {
        $credential = $this->getPost('usuario');
        $password   = $this->getPost('password');

        if (!$credential || !$password) {
            $this->error('Complete todos los campos.');
            return;
        }

        try {
            $db   = Database::main();
            $user = $db->fetchOne(
                "SELECT u.*, r.slug AS rol_slug, r.nombre AS rol_nombre
                 FROM sag_usuarios u
                 INNER JOIN sag_roles r ON r.id_rol = u.id_rol
                 WHERE (u.username = ? OR u.email = ?) AND u.activo = 1",
                [$credential, $credential]
            );

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $this->error('Credenciales incorrectas. Verifique usuario y contraseña.');
                return;
            }

            // Regenerar ID de sesión y token CSRF tras login (anti-fixation)
            session_regenerate_id(true);
            $_SESSION['_csrf']        = bin2hex(random_bytes(32));
            $_SESSION['_last_regen']  = time();

            // Guardar en sesión
            $_SESSION['user'] = [
                'id_usuario'  => $user['id_usuario'],
                'nombre'      => $user['nombre'],
                'apellido'    => $user['apellido'],
                'email'       => $user['email'],
                'username'    => $user['username'],
                'id_rol'      => $user['id_rol'],
                'rol_slug'    => $user['rol_slug'],
                'rol_nombre'  => $user['rol_nombre'],
                'initials'    => strtoupper(substr($user['nombre'], 0, 1) . substr($user['apellido'], 0, 1)),
            ];
            $_SESSION['_last_activity'] = time();

            // Actualizar último acceso
            $db->execute(
                "UPDATE sag_usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?",
                [$user['id_usuario']]
            );

            $this->logAction('LOGIN', 'auth', "Usuario: {$user['username']}");
            $this->success('Acceso correcto.', ['redirect' => BASE_URL . '/programas']);

        } catch (Exception $e) {
            error_log('AuthController::doLogin — ' . $e->getMessage());
            $this->error('Error del servidor. Intente nuevamente.');
        }
    }

    public function logout(): void
    {
        $this->logAction('LOGOUT', 'auth');
        session_unset();
        session_destroy();
        $this->redirect('/auth/login');
    }
}
