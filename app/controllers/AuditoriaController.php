<?php
/**
 * AuditoriaController — Visor de la bitácora del sistema (sag_logs)
 * Solo administradores. La tabla puede crecer a cientos de miles de
 * registros, por lo que el listado usa el protocolo server-side de
 * DataTables (draw / start / length / search + filtros propios).
 */
class AuditoriaController extends Controller
{
    public function __construct()
    {
        $this->requirePrograma();
        $this->requirePermission('auditoria', ACC_VER);
    }

    /** Resumen para los cards superiores (compartido por index y listar). */
    private function resumen(): array
    {
        return Database::main()->fetchOne(
            "SELECT COUNT(*) AS total,
                    SUM(DATE(created_at) = CURDATE()) AS hoy,
                    SUM(accion = 'LOGIN'         AND DATE(created_at) = CURDATE()) AS logins_hoy,
                    SUM(accion = 'LOGIN_FALLIDO' AND DATE(created_at) = CURDATE()) AS fallidos_hoy
             FROM sag_logs"
        ) ?: ['total' => 0, 'hoy' => 0, 'logins_hoy' => 0, 'fallidos_hoy' => 0];
    }

    public function index(): void
    {
        $db = Database::main();

        $resumen  = $this->resumen();
        $usuarios = $db->fetchAll(
            "SELECT id_usuario, CONCAT(nombre, ' ', apellido) AS nombre FROM sag_usuarios ORDER BY nombre, apellido"
        );
        $modulos = $db->fetchAll(
            "SELECT DISTINCT modulo FROM sag_logs WHERE modulo IS NOT NULL AND modulo <> '' ORDER BY modulo"
        );
        $acciones = $db->fetchAll(
            "SELECT DISTINCT accion FROM sag_logs ORDER BY accion"
        );

        $pageTitle = 'Auditoría — ' . APP_NAME;
        $this->view('auditoria/index', compact('resumen', 'usuarios', 'modulos', 'acciones', 'pageTitle'));
    }

    public function listar(): void
    {
        try {
            $db = Database::main();

            // Protocolo DataTables server-side
            $draw   = (int) $this->getPost('draw', 1);
            $start  = max(0, (int) $this->getPost('start', 0));
            $length = (int) $this->getPost('length', 25);
            if ($length < 1 || $length > 200) $length = 25;
            $search = trim((string) ($_POST['search']['value'] ?? ''));

            // Filtros propios
            $where  = [];
            $params = [];
            $idUsuario = (int) $this->getPost('id_usuario', 0);
            $modulo    = trim((string) $this->getPost('modulo', ''));
            $accion    = trim((string) $this->getPost('accion', ''));
            $fDesde    = trim((string) $this->getPost('fecha_desde', ''));
            $fHasta    = trim((string) $this->getPost('fecha_hasta', ''));

            if ($idUsuario)     { $where[] = 'l.id_usuario = ?'; $params[] = $idUsuario; }
            if ($modulo !== '') { $where[] = 'l.modulo = ?';     $params[] = $modulo; }
            if ($accion !== '') { $where[] = 'l.accion = ?';     $params[] = $accion; }
            if ($fDesde !== '' && DateTime::createFromFormat('Y-m-d', $fDesde)) {
                $where[] = 'l.created_at >= ?'; $params[] = $fDesde . ' 00:00:00';
            }
            if ($fHasta !== '' && DateTime::createFromFormat('Y-m-d', $fHasta)) {
                $where[] = 'l.created_at <= ?'; $params[] = $fHasta . ' 23:59:59';
            }
            if ($search !== '') {
                $where[] = "(l.detalle LIKE ? OR l.accion LIKE ? OR u.username LIKE ? OR CONCAT(u.nombre,' ',u.apellido) LIKE ?)";
                $like = '%' . $search . '%';
                array_push($params, $like, $like, $like, $like);
            }

            $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
            $joinSql  = 'FROM sag_logs l LEFT JOIN sag_usuarios u ON u.id_usuario = l.id_usuario';

            $total = (int) ($db->fetchOne("SELECT COUNT(*) AS t FROM sag_logs")['t'] ?? 0);
            $filtrados = (int) ($db->fetchOne("SELECT COUNT(*) AS t {$joinSql} {$whereSql}", $params)['t'] ?? 0);

            $rows = $db->fetchAll(
                "SELECT l.id_log, l.programa, l.accion, l.modulo, l.detalle, l.ip, l.created_at,
                        u.username, CONCAT(u.nombre, ' ', u.apellido) AS usuario
                 {$joinSql} {$whereSql}
                 ORDER BY l.created_at DESC, l.id_log DESC
                 LIMIT {$length} OFFSET {$start}",
                $params
            );

            $data = array_map(function ($l) {
                return [
                    'fecha'    => $l['created_at'],
                    'usuario'  => htmlspecialchars($l['usuario'] ?: '(sistema)')
                                  . ($l['username'] ? '<div style="font-size:.72rem;color:#94a3b8;">' . htmlspecialchars($l['username']) . '</div>' : ''),
                    'programa' => $l['programa'] ? '<span class="badge-count" style="font-size:.68rem;">' . htmlspecialchars(strtoupper($l['programa'])) . '</span>' : '—',
                    'accion'   => '<span class="badge-accion ba-' . htmlspecialchars(self::claseAccion($l['accion'])) . '">' . htmlspecialchars($l['accion']) . '</span>',
                    'modulo'   => htmlspecialchars($l['modulo'] ?: '—'),
                    'detalle'  => htmlspecialchars($l['detalle'] ?: '—'),
                    'ip'       => htmlspecialchars($l['ip'] ?: '—'),
                ];
            }, $rows);

            $this->json([
                'draw'            => $draw,
                'recordsTotal'    => $total,
                'recordsFiltered' => $filtrados,
                'data'            => $data,
                'resumen'         => $this->resumen(),
            ]);
        } catch (Exception $e) {
            error_log('AuditoriaController::listar — ' . $e->getMessage());
            $this->json(['draw' => (int) $this->getPost('draw', 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Error al cargar la bitácora.']);
        }
    }

    /** Familia visual del badge según el verbo de la acción. */
    private static function claseAccion(string $accion): string
    {
        $a = strtoupper($accion);
        if (str_starts_with($a, 'LOGIN_FALLIDO')) return 'danger';
        if (str_starts_with($a, 'LOGIN') || str_starts_with($a, 'LOGOUT') || str_starts_with($a, 'PROGRAMA')) return 'info';
        if (str_starts_with($a, 'CREAR') || str_contains($a, 'ADD') || str_contains($a, 'SAVE')) return 'ok';
        if (str_starts_with($a, 'ELIMINAR') || str_contains($a, 'DEL') || str_contains($a, 'CANCELAR')) return 'danger';
        if (str_starts_with($a, 'EDITAR') || str_starts_with($a, 'ESTADO') || str_contains($a, 'VALIDAR') || str_contains($a, 'APROBAR') || str_contains($a, 'AUTORIZAR') || str_contains($a, 'FINALIZAR')) return 'warn';
        return 'neutral';
    }
}
