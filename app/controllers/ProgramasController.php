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
                if ($prog['id'] === 'fprog') {
                    $r = $db->fetchOne(
                        "SELECT
                            COUNT(*) AS total,
                            SUM(estado='planificado')   AS planificado,
                            SUM(estado='en_ejecucion')  AS en_ejecucion,
                            SUM(estado='completado')    AS completado
                         FROM sag_acciones_fortalecimiento
                         WHERE id_proyecto = ? AND activo = 1",
                        [$pid]
                    );
                    $stats[$key] = [
                        'tipo'        => 'fprog',
                        'total'       => (int) ($r['total']        ?? 0),
                        'planificado' => (int) ($r['planificado']  ?? 0),
                        'ejecucion'   => (int) ($r['en_ejecucion'] ?? 0),
                        'completado'  => (int) ($r['completado']   ?? 0),
                    ];
                } else {
                    // 4 queries individuales → 1 round-trip con subqueries escalares
                    $r = $db->fetchOne(
                        "SELECT
                            (SELECT COUNT(*) FROM sag_organizaciones       WHERE estado='activa' AND id_proyecto=?) AS orgs,
                            (SELECT COUNT(*) FROM sag_beneficiarios        WHERE estado='activo' AND id_proyecto=?) AS benes,
                            (SELECT COUNT(*) FROM sag_capacitaciones       WHERE id_proyecto=?)                     AS caps,
                            (SELECT COUNT(*) FROM sag_asistencias_tecnicas WHERE id_proyecto=?)                     AS at",
                        [$pid, $pid, $pid, $pid]
                    );
                    $stats[$key] = [
                        'tipo'  => 'pip',
                        'orgs'  => (int)($r['orgs']  ?? 0),
                        'benes' => (int)($r['benes'] ?? 0),
                        'caps'  => (int)($r['caps']  ?? 0),
                        'at'    => (int)($r['at']    ?? 0),
                    ];
                }
            } catch (Exception $e) {
                $stats[$key] = ($prog['id'] === 'fprog')
                    ? ['tipo' => 'fprog', 'total' => 0, 'planificado' => 0, 'ejecucion' => 0, 'completado' => 0]
                    : ['tipo' => 'pip',   'orgs'  => 0, 'benes'       => 0, 'caps'       => 0, 'at'         => 0];
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
