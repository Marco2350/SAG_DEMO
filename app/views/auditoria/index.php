<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/auditoria.js"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">

  <!-- Breadcrumb -->
  <div class="crumb-bar">
    <i class="fas fa-gears"></i>
    <span>Sistema</span>
    <i class="fas fa-chevron-right crumb-sep"></i>
    <span class="crumb-pill">
      <i class="fas fa-clipboard-list"></i> Auditoría
    </span>
  </div>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-clipboard-list" style="color:var(--primario);margin-right:8px;"></i>Auditoría del Sistema
      <small>Bitácora de accesos y acciones de todos los programas</small>
    </div>
  </div>

  <!-- Mini stats -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="ms-val"><?= number_format((int) ($resumen['total'] ?? 0)) ?></div>
        <div class="ms-lbl"><i class="fas fa-clipboard-list" style="color:var(--primario);margin-right:4px;"></i>Registros totales</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat blue">
        <div class="ms-val"><?= (int) ($resumen['hoy'] ?? 0) ?></div>
        <div class="ms-lbl"><i class="fas fa-calendar-day" style="color:#3b82f6;margin-right:4px;"></i>Acciones hoy</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#16a34a;">
        <div class="ms-val" style="color:#16a34a;"><?= (int) ($resumen['logins_hoy'] ?? 0) ?></div>
        <div class="ms-lbl"><i class="fas fa-right-to-bracket" style="color:#16a34a;margin-right:4px;"></i>Ingresos hoy</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#dc2626;">
        <div class="ms-val" style="color:#dc2626;"><?= (int) ($resumen['fallidos_hoy'] ?? 0) ?></div>
        <div class="ms-lbl"><i class="fas fa-user-slash" style="color:#dc2626;margin-right:4px;"></i>Intentos fallidos hoy</div>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-list-ul"></i> Bitácora</h6>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <select class="fs" id="filtroUsuario" style="width:180px;padding:6px 10px;">
          <option value="">Todos los usuarios</option>
          <?php foreach ($usuarios as $u): ?>
          <option value="<?= (int) $u['id_usuario'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <select class="fs" id="filtroModulo" style="width:160px;padding:6px 10px;">
          <option value="">Todos los módulos</option>
          <?php foreach ($modulos as $m): ?>
          <option value="<?= htmlspecialchars($m['modulo']) ?>"><?= htmlspecialchars(ucfirst($m['modulo'])) ?></option>
          <?php endforeach; ?>
        </select>
        <select class="fs" id="filtroAccion" style="width:150px;padding:6px 10px;">
          <option value="">Todas las acciones</option>
          <?php foreach ($acciones as $a): ?>
          <option value="<?= htmlspecialchars($a['accion']) ?>"><?= htmlspecialchars($a['accion']) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="date" class="fc" id="filtroDesde" style="width:150px;padding:6px 10px;" title="Desde"/>
        <input type="date" class="fc" id="filtroHasta" style="width:150px;padding:6px 10px;" title="Hasta"/>
        <button class="btn-outline" id="btnLimpiarFiltros" style="padding:6px 14px;" title="Quitar filtros">
          <i class="fas fa-rotate-left"></i>
        </button>
      </div>
    </div>
    <div style="padding:16px;overflow-x:auto;">
      <table id="tablaAuditoria" class="sag-table" style="width:100%;">
        <thead>
          <tr>
            <th style="width:140px;">Fecha</th><th>Usuario</th><th>Programa</th>
            <th>Acción</th><th>Módulo</th><th>Detalle</th><th style="width:110px;">IP</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

</div><!-- /content -->

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>
