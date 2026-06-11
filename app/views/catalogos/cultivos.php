<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/catalogos.js"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<?php
$tipoLabels = ['cultivo' => 'Cultivo', 'ganaderia' => 'Ganadería', 'otro' => 'Otro'];
?>
<div class="content">

  <!-- Breadcrumb -->
  <div style="display:flex;align-items:center;gap:8px;font-size:.78rem;color:#888;margin-bottom:8px;">
    <i class="fas fa-sliders"></i>
    <span>Parametrización</span>
    <i class="fas fa-chevron-right" style="font-size:.65rem;color:#bbb;"></i>
    <span style="background:var(--primario);color:#fff;padding:3px 12px;border-radius:14px;font-weight:700;font-size:.74rem;letter-spacing:.3px;">
      <i class="fas fa-seedling"></i> Cultivos y Rubros
    </span>
  </div>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-seedling" style="color:var(--primario);margin-right:8px;"></i>Cultivos y Rubros
      <small>Catálogo de cultivos del programa <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?></small>
    </div>
    <button class="btn-primario" id="btnNuevoCultivo">
      <i class="fas fa-plus"></i> Nuevo Cultivo
    </button>
  </div>

  <!-- Tabla -->
  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-list-ul"></i> Listado de Cultivos <span class="badge-count ms-1"><?= count($cultivos) ?></span></h6>
    </div>
    <div style="padding:16px;overflow-x:auto;">
      <table id="tablaCultivos" class="sag-table" style="width:100%;">
        <thead>
          <tr><th>#</th><th>Nombre</th><th>Tipo</th><th>Estado</th><th style="width:100px;">Acciones</th></tr>
        </thead>
        <tbody>
          <?php foreach ($cultivos as $c): ?>
          <tr>
            <td><?= (int) $c['id_cultivo'] ?></td>
            <td><strong><?= htmlspecialchars($c['nombre']) ?></strong></td>
            <td><?= htmlspecialchars($tipoLabels[$c['tipo']] ?? ucfirst($c['tipo'])) ?></td>
            <td><?= $c['activo'] ? '<span class="badge-activo">Activo</span>' : '<span class="badge-inactivo">Inactivo</span>' ?></td>
            <td class="text-center">
              <button class="btn-outline btn-sm-icon btn-editar-cat" title="Editar"
                      data-row="<?= htmlspecialchars(json_encode([
                        'id'     => (int) $c['id_cultivo'],
                        'nombre' => $c['nombre'],
                        'tipo'   => $c['tipo'],
                        'activo' => (int) $c['activo'],
                      ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>">
                <i class="fas fa-pen"></i>
              </button>
              <?php if ($c['activo']): ?>
              <button class="btn-danger-sm ms-1 btn-desactivar-cat" title="Desactivar"
                      data-id="<?= (int) $c['id_cultivo'] ?>"
                      data-nombre="<?= htmlspecialchars($c['nombre'], ENT_QUOTES) ?>">
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

<!-- MODAL Crear / Editar Cultivo -->
<div class="modal fade" id="modalCatalogo" tabindex="-1" aria-hidden="true" data-catalogo="cultivos">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title" id="modalCatTitulo">
          <i class="fas fa-seedling me-2"></i>Nuevo Cultivo
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formCatalogo" novalidate>
          <input type="hidden" id="catId" name="id_cultivo" value="0"/>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label-b">Nombre del cultivo <span class="req">*</span></label>
              <input type="text" class="fc" id="catNombre" name="nombre" placeholder="Ej. Café" maxlength="100"/>
            </div>
            <div class="col-md-7">
              <label class="form-label-b">Tipo</label>
              <select class="fs" id="catTipo" name="tipo">
                <option value="cultivo">Cultivo</option>
                <option value="ganaderia">Ganadería</option>
                <option value="otro">Otro</option>
              </select>
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
