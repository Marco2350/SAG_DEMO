<?php
$cssExtra = '<style>
/* ── SECTION PANEL ── */
.section-panel{display:none;}
.section-panel.active{display:block;}

/* ── DATA TABLE ── */
.data-table{width:100%;border-collapse:collapse;font-size:.82rem;}
.data-table thead th{background:#f8f9fa;color:#555;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;padding:10px 14px;border-bottom:2px solid #eee;white-space:nowrap;}
.data-table tbody tr{border-bottom:1px solid #f5f5f5;transition:background .1s;}
.data-table tbody tr:hover{background:#f8faff;}
.data-table tbody td{padding:9px 14px;vertical-align:middle;}

/* ── BADGE ── */
.badge-pill{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;}
.bp-green{background:#d1fae5;color:#065f46;}
.bp-yellow{background:#fef9c3;color:#854d0e;}
.bp-blue{background:#dbeafe;color:#1d4ed8;}
.bp-gray{background:#f1f5f9;color:#475569;}
.bp-red{background:#fee2e2;color:#991b1b;}
.bp-purple{background:#ede9fe;color:#5b21b6;}
.bp-orange{background:#ffedd5;color:#c2410c;}

/* ── BUTTONS ── */
.btn-prim{background:var(--primario);color:#fff;border:none;border-radius:8px;padding:8px 16px;font-size:.84rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:opacity .15s;}
.btn-prim:hover{opacity:.88;}
.btn-prim.btn-sm{padding:5px 10px;font-size:.78rem;}
.btn-sec{background:#fff;color:#444;border:1.5px solid #ddd;border-radius:8px;padding:7px 14px;font-size:.82rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:7px;transition:all .15s;}
.btn-sec:hover{border-color:var(--primario);color:var(--primario);}
.btn-danger{background:#fee2e2;color:#991b1b;border:1.5px solid #fecaca;border-radius:7px;padding:5px 10px;font-size:.78rem;cursor:pointer;transition:all .15s;}
.btn-danger:hover{background:#fecaca;}
.btn-edit{background:#eff6ff;color:#1d4ed8;border:1.5px solid #dbeafe;border-radius:7px;padding:5px 10px;font-size:.78rem;cursor:pointer;transition:all .15s;}
.btn-edit:hover{background:#dbeafe;}

/* ── FORM CONTROLS ── */
.fc{width:100%;padding:8px 11px;border:1.5px solid var(--borde);border-radius:8px;font-size:.84rem;color:var(--texto);background:#fff;outline:none;transition:border .15s;}
.fc:focus{border-color:var(--primario);box-shadow:0 0 0 3px color-mix(in srgb, var(--primario) 12%, transparent);}
.fl{font-size:.78rem;font-weight:700;color:#444;margin-bottom:4px;display:block;}
.req{color:#ef4444;}

/* ── USER CARD ── */
.user-card{background:#f8f9fb;border:1.5px solid #eaecf0;border-radius:10px;padding:14px;display:flex;align-items:center;gap:12px;transition:border .15s;}
.user-card:hover{border-color:var(--primario);}
.user-avatar-lg{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.88rem;font-weight:700;flex-shrink:0;}

/* ── PARAM ROW ── */
.param-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #f5f5f5;}
.param-row:last-child{border-bottom:none;}
.param-icon{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;background:#eef0f7;color:var(--primario);}
.param-label{flex:1;}
.param-label strong{font-size:.85rem;color:#1a1a1a;display:block;}
.param-label span{font-size:.75rem;color:#888;}

/* ── MODAL ── */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1040;display:none;align-items:center;justify-content:center;}
.modal-overlay.show{display:flex;}
.modal-box{background:#fff;border-radius:14px;width:92%;max-width:520px;display:flex;flex-direction:column;overflow:hidden;max-height:88vh;}
.modal-head{padding:16px 20px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.modal-head h6{font-size:.9rem;font-weight:700;color:#1a1a1a;margin:0;}
.modal-body-inner{flex:1;overflow-y:auto;padding:20px;}
.modal-foot{padding:14px 20px;border-top:1px solid #f0f0f0;display:flex;justify-content:flex-end;gap:10px;flex-shrink:0;}
.btn-close-x{background:none;border:none;font-size:1.1rem;color:#888;cursor:pointer;padding:4px 6px;border-radius:6px;}
.btn-close-x:hover{background:#f0f0f0;}

/* ── CONFIRM ── */
.confirm-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1050;display:none;align-items:center;justify-content:center;}
.confirm-overlay.show{display:flex;}
.confirm-box{background:#fff;border-radius:12px;padding:24px;max-width:380px;width:90%;text-align:center;}
</style>';
?>
<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/mantenimiento.js"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>
<?php
    // Mapa usuario → [id_proyecto...] para el alcance multi-proyecto.
    $mapUsuarioProy = [];
    foreach (($usuariosProyectos ?? []) as $rel) {
        $mapUsuarioProy[(int) $rel['id_usuario']][] = (int) $rel['id_proyecto'];
    }
?>

<script>
const SAG_MANT = {
    tecnicos: <?= json_encode(array_map(fn($t) => [
        'id'              => (int)$t['id_tecnico'],
        'nombre'          => $t['nombre_completo'],
        'especialidad'    => $t['especialidad'] ?? '',
        'id_departamento' => $t['id_departamento'] ?? '',
        'telefono'        => $t['telefono'] ?? '',
        'email'           => $t['email'] ?? '',
        'activo'          => (int)$t['activo'],
    ], $tecnicos), JSON_UNESCAPED_UNICODE) ?>,
    temas: <?= json_encode(array_map(fn($t) => [
        'id'     => (int)$t['id_tema'],
        'nombre' => $t['nombre'],
        'activo' => (int)$t['activo'],
    ], $temas), JSON_UNESCAPED_UNICODE) ?>,
    subtemas: <?= json_encode(array_map(fn($s) => [
        'id'      => (int)$s['id_subtema'],
        'nombre'  => $s['nombre'],
        'id_tema' => (int)$s['id_tema'],
        'tema'    => $s['tema'],
        'activo'  => (int)$s['activo'],
    ], $subtemas), JSON_UNESCAPED_UNICODE) ?>,
    tiposAt: <?= json_encode(array_map(fn($t) => [
        'id'          => (int)$t['id_tipo_at'],
        'nombre'      => $t['nombre'],
        'descripcion' => $t['descripcion'] ?? '',
        'icono'       => $t['icono'] ?? 'fa-circle-check',
        'activo'      => (int)$t['activo'],
    ], $tiposAt), JSON_UNESCAPED_UNICODE) ?>,
    cultivos: <?= json_encode(array_map(fn($c) => [
        'id'     => (int)$c['id_cultivo'],
        'nombre' => $c['nombre'],
        'activo' => (int)$c['activo'],
    ], $cultivos), JSON_UNESCAPED_UNICODE) ?>,
    usuarios: <?= json_encode(array_map(fn($u) => [
        'id'        => (int)$u['id_usuario'],
        'nombre'    => trim(($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? '')),
        'nombre_solo'   => $u['nombre'] ?? '',
        'apellido_solo' => $u['apellido'] ?? '',
        'email'     => $u['email'] ?? '',
        'username'  => $u['username'] ?? '',
        'rol'       => $u['rol_nombre'] ?? '',
        'id_rol'    => (int)$u['id_rol'],
        'activo'    => (int)$u['activo'],
        'todos_proyectos' => (int)($u['todos_proyectos'] ?? 0),
        'proyectos' => $mapUsuarioProy[(int)$u['id_usuario']] ?? [],
    ], $usuarios), JSON_UNESCAPED_UNICODE) ?>,
    roles: <?= json_encode(array_map(fn($r) => [
        'id'          => (int)$r['id_rol'],
        'slug'        => $r['slug'] ?? '',
        'nombre'      => $r['nombre'],
        'descripcion' => $r['descripcion'] ?? '',
        'es_admin'    => (int)($r['es_admin'] ?? 0),
        'activo'      => (int)($r['activo'] ?? 1),
    ], $roles), JSON_UNESCAPED_UNICODE) ?>,
    modulos: <?= json_encode(array_map(fn($m) => [
        'id'     => (int)$m['id_modulo'],
        'slug'   => $m['slug'],
        'nombre' => $m['nombre'],
    ], $modulos ?? []), JSON_UNESCAPED_UNICODE) ?>,
    acciones: <?= json_encode(array_map(fn($a) => [
        'id'     => (int)$a['id_accion'],
        'slug'   => $a['slug'],
        'nombre' => $a['nombre'],
    ], $acciones ?? []), JSON_UNESCAPED_UNICODE) ?>,
    privilegios: <?= json_encode(array_map(fn($p) => [
        (int)$p['id_rol'], (int)$p['id_modulo'], (int)$p['id_accion'],
    ], $privilegios ?? []), JSON_UNESCAPED_UNICODE) ?>,
    proyectos: <?= json_encode(array_map(fn($p) => [
        'id'     => (int)$p['id_proyecto'],
        'codigo' => $p['codigo'],
        'sigla'  => $p['sigla'],
        'nombre' => $p['nombre'],
    ], $proyectos ?? []), JSON_UNESCAPED_UNICODE) ?>,
    esAdminRoles: <?= !empty($esAdminRoles) ? 'true' : 'false' ?>,
    departamentos: <?= json_encode(array_map(fn($d) => [
        'id'     => (int)$d['id_departamento'],
        'nombre' => $d['nombre'],
    ], $departamentos), JSON_UNESCAPED_UNICODE) ?>
};
</script>

<div class="content">

  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="page-title">
      <i class="fas fa-gears me-2" style="color:var(--primario);"></i>Mantenimiento y Configuración
      <small>Catálogos, usuarios y parámetros del sistema</small>
    </div>
  </div>

  <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:12px 16px;margin-bottom:18px;display:flex;align-items:flex-start;gap:10px;">
    <i class="fas fa-shield-halved" style="color:#1d4ed8;margin-top:2px;flex-shrink:0;"></i>
    <div style="font-size:.82rem;color:#1e3a5f;">
      <strong>Área de administración:</strong> Los cambios realizados aquí afectan el funcionamiento general del sistema.
      Solo personal autorizado puede modificar catálogos y parámetros.
    </div>
  </div>

  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-database"></i> Catálogos del sistema</h6>
      <div class="mode-tabs" style="flex-wrap:wrap;">
        <button class="mode-tab active" data-tab="Tecnicos"><i class="fas fa-user-tie me-1"></i>Técnicos</button>
        <button class="mode-tab" data-tab="Temas"><i class="fas fa-tags me-1"></i>Temas</button>
        <button class="mode-tab" data-tab="Cultivos"><i class="fas fa-seedling me-1"></i>Cultivos</button>
        <button class="mode-tab" data-tab="TiposAt"><i class="fas fa-list-check me-1"></i>Tipos AT</button>
        <button class="mode-tab" data-tab="Usuarios"><i class="fas fa-users-gear me-1"></i>Usuarios</button>
        <?php if (!empty($esAdminRoles)): ?>
        <button class="mode-tab" data-tab="Roles"><i class="fas fa-user-shield me-1"></i>Roles y privilegios</button>
        <?php endif; ?>
        <button class="mode-tab" data-tab="Parametros"><i class="fas fa-sliders me-1"></i>Parámetros</button>
      </div>
    </div>

    <div class="card-box-body">

      <!-- TÉCNICOS -->
      <div class="section-panel active" id="tabTecnicos">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <div style="font-size:.84rem;color:#555;">Gestión de técnicos de campo del sistema</div>
          <div class="d-flex gap-2">
            <input type="text" class="form-control form-control-sm" id="searchTec" placeholder="Buscar..." style="width:180px;"/>
            <button class="btn-prim" id="btnNuevoTecnico"><i class="fas fa-plus"></i> Nuevo</button>
          </div>
        </div>
        <table class="data-table">
          <thead><tr><th>#</th><th>Nombre</th><th>Email</th><th>Especialidad</th><th>Teléfono</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody id="bodyTecnicos"></tbody>
        </table>
      </div>

      <!-- TEMAS -->
      <div class="section-panel" id="tabTemas">
        <div class="row g-3">
          <div class="col-md-5">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div style="font-size:.84rem;font-weight:700;color:#333;"><i class="fas fa-tags me-1" style="color:var(--primario);"></i>Temas</div>
              <button class="btn-prim btn-sm" id="btnNuevoTema"><i class="fas fa-plus"></i> Nuevo</button>
            </div>
            <table class="data-table">
              <thead><tr><th>#</th><th>Nombre</th><th>Estado</th><th></th></tr></thead>
              <tbody id="bodyTemas"></tbody>
            </table>
          </div>
          <div class="col-md-7">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div style="font-size:.84rem;font-weight:700;color:#333;"><i class="fas fa-tag me-1" style="color:var(--primario);"></i>Subtemas</div>
              <button class="btn-prim btn-sm" id="btnNuevoSubtema"><i class="fas fa-plus"></i> Nuevo</button>
            </div>
            <table class="data-table">
              <thead><tr><th>#</th><th>Nombre</th><th>Tema</th><th>Estado</th><th></th></tr></thead>
              <tbody id="bodySubtemas"></tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- CULTIVOS -->
      <div class="section-panel" id="tabCultivos">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <div style="font-size:.84rem;color:#555;">Cultivos disponibles en el sistema</div>
          <button class="btn-prim" id="btnNuevoCultivo"><i class="fas fa-plus"></i> Nuevo cultivo</button>
        </div>
        <table class="data-table">
          <thead><tr><th>#</th><th>Nombre del cultivo</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody id="bodyCultivos"></tbody>
        </table>
      </div>

      <!-- TIPOS AT -->
      <div class="section-panel" id="tabTiposAt">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <div style="font-size:.84rem;color:#555;">Tipos de visita disponibles en Asistencia Técnica</div>
          <button class="btn-prim" id="btnNuevoTipoAt"><i class="fas fa-plus"></i> Nuevo tipo</button>
        </div>
        <table class="data-table">
          <thead><tr><th>#</th><th>Nombre</th><th>Descripción</th><th>Ícono</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody id="bodyTiposAt"></tbody>
        </table>
      </div>

      <!-- USUARIOS -->
      <div class="section-panel" id="tabUsuarios">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <div style="font-size:.84rem;color:#555;">Gestión de acceso al sistema por rol</div>
          <button class="btn-prim" id="btnNuevoUsuario"><i class="fas fa-user-plus"></i> Nuevo usuario</button>
        </div>
        <div class="row g-3" id="gridUsuarios"></div>
      </div>

      <?php if (!empty($esAdminRoles)): ?>
      <!-- ROLES Y PRIVILEGIOS -->
      <div class="section-panel" id="tabRoles">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <div style="font-size:.84rem;color:#555;">Define roles y controla qué módulos y acciones puede realizar cada uno</div>
          <button class="btn-prim" id="btnNuevoRol"><i class="fas fa-plus"></i> Nuevo rol</button>
        </div>
        <table class="data-table">
          <thead><tr><th>#</th><th>Rol</th><th>Identificador</th><th>Descripción</th><th>Tipo</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody id="bodyRoles"></tbody>
        </table>
      </div>
      <?php endif; ?>

      <!-- PARÁMETROS -->
      <div class="section-panel" id="tabParametros">
        <div class="row g-3">
          <div class="col-md-6">
            <div style="font-size:.85rem;font-weight:700;color:#333;margin-bottom:12px;">
              <i class="fas fa-info-circle me-1" style="color:var(--primario);"></i> Información del sistema
            </div>
            <div style="background:#f8f9fb;border-radius:10px;padding:16px;">
              <div class="param-row">
                <div class="param-icon"><i class="fas fa-seedling"></i></div>
                <div class="param-label">
                  <strong>Programa activo</strong>
                  <span><?= htmlspecialchars($_SESSION['programa']['nombre'] ?? '—') ?> (<?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?>)</span>
                </div>
              </div>
              <div class="param-row">
                <div class="param-icon"><i class="fas fa-database"></i></div>
                <div class="param-label">
                  <strong>Base de datos</strong>
                  <span><?= htmlspecialchars($_SESSION['programa']['db_name'] ?? '—') ?></span>
                </div>
              </div>
              <div class="param-row">
                <div class="param-icon"><i class="fas fa-user-tie"></i></div>
                <div class="param-label">
                  <strong>Técnicos registrados</strong>
                  <span><?= count($tecnicos) ?> técnicos activos</span>
                </div>
              </div>
              <div class="param-row">
                <div class="param-icon"><i class="fas fa-tags"></i></div>
                <div class="param-label">
                  <strong>Temas / Subtemas</strong>
                  <span><?= count($temas) ?> temas · <?= count($subtemas) ?> subtemas</span>
                </div>
              </div>
              <div class="param-row">
                <div class="param-icon"><i class="fas fa-seedling"></i></div>
                <div class="param-label">
                  <strong>Cultivos</strong>
                  <span><?= count($cultivos) ?> cultivos</span>
                </div>
              </div>
              <div class="param-row">
                <div class="param-icon"><i class="fas fa-users-gear"></i></div>
                <div class="param-label">
                  <strong>Usuarios del sistema</strong>
                  <span><?= count($usuarios) ?> usuarios registrados</span>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div style="font-size:.85rem;font-weight:700;color:#333;margin-bottom:12px;">
              <i class="fas fa-key me-1" style="color:var(--primario);"></i> Accesos rápidos
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;">
              <a href="<?= BASE_URL ?>/exportar" class="btn-sec">
                <i class="fas fa-file-export"></i> Ir a Exportar Datos
              </a>
              <a href="<?= BASE_URL ?>/estadisticas" class="btn-sec">
                <i class="fas fa-chart-bar"></i> Ver Estadísticas
              </a>
              <a href="<?= BASE_URL ?>/dashboard" class="btn-sec">
                <i class="fas fa-house"></i> Volver al Dashboard
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

</div><!-- /content -->

<!-- MODAL TÉCNICO -->
<div class="modal-overlay" id="modalTecnico">
  <div class="modal-box">
    <div class="modal-head">
      <h6 id="tituloModalTec"><i class="fas fa-user-tie me-2" style="color:var(--primario);"></i>Nuevo técnico</h6>
      <button class="btn-close-x" onclick="cerrarModal('modalTecnico')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body-inner">
      <div class="row g-3">
        <div class="col-12">
          <label class="fl">Nombre completo <span class="req">*</span></label>
          <input type="text" class="fc" id="tecNombre" placeholder="Nombre completo"/>
        </div>
        <div class="col-md-6">
          <label class="fl">Especialidad</label>
          <input type="text" class="fc" id="tecEspec" placeholder="Ej. Agrónomo"/>
        </div>
        <div class="col-md-6">
          <label class="fl">Departamento asignado</label>
          <select class="fc" id="tecDpto">
            <option value="">— Nacional (todos) —</option>
            <?php foreach ($departamentos as $d): ?>
            <option value="<?= $d['id_departamento'] ?>"><?= htmlspecialchars($d['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="fl">Teléfono</label>
          <input type="text" class="fc" id="tecTel" placeholder="9999-9999"/>
        </div>
        <div class="col-md-6">
          <label class="fl">Email</label>
          <input type="email" class="fc" id="tecEmail" placeholder="tecnico@sag.hn"/>
        </div>
        <div class="col-md-6">
          <label class="fl">Estado</label>
          <select class="fc" id="tecActivo">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
        <input type="hidden" id="tecId"/>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-sec" onclick="cerrarModal('modalTecnico')">Cancelar</button>
      <button class="btn-prim" id="btnGuardarTecnico"><i class="fas fa-save"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL TEMA -->
<div class="modal-overlay" id="modalTema">
  <div class="modal-box">
    <div class="modal-head">
      <h6 id="tituloModalTema"><i class="fas fa-tag me-2" style="color:var(--primario);"></i>Nuevo tema</h6>
      <button class="btn-close-x" onclick="cerrarModal('modalTema')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body-inner">
      <div class="row g-3">
        <div class="col-12">
          <label class="fl">Nombre del tema <span class="req">*</span></label>
          <input type="text" class="fc" id="temaNombre"/>
        </div>
        <div class="col-md-6">
          <label class="fl">Estado</label>
          <select class="fc" id="temaActivo">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
        <input type="hidden" id="temaId"/>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-sec" onclick="cerrarModal('modalTema')">Cancelar</button>
      <button class="btn-prim" id="btnGuardarTema"><i class="fas fa-save"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL SUBTEMA -->
<div class="modal-overlay" id="modalSubtema">
  <div class="modal-box">
    <div class="modal-head">
      <h6 id="tituloModalSub"><i class="fas fa-tag me-2" style="color:var(--primario);"></i>Nuevo subtema</h6>
      <button class="btn-close-x" onclick="cerrarModal('modalSubtema')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body-inner">
      <div class="row g-3">
        <div class="col-12">
          <label class="fl">Nombre del subtema <span class="req">*</span></label>
          <input type="text" class="fc" id="subNombre"/>
        </div>
        <div class="col-12">
          <label class="fl">Tema padre <span class="req">*</span></label>
          <select class="fc" id="subTema">
            <option value="">— Seleccionar tema —</option>
            <?php foreach ($temas as $t): ?>
            <option value="<?= $t['id_tema'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="fl">Estado</label>
          <select class="fc" id="subActivo">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
        <input type="hidden" id="subId"/>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-sec" onclick="cerrarModal('modalSubtema')">Cancelar</button>
      <button class="btn-prim" id="btnGuardarSubtema"><i class="fas fa-save"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL CULTIVO -->
<div class="modal-overlay" id="modalCultivo">
  <div class="modal-box">
    <div class="modal-head">
      <h6 id="tituloModalCul"><i class="fas fa-seedling me-2" style="color:var(--primario);"></i>Nuevo cultivo</h6>
      <button class="btn-close-x" onclick="cerrarModal('modalCultivo')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body-inner">
      <div class="row g-3">
        <div class="col-12">
          <label class="fl">Nombre del cultivo <span class="req">*</span></label>
          <input type="text" class="fc" id="culNombre"/>
        </div>
        <div class="col-md-6">
          <label class="fl">Estado</label>
          <select class="fc" id="culActivo">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
        <input type="hidden" id="culId"/>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-sec" onclick="cerrarModal('modalCultivo')">Cancelar</button>
      <button class="btn-prim" id="btnGuardarCultivo"><i class="fas fa-save"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL TIPO AT -->
<div class="modal-overlay" id="modalTipoAt">
  <div class="modal-box">
    <div class="modal-head">
      <h6 id="tituloModalTipoAt"><i class="fas fa-list-check me-2" style="color:var(--primario);"></i>Nuevo tipo de AT</h6>
      <button class="btn-close-x" onclick="cerrarModal('modalTipoAt')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body-inner">
      <div class="row g-3">
        <div class="col-12">
          <label class="fl">Nombre del tipo <span class="req">*</span></label>
          <input type="text" class="fc" id="atNombre"/>
        </div>
        <div class="col-12">
          <label class="fl">Descripción</label>
          <textarea class="fc" id="atDesc" rows="2" placeholder="Descripción breve..."></textarea>
        </div>
        <div class="col-md-6">
          <label class="fl">Ícono (Font Awesome)</label>
          <input type="text" class="fc" id="atIcono" placeholder="fa-calendar-check"/>
        </div>
        <div class="col-md-6">
          <label class="fl">Estado</label>
          <select class="fc" id="atActivo">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
        <input type="hidden" id="atId"/>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-sec" onclick="cerrarModal('modalTipoAt')">Cancelar</button>
      <button class="btn-prim" id="btnGuardarTipoAt"><i class="fas fa-save"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL USUARIO -->
<div class="modal-overlay" id="modalUsuario">
  <div class="modal-box">
    <div class="modal-head">
      <h6 id="tituloModalUsr"><i class="fas fa-user-plus me-2" style="color:var(--primario);"></i>Nuevo usuario</h6>
      <button class="btn-close-x" onclick="cerrarModal('modalUsuario')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body-inner">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="fl">Nombre <span class="req">*</span></label>
          <input type="text" class="fc" id="usrNombre"/>
        </div>
        <div class="col-md-6">
          <label class="fl">Apellido <span class="req">*</span></label>
          <input type="text" class="fc" id="usrApellido"/>
        </div>
        <div class="col-md-6">
          <label class="fl">Email <span class="req">*</span></label>
          <input type="email" class="fc" id="usrEmail" placeholder="usuario@sag.hn"/>
        </div>
        <div class="col-md-6">
          <label class="fl">Usuario (login) <span class="req">*</span></label>
          <input type="text" class="fc" id="usrUsername" placeholder="ej. jperez" autocomplete="off"/>
        </div>
        <div class="col-md-6">
          <label class="fl">Rol <span class="req">*</span></label>
          <select class="fc" id="usrRol">
            <option value="">— Seleccionar —</option>
            <?php foreach ($roles as $r): ?>
            <option value="<?= $r['id_rol'] ?>"><?= htmlspecialchars($r['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6" id="campoPassword">
          <label class="fl">Contraseña <span class="req" id="pwReq">*</span></label>
          <input type="password" class="fc" id="usrPassword" placeholder="Mínimo 8 caracteres"/>
        </div>
        <div class="col-md-6">
          <label class="fl">Estado</label>
          <select class="fc" id="usrActivo">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
        <div class="col-12">
          <label class="fl">Acceso a proyectos <span class="req">*</span></label>
          <div style="border:1.5px solid var(--borde);border-radius:8px;padding:10px 12px;">
            <label style="display:flex;align-items:center;gap:8px;font-size:.82rem;font-weight:600;cursor:pointer;margin-bottom:6px;">
              <input type="checkbox" id="usrTodosProy"/> Acceso a todos los proyectos
            </label>
            <div id="usrProyectosBox" style="display:flex;flex-wrap:wrap;gap:12px;padding-top:8px;border-top:1px solid #f0f0f0;">
              <?php foreach (($proyectos ?? []) as $p): ?>
              <label style="display:flex;align-items:center;gap:6px;font-size:.8rem;cursor:pointer;">
                <input type="checkbox" class="usrProyChk" value="<?= (int)$p['id_proyecto'] ?>"/> <?= htmlspecialchars($p['sigla']) ?>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <input type="hidden" id="usrId"/>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-sec" onclick="cerrarModal('modalUsuario')">Cancelar</button>
      <button class="btn-prim" id="btnGuardarUsuario"><i class="fas fa-save"></i> Guardar</button>
    </div>
  </div>
</div>

<?php if (!empty($esAdminRoles)): ?>
<!-- MODAL ROL -->
<div class="modal-overlay" id="modalRol">
  <div class="modal-box">
    <div class="modal-head">
      <h6 id="tituloModalRol"><i class="fas fa-user-shield me-2" style="color:var(--primario);"></i>Nuevo rol</h6>
      <button class="btn-close-x" onclick="cerrarModal('modalRol')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body-inner">
      <div class="row g-3">
        <div class="col-12">
          <label class="fl">Nombre del rol <span class="req">*</span></label>
          <input type="text" class="fc" id="rolNombre" placeholder="Ej. Coordinador Regional"/>
        </div>
        <div class="col-12" id="rolSlugCampo">
          <label class="fl">Identificador (slug) <span class="req">*</span></label>
          <input type="text" class="fc" id="rolSlug" placeholder="ej. coord_regional"/>
          <small style="color:#888;font-size:.72rem;">Solo minúsculas, números y guion bajo. No se podrá cambiar después.</small>
        </div>
        <div class="col-12">
          <label class="fl">Descripción</label>
          <textarea class="fc" id="rolDesc" rows="2" placeholder="Breve descripción del rol..."></textarea>
        </div>
        <div class="col-md-6">
          <label class="fl">Estado</label>
          <select class="fc" id="rolActivo">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
        <input type="hidden" id="rolId"/>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-sec" onclick="cerrarModal('modalRol')">Cancelar</button>
      <button class="btn-prim" id="btnGuardarRol"><i class="fas fa-save"></i> Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL PRIVILEGIOS -->
<div class="modal-overlay" id="modalPrivilegios">
  <div class="modal-box" style="max-width:780px;">
    <div class="modal-head">
      <h6 id="tituloModalPriv"><i class="fas fa-shield-halved me-2" style="color:var(--primario);"></i>Privilegios</h6>
      <button class="btn-close-x" onclick="cerrarModal('modalPrivilegios')"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body-inner">
      <div id="privAdminAviso" style="display:none;background:#fef9c3;border:1px solid #fde68a;border-radius:8px;padding:10px 12px;font-size:.8rem;color:#854d0e;margin-bottom:10px;">
        <i class="fas fa-crown me-1"></i> Rol administrador: tiene acceso total al sistema y no se edita.
      </div>
      <div style="font-size:.78rem;color:#666;margin-bottom:10px;">Marca las acciones permitidas por módulo. La columna <strong>Ver</strong> habilita el acceso al módulo.</div>
      <div style="overflow-x:auto;">
        <table class="data-table" id="tablaPrivilegios" style="font-size:.78rem;">
          <thead><tr id="privHead"></tr></thead>
          <tbody id="privBody"></tbody>
        </table>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-sec" onclick="cerrarModal('modalPrivilegios')">Cancelar</button>
      <button class="btn-prim" id="btnGuardarPriv"><i class="fas fa-save"></i> Guardar privilegios</button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- MODAL CONFIRMACIÓN -->
<div class="confirm-overlay" id="confirmOverlay">
  <div class="confirm-box">
    <div style="font-size:2rem;margin-bottom:12px;">⚠️</div>
    <div style="font-size:.9rem;font-weight:700;color:#1a1a1a;margin-bottom:8px;" id="confirmTitulo">¿Confirmar acción?</div>
    <div style="font-size:.82rem;color:#666;margin-bottom:20px;" id="confirmMsg"></div>
    <div class="d-flex gap-2 justify-content-center">
      <button class="btn-sec" onclick="cerrarConfirm()">Cancelar</button>
      <button class="btn-danger" style="padding:8px 18px;font-size:.84rem;" id="btnConfirmOk">Confirmar</button>
    </div>
  </div>
</div>

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>
