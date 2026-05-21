<?php
class ProgramasController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        // Si ya tiene programa activo y no es selección forzada, ir al dashboard
        if (!empty($_SESSION['programa']) && empty($_GET['cambiar'])) {
            $this->redirect('/dashboard');
        }

        $programas = PROGRAMAS;

        // Stats rápidas por programa (para mostrar en las tarjetas)
        $stats = [];
        foreach ($programas as $key => $prog) {
            try {
                // Conexión temporal al programa
                $cfg = array_merge(DB_MAIN, ['database' => $prog['db']]);
                // Usamos PDO directamente para no contaminar el singleton
                $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset={$cfg['charset']}";
                $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT]);
                $stats[$key] = [
                    'orgs'     => (int) $pdo->query("SELECT COUNT(*) FROM sag_organizaciones WHERE estado='activa'")->fetchColumn(),
                    'benes'    => (int) $pdo->query("SELECT COUNT(*) FROM sag_beneficiarios WHERE estado='activo'")->fetchColumn(),
                    'caps'     => (int) $pdo->query("SELECT COUNT(*) FROM sag_capacitaciones")->fetchColumn(),
                    'at'       => (int) $pdo->query("SELECT COUNT(*) FROM sag_asistencias_tecnicas")->fetchColumn(),
                ];
            } catch (Exception $e) {
                $stats[$key] = ['orgs' => 0, 'benes' => 0, 'caps' => 0, 'at' => 0];
            }
        }

        $pageTitle = 'Seleccionar Programa — ' . APP_NAME;
        $this->view('programas/selector', compact('programas', 'stats', 'pageTitle'));
    }

    public function seleccionar(): void
    {
        $this->requireAuth();

        $id = $this->getPost('programa');
        $programas = PROGRAMAS;

        if (!isset($programas[$id])) {
            $this->error('Programa no válido.');
            return;
        }

        $_SESSION['programa'] = $programas[$id];
        $this->logAction('SELECCIONAR_PROGRAMA', 'programas', "Programa: {$programas[$id]['sigla']}");
        $this->success('Programa seleccionado.', ['redirect' => BASE_URL . '/dashboard']);
    }

    public function salir(): void
    {
        $this->requireAuth();
        unset($_SESSION['programa']);
        $this->redirect('/programas');
    }
}
