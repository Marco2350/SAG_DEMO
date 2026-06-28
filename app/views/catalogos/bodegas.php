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
      <i class="fas fa-warehouse"></i> Bodegas
    </span>
  </div>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-warehouse" style="color:var(--primario);margin-right:8px;"></i>Bodegas
      <small>Catálogo de bodegas / centros de acopio — <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?></small>
    </div>
    <button class="btn-primario" id="btnNuevaBodega">
      <i class="fas fa-plus"></i> Nueva Bodega
    </button>
  </div>

  <!-- Tabla -->
  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-list-ul"></i> Listado de Bodegas</h6>
    </div>
    <div style="padding:16px;overflow-x:auto;">
      <table id="tablaBodegas" class="sag-table" style="width:100%;">
        <thead>
          <tr>
            <th>#</th><th>Código</th><th>CUE OIRSA</th><th>Nombre</th>
            <th>Ubicación</th><th>Responsable</th>
            <th>Teléfono</th><th class="text-end">Capacidad</th>
            <th>Estado</th><th style="width:100px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($bodegas as $b): ?>
          <tr>
            <td><?= (int) $b['id_bodega'] ?></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars($b['codigo']) ?></span></td>
            <td><?= !empty($b['oirsa_cue']) ? '<span class="badge bg-info text-dark">' . htmlspecialchars($b['oirsa_cue']) . '</span>' : '<span class="muted">Sin mapear</span>' ?></td>
            <td>
              <strong><?= htmlspecialchars($b['nombre']) ?></strong>
              <?php if (!empty($b['direccion'])): ?>
                <br><small class="muted"><?= htmlspecialchars($b['direccion']) ?></small>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($b['departamento']) || !empty($b['municipio'])): ?>
                <?= htmlspecialchars(($b['municipio'] ?: '—') . ', ' . ($b['departamento'] ?: '—')) ?>
              <?php else: ?>
                <span class="muted">Sin asignar</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($b['responsable'] ?: '—') ?></td>
            <td><?= htmlspecialchars($b['telefono'] ?: '—') ?></td>
            <td class="text-end"><?= $b['capacidad'] !== null ? number_format((float) $b['capacidad'], 2) : '—' ?></td>
            <td><?= $b['activo'] ? '<span class="badge-activo">Activa</span>' : '<span class="badge-inactivo">Inactiva</span>' ?></td>
            <td class="text-center">
              <button class="btn-outline btn-sm-icon btn-editar-cat" title="Editar"
                      data-row="<?= htmlspecialchars(json_encode([
                        'id'              => (int) $b['id_bodega'],
                        'codigo'          => $b['codigo'],
                        'oirsa_cue'       => $b['oirsa_cue'] ?? '',
                        'nombre'          => $b['nombre'],
                        'id_departamento' => (int) ($b['id_departamento'] ?? 0),
                        'id_municipio'    => (int) ($b['id_municipio'] ?? 0),
                        'direccion'       => $b['direccion'] ?? '',
                        'responsable'     => $b['responsable'] ?? '',
                        'telefono'        => $b['telefono'] ?? '',
                        'capacidad'       => $b['capacidad'] ?? '',
                        'coordenadas'     => $b['coordenadas'] ?? '',
                        'activo'          => (int) $b['activo'],
                      ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>">
                <i class="fas fa-pen"></i>
              </button>
              <?php if ($b['activo']): ?>
              <button class="btn-danger-sm ms-1 btn-desactivar-cat" title="Desactivar"
                      data-id="<?= (int) $b['id_bodega'] ?>"
                      data-nombre="<?= htmlspecialchars($b['codigo'] . ' — ' . $b['nombre'], ENT_QUOTES) ?>">
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

<!-- MODAL Crear / Editar Bodega -->
<div class="modal fade" id="modalCatalogo" tabindex="-1" aria-hidden="true" data-catalogo="bodegas">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title" id="modalCatTitulo">
          <i class="fas fa-warehouse me-2"></i>Nueva Bodega
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formCatalogo" novalidate>
          <input type="hidden" id="catId" name="id_bodega" value="0"/>
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label-b">Código <span class="req">*</span></label>
              <input type="text" class="fc" id="catCodigo" name="codigo" placeholder="Ej. BOD-FM-01" maxlength="20" style="text-transform:uppercase"/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">CUE OIRSA</label>
              <input type="text" class="fc" id="catOirsaCue" name="oirsa_cue" placeholder="SourceEndpointCode" maxlength="60" style="text-transform:uppercase"/>
              <small class="muted">Código de establecimiento usado por OIRSA.</small>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Nombre de la bodega <span class="req">*</span></label>
              <input type="text" class="fc" id="catNombre" name="nombre" placeholder="Ej. Bodega Central Francisco Morazán" maxlength="200"/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Estado</label>
              <select class="fs" id="catActivo" name="activo">
                <option value="1">Activa</option>
                <option value="0">Inactiva</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label-b">Departamento</label>
              <select class="fs" id="catDep" name="id_departamento">
                <option value="">— Sin asignar —</option>
                <?php foreach ($departamentos as $d): ?>
                <option value="<?= $d['id_departamento'] ?>"><?= htmlspecialchars($d['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Municipio</label>
              <select class="fs" id="catMuni" name="id_municipio">
                <option value="">— Seleccione un depto. primero —</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Coordenadas (lat, lon)</label>
              <input type="text" class="fc" id="catCoordenadas" name="coordenadas" placeholder="Ej. 14.0723, -87.1921" maxlength="60"/>
            </div>

            <div class="col-md-12">
              <label class="form-label-b">Dirección</label>
              <input type="text" class="fc" id="catDireccion" name="direccion" placeholder="Aldea / Col. / Bo. / referencia" maxlength="300"/>
            </div>

            <div class="col-md-5">
              <label class="form-label-b">Responsable</label>
              <input type="text" class="fc" id="catResponsable" name="responsable" placeholder="Nombre del encargado" maxlength="200"/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Teléfono</label>
              <input type="text" class="fc input-tel" id="catTelefono" name="telefono" placeholder="9999-9999" maxlength="9" inputmode="numeric"/>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Capacidad (qq / m³ / unidades)</label>
              <input type="number" class="fc" id="catCapacidad" name="capacidad" placeholder="0.00" min="0" step="0.01"/>
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
