<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/catalogos.js"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<?php
$tipoLabels = ['capacitacion' => 'Capacitación', 'at' => 'Asistencia Técnica', 'ambos' => 'Ambos'];
?>
<div class="content">

  <!-- Breadcrumb -->
  <div class="crumb-bar">
    <i class="fas fa-sliders"></i>
    <span>Parametrización</span>
    <i class="fas fa-chevron-right crumb-sep"></i>
    <span class="crumb-pill">
      <i class="fas fa-tags"></i> Temas y Subtemas
    </span>
  </div>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-tags" style="color:var(--primario);margin-right:8px;"></i>Temas y Subtemas
      <small>Contenido técnico para capacitaciones y asistencias — <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?></small>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <button class="btn-outline" id="btnNuevoSubtema">
        <i class="fas fa-plus"></i> Nuevo Subtema
      </button>
      <button class="btn-primario" id="btnNuevoTema">
        <i class="fas fa-plus"></i> Nuevo Tema
      </button>
    </div>
  </div>

  <div class="row g-3">
    <!-- TEMAS -->
    <div class="col-lg-5">
      <div class="card-box">
        <div class="card-box-header">
          <h6><i class="fas fa-tags"></i> Temas <span class="badge-count ms-1"><?= count($temas) ?></span></h6>
        </div>
        <div style="padding:16px;overflow-x:auto;">
          <table id="tablaTemas" class="sag-table" style="width:100%;">
            <thead>
              <tr><th>#</th><th>Nombre</th><th>Aplica a</th><th>Subtemas</th><th>Estado</th><th style="width:100px;">Acciones</th></tr>
            </thead>
            <tbody>
              <?php foreach ($temas as $t): ?>
              <tr>
                <td><?= (int) $t['id_tema'] ?></td>
                <td><strong><?= htmlspecialchars($t['nombre']) ?></strong></td>
                <td><?= htmlspecialchars($tipoLabels[$t['tipo']] ?? ucfirst($t['tipo'])) ?></td>
                <td class="text-center"><span class="badge-count"><?= (int) $t['num_subtemas'] ?></span></td>
                <td><?= $t['activo'] ? '<span class="badge-activo">Activo</span>' : '<span class="badge-inactivo">Inactivo</span>' ?></td>
                <td class="text-center">
                  <button class="btn-outline btn-sm-icon btn-editar-tema" title="Editar"
                          data-row="<?= htmlspecialchars(json_encode([
                            'id'     => (int) $t['id_tema'],
                            'nombre' => $t['nombre'],
                            'tipo'   => $t['tipo'],
                            'activo' => (int) $t['activo'],
                          ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>">
                    <i class="fas fa-pen"></i>
                  </button>
                  <?php if ($t['activo']): ?>
                  <button class="btn-danger-sm ms-1 btn-desactivar-tema" title="Desactivar"
                          data-id="<?= (int) $t['id_tema'] ?>"
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
    </div>

    <!-- SUBTEMAS -->
    <div class="col-lg-7">
      <div class="card-box">
        <div class="card-box-header">
          <h6><i class="fas fa-tag"></i> Subtemas <span class="badge-count ms-1"><?= count($subtemas) ?></span></h6>
        </div>
        <div style="padding:16px;overflow-x:auto;">
          <table id="tablaSubtemas" class="sag-table" style="width:100%;">
            <thead>
              <tr><th>#</th><th>Nombre</th><th>Tema padre</th><th>Estado</th><th style="width:100px;">Acciones</th></tr>
            </thead>
            <tbody>
              <?php foreach ($subtemas as $s): ?>
              <tr>
                <td><?= (int) $s['id_subtema'] ?></td>
                <td><strong><?= htmlspecialchars($s['nombre']) ?></strong></td>
                <td><?= htmlspecialchars($s['tema']) ?></td>
                <td><?= $s['activo'] ? '<span class="badge-activo">Activo</span>' : '<span class="badge-inactivo">Inactivo</span>' ?></td>
                <td class="text-center">
                  <button class="btn-outline btn-sm-icon btn-editar-subtema" title="Editar"
                          data-row="<?= htmlspecialchars(json_encode([
                            'id'      => (int) $s['id_subtema'],
                            'nombre'  => $s['nombre'],
                            'id_tema' => (int) $s['id_tema'],
                            'activo'  => (int) $s['activo'],
                          ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>">
                    <i class="fas fa-pen"></i>
                  </button>
                  <?php if ($s['activo']): ?>
                  <button class="btn-danger-sm ms-1 btn-desactivar-subtema" title="Desactivar"
                          data-id="<?= (int) $s['id_subtema'] ?>"
                          data-nombre="<?= htmlspecialchars($s['nombre'], ENT_QUOTES) ?>">
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
    </div>
  </div>

</div><!-- /content -->

<!-- MODAL Tema -->
<div class="modal fade" id="modalTema" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title" id="modalTemaTitulo"><i class="fas fa-tags me-2"></i>Nuevo Tema</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formTema" novalidate>
          <input type="hidden" id="temaId" name="id_tema" value="0"/>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label-b">Nombre del tema <span class="req">*</span></label>
              <input type="text" class="fc" id="temaNombre" name="nombre" maxlength="200"/>
            </div>
            <div class="col-md-7">
              <label class="form-label-b">Aplica a</label>
              <select class="fs" id="temaTipo" name="tipo">
                <option value="ambos">Ambos</option>
                <option value="capacitacion">Capacitación</option>
                <option value="at">Asistencia Técnica</option>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label-b">Estado</label>
              <select class="fs" id="temaActivo" name="activo">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-gris" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn-primario" id="btnGuardarTema">
          <i class="fas fa-floppy-disk me-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL Subtema -->
<div class="modal fade" id="modalSubtema" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title" id="modalSubtemaTitulo"><i class="fas fa-tag me-2"></i>Nuevo Subtema</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formSubtema" novalidate>
          <input type="hidden" id="subId" name="id_subtema" value="0"/>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label-b">Nombre del subtema <span class="req">*</span></label>
              <input type="text" class="fc" id="subNombre" name="nombre" maxlength="200"/>
            </div>
            <div class="col-md-7">
              <label class="form-label-b">Tema padre <span class="req">*</span></label>
              <select class="fs" id="subTema" name="id_tema">
                <option value="">— Seleccionar tema —</option>
                <?php foreach ($temas as $t): if (!$t['activo']) continue; ?>
                <option value="<?= $t['id_tema'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label-b">Estado</label>
              <select class="fs" id="subActivo" name="activo">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-gris" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn-primario" id="btnGuardarSubtema">
          <i class="fas fa-floppy-disk me-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>
