<?php
class MantenimientoController extends Controller
{
    public function __construct()
    {
        $this->requirePrograma();
    }

    public function index(): void
    {
        $db      = Database::programa();
        $dbMain  = Database::main();

        $tecnicos  = $db->fetchAll("SELECT * FROM sag_tecnicos ORDER BY nombre_completo");
        $temas     = $db->fetchAll("SELECT * FROM sag_temas ORDER BY nombre");
        $subtemas  = $db->fetchAll(
            "SELECT s.*, t.nombre AS tema FROM sag_subtemas s
             INNER JOIN sag_temas t ON t.id_tema = s.id_tema ORDER BY t.nombre, s.nombre"
        );
        $cultivos  = $db->fetchAll("SELECT * FROM sag_cultivos ORDER BY nombre");
        $tiposAt   = $db->fetchAll("SELECT * FROM sag_tipo_at ORDER BY nombre");
        $usuarios  = $dbMain->fetchAll(
            "SELECT u.*, r.nombre AS rol_nombre FROM sag_usuarios u
             INNER JOIN sag_roles r ON r.id_rol = u.id_rol ORDER BY u.nombre, u.apellido"
        );
        $roles     = $dbMain->fetchAll("SELECT * FROM sag_roles ORDER BY nombre");
        $departamentos = $db->fetchAll("SELECT * FROM sag_departamentos WHERE activo=1 ORDER BY nombre");

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
        if (!$nombre) { $this->error('El nombre es obligatorio.'); return; }

        $data = [
            'nombre_completo' => $nombre,
            'especialidad'    => $this->getPost('especialidad', ''),
            'id_departamento' => ($this->getPost('id_departamento', '') ?: null),
            'telefono'        => $this->getPost('telefono', ''),
            'email'           => $this->getPost('email', ''),
            'activo'          => (int) $this->getPost('activo', 1),
        ];

        try {
            $db = Database::programa();
            if ($id) {
                $sets = implode(', ', array_map(fn($k) => "$k=?", array_keys($data)));
                $db->execute("UPDATE sag_tecnicos SET $sets WHERE id_tecnico=?", [...array_values($data), $id]);
                $this->logAction('EDITAR', 'mantenimiento_tecnicos', "ID:{$id}");
                $this->success('Técnico actualizado.');
            } else {
                $cols = implode(', ', array_keys($data));
                $plh  = implode(', ', array_fill(0, count($data), '?'));
                $db->execute("INSERT INTO sag_tecnicos ($cols) VALUES ($plh)", array_values($data));
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
            $db->execute("UPDATE sag_tecnicos SET activo=0 WHERE id_tecnico=?", [$id]);
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

        try {
            $db = Database::programa();
            if ($id) {
                $db->execute("UPDATE sag_temas SET nombre=?, tipo=? WHERE id_tema=?", [$nombre, $tipo, $id]);
                $this->success('Tema actualizado.');
            } else {
                $db->execute("INSERT INTO sag_temas (nombre, tipo) VALUES (?, ?)", [$nombre, $tipo]);
                $this->success('Tema creado.');
            }
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'mantenimiento_temas', $nombre);
        } catch (Exception $e) {
            $this->error('Error al guardar tema.');
        }
    }

    public function deleteTema(): void
    {
        $id = (int) $this->getPost('id', 0);
        try {
            Database::programa()->execute("DELETE FROM sag_temas WHERE id_tema=?", [$id]);
            $this->logAction('ELIMINAR', 'mantenimiento_temas', "ID:{$id}");
            $this->success('Tema eliminado.');
        } catch (Exception $e) {
            $this->error('No se puede eliminar: el tema tiene subtemas asociados.');
        }
    }

    // ── SUBTEMAS ───────────────────────────────────────

    public function saveSubtema(): void
    {
        $id      = (int) $this->getPost('id_subtema', 0);
        $idTema  = (int) $this->getPost('id_tema', 0);
        $nombre  = trim($this->getPost('nombre', ''));
        if (!$nombre || !$idTema) { $this->error('Nombre y tema son obligatorios.'); return; }

        try {
            $db = Database::programa();
            if ($id) {
                $db->execute("UPDATE sag_subtemas SET nombre=?, id_tema=? WHERE id_subtema=?", [$nombre, $idTema, $id]);
                $this->success('Subtema actualizado.');
            } else {
                $db->execute("INSERT INTO sag_subtemas (id_tema, nombre) VALUES (?, ?)", [$idTema, $nombre]);
                $this->success('Subtema creado.');
            }
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'mantenimiento_subtemas', $nombre);
        } catch (Exception $e) {
            $this->error('Error al guardar subtema.');
        }
    }

    public function deleteSubtema(): void
    {
        $id = (int) $this->getPost('id', 0);
        try {
            Database::programa()->execute("DELETE FROM sag_subtemas WHERE id_subtema=?", [$id]);
            $this->logAction('ELIMINAR', 'mantenimiento_subtemas', "ID:{$id}");
            $this->success('Subtema eliminado.');
        } catch (Exception $e) {
            $this->error('Error al eliminar subtema.');
        }
    }

    // ── CULTIVOS ───────────────────────────────────────

    public function saveCultivo(): void
    {
        $id     = (int) $this->getPost('id_cultivo', 0);
        $nombre = trim($this->getPost('nombre', ''));
        $tipo   = $this->getPost('tipo', 'cultivo');
        if (!$nombre) { $this->error('El nombre es obligatorio.'); return; }

        try {
            $db = Database::programa();
            if ($id) {
                $db->execute("UPDATE sag_cultivos SET nombre=?, tipo=? WHERE id_cultivo=?", [$nombre, $tipo, $id]);
                $this->success('Cultivo actualizado.');
            } else {
                $db->execute("INSERT INTO sag_cultivos (nombre, tipo) VALUES (?, ?)", [$nombre, $tipo]);
                $this->success('Cultivo creado.');
            }
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'mantenimiento_cultivos', $nombre);
        } catch (Exception $e) {
            $this->error('Error al guardar cultivo.');
        }
    }

    public function deleteCultivo(): void
    {
        $id = (int) $this->getPost('id', 0);
        try {
            Database::programa()->execute("DELETE FROM sag_cultivos WHERE id_cultivo=?", [$id]);
            $this->logAction('ELIMINAR', 'mantenimiento_cultivos', "ID:{$id}");
            $this->success('Cultivo eliminado.');
        } catch (Exception $e) {
            $this->error('Error al eliminar cultivo.');
        }
    }

    // ── TIPO AT ────────────────────────────────────────

    public function saveTipoAT(): void
    {
        $id     = (int) $this->getPost('id_tipo_at', 0);
        $nombre = trim($this->getPost('nombre', ''));
        $icono  = $this->getPost('icono', 'fa-circle');
        if (!$nombre) { $this->error('El nombre es obligatorio.'); return; }

        try {
            $db = Database::programa();
            if ($id) {
                $db->execute("UPDATE sag_tipo_at SET nombre=?, icono=? WHERE id_tipo_at=?", [$nombre, $icono, $id]);
                $this->success('Tipo AT actualizado.');
            } else {
                $db->execute("INSERT INTO sag_tipo_at (nombre, icono) VALUES (?, ?)", [$nombre, $icono]);
                $this->success('Tipo AT creado.');
            }
            $this->logAction($id ? 'EDITAR' : 'CREAR', 'mantenimiento_tipoat', $nombre);
        } catch (Exception $e) {
            $this->error('Error al guardar tipo AT.');
        }
    }

    public function deleteTipoAT(): void
    {
        $id = (int) $this->getPost('id', 0);
        try {
            Database::programa()->execute("DELETE FROM sag_tipo_at WHERE id_tipo_at=?", [$id]);
            $this->logAction('ELIMINAR', 'mantenimiento_tipoat', "ID:{$id}");
            $this->success('Tipo AT eliminado.');
        } catch (Exception $e) {
            $this->error('Error al eliminar tipo AT.');
        }
    }

    // ── USUARIOS ───────────────────────────────────────

    public function saveUsuario(): void
    {
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
        if (!$id && !$password) {
            $this->error('La contraseña es obligatoria para usuarios nuevos.'); return;
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
