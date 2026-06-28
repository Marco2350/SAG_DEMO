<?php
/**
 * PresupuestoController — Módulo de Ejecución Presupuestaria
 * Maneja: presupuestos, líneas presupuestarias, compras, viáticos,
 *         gastos varios y documentos del programa.
 * Permisos basados en $_SESSION['user']['rol_slug']:
 *   admin        → acceso total
 *   coordinador  → autorizar presupuesto, aprobar compras/viáticos (nivel 2)
 *   jefe         → visto bueno de viáticos (nivel 1)
 *   tecnico/any  → solicitar viáticos, registrar gastos, ver reportes
 */
class PresupuestoController extends Controller
{
    // Roles con acceso total al módulo
    // Roles que pueden dar visto bueno (nivel 1 viáticos)
    // Formatos admitidos por los formularios de respaldo y autorización.
    private const TIPOS_DOCUMENTO_PRESUPUESTO = ['pdf', 'doc', 'docx'];

    public function __construct()
    {
        $this->requirePrograma();
    }

    // ─── HELPER: obtener rol del usuario en sesión ────────────
    private function rolActual(): string
    {
        return $_SESSION['user']['rol_slug'] ?? 'tecnico';
    }

    private function esAdmin(): bool
    {
        return Permisos::puedeEn('presupuesto', ACC_ELIMINAR);
    }

    private function esJefeOSuperior(): bool
    {
        return Permisos::puedeEn('presupuesto', ACC_APROBAR);
    }

    private function userId(): int
    {
        return (int) ($_SESSION['user']['id_usuario'] ?? 0);
    }

    // ─── HELPER: subir archivo ────────────────────────────────
    private function subirArchivo(string $campo, string $carpeta, array $tiposPermitidos = ['pdf','jpg','jpeg','png']): ?string
    {
        if (empty($_FILES[$campo]['name'])) return null;
        $file    = $_FILES[$campo];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
            || empty($file['tmp_name'])
            || !is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('La carga del archivo no es válida o quedó incompleta.');
        }
        if (!in_array($carpeta, ['presupuesto', 'compras', 'viaticos', 'documentos'], true)) {
            throw new InvalidArgumentException('Destino de archivo no permitido.');
        }
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $tiposPermitidos, true)) {
            throw new InvalidArgumentException(
                'Tipo de archivo no permitido. Formatos aceptados: ' . strtoupper(implode(', ', $tiposPermitidos)) . '.'
            );
        }
        if ($file['size'] > 10 * 1024 * 1024) { // 10 MB max
            throw new InvalidArgumentException('El archivo supera el tamaño máximo de 10 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
        $mimesPorExtension = [
            'pdf'  => ['application/pdf'],
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'doc'  => ['application/msword', 'application/x-ole-storage', 'application/CDFV2', 'application/octet-stream'],
            'xls'  => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/CDFV2', 'application/octet-stream'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        ];
        if (!isset($mimesPorExtension[$ext]) || !in_array($mime, $mimesPorExtension[$ext], true)) {
            throw new InvalidArgumentException('El contenido del archivo no coincide con su extensión.');
        }
        $nombre  = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destDir = ROOT_PATH . '/public/uploads/' . $carpeta . '/';
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
            throw new RuntimeException('No se pudo preparar el directorio de archivos.');
        }
        if (!move_uploaded_file($file['tmp_name'], $destDir . $nombre)) {
            throw new Exception('Error al guardar el archivo en el servidor.');
        }
        return $carpeta . '/' . $nombre;
    }

    // ════════════════════════════════════════════════════════════
    //  VISTA PRINCIPAL — DASHBOARD PRESUPUESTARIO
    // ════════════════════════════════════════════════════════════
    public function index(): void
    {
        $db     = Database::programa();
        $proyId = Database::proyectoId();

        // Presupuesto activo
        $presupuesto = $db->fetchOne(
            "SELECT p.*, u.nombre AS nombre_autorizador
             FROM sag_presupuestos p
             LEFT JOIN sag_usuarios u ON u.id_usuario = p.id_usuario_autoriza
             WHERE p.estado = 'activo' AND p.id_proyecto=? ORDER BY p.anio DESC LIMIT 1",
            [$proyId]
        );

        // KPIs del presupuesto activo
        $kpis = ['aprobado' => 0, 'comprometido' => 0, 'ejecutado' => 0, 'saldo' => 0];
        $lineas = [];

        if ($presupuesto) {
            $pid = $presupuesto['id_presupuesto'];
            $lineas = $db->fetchAll(
                "SELECT l.*,
                    COALESCE((SELECT SUM(monto_estimado) FROM sag_compras c
                              WHERE c.id_linea=l.id_linea AND c.estado IN ('solicitada','cotizando','aprobada')),0) AS comprometido,
                    COALESCE((SELECT SUM(monto_adjudicado) FROM sag_compras c
                              WHERE c.id_linea=l.id_linea AND c.estado='ejecutada'),0) AS ejecutado_compras,
                    COALESCE((SELECT SUM(monto_solicitado) FROM sag_solicitudes_viaticos v
                              WHERE v.id_linea=l.id_linea AND v.estado IN ('visto_bueno','aprobada')),0) AS comprometido_viaticos,
                    COALESCE((SELECT SUM(monto_ejecutado) FROM sag_solicitudes_viaticos v
                              WHERE v.id_linea=l.id_linea AND v.estado='liquidada'),0) AS ejecutado_viaticos,
                    COALESCE((SELECT SUM(monto) FROM sag_gastos_varios g
                              WHERE g.id_linea=l.id_linea AND g.estado='aprobado'),0) AS ejecutado_gastos
                 FROM sag_lineas_presupuestarias l
                 WHERE l.id_presupuesto=? AND l.activo=1 ORDER BY l.orden, l.nombre",
                [$pid]
            );

            foreach ($lineas as &$ln) {
                $ln['ejecutado'] = $ln['ejecutado_compras'] + $ln['ejecutado_viaticos'] + $ln['ejecutado_gastos'];
                $ln['comprometido_total'] = $ln['comprometido'] + $ln['comprometido_viaticos'];
                $ln['saldo'] = $ln['monto_aprobado'] - $ln['ejecutado'] - $ln['comprometido_total'];
                $kpis['aprobado']     += $ln['monto_aprobado'];
                $kpis['comprometido'] += $ln['comprometido_total'];
                $kpis['ejecutado']    += $ln['ejecutado'];
            }
            unset($ln);
            $kpis['saldo'] = $kpis['aprobado'] - $kpis['ejecutado'] - $kpis['comprometido'];
        }

        // Conteos de estados recientes
        $conteos = [
            'compras_pendientes'  => (int)($db->fetchOne("SELECT COUNT(*) AS c FROM sag_compras WHERE estado='solicitada' AND id_proyecto=?", [$proyId])['c'] ?? 0),
            'viaticos_pendientes' => (int)($db->fetchOne("SELECT COUNT(*) AS c FROM sag_solicitudes_viaticos WHERE estado='pendiente' AND id_proyecto=?", [$proyId])['c'] ?? 0),
            'viaticos_visto'      => (int)($db->fetchOne("SELECT COUNT(*) AS c FROM sag_solicitudes_viaticos WHERE estado='visto_bueno' AND id_proyecto=?", [$proyId])['c'] ?? 0),
            'documentos'          => (int)($db->fetchOne("SELECT COUNT(*) AS c FROM sag_documentos_programa WHERE activo=1 AND id_proyecto=?", [$proyId])['c'] ?? 0),
        ];

        // Lista de presupuestos con nombre del autorizador
        $presupuestos = $db->fetchAll(
            "SELECT p.*, CONCAT(COALESCE(u.nombre,''),' ',COALESCE(u.apellido,'')) AS nombre_autorizador
             FROM sag_presupuestos p
             LEFT JOIN sag_usuarios u ON u.id_usuario = p.id_usuario_autoriza
             WHERE p.id_proyecto=?
             ORDER BY p.anio DESC, p.id_presupuesto DESC",
            [$proyId]
        );

        $pageTitle  = 'Ejecución Presupuestaria — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;
        $esAdmin    = $this->esAdmin();
        $esJefe     = $this->esJefeOSuperior();
        $rolActual  = $this->rolActual();

        $this->view('presupuesto/index', compact(
            'pageTitle', 'presupuesto', 'lineas', 'kpis', 'conteos',
            'presupuestos', 'esAdmin', 'esJefe', 'rolActual'
        ));
    }

    // ════════════════════════════════════════════════════════════
    //  PRESUPUESTOS CRUD
    // ════════════════════════════════════════════════════════════
    public function savePresupuesto(): void
    {
        if (!$this->esAdmin()) { $this->error('Sin permisos para esta acción.'); return; }
        $id     = (int) $this->getPost('id_presupuesto', 0);
        $esNuevo = $id === 0;
        $nombre = trim($this->getPost('nombre'));
        $anio   = (int) $this->getPost('anio', date('Y'));
        $monto  = (float) str_replace(',', '', $this->getPost('monto_total', '0'));

        if (!$nombre) { $this->error('El nombre es obligatorio.'); return; }

        $db   = Database::programa();
        $data = [
            'nombre'      => $nombre,
            'descripcion' => $this->getPost('descripcion'),
            'anio'        => $anio,
            'moneda'      => $this->getPost('moneda', 'HNL'),
            'tipo_cambio' => (float) $this->getPost('tipo_cambio', 1),
            'monto_total' => $monto,
            'estado'      => $this->getPost('estado', 'borrador'),
            'observaciones'=> $this->getPost('observaciones'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        try {
            // Archivo de respaldo
            if (!empty($_FILES['doc_respaldo']['name'])) {
                $data['documento_respaldo'] = $this->subirArchivo(
                    'doc_respaldo',
                    'presupuesto',
                    self::TIPOS_DOCUMENTO_PRESUPUESTO
                );
            }

            if ($id) {
                $sets = implode(',', array_map(fn($k) => "$k=?", array_keys($data)));
                $db->execute("UPDATE sag_presupuestos SET $sets WHERE id_presupuesto=? AND id_proyecto=?",
                    [...array_values($data), $id, Database::proyectoId()]);
            } else {
                $data['created_by']  = $this->userId();
                $data['id_proyecto'] = Database::proyectoId();
                $keys = implode(',', array_keys($data));
                $vals = implode(',', array_fill(0, count($data), '?'));
                $db->execute("INSERT INTO sag_presupuestos ($keys) VALUES ($vals)", array_values($data));
                $id = $db->lastInsertId();
            }

            // Si se guardó como activo, cerrar otros presupuestos activos del programa
            if ($data['estado'] === 'activo') {
                $db->execute(
                    "UPDATE sag_presupuestos SET estado='cerrado' WHERE estado='activo' AND id_presupuesto!=? AND id_proyecto=?",
                    [$id, Database::proyectoId()]
                );
                // Registrar quién lo autorizó
                $db->execute(
                    "UPDATE sag_presupuestos SET id_usuario_autoriza=?, fecha_autorizacion=NOW() WHERE id_presupuesto=? AND id_usuario_autoriza IS NULL",
                    [$this->userId(), $id]
                );
            }

            $this->logAction($esNuevo ? 'CREAR' : 'EDITAR', 'presupuesto', "ID:$id — $nombre estado:{$data['estado']}");
            $this->success($esNuevo ? 'Presupuesto creado.' : 'Presupuesto actualizado.', ['id' => $id, 'estado' => $data['estado']]);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
        } catch (Exception $e) {
            error_log('PresupuestoController::savePresupuesto — ' . $e->getMessage());
            $this->error('Error al guardar el presupuesto. Revise los datos e intente de nuevo.');
        }
    }

    public function autorizarPresupuesto(): void
    {
        if (!$this->esAdmin()) { $this->error('Sin permisos.'); return; }
        $id = (int) $this->getPost('id', 0);
        $db = Database::programa();

        try {
            $archivo = null;
            if (!empty($_FILES['doc_autorizacion']['name'])) {
                $archivo = $this->subirArchivo(
                    'doc_autorizacion',
                    'presupuesto',
                    self::TIPOS_DOCUMENTO_PRESUPUESTO
                );
            }
            $db->execute(
                "UPDATE sag_presupuestos SET estado='activo', id_usuario_autoriza=?, fecha_autorizacion=NOW(),
                 documento_respaldo=COALESCE(?, documento_respaldo) WHERE id_presupuesto=?",
                [$this->userId(), $archivo, $id]
            );
            // Cerrar otros presupuestos activos del programa (solo uno activo a la vez)
            $db->execute(
                "UPDATE sag_presupuestos SET estado='cerrado' WHERE estado='activo' AND id_presupuesto!=? AND id_proyecto=?",
                [$id, Database::proyectoId()]
            );
            $this->logAction('AUTORIZAR', 'presupuesto', "ID:$id");
            $this->success('Presupuesto autorizado y activado.');
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
        } catch (Exception $e) {
            error_log('PresupuestoController::autorizarPresupuesto — ' . $e->getMessage());
            $this->error('Error al autorizar el presupuesto.');
        }
    }

    public function getPresupuesto(): void
    {
        $id  = (int) $this->getPost('id', 0);
        $db  = Database::programa();
        $pid = Database::proyectoId();
        // Aislamiento: sólo presupuestos del proyecto activo
        $p   = $db->fetchOne(
            "SELECT * FROM sag_presupuestos WHERE id_presupuesto=? AND id_proyecto=?",
            [$id, $pid]
        );
        if (!$p) { $this->error('No encontrado.'); return; }
        $lineas = $db->fetchAll(
            "SELECT * FROM sag_lineas_presupuestarias
             WHERE id_presupuesto=? AND id_proyecto=? AND activo=1
             ORDER BY orden, nombre",
            [$id, $pid]
        );
        $this->success('OK', ['presupuesto' => $p, 'lineas' => $lineas]);
    }

    // ════════════════════════════════════════════════════════════
    //  LÍNEAS PRESUPUESTARIAS CRUD
    // ════════════════════════════════════════════════════════════
    public function saveLinea(): void
    {
        if (!$this->esAdmin()) { $this->error('Sin permisos.'); return; }
        $id   = (int) $this->getPost('id_linea', 0);
        $pid  = (int) $this->getPost('id_presupuesto', 0);
        $nombre = trim($this->getPost('nombre'));
        if (!$pid || !$nombre) { $this->error('Datos incompletos.'); return; }

        $db   = Database::programa();
        $monto = (float) str_replace(',', '', $this->getPost('monto_aprobado', '0'));
        $data  = [
            'id_presupuesto' => $pid,
            'codigo'         => $this->getPost('codigo'),
            'nombre'         => $nombre,
            'descripcion'    => $this->getPost('descripcion'),
            'tipo'           => $this->getPost('tipo', 'otro'),
            'monto_aprobado' => $monto,
            'orden'          => (int) $this->getPost('orden', 0),
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        try {
            if ($id) {
                // Guardar auditoría del cambio de monto — aislado por proyecto
                $anterior = $db->fetchOne(
                    "SELECT monto_aprobado FROM sag_lineas_presupuestarias
                     WHERE id_linea=? AND id_proyecto=?",
                    [$id, Database::proyectoId()]
                );
                if (!$anterior) { $this->error('Línea no encontrada en su proyecto.'); return; }
                if ($anterior && (float)$anterior['monto_aprobado'] !== $monto) {
                    $tipo = $monto > (float)$anterior['monto_aprobado'] ? 'ampliacion' : 'reduccion';
                    $db->execute(
                        "INSERT INTO sag_modificaciones_presupuesto (id_proyecto,id_linea,tipo,monto_anterior,monto_nuevo,justificacion,id_usuario) VALUES (?,?,?,?,?,?,?)",
                        [Database::proyectoId(), $id, $tipo, $anterior['monto_aprobado'], $monto, $this->getPost('justificacion_ajuste', 'Modificación desde interfaz.'), $this->userId()]
                    );
                }
                $sets = implode(',', array_map(fn($k) => "$k=?", array_keys($data)));
                $db->execute("UPDATE sag_lineas_presupuestarias SET $sets WHERE id_linea=? AND id_proyecto=?", [...array_values($data), $id, Database::proyectoId()]);
            } else {
                $data['id_proyecto'] = Database::proyectoId();
                $keys = implode(',', array_keys($data));
                $vals = implode(',', array_fill(0, count($data), '?'));
                $db->execute("INSERT INTO sag_lineas_presupuestarias ($keys) VALUES ($vals)", array_values($data));
                $id = $db->lastInsertId();
            }
            // Actualizar monto_total del presupuesto — aislado por proyecto
            $proyId = Database::proyectoId();
            $db->execute(
                "UPDATE sag_presupuestos
                    SET monto_total=(
                        SELECT COALESCE(SUM(monto_aprobado),0)
                          FROM sag_lineas_presupuestarias
                         WHERE id_presupuesto=? AND id_proyecto=? AND activo=1
                    )
                  WHERE id_presupuesto=? AND id_proyecto=?",
                [$pid, $proyId, $pid, $proyId]
            );
            $this->success($id ? 'Línea guardada.' : 'Línea creada.', ['id' => $id]);
        } catch (Exception $e) {
            error_log('PresupuestoController::saveLinea — ' . $e->getMessage());
            $this->error('Error al guardar la línea presupuestaria.');
        }
    }

    public function deleteLinea(): void
    {
        if (!$this->esAdmin()) { $this->error('Sin permisos.'); return; }
        $id  = (int) $this->getPost('id', 0);
        $db  = Database::programa();
        $pid = Database::proyectoId();
        // Soft-delete aislado por proyecto. Si la línea no pertenece al
        // proyecto activo, el UPDATE afecta 0 filas y devolvemos error.
        $afect = $db->execute(
            "UPDATE sag_lineas_presupuestarias
                SET activo=0
              WHERE id_linea=? AND id_proyecto=?",
            [$id, $pid]
        );
        if ($afect === 0) { $this->error('Línea no encontrada en su proyecto.'); return; }
        $this->logAction('DELETE_LINEA_PRESUP', 'presupuesto', "id_linea={$id}");
        $this->success('Línea eliminada.');
    }

    public function listarLineas(): void
    {
        $pid = (int) $this->getPost('id_presupuesto', 0);
        $db  = Database::programa();
        $rows = $db->fetchAll(
            "SELECT l.*,
                COALESCE((SELECT SUM(monto_estimado) FROM sag_compras c WHERE c.id_linea=l.id_linea AND c.estado IN ('solicitada','cotizando','aprobada')),0) AS comprometido,
                COALESCE((SELECT SUM(monto_adjudicado) FROM sag_compras c WHERE c.id_linea=l.id_linea AND c.estado='ejecutada'),0) AS ejecutado_compras,
                COALESCE((SELECT SUM(monto_solicitado) FROM sag_solicitudes_viaticos v WHERE v.id_linea=l.id_linea AND v.estado IN ('visto_bueno','aprobada')),0) AS comprometido_viaticos,
                COALESCE((SELECT SUM(monto_ejecutado) FROM sag_solicitudes_viaticos v WHERE v.id_linea=l.id_linea AND v.estado='liquidada'),0) AS ejecutado_viaticos,
                COALESCE((SELECT SUM(monto) FROM sag_gastos_varios g WHERE g.id_linea=l.id_linea AND g.estado='aprobado'),0) AS ejecutado_gastos
             FROM sag_lineas_presupuestarias l
             WHERE l.id_presupuesto=? AND l.activo=1 ORDER BY l.orden, l.nombre",
            [$pid]
        );
        foreach ($rows as &$ln) {
            $ln['ejecutado']          = $ln['ejecutado_compras'] + $ln['ejecutado_viaticos'] + $ln['ejecutado_gastos'];
            $ln['comprometido_total'] = $ln['comprometido'] + $ln['comprometido_viaticos'];
            $ln['saldo']              = $ln['monto_aprobado'] - $ln['ejecutado'] - $ln['comprometido_total'];
            $ln['pct']                = $ln['monto_aprobado'] > 0 ? round($ln['ejecutado'] / $ln['monto_aprobado'] * 100, 1) : 0;
        }
        $this->json(['data' => $rows]);
    }

    // ════════════════════════════════════════════════════════════
    //  COMPRAS
    // ════════════════════════════════════════════════════════════
    public function listarCompras(): void
    {
        $db    = Database::programa();
        $estado = $this->getPost('estado', '');
        $where = ['c.id_proyecto=?'];
        $params = [Database::proyectoId()];

        // Técnicos/no-admin solo ven sus propias solicitudes
        if (!$this->esJefeOSuperior()) {
            $where[] = 'c.id_usuario_solicita=?';
            $params[] = $this->userId();
        }
        if ($estado) { $where[] = 'c.estado=?'; $params[] = $estado; }

        $rows = $db->fetchAll(
            "SELECT c.*, l.nombre AS linea_nombre, l.codigo AS linea_codigo,
                    CONCAT(u.nombre,' ',u.apellido) AS solicitante
             FROM sag_compras c
             LEFT JOIN sag_lineas_presupuestarias l ON l.id_linea=c.id_linea
             LEFT JOIN sag_usuarios u ON u.id_usuario=c.id_usuario_solicita
             WHERE " . implode(' AND ', $where) . " ORDER BY c.created_at DESC",
            $params
        );
        $this->json(['data' => $rows]);
    }

    public function saveCompra(): void
    {
        $id    = (int) $this->getPost('id_compra', 0);
        $desc  = trim($this->getPost('descripcion'));
        if (!$desc) { $this->error('La descripción es obligatoria.'); return; }

        $db    = Database::programa();
        $monto = (float) str_replace(',', '', $this->getPost('monto_estimado', '0'));

        // Si ya existe, verificar que pueda editarla
        if ($id) {
            $existing = $db->fetchOne("SELECT * FROM sag_compras WHERE id_compra=?", [$id]);
            if (!$existing) { $this->error('Solicitud no encontrada.'); return; }
            if (!$this->esAdmin() && $existing['id_usuario_solicita'] != $this->userId()) {
                $this->error('No puede editar esta solicitud.'); return;
            }
            if (!$this->esAdmin() && $existing['estado'] !== 'borrador') {
                $this->error('Solo se pueden editar solicitudes en borrador.'); return;
            }
        }

        $data = [
            'id_linea'       => (int)$this->getPost('id_linea', 0) ?: null,
            'descripcion'    => $desc,
            'justificacion'  => $this->getPost('justificacion'),
            'monto_estimado' => $monto,
            'moneda'         => $this->getPost('moneda', 'HNL'),
            'proveedor'      => $this->getPost('proveedor'),
            'numero_factura' => $this->getPost('numero_factura'),
            'fecha_solicitud'=> $this->getPost('fecha_solicitud') ?: date('Y-m-d'),
            'observaciones'  => $this->getPost('observaciones'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        try {
            // Archivos adjuntos
            $campos_archivo = ['archivo_solicitud' => 'compras', 'archivo_cotizacion' => 'compras', 'archivo_factura' => 'compras'];
            foreach ($campos_archivo as $campo => $carpeta) {
                if (!empty($_FILES[$campo]['name'])) {
                    $data[$campo] = $this->subirArchivo($campo, $carpeta, ['pdf','jpg','jpeg','png','xlsx','xls']);
                }
            }

            if ($id) {
                $sets = implode(',', array_map(fn($k) => "$k=?", array_keys($data)));
                $db->execute("UPDATE sag_compras SET $sets WHERE id_compra=? AND id_proyecto=?", [...array_values($data), $id, Database::proyectoId()]);
            } else {
                $data['id_usuario_solicita'] = $this->userId();
                $data['estado']              = 'borrador';
                $data['id_proyecto']         = Database::proyectoId();
                $n = $db->fetchOne("SELECT COUNT(*)+1 AS n FROM sag_compras WHERE id_proyecto=?", [Database::proyectoId()])['n'] ?? 1;
                $data['numero_solicitud']    = 'SOL-' . date('Y') . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
                $keys = implode(',', array_keys($data));
                $vals = implode(',', array_fill(0, count($data), '?'));
                $db->execute("INSERT INTO sag_compras ($keys) VALUES ($vals)", array_values($data));
                $id = $db->lastInsertId();
            }
            $this->logAction('COMPRA_SAVE', 'compras', "ID:$id — $desc");
            $this->success('Solicitud guardada.', ['id' => $id, 'numero' => $data['numero_solicitud'] ?? '']);
        } catch (Exception $e) {
            error_log('PresupuestoController::saveCompra — ' . $e->getMessage());
            $this->error('Error al guardar la solicitud de compra.');
        }
    }

    public function estadoCompra(): void
    {
        if (!$this->esJefeOSuperior()) { $this->error('Sin permisos.'); return; }
        $id     = (int) $this->getPost('id', 0);
        $estado = $this->getPost('estado', '');
        $obs    = (string) $this->getPost('observacion_estado', '');
        $estados = ['borrador','solicitada','cotizando','aprobada','ejecutada','anulada'];
        if (!in_array($estado, $estados, true)) { $this->error('Estado no válido.'); return; }

        $db  = Database::programa();
        $pid = Database::proyectoId();

        // Validar que la compra pertenece al proyecto activo (defensa explícita)
        $compra = $db->fetchOne(
            "SELECT id_compra FROM sag_compras WHERE id_compra=? AND id_proyecto=?",
            [$id, $pid]
        );
        if (!$compra) { $this->error('Compra no encontrada en su proyecto.'); return; }

        // Armado de SET dinámico controlado por lista cerrada
        $sets   = ['estado=?',
                   "observaciones=CONCAT(COALESCE(observaciones,''),' | " . date('d/m/Y') . ": ',?)"];
        $params = [$estado, $obs];

        if ($estado === 'aprobada' && $this->esAdmin()) {
            $sets[]   = 'id_usuario_aprueba=?';
            $sets[]   = 'fecha_aprobacion=NOW()';
            $params[] = $this->userId();
        }
        if ($estado === 'ejecutada') {
            $monto_adj = (float) str_replace(',', '', (string)$this->getPost('monto_adjudicado', '0'));
            $sets[]    = 'monto_adjudicado=?';
            $sets[]    = 'fecha_ejecucion=NOW()';
            $params[]  = $monto_adj;
        }
        $params[] = $id;
        $params[] = $pid;

        $db->execute(
            "UPDATE sag_compras SET " . implode(', ', $sets) .
            " WHERE id_compra=? AND id_proyecto=?",
            $params
        );
        $this->logAction('COMPRA_ESTADO', 'compras', "ID:$id → $estado");
        $this->success("Estado actualizado a: $estado");
    }

    // ════════════════════════════════════════════════════════════
    //  VIÁTICOS
    // ════════════════════════════════════════════════════════════
    public function listarViaticos(): void
    {
        $db     = Database::programa();
        $estado = $this->getPost('estado', '');
        $where  = ['v.id_proyecto=?'];
        $params = [Database::proyectoId()];

        if (!$this->esJefeOSuperior()) {
            // Usuario normal: solo ve las suyas
            $where[] = 'v.id_solicitante=?';
            $params[] = $this->userId();
        }
        if ($estado) { $where[] = 'v.estado=?'; $params[] = $estado; }

        $rows = $db->fetchAll(
            "SELECT v.*, l.nombre AS linea_nombre,
                    CONCAT(u.nombre,' ',u.apellido) AS nombre_solicitante,
                    u.email AS email_solicitante
             FROM sag_solicitudes_viaticos v
             LEFT JOIN sag_lineas_presupuestarias l ON l.id_linea=v.id_linea
             LEFT JOIN sag_usuarios u ON u.id_usuario=v.id_solicitante
             WHERE " . implode(' AND ', $where) . " ORDER BY v.created_at DESC",
            $params
        );
        $this->json(['data' => $rows]);
    }

    public function saveViatico(): void
    {
        $id          = (int) $this->getPost('id_solicitud', 0);
        $destino     = trim($this->getPost('destino'));
        $objetivo    = trim($this->getPost('objetivo'));
        $fechaSalida = $this->getPost('fecha_salida');
        $fechaRetorno= $this->getPost('fecha_retorno');

        if (!$destino || !$objetivo || !$fechaSalida || !$fechaRetorno) {
            $this->error('Complete todos los campos obligatorios.'); return;
        }

        if ($id) {
            $db = Database::programa();
            $ex = $db->fetchOne("SELECT * FROM sag_solicitudes_viaticos WHERE id_solicitud=?", [$id]);
            if (!$ex) { $this->error('No encontrada.'); return; }
            if (!$this->esAdmin() && $ex['id_solicitante'] != $this->userId()) {
                $this->error('Sin permisos.'); return;
            }
            if (!$this->esAdmin() && $ex['estado'] !== 'pendiente') {
                $this->error('No se puede modificar una solicitud en proceso.'); return;
            }
        }

        $hospedaje    = (float) $this->getPost('hospedaje', 0);
        $alimentacion = (float) $this->getPost('alimentacion', 0);
        $transporte   = (float) $this->getPost('transporte', 0);
        $otros        = (float) $this->getPost('otros', 0);
        $monto        = $hospedaje + $alimentacion + $transporte + $otros;
        $dias         = max(1, (int)((strtotime($fechaRetorno) - strtotime($fechaSalida)) / 86400) + 1);

        $data = [
            'id_linea'        => (int)$this->getPost('id_linea', 0) ?: null,
            'destino'         => $destino,
            'objetivo'        => $objetivo,
            'fecha_salida'    => $fechaSalida,
            'fecha_retorno'   => $fechaRetorno,
            'dias'            => $dias,
            'monto_solicitado'=> $monto,
            'moneda'          => $this->getPost('moneda', 'HNL'),
            'hospedaje'       => $hospedaje,
            'alimentacion'    => $alimentacion,
            'transporte'      => $transporte,
            'otros'           => $otros,
            'updated_at'      => date('Y-m-d H:i:s'),
        ];

        $db = Database::programa();
        try {
            if (!empty($_FILES['archivo_solicitud']['name'])) {
                $data['archivo_solicitud'] = $this->subirArchivo('archivo_solicitud', 'viaticos');
            }
            if ($id) {
                $sets = implode(',', array_map(fn($k) => "$k=?", array_keys($data)));
                $db->execute("UPDATE sag_solicitudes_viaticos SET $sets WHERE id_solicitud=? AND id_proyecto=?", [...array_values($data), $id, Database::proyectoId()]);
            } else {
                $data['id_solicitante']  = $this->userId();
                $data['estado']          = 'pendiente';
                $data['id_proyecto']     = Database::proyectoId();
                $n = $db->fetchOne("SELECT COUNT(*)+1 AS n FROM sag_solicitudes_viaticos WHERE id_proyecto=?", [Database::proyectoId()])['n'] ?? 1;
                $data['numero_solicitud']= 'VIA-' . date('Y') . '-' . str_pad($n, 4, '0', STR_PAD_LEFT);
                $keys = implode(',', array_keys($data));
                $vals = implode(',', array_fill(0, count($data), '?'));
                $db->execute("INSERT INTO sag_solicitudes_viaticos ($keys) VALUES ($vals)", array_values($data));
                $id = $db->lastInsertId();
            }
            $this->logAction('VIATICO_SAVE', 'viaticos', "ID:$id — $destino");
            $this->success('Solicitud guardada.', ['id' => $id]);
        } catch (Exception $e) {
            error_log('PresupuestoController::saveViatico — ' . $e->getMessage());
            $this->error('Error al guardar la solicitud de viáticos.');
        }
    }

    public function vistoBoeno(): void
    {
        // Nivel 1: jefe inmediato
        if (!$this->esJefeOSuperior()) { $this->error('Sin permisos para dar visto bueno.'); return; }
        $id  = (int) $this->getPost('id', 0);
        $db  = Database::programa();
        $pid = Database::proyectoId();
        // Aislamiento: la solicitud debe pertenecer al proyecto activo
        $v   = $db->fetchOne(
            "SELECT * FROM sag_solicitudes_viaticos WHERE id_solicitud=? AND id_proyecto=?",
            [$id, $pid]
        );
        if (!$v || $v['estado'] !== 'pendiente') { $this->error('Solicitud no válida o ya procesada.'); return; }
        $db->execute(
            "UPDATE sag_solicitudes_viaticos
                SET estado='visto_bueno', id_jefe=?, fecha_visto_bueno=NOW(), observacion_jefe=?
              WHERE id_solicitud=? AND id_proyecto=?",
            [$this->userId(), $this->getPost('observacion'), $id, $pid]
        );
        $this->logAction('VISTO_BUENO', 'viaticos', "ID:$id");
        $this->success('Visto bueno otorgado.');
    }

    public function aprobarViatico(): void
    {
        // Nivel 2: coordinador/ministro
        if (!$this->esAdmin()) { $this->error('Sin permisos para aprobar.'); return; }
        $id     = (int) $this->getPost('id', 0);
        $accion = $this->getPost('accion', 'aprobar'); // aprobar | rechazar
        $db     = Database::programa();
        $pid    = Database::proyectoId();
        // Aislamiento: la solicitud debe pertenecer al proyecto activo
        $v = $db->fetchOne(
            "SELECT * FROM sag_solicitudes_viaticos WHERE id_solicitud=? AND id_proyecto=?",
            [$id, $pid]
        );
        if (!$v || !in_array($v['estado'], ['visto_bueno','pendiente'], true)) {
            $this->error('Solicitud no válida o ya procesada.'); return;
        }
        $estado = $accion === 'aprobar' ? 'aprobada' : 'rechazada';
        $db->execute(
            "UPDATE sag_solicitudes_viaticos
                SET estado=?, id_autoridad=?, fecha_aprobacion=NOW(), observacion_autoridad=?
              WHERE id_solicitud=? AND id_proyecto=?",
            [$estado, $this->userId(), $this->getPost('observacion'), $id, $pid]
        );
        $this->logAction('APROBAR_VIATICO', 'viaticos', "ID:$id → $estado");
        $this->success('Solicitud ' . ($estado === 'aprobada' ? 'aprobada.' : 'rechazada.'));
    }

    public function liquidarViatico(): void
    {
        $id  = (int) $this->getPost('id', 0);
        $db  = Database::programa();
        $pid = Database::proyectoId();
        // Aislamiento: la solicitud debe pertenecer al proyecto activo
        $v   = $db->fetchOne(
            "SELECT * FROM sag_solicitudes_viaticos WHERE id_solicitud=? AND id_proyecto=?",
            [$id, $pid]
        );
        if (!$v || $v['estado'] !== 'aprobada') { $this->error('Solo se liquidan solicitudes aprobadas.'); return; }
        if (!$this->esAdmin() && $v['id_solicitante'] != $this->userId()) { $this->error('Sin permisos.'); return; }

        try {
            $archivo = null;
            if (!empty($_FILES['archivo_liquidacion']['name'])) {
                $archivo = $this->subirArchivo('archivo_liquidacion', 'viaticos');
            }
            $monto_ej = (float) str_replace(',', '', $this->getPost('monto_ejecutado', '0'));
            $db->execute(
                "UPDATE sag_solicitudes_viaticos
                    SET estado='liquidada', monto_ejecutado=?, fecha_liquidacion=?,
                        archivo_liquidacion=COALESCE(?,archivo_liquidacion)
                  WHERE id_solicitud=? AND id_proyecto=?",
                [$monto_ej, $this->getPost('fecha_liquidacion') ?: date('Y-m-d'), $archivo, $id, $pid]
            );
            $this->logAction('LIQUIDAR_VIATICO', 'viaticos', "ID:$id · monto=$monto_ej");
            $this->success('Viático liquidado correctamente.');
        } catch (Exception $e) {
            error_log('PresupuestoController::liquidarViatico — ' . $e->getMessage());
            $this->error('Error al liquidar el viático.');
        }
    }

    // ════════════════════════════════════════════════════════════
    //  GASTOS VARIOS
    // ════════════════════════════════════════════════════════════
    public function listarGastos(): void
    {
        $db  = Database::programa();
        $rows = $db->fetchAll(
            "SELECT g.*, l.nombre AS linea_nombre, CONCAT(u.nombre,' ',u.apellido) AS registrado_por
             FROM sag_gastos_varios g
             LEFT JOIN sag_lineas_presupuestarias l ON l.id_linea=g.id_linea
             LEFT JOIN sag_usuarios u ON u.id_usuario=g.id_usuario
             WHERE g.id_proyecto=?
             ORDER BY g.fecha_gasto DESC",
            [Database::proyectoId()]
        );
        $this->json(['data' => $rows]);
    }

    public function saveGasto(): void
    {
        $id   = (int) $this->getPost('id_gasto', 0);
        $desc = trim($this->getPost('descripcion'));
        if (!$desc) { $this->error('La descripción es obligatoria.'); return; }
        $db   = Database::programa();
        $data = [
            'id_linea'        => (int)$this->getPost('id_linea', 0) ?: null,
            'descripcion'     => $desc,
            'tipo'            => $this->getPost('tipo', 'otro'),
            'monto'           => (float) str_replace(',', '', $this->getPost('monto', '0')),
            'moneda'          => $this->getPost('moneda', 'HNL'),
            'fecha_gasto'     => $this->getPost('fecha_gasto') ?: date('Y-m-d'),
            'beneficiario'    => $this->getPost('beneficiario'),
            'numero_documento'=> $this->getPost('numero_documento'),
            'observaciones'   => $this->getPost('observaciones'),
        ];
        try {
            if (!empty($_FILES['archivo']['name'])) {
                $data['archivo'] = $this->subirArchivo('archivo', 'compras');
            }
            if ($id) {
                $sets = implode(',', array_map(fn($k) => "$k=?", array_keys($data)));
                $db->execute("UPDATE sag_gastos_varios SET $sets WHERE id_gasto=? AND id_proyecto=?", [...array_values($data), $id, Database::proyectoId()]);
            } else {
                $data['id_usuario']  = $this->userId();
                $data['estado']      = 'registrado';
                $data['id_proyecto'] = Database::proyectoId();
                $keys = implode(',', array_keys($data));
                $vals = implode(',', array_fill(0, count($data), '?'));
                $db->execute("INSERT INTO sag_gastos_varios ($keys) VALUES ($vals)", array_values($data));
                $id = $db->lastInsertId();
            }
            $this->success('Gasto guardado.', ['id' => $id]);
        } catch (Exception $e) {
            error_log('PresupuestoController::saveGasto — ' . $e->getMessage());
            $this->error('Error al guardar el gasto.');
        }
    }

    public function estadoGasto(): void
    {
        if (!$this->esAdmin()) { $this->error('Sin permisos.'); return; }
        $id     = (int) $this->getPost('id', 0);
        $estado = $this->getPost('estado', '');
        if (!in_array($estado, ['registrado','aprobado','rechazado'], true)) { $this->error('Estado inválido.'); return; }
        $pid    = Database::proyectoId();
        $afect  = Database::programa()->execute(
            "UPDATE sag_gastos_varios SET estado=? WHERE id_gasto=? AND id_proyecto=?",
            [$estado, $id, $pid]
        );
        if ($afect === 0) { $this->error('Gasto no encontrado en su proyecto.'); return; }
        $this->logAction('GASTO_ESTADO', 'gastos', "ID:$id → $estado");
        $this->success("Estado actualizado.");
    }

    // ════════════════════════════════════════════════════════════
    //  DOCUMENTOS DEL PROGRAMA
    // ════════════════════════════════════════════════════════════
    public function listarDocumentos(): void
    {
        $db   = Database::programa();
        $rows = $db->fetchAll(
            "SELECT d.*, CONCAT(u.nombre,' ',u.apellido) AS subido_por
             FROM sag_documentos_programa d
             LEFT JOIN sag_usuarios u ON u.id_usuario=d.created_by
             WHERE d.activo=1 AND d.id_proyecto=? ORDER BY d.created_at DESC",
            [Database::proyectoId()]
        );
        $this->json(['data' => $rows]);
    }

    public function saveDocumento(): void
    {
        if (!$this->esAdmin()) { $this->error('Sin permisos.'); return; }
        $id     = (int) $this->getPost('id_documento', 0);
        $nombre = trim($this->getPost('nombre'));
        $tipo   = $this->getPost('tipo', 'otro');
        if (!$nombre || !$tipo) { $this->error('Nombre y tipo son obligatorios.'); return; }

        $db   = Database::programa();
        $data = [
            'tipo'           => $tipo,
            'nombre'         => $nombre,
            'descripcion'    => $this->getPost('descripcion'),
            'fecha_documento'=> $this->getPost('fecha_documento') ?: null,
            'vigente'        => (int) $this->getPost('vigente', 1),
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        try {
            if (!empty($_FILES['archivo_doc']['name'])) {
                $file = $this->subirArchivo('archivo_doc', 'documentos', ['pdf','doc','docx','xls','xlsx','jpg','png']);
                $data['archivo']       = $file;
                $data['tamano_bytes']  = $_FILES['archivo_doc']['size'];
            } elseif (!$id) {
                $this->error('El archivo es obligatorio.'); return;
            }

            if ($id) {
                $sets = implode(',', array_map(fn($k) => "$k=?", array_keys($data)));
                $db->execute("UPDATE sag_documentos_programa SET $sets WHERE id_documento=? AND id_proyecto=?", [...array_values($data), $id, Database::proyectoId()]);
            } else {
                $data['created_by']  = $this->userId();
                $data['activo']      = 1;
                $data['id_proyecto'] = Database::proyectoId();
                $keys = implode(',', array_keys($data));
                $vals = implode(',', array_fill(0, count($data), '?'));
                $db->execute("INSERT INTO sag_documentos_programa ($keys) VALUES ($vals)", array_values($data));
                $id = $db->lastInsertId();
            }
            $this->logAction('DOC_SAVE', 'documentos', "ID:$id — $nombre");
            $this->success('Documento guardado.', ['id' => $id]);
        } catch (Exception $e) {
            error_log('PresupuestoController::saveDocumento — ' . $e->getMessage());
            $this->error('Error al guardar el documento.');
        }
    }

    public function deleteDocumento(): void
    {
        if (!$this->esAdmin()) { $this->error('Sin permisos.'); return; }
        $id  = (int) $this->getPost('id', 0);
        $pid = Database::proyectoId();
        $afect = Database::programa()->execute(
            "UPDATE sag_documentos_programa SET activo=0 WHERE id_documento=? AND id_proyecto=?",
            [$id, $pid]
        );
        if ($afect === 0) { $this->error('Documento no encontrado en su proyecto.'); return; }
        $this->logAction('DOC_DELETE', 'documentos', "ID:$id");
        $this->success('Documento eliminado.');
    }

    public function descargarDocumento(): void
    {
        $id  = (int) $this->getQuery('id', 0);
        $db  = Database::programa();
        $pid = Database::proyectoId();
        // Aislamiento crítico: previene leak de documentos de otro programa
        $doc = $db->fetchOne(
            "SELECT * FROM sag_documentos_programa
              WHERE id_documento=? AND id_proyecto=? AND activo=1",
            [$id, $pid]
        );
        if (!$doc) { http_response_code(404); echo 'Documento no encontrado.'; exit; }

        $ruta = ROOT_PATH . '/public/uploads/' . $doc['archivo'];
        if (!file_exists($ruta)) { http_response_code(404); echo 'Archivo no encontrado.'; exit; }
        $this->logAction('DOC_DESCARGAR', 'documentos', "ID:$id");

        $ext  = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        $mime = match($ext) {
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => 'application/octet-stream',
        };
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($doc['nombre']) . '.' . $ext . '"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit;
    }

    // ════════════════════════════════════════════════════════════
    //  API: LÍNEAS PARA SELECTS
    // ════════════════════════════════════════════════════════════
    public function apiLineas(): void
    {
        $db = Database::programa();
        $rows = $db->fetchAll(
            "SELECT l.id_linea, CONCAT(COALESCE(l.codigo,''),' - ',l.nombre) AS label, l.monto_aprobado
             FROM sag_lineas_presupuestarias l
             INNER JOIN sag_presupuestos p ON p.id_presupuesto=l.id_presupuesto
             WHERE p.estado='activo' AND l.activo=1
               AND p.id_proyecto=? AND l.id_proyecto=?
             ORDER BY l.orden, l.nombre",
            [Database::proyectoId(), Database::proyectoId()]
        );
        $this->json($rows);
    }
}
