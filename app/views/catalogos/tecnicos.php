<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/catalogos.js"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">

  <!-- Breadcrumb -->
  <div style="display:flex;align-items:center;gap:8px;font-size:.78rem;color:#888;margin-bottom:8px;">
    <i class="fas fa-sliders"></i>
    <span>Parametrización</span>
    <i class="fas fa-chevron-right" style="font-size:.65rem;color:#bbb;"></i>
    <span style="background:var(--primario);color:#fff;padding:3px 12px;border-radius:14px;font-weight:700;font-size:.74rem;letter-spacing:.3px;">
      <i class="fas fa-user-tie"></i> Técnicos
    </span>
  </div>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-user-tie" style="color:var(--primario);margin-right:8px;"></i>Técnicos de Campo
      <small>Catálogo de técnicos del programa <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?></small>
    </div>
    <button class="btn-primario" id="btnNuevoTec">
      <i class="fas fa-plus"></i> Nuevo Técnico
    </button>
  </div>

  <!-- Tabla -->
  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-list-ul"></i> Listado de Técnicos</h6>
    </div>
    <div style="padding:16px;overflow-x:auto;">
      <table id="tablaTecnicos" class="sag-table" style="width:100%;">
        <thead>
          <tr>
            <th>#</th><th>Nombre Completo</th><th>Especialidad</th>
            <th>Departamento</th><th>Teléfono</th><th>Email</th>
            <th>Estado</th><th style="width:100px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tecnicos as $t): ?>
          <tr>
            <td><?= (int) $t['id_tecnico'] ?></td>
            <td><strong><?= htmlspecialchars($t['nombre_completo']) ?></strong></td>
            <td><?= htmlspecialchars($t['especialidad'] ?: '—') ?></td>
            <td><?= htmlspecialchars($t['departamento'] ?: 'Nacional') ?></td>
            <td><?= htmlspecialchars($t['telefono'] ?: '—') ?></td>
            <td><?= htmlspecialchars($t['email'] ?: '—') ?></td>
            <td><?= $t['activo'] ? '<span class="badge-activo">Activo</span>' : '<span class="badge-inactivo">Inactivo</span>' ?></td>
            <td class="text-center">
              <button class="btn-outline btn-sm-icon btn-editar-cat" title="Editar"
                      data-row="<?= htmlspecialchars(json_encode([
                        'id'              => (int) $t['id_tecnico'],
                        'nombre_completo' => $t['nombre_completo'],
                        'especialidad'    => $t['especialidad'] ?? '',
                        'id_departamento' => (int) ($t['id_departamento'] ?? 0),
                        'telefono'        => $t['telefono'] ?? '',
                        'email'           => $t['email'] ?? '',
                        'activo'          => (int) $t['activo'],
                      ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>">
                <i class="fas fa-pen"></i>
              </button>
              <?php if ($t['activo']): ?>
              <button class="btn-danger-sm ms-1 btn-desactivar-cat" title="Desactivar"
                      data-id="<?= (int) $t['id_tecnico'] ?>"
                      data-nombre="<?= htmlspecialchars($t['nombre_completo'], ENT_QUOTES) ?>">
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

<!-- MODAL Crear / Editar Técnico -->
<div class="modal fade" id="modalCatalogo" tabindex="-1" aria-hidden="true" data-catalogo="tecnicos">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title" id="modalCatTitulo">
          <i class="fas fa-user-tie me-2"></i>Nuevo Técnico
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formCatalogo" novalidate>
          <input type="hidden" id="catId" name="id_tecnico" value="0"/>
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label-b">Nombre completo <span class="req">*</span></label>
              <input type="text" class="fc" id="catNombre" name="nombre_completo" placeholder="Nombres y apellidos" maxlength="200"/>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Especialidad</label>
              <input type="text" class="fc" id="catEspecialidad" name="especialidad" placeholder="Ej. Agrónomo" maxlength="100"/>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Departamento asignado</label>
              <select class="fs" id="catDep" name="id_departamento">
                <option value="">— Nacional (todos) —</option>
                <?php foreach ($departamentos as $d): ?>
                <option value="<?= $d['id_departamento'] ?>"><?= htmlspecialchars($d['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Teléfono</label>
              <input type="text" class="fc input-tel" id="catTelefono" name="telefono" placeholder="9999-9999" maxlength="9" inputmode="numeric"/>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Email</label>
              <input type="email" class="fc" id="catEmail" name="email" placeholder="tecnico@sag.hn" maxlength="150"/>
            </div>
            <div class="col-md-4">
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
