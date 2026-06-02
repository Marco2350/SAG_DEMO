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

        // Stats rápidas por programa (para mostrar en las tarjetas).
        // Todo vive en sag_main; se filtra por id_proyecto.
        $stats = [];
        $db    = Database::main();
        foreach ($programas as $key => $prog) {
            $pid = (int) ($prog['id_proyecto'] ?? 0);
            try {
                $stats[$key] = [
                    'orgs'  => (int) $db->fetchOne("SELECT COUNT(*) AS c FROM sag_organizaciones WHERE estado='activa' AND id_proyecto=?", [$pid])['c'],
                    'benes' => (int) $db->fetchOne("SELECT COUNT(*) AS c FROM sag_beneficiarios WHERE estado='activo' AND id_proyecto=?", [$pid])['c'],
                    'caps'  => (int) $db->fetchOne("SELECT COUNT(*) AS c FROM sag_capacitaciones WHERE id_proyecto=?", [$pid])['c'],
                    'at'    => (int) $db->fetchOne("SELECT COUNT(*) AS c FROM sag_asistencias_tecnicas WHERE id_proyecto=?", [$pid])['c'],
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
