<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/catalogos.js"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">

  <!-- Breadcrumb -->
  <div class="crumb-bar">
    <i class="fas fa-sliders"></i>
    <span>Parametrización</span>
    <i class="fas fa-chevron-right crumb-sep"></i>
    <span class="crumb-pill">
      <i class="fas fa-list-check"></i> Tipos de Asistencia
    </span>
  </div>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-list-check" style="color:var(--primario);margin-right:8px;"></i>Tipos de Asistencia Técnica
      <small>Tipos de visita disponibles en el módulo de Asistencia Técnica — <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?></small>
    </div>
    <button class="btn-primario" id="btnNuevoTipoAt">
      <i class="fas fa-plus"></i> Nuevo Tipo
    </button>
  </div>

  <!-- Tabla -->
  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-list-ul"></i> Listado de Tipos <span class="badge-count ms-1"><?= count($tiposAt) ?></span></h6>
    </div>
    <div style="padding:16px;overflow-x:auto;">
      <table id="tablaTiposAt" class="sag-table" style="width:100%;">
        <thead>
          <tr><th>#</th><th>Ícono</th><th>Nombre</th><th>Estado</th><th style="width:100px;">Acciones</th></tr>
        </thead>
        <tbody>
          <?php foreach ($tiposAt as $t): ?>
          <tr>
            <td><?= (int) $t['id_tipo_at'] ?></td>
            <td class="text-center">
              <i class="fas <?= htmlspecialchars($t['icono'] ?: 'fa-circle-check') ?>" style="color:var(--primario);font-size:1rem;"></i>
              <code style="font-size:.7rem;margin-left:6px;"><?= htmlspecialchars($t['icono'] ?: 'fa-circle-check') ?></code>
            </td>
            <td><strong><?= htmlspecialchars($t['nombre']) ?></strong></td>
            <td><?= $t['activo'] ? '<span class="badge-activo">Activo</span>' : '<span class="badge-inactivo">Inactivo</span>' ?></td>
            <td class="text-center">
              <button class="btn-outline btn-sm-icon btn-editar-cat" title="Editar"
                      data-row="<?= htmlspecialchars(json_encode([
                        'id'     => (int) $t['id_tipo_at'],
                        'nombre' => $t['nombre'],
                        'icono'  => $t['icono'] ?? '',
                        'activo' => (int) $t['activo'],
                      ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>">
                <i class="fas fa-pen"></i>
              </button>
              <?php if ($t['activo']): ?>
              <button class="btn-danger-sm ms-1 btn-desactivar-cat" title="Desactivar"
                      data-id="<?= (int) $t['id_tipo_at'] ?>"
                      data-nombre="<?= htmlspecialchars($t['nombre'], ENT_QUOTES) ?>">
                <i class="fas fa-ban"></i>
              </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /content -->

<!-- MODAL Crear / Editar Tipo AT -->
<div class="modal fade" id="modalCatalogo" tabindex="-1" aria-hidden="true" data-catalogo="tiposat">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title" id="modalCatTitulo">
          <i class="fas fa-list-check me-2"></i>Nuevo Tipo de Asistencia
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formCatalogo" novalidate>
          <input type="hidden" id="catId" name="id_tipo_at" value="0"/>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label-b">Nombre del tipo <span class="req">*</span></label>
              <input type="text" class="fc" id="catNombre" name="nombre" placeholder="Ej. Visita de seguimiento" maxlength="100"/>
            </div>
            <div class="col-md-7">
              <label class="form-label-b">Ícono (Font Awesome)</label>
              <input type="text" class="fc" id="catIcono" name="icono" placeholder="fa-calendar-check" maxlength="50"/>
              <small style="color:#888;font-size:.7rem;">Ej: fa-handshake, fa-calendar-check, fa-stethoscope</small>
            </div>
            <div class="col-md-5">
              <label class="form-label-b">Estado</label>
              <select class="fs" id="catActivo" name="activo">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-gris" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn-primario" id="btnGuardarCat">
          <i class="fas fa-floppy-disk me-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>
