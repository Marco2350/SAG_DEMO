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
      <i class="fas fa-boxes-stacked"></i> Productos
    </span>
  </div>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-boxes-stacked" style="color:var(--primario);margin-right:8px;"></i>Productos de Inventario
      <small>Catálogo de insumos / incentivos del programa <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?></small>
    </div>
    <button class="btn-primario" id="btnNuevoProducto">
      <i class="fas fa-plus"></i> Nuevo Producto
    </button>
  </div>

  <!-- Tabla -->
  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-list-ul"></i> Listado de Productos</h6>
    </div>
    <div style="padding:16px;overflow-x:auto;">
      <table id="tablaProductos" class="sag-table" style="width:100%;">
        <thead>
          <tr>
            <th>#</th><th>Código</th><th>Código OIRSA</th><th>Nombre</th>
            <th>Categoría</th><th>Unidad</th><th>Presentación</th>
            <th class="text-end">Precio Unit. (L.)</th>
            <th>Estado</th><th style="width:100px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($productos as $p): ?>
          <tr>
            <td><?= (int) $p['id_producto'] ?></td>
            <td><span class="badge bg-secondary"><?= htmlspecialchars($p['codigo']) ?></span></td>
            <td><?= !empty($p['oirsa_codigo']) ? '<span class="badge bg-info text-dark">' . htmlspecialchars($p['oirsa_codigo']) . '</span>' : '<span class="muted">Sin mapear</span>' ?></td>
            <td>
              <strong><?= htmlspecialchars($p['nombre']) ?></strong>
              <?php if (!empty($p['descripcion'])): ?>
                <br><small class="muted"><?= htmlspecialchars($p['descripcion']) ?></small>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($p['categoria'] ?: '—') ?></td>
            <td><?= htmlspecialchars($p['unidad']) ?></td>
            <td><?= htmlspecialchars($p['presentacion'] ?: '—') ?></td>
            <td class="text-end">
              <?= $p['precio_unitario'] !== null ? 'L. ' . number_format((float) $p['precio_unitario'], 2) : '—' ?>
            </td>
            <td><?= $p['activo'] ? '<span class="badge-activo">Activo</span>' : '<span class="badge-inactivo">Inactivo</span>' ?></td>
            <td class="text-center">
              <button class="btn-outline btn-sm-icon btn-editar-cat" title="Editar"
                      data-row="<?= htmlspecialchars(json_encode([
                        'id'              => (int) $p['id_producto'],
                        'codigo'          => $p['codigo'],
                        'oirsa_codigo'    => $p['oirsa_codigo'] ?? '',
                        'nombre'          => $p['nombre'],
                        'descripcion'     => $p['descripcion'] ?? '',
                        'unidad'          => $p['unidad'] ?? 'unidad',
                        'presentacion'    => $p['presentacion'] ?? '',
                        'categoria'       => $p['categoria'] ?? '',
                        'precio_unitario' => $p['precio_unitario'] ?? '',
                        'activo'          => (int) $p['activo'],
                      ], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>">
                <i class="fas fa-pen"></i>
              </button>
              <?php if ($p['activo']): ?>
              <button class="btn-danger-sm ms-1 btn-desactivar-cat" title="Desactivar"
                      data-id="<?= (int) $p['id_producto'] ?>"
                      data-nombre="<?= htmlspecialchars($p['codigo'] . ' — ' . $p['nombre'], ENT_QUOTES) ?>">
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

<!-- MODAL Crear / Editar Producto -->
<div class="modal fade" id="modalCatalogo" tabindex="-1" aria-hidden="true" data-catalogo="productos">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title" id="modalCatTitulo">
          <i class="fas fa-boxes-stacked me-2"></i>Nuevo Producto
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formCatalogo" novalidate>
          <input type="hidden" id="catId" name="id_producto" value="0"/>
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label-b">Código <span class="req">*</span></label>
              <input type="text" class="fc" id="catCodigo" name="codigo" placeholder="Ej. SEM-MAIZ-01" maxlength="40" style="text-transform:uppercase"/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Código OIRSA</label>
              <input type="text" class="fc" id="catOirsaCodigo" name="oirsa_codigo" placeholder="ProductTypeCode" maxlength="80" style="text-transform:uppercase"/>
              <small class="muted">Vincula el producto con el objeto trazable.</small>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Nombre del producto <span class="req">*</span></label>
              <input type="text" class="fc" id="catNombre" name="nombre" placeholder="Ej. Semilla de maíz híbrido" maxlength="200"/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Categoría</label>
              <input type="text" class="fc" id="catCategoria" name="categoria" list="catCategoriasList" placeholder="Ej. Semilla" maxlength="80"/>
              <datalist id="catCategoriasList">
                <option value="Semilla"></option>
                <option value="Fertilizante"></option>
                <option value="Plaguicida"></option>
                <option value="Herramienta"></option>
                <option value="Equipo"></option>
                <option value="Material"></option>
                <option value="Servicio"></option>
              </datalist>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Unidad de medida</label>
              <select class="fs" id="catUnidad" name="unidad">
                <option value="unidad">Unidad</option>
                <option value="kg">Kilogramo (kg)</option>
                <option value="lb">Libra (lb)</option>
                <option value="qq">Quintal (qq)</option>
                <option value="lt">Litro (lt)</option>
                <option value="gal">Galón (gal)</option>
                <option value="mt">Metro (mt)</option>
                <option value="mz">Manzana (mz)</option>
                <option value="ha">Hectárea (ha)</option>
                <option value="bolsa">Bolsa</option>
                <option value="saco">Saco</option>
                <option value="caja">Caja</option>
                <option value="paquete">Paquete</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Presentación</label>
              <input type="text" class="fc" id="catPresentacion" name="presentacion" placeholder="Ej. Bolsa de 25 kg" maxlength="80"/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Precio unitario (L.)</label>
              <input type="number" class="fc" id="catPrecio" name="precio_unitario" placeholder="0.00" min="0" step="0.01"/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Estado</label>
              <select class="fs" id="catActivo" name="activo">
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
            <div class="col-md-12">
              <label class="form-label-b">Descripción</label>
              <textarea class="fc" id="catDescripcion" name="descripcion" rows="2" maxlength="500" placeholder="Especificaciones técnicas, características, etc."></textarea>
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
