<?php
class MantenimientoController extends Controller
{
    // Roles con acceso a catálogos del programa (técnicos, temas, cultivos, tipos AT)
    private const ROLES_CATALOGOS = ['admin', 'super_admin', 'coordinador', 'coord_nacional', 'coord_pip'];
    // Roles que pueden administrar usuarios y roles del sistema
    private const ROLES_USUARIOS  = ['admin', 'super_admin'];

    public function __construct()
    {
        $this->requirePrograma();
        $this->requireRole(self::ROLES_CATALOGOS);
    }

    public function index(): void
    {
        $db      = Database::programa();
        $dbMain  = Database::main();
        $pid     = Database::proyectoId();

        $tecnicos  = $db->fetchAll("SELECT * FROM sag_tecnicos WHERE id_proyecto=? ORDER BY nombre_completo", [$pid]);
        $temas     = $db->fetchAll("SELECT * FROM sag_temas WHERE id_proyecto=? ORDER BY nombre", [$pid]);
        $subtemas  = $db->fetchAll(
            "SELECT s.*, t.nombre AS tema FROM sag_subtemas s
             INNER JOIN sag_temas t ON t.id_tema = s.id_tema
             WHERE s.id_proyecto=? ORDER BY t.nombre, s.nombre",
            [$pid]
        );
        $cultivos  = $db->fetchAll("SELECT * FROM sag_cultivos WHERE id_proyecto=? ORDER BY nombre", [$pid]);
        $tiposAt   = $db->fetchAll("SELECT * FROM sag_tipo_at WHERE id_proyecto=? ORDER BY nombre", [$pid]);
        $usuarios  = $dbMain->fetchAll(
            "SELECT u.*, r.nombre AS rol_nombre FROM sag_usuarios u
             INNER JOIN sag_roles r ON r.id_rol = u.id_rol ORDER BY u.nombre, u.apellido"
        );
        $roles     = $dbMain->fetchAll("SELECT * FROM sag_roles ORDER BY nombre");
        $departamentos = $db->fetchAll("SELECT * FROM sag_departamentos WHERE activo=1 AND id_proyecto=? ORDER BY nombre", [$pid]);

        $pageTitle = 'Mantenimiento — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;
        $this->view('mantenimiento/index', compact(
            'tecnicos', 'temas', 'subtemas', 'cultivos', 'tiposAt',
            'usuarios', 'roles', 'departamentos', 'pageTitle'
        ));
    }

    // ── TÉCNICOS ───────────────────────────────────────

    public function saveTecnico(): void
    {
        $id   = (int) $this->getPost('id_tecnico', 0);
        $nombre = trim($this->getPost('nombre_completo', ''));
        $email  = trim($this->getPost('email', ''));
        if (!$nombre) { $this->error('El nombre es obligatorio.'); return; }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('El correo electrónico no es válido.'); return;
        }
        $telefono = preg_replace('/\D/', '', (string) $this->getPost('telefono', ''));
        if ($telefono !== '' && strlen($telefono) !== 8) {
            $this->error('El teléfono debe tener 8 dígitos (formato Honduras).'); return;
        }

        $data = [
            'nombre_completo' => $nombre,
            'especialidad'    => $this->getPost('especialidad', ''),
            'id_departamento' => ((int) $this->getPost('id_departamento', 0)) ?: null,
            'telefono'        => $telefono !== '' ? substr($telefono, 0, 4) . '-' . substr($telefono, 4) : '',
            'email'           => $email,
            'activo'          => (int) $this->getPost('activo', 1),
        ];

        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();
            if ($id) {
                $sets = implode(', ', array_map(fn($k) => "$k=?", array_keys($data)));
                $db->execute("UPDATE sag_tecnicos SET $sets WHERE id_tecnico=? AND id_proyecto=?", [...array_values($data), $id, $pid]);
                $this->logAction('EDITAR', 'mantenimiento_tecnicos', "ID:{$id}");
                $this->success('Técnico actualizado.');
            } else {
                $dataIns = ['id_proyecto' => $pid] + $data;
                $cols = implode(', ', array_keys($dataIns));
                $plh  = implode(', ', array_fill(0, count($dataIns), '?'));
                $db->execute("INSERT INTO sag_tecnicos ($cols) VALUES ($plh)", array_values($dataIns));
                $this->logAction('CREAR', 'mantenimiento_tecnicos', $nombre);
                $this->success('Técnico creado.');
            }
        } catch (Exception $e) {
            $this->error('Error al guardar técnico.');
        }
    }

    public function deleteTecnico(): void
    {
        $id = (int) $this->getPost('id', 0);
        try {
            $db = Database::programa();
            $db->execute("UPDATE sag_tecnicos SET activo=0 WHERE id_tecnico=? AND id_proyecto=?", [$id, Database::proyectoId()]);
            $this->logAction('ELIMINAR', 'mantenimiento_tecnicos', "ID:{$id}");
            $this->success('Técnico desactivado.');
        } catch (Exception $e) {
            $this->error('Error al eliminar técnico.');
        }
    }

    // ── TEMAS ──────────────────────────────────────────

    public function saveTema(): void
    {
        $id     = (int) $this->getPost('id_tema', 0);
        $nombre = trim($this->getPost('nombre', ''));
        $tipo   = $this->getPost('tipo', 'ambos');
        if (!$nombre) { $this->error('El nombre es obligatorio.'); return; }
        if (!in_array($tipo, ['capacitacion', 'at', 'ambos'], true)) {
            $this->error('Tipo de tema no válido.'); return;
        }

        $activo = (int) $this->getPost('activo', 1) ? 1 : 0;
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();
            if ($id) {
                $db->execute("UPDATE sag_temas SET nombre=?, tipo=?, activo=? WHERE id_tema=? AND id_proyecto=?", [$nombre, $tipo, $activo, $id, $pid]);
                $this->success('Tema actualizado.');
            } else {
                $db->execute("INSERT INTO sag_temas (id_proyecto, nombre, tipo, activo) VALUES (?, ?, ?, ?)", [$pid, $nombre, $tipo, $activo]);
                $this->success('Tema creado.');
            }
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'mantenimiento_temas', $nombre);
        } catch (Exception $e) {
            error_log('MantenimientoController::saveTema — ' . $e->getMessage());
            $this->error('Error al guardar tema.');
        }
    }

    public function deleteTema(): void
    {
        $id = (int) $this->getPost('id', 0);
        try {
            // Desactivación (no borrado físico): capacitaciones y AT históricas referencian el tema
            Database::programa()->execute("UPDATE sag_temas SET activo=0 WHERE id_tema=? AND id_proyecto=?", [$id, Database::proyectoId()]);
            $this->logAction('ELIMINAR', 'mantenimiento_temas', "ID:{$id}");
            $this->success('Tema desactivado.');
        } catch (Exception $e) {
            error_log('MantenimientoController::deleteTema — ' . $e->getMessage());
            $this->error('Error al desactivar el tema.');
        }
    }

    // ── SUBTEMAS ───────────────────────────────────────

    public function saveSubtema(): void
    {
        $id      = (int) $this->getPost('id_subtema', 0);
        $idTema  = (int) $this->getPost('id_tema', 0);
        $nombre  = trim($this->getPost('nombre', ''));
        $activo  = (int) $this->getPost('activo', 1) ? 1 : 0;
        if (!$nombre || !$idTema) { $this->error('Nombre y tema son obligatorios.'); return; }

        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();
            if (!$db->fetchOne("SELECT id_tema FROM sag_temas WHERE id_tema=? AND id_proyecto=?", [$idTema, $pid])) {
                $this->error('El tema padre seleccionado no es válido.'); return;
            }
            if ($id) {
                $db->execute("UPDATE sag_subtemas SET nombre=?, id_tema=?, activo=? WHERE id_subtema=? AND id_proyecto=?", [$nombre, $idTema, $activo, $id, $pid]);
                $this->success('Subtema actualizado.');
            } else {
                $db->execute("INSERT INTO sag_subtemas (id_proyecto, id_tema, nombre, activo) VALUES (?, ?, ?, ?)", [$pid, $idTema, $nombre, $activo]);
                $this->success('Subtema creado.');
            }
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'mantenimiento_subtemas', $nombre);
        } catch (Exception $e) {
            error_log('MantenimientoController::saveSubtema — ' . $e->getMessage());
            $this->error('Error al guardar subtema.');
        }
    }

    public function deleteSubtema(): void
    {
        $id = (int) $this->getPost('id', 0);
        try {
            Database::programa()->execute("UPDATE sag_subtemas SET activo=0 WHERE id_subtema=? AND id_proyecto=?", [$id, Database::proyectoId()]);
            $this->logAction('ELIMINAR', 'mantenimiento_subtemas', "ID:{$id}");
            $this->success('Subtema desactivado.');
        } catch (Exception $e) {
            error_log('MantenimientoController::deleteSubtema — ' . $e->getMessage());
            $this->error('Error al desactivar el subtema.');
        }
    }

    // ── CULTIVOS ───────────────────────────────────────

    public function saveCultivo(): void
    {
        $id     = (int) $this->getPost('id_cultivo', 0);
        $nombre = trim($this->getPost('nombre', ''));
        $tipo   = $this->getPost('tipo', 'cultivo');
        if (!$nombre) { $this->error('El nombre es obligatorio.'); return; }
        if (!in_array($tipo, ['cultivo', 'ganaderia', 'otro'], true)) {
            $this->error('Tipo de cultivo no válido.'); return;
        }

        $activo = (int) $this->getPost('activo', 1) ? 1 : 0;
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();
            if ($id) {
                $db->execute("UPDATE sag_cultivos SET nombre=?, tipo=?, activo=? WHERE id_cultivo=? AND id_proyecto=?", [$nombre, $tipo, $activo, $id, $pid]);
                $this->success('Cultivo actualizado.');
            } else {
                $db->execute("INSERT INTO sag_cultivos (id_proyecto, nombre, tipo, activo) VALUES (?, ?, ?, ?)", [$pid, $nombre, $tipo, $activo]);
                $this->success('Cultivo creado.');
            }
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'mantenimiento_cultivos', $nombre);
        } catch (Exception $e) {
            error_log('MantenimientoController::saveCultivo — ' . $e->getMessage());
            $this->error('Error al guardar cultivo.');
        }
    }

    public function deleteCultivo(): void
    {
        $id = (int) $this->getPost('id', 0);
        try {
            Database::programa()->execute("UPDATE sag_cultivos SET activo=0 WHERE id_cultivo=? AND id_proyecto=?", [$id, Database::proyectoId()]);
            $this->logAction('ELIMINAR', 'mantenimiento_cultivos', "ID:{$id}");
            $this->success('Cultivo desactivado.');
        } catch (Exception $e) {
            error_log('MantenimientoController::deleteCultivo — ' . $e->getMessage());
            $this->error('Error al desactivar el cultivo.');
        }
    }

    // ── TIPO AT ────────────────────────────────────────

    public function saveTipoAT(): void
    {
        $id     = (int) $this->getPost('id_tipo_at', 0);
        $nombre = trim($this->getPost('nombre', ''));
        $icono  = $this->getPost('icono', 'fa-circle');
        if (!$nombre) { $this->error('El nombre es obligatorio.'); return; }

        $activo = (int) $this->getPost('activo', 1) ? 1 : 0;
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();
            if ($id) {
                $db->execute("UPDATE sag_tipo_at SET nombre=?, icono=?, activo=? WHERE id_tipo_at=? AND id_proyecto=?", [$nombre, $icono, $activo, $id, $pid]);
                $this->success('Tipo AT actualizado.');
            } else {
                $db->execute("INSERT INTO sag_tipo_at (id_proyecto, nombre, icono, activo) VALUES (?, ?, ?, ?)", [$pid, $nombre, $icono, $activo]);
                $this->success('Tipo AT creado.');
            }
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'mantenimiento_tipoat', $nombre);
        } catch (Exception $e) {
            error_log('MantenimientoController::saveTipoAT — ' . $e->getMessage());
            $this->error('Error al guardar tipo AT.');
        }
    }

    public function deleteTipoAT(): void
    {
        $id = (int) $this->getPost('id', 0);
        try {
            Database::programa()->execute("UPDATE sag_tipo_at SET activo=0 WHERE id_tipo_at=? AND id_proyecto=?", [$id, Database::proyectoId()]);
            $this->logAction('ELIMINAR', 'mantenimiento_tipoat', "ID:{$id}");
            $this->success('Tipo AT desactivado.');
        } catch (Exception $e) {
            error_log('MantenimientoController::deleteTipoAT — ' . $e->getMessage());
            $this->error('Error al desactivar el tipo AT.');
        }
    }

    // ── USUARIOS ───────────────────────────────────────

    public function saveUsuario(): void
    {
        $this->requireRole(self::ROLES_USUARIOS);

        $id       = (int) $this->getPost('id_usuario', 0);
        $nombre   = trim($this->getPost('nombre', ''));
        $apellido = trim($this->getPost('apellido', ''));
        $email    = trim($this->getPost('email', ''));
        $username = trim($this->getPost('username', ''));
        $idRol    = (int) $this->getPost('id_rol', 0);
        $password = $this->getPost('password', '');

        if (!$nombre || !$apellido || !$email || !$username || !$idRol) {
            $this->error('Todos los campos son obligatorios.'); return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('El correo electrónico no es válido.'); return;
        }
        if (!$id && !$password) {
            $this->error('La contraseña es obligatoria para usuarios nuevos.'); return;
        }
        if ($password && strlen($password) < 8) {
            $this->error('La contraseña debe tener al menos 8 caracteres.'); return;
        }
        if (!Database::main()->fetchOne("SELECT id_rol FROM sag_roles WHERE id_rol=?", [$idRol])) {
            $this->error('El rol seleccionado no es válido.'); return;
        }

        try {
            $db = Database::main();
            if ($id) {
                $sql    = "UPDATE sag_usuarios SET nombre=?, apellido=?, email=?, username=?, id_rol=?, activo=?";
                $params = [$nombre, $apellido, $email, $username, $idRol, (int)$this->getPost('activo', 1)];
                if ($password) {
                    $sql    .= ", password_hash=?";
                    $params[] = password_hash($password, PASSWORD_BCRYPT);
                }
                $sql .= " WHERE id_usuario=?";
                $params[] = $id;
                $db->execute($sql, $params);
                $this->logAction('EDITAR', 'mantenimiento_usuarios', "ID:{$id}");
                $this->success('Usuario actualizado.');
            } else {
                $db->execute(
                    "INSERT INTO sag_usuarios (nombre, apellido, email, username, password_hash, id_rol)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$nombre, $apellido, $email, $username, password_hash($password, PASSWORD_BCRYPT), $idRol]
                );
                $this->logAction('CREAR', 'mantenimiento_usuarios', $username);
                $this->success('Usuario creado.');
            }
        } catch (Exception $e) {
            $this->error('Error al guardar usuario. El email o username puede estar duplicado.');
        }
    }

    public function deleteUsuario(): void
    {
        $this->requireRole(self::ROLES_USUARIOS);

        $id = (int) $this->getPost('id', 0);
        if ($id === ($_SESSION['user']['id_usuario'] ?? 0)) {
            $this->error('No puede eliminar su propio usuario.'); return;
        }
        try {
            Database::main()->execute("UPDATE sag_usuarios SET activo=0 WHERE id_usuario=?", [$id]);
            $this->logAction('ELIMINAR', 'mantenimiento_usuarios', "ID:{$id}");
            $this->success('Usuario desactivado.');
        } catch (Exception $e) {
            $this->error('Error al eliminar usuario.');
        }
    }
}
