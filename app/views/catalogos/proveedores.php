<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php
$_jsFile = ROOT_PATH . '/public/assets/js/modules/catalogos.js';
$_jsVer  = file_exists($_jsFile) ? filemtime($_jsFile) : time();
$jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/catalogos.js?v=' . $_jsVer . '"></script>';
?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">

  <!-- Breadcrumb -->
  <div class="crumb-bar">
    <i class="fas fa-sliders"></i>
    <span>Parametrización</span>
    <i class="fas fa-chevron-right crumb-sep"></i>
    <span class="crumb-pill">
      <i class="fas fa-truck"></i> Proveedores
    </span>
  </div>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-truck" style="color:var(--primario);margin-right:8px;"></i>Proveedores
      <small>Catálogo de proveedores del programa <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?></small>
    </div>
    <button class="btn-primario" id="btnNuevoProveedor">
      <i class="fas fa-plus"></i> Nuevo Proveedor
    </button>
  </div>

  <!-- Tabla -->
  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-list-ul"></i> Listado de Proveedores</h6>
    </div>
    <div style="padding:16px;overflow-x:auto;">
      <table id="tablaProveedores" class="sag-table" style="width:100%;">
        <thead>
          <tr>
            <th>#</th><th>Nombre / Razón Social</th><th>RTN</th>
            <th>Contacto</th><th>Teléfono</th><th>Email</th>
            <th>Estado</th><th style="width:100px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($proveedores as $p): ?>
          <tr>
            <td><?= (int) $p['id_proveedor'] ?></td>
            <td>
              <strong><?= htmlspecialchars($p['nombre']) ?></strong>
              <?php if (!empty($p['direccion'])): ?>
                <br><small class="muted"><?= htmlspecialchars($p['direccion']) ?></small>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($p['rtn'] ?: '—') ?></td>
            <td><?= htmlspecialchars($p['contacto'] ?: '—') ?></td>
            <td><?= htmlspecialchars($p['telefono'] ?: '—') ?></td>
            <td><?= htmlspecialchars($p['email'] ?: '—') ?></td>
            <td><?= $p['activo'] ? '<span class="badge-activo">Activo</span>' : '<span class="badge-inactivo">Inactivo</span>' ?></td>
            <td class="text-center">
              <button class="btn-outline btn-sm-icon btn-editar-cat" title="Editar"
                      data-row="<?= htmlspecialchars(json_encode([
                        'id'        => (int) $p['id_proveedor'],
                        'nombre'    => $p['nombre'],
                        'rtn'       => $p['rtn'] ?? '',
                        'contacto'  => $p['contacto'] ?? '',
                        'telefono'  => $p['telefono'] ?? '',
                        'email'     => $p['email'] ?? '',
                        'direccion' => $p['direccion'] ?? '',
                        'activo'    => (int) $p['activo'],
                      ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>">
                <i class="fas fa-pen"></i>
              </button>
              <?php if ($p['activo']): ?>
              <button class="btn-danger-sm ms-1 btn-desactivar-cat" title="Desactivar"
                      data-id="<?= (int) $p['id_proveedor'] ?>"
                      data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>">
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

<!-- MODAL Crear / Editar Proveedor -->
<div class="modal fade" id="modalCatalogo" tabindex="-1" aria-hidden="true" data-catalogo="proveedores">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title" id="modalCatTitulo">
          <i class="fas fa-truck me-2"></i>Nuevo Proveedor
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formCatalogo" novalidate>
          <input type="hidden" id="catId" name="id_proveedor" value="0"/>
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label-b">Nombre / Razón social <span class="req">*</span></label>
              <input type="text" class="fc" id="catNombre" name="nombre" placeholder="Ej. Insumos Agrícolas S. de R.L." maxlength="200"/>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">RTN</label>
              <input type="text" class="fc" id="catRtn" name="rtn" placeholder="14 dígitos" maxlength="14" inputmode="numeric"/>
            </div>
            <div class="col-md-6">
              <label class="form-label-b">Persona de contacto</label>
              <input type="text" class="fc" id="catContacto" name="contacto" placeholder="Ej. Juan Pérez" maxlength="150"/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Teléfono</label>
              <input type="text" class="fc input-tel" id="catTelefono" name="telefono" placeholder="9999-9999" maxlength="9" inputmode="numeric"/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Estado</label>
              <select class="fs" id="catActivo" name="activo">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label-b">Email</label>
              <input type="email" class="fc" id="catEmail" name="email" placeholder="contacto@proveedor.hn" maxlength="150"/>
            </div>
            <div class="col-md-6">
              <label class="form-label-b">Dirección</label>
              <input type="text" class="fc" id="catDireccion" name="direccion" placeholder="Ej. Bo. La Granja, Tegucigalpa" maxlength="300"/>
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
