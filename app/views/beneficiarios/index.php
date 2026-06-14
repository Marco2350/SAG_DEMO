<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/beneficiarios.js"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">

  <!-- Breadcrumb del módulo activo -->
  <div class="crumb-bar">
    <i class="fas fa-house"></i>
    <span>Inicio</span>
    <i class="fas fa-chevron-right crumb-sep"></i>
    <span class="crumb-pill">
      <i class="fas fa-users"></i> Productores
    </span>
  </div>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-users" style="color:var(--primario);margin-right:8px;"></i>Beneficiarios
      <small>Registro y gestión de productores — <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?></small>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <button class="btn-outline" id="btnCargaMasiva">
        <i class="fas fa-file-csv"></i> Carga Masiva
      </button>
      <button class="btn-primario" id="btnNuevoBene">
        <i class="fas fa-user-plus"></i> Nuevo Productor
      </button>
    </div>
  </div>

  <!-- Mini stats -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="ms-val"><?= $resumen['total'] ?></div>
        <div class="ms-lbl"><i class="fas fa-users" style="color:var(--primario);margin-right:4px;"></i>Total</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat blue">
        <div class="ms-val"><?= $resumen['hombres'] ?></div>
        <div class="ms-lbl"><i class="fas fa-user" style="color:#3b82f6;margin-right:4px;"></i>Hombres</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#db2777;">
        <div class="ms-val"><?= $resumen['mujeres'] ?></div>
        <div class="ms-lbl"><i class="fas fa-user" style="color:#db2777;margin-right:4px;"></i>Mujeres</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat gold">
        <div class="ms-val"><?= $resumen['orgs'] ?></div>
        <div class="ms-lbl"><i class="fas fa-building-wheat" style="color:#f5a623;margin-right:4px;"></i>Organizaciones</div>
      </div>
    </div>
  </div>

  <!-- Tabla principal -->
  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-list-ul"></i> Listado de Beneficiarios</h6>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <select class="fs" id="filtroOrg" style="width:180px;padding:6px 10px;">
          <option value="">Todas las organizaciones</option>
          <?php foreach ($organizaciones as $org): ?>
          <option value="<?= $org['id_organizacion'] ?>"><?= htmlspecialchars($org['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <select class="fs" id="filtroDepBene" style="width:170px;padding:6px 10px;">
          <option value="">Todos los departamentos</option>
          <?php foreach ($departamentos as $dep): ?>
          <option value="<?= $dep['id_departamento'] ?>"><?= htmlspecialchars($dep['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <select class="fs" id="filtroSexo" style="width:120px;padding:6px 10px;">
          <option value="">Ambos sexos</option>
          <option value="M">Masculino</option>
          <option value="F">Femenino</option>
        </select>
        <button class="btn-outline" id="btnFiltrarBene" style="padding:6px 14px;">
          <i class="fas fa-filter"></i> Filtrar
        </button>
      </div>
    </div>
    <div style="padding:16px;overflow-x:auto;">
      <table id="tablaBeneficiarios" class="sag-table" style="width:100%;">
        <thead>
          <tr>
            <th>#</th><th>Nombre Completo</th><th>DNI</th><th>Edad</th>
            <th>Sexo</th><th>Organización</th><th>Ubicación</th>
            <th>Teléfono</th><th style="width:120px;">Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

</div><!-- /content -->

<!-- MODAL Crear / Editar Beneficiario -->
<div class="modal fade" id="modalBeneficiario" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title" id="modalBeneTitulo">
          <i class="fas fa-user-plus me-2"></i>Nuevo Productor
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formBeneficiario" novalidate>
          <input type="hidden" id="beneId" name="id_beneficiario" value="0"/>

          <!-- Datos personales -->
          <div class="form-section-title" style="background:#f0fdf4;padding:8px 12px;border-left:4px solid #16a34a;border-radius:4px;margin-bottom:14px;">
            <i class="fas fa-id-card me-1" style="color:#16a34a;"></i>Identidad del Productor
          </div>
          <!-- ── DNI primero — Búsqueda automática en censo y beneficiarios ── -->
          <div class="row g-3 mb-2">
            <div class="col-md-6">
              <label class="form-label-b">DNI / Identidad <span class="req">*</span></label>
              <input type="text" class="fc input-dni" id="bDni" name="dni"
                     placeholder="0000-0000-00000" maxlength="15" inputmode="numeric" autofocus/>
              <small style="color:#6b7280;font-size:.72rem;">Ingrese el DNI primero para buscar en sus beneficiarios y en el censo nacional.</small>
            </div>
            <div class="col-md-6">
              <!-- Banner de estado de búsqueda por DNI -->
              <div id="bDniEstado" style="display:none;margin-top:22px;padding:8px 12px;border-radius:8px;font-size:.82rem;line-height:1.3;"></div>
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label-b">Nombre <span class="req">*</span></label>
              <input type="text" class="fc" id="bNombre" name="nombre" placeholder="Primer nombre" maxlength="100"/>
            </div>
            <div class="col-md-6">
              <label class="form-label-b">Apellido <span class="req">*</span></label>
              <input type="text" class="fc" id="bApellido" name="apellido" placeholder="Primer apellido" maxlength="100"/>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Fecha de Nacimiento</label>
              <input type="date" class="fc" id="bFechaNac" name="fecha_nacimiento"/>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Sexo <span class="req">*</span></label>
              <select class="fs" id="bSexo" name="sexo" required>
                <option value="">— Seleccione —</option>
                <option value="M">Masculino</option>
                <option value="F">Femenino</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Etnia</label>
              <select class="fs" id="bEtnia" name="etnia">
                <option value="">— Seleccione —</option>
                <?php foreach (ETNIAS_HONDURAS as $val => $label): ?>
                  <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Ubicación -->
          <div class="form-section-title" style="background:#eff6ff;padding:8px 12px;border-left:4px solid #1e40af;border-radius:4px;margin-bottom:14px;">
            <i class="fas fa-location-dot me-1" style="color:#1e40af;"></i>Ubicación
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label-b">Departamento <span class="req">*</span></label>
              <select class="fs" id="bDep" name="id_departamento" required>
                <option value="">— Seleccione —</option>
                <?php foreach ($departamentos as $dep): ?>
                <option value="<?= $dep['id_departamento'] ?>"><?= htmlspecialchars($dep['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Municipio <span class="req">*</span></label>
              <select class="fs" id="bMun" name="id_municipio" required>
                <option value="">— Seleccione departamento —</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Aldea</label>
              <!-- Select dependiente del municipio (catálogo nacional 3,733 aldeas) -->
              <select class="fs sag-search" id="bAldeaSelect" style="margin-bottom:4px;display:none;">
                <option value="">— Seleccione municipio primero —</option>
              </select>
              <!-- Fallback texto libre (si el municipio no está en catálogo o el usuario quiere otra) -->
              <input type="text" class="fc" id="bAldea" name="aldea" placeholder="Ej. El Porvenir" maxlength="200"/>
              <small style="color:#6b7280;font-size:.7rem;">Elija del catálogo o escriba directamente.</small>
            </div>
          </div>

          <!-- Contacto y organización -->
          <div class="form-section-title" style="background:#f5f3ff;padding:8px 12px;border-left:4px solid #7c3aed;border-radius:4px;margin-bottom:14px;">
            <i class="fas fa-address-card me-1" style="color:#7c3aed;"></i>Contacto y Organización
          </div>
          <div class="row g-3 mb-2">
            <div class="col-md-5">
              <label class="form-label-b">Teléfono</label>
              <input type="text" class="fc input-tel" id="bTelefono" name="telefono" placeholder="9999-9999" maxlength="9" inputmode="numeric"/>
            </div>
            <div class="col-md-7">
              <label class="form-label-b">Organización</label>
              <select class="fs" id="bOrg" name="id_organizacion">
                <option value="">— Sin organización —</option>
                <?php foreach ($organizaciones as $org): ?>
                <option value="<?= $org['id_organizacion'] ?>"><?= htmlspecialchars($org['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-gris" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn-primario" id="btnGuardarBene">
          <i class="fas fa-floppy-disk me-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL Carga Masiva -->
<div class="modal fade" id="modalCargaMasiva" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title"><i class="fas fa-file-import me-2"></i>Carga Masiva de Beneficiarios</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p style="font-size:.82rem;color:#555;margin-bottom:8px;">Columnas requeridas (CSV o Excel .xlsx):</p>
        <div style="display:flex;gap:6px;flex-wrap:wrap;padding:6px 0 10px;">
          <?php foreach (['nombre','apellido','dni','fecha_nacimiento','sexo','etnia','id_departamento','id_municipio','aldea','id_organizacion','telefono'] as $col): ?>
          <span style="background:#e8edf5;color:var(--primario);padding:4px 10px;border-radius:5px;font-size:.73rem;font-weight:600;"><?= $col ?></span>
          <?php endforeach; ?>
        </div>
        <div class="upload-zone" id="uploadZone" style="margin-top:6px;">
          <input type="file" id="inputCSV" accept=".csv,.xlsx" style="display:none;"/>
          <i class="fas fa-file-import" style="font-size:2.5rem;color:#b0bec5;display:block;margin-bottom:10px;"></i>
          <h6 style="font-size:.9rem;font-weight:700;color:#444;margin-bottom:4px;">Arrastra tu archivo CSV o Excel (.xlsx) aquí</h6>
          <p style="font-size:.78rem;color:#888;margin-bottom:12px;">o haz clic para seleccionarlo</p>
          <button type="button" class="btn-outline" onclick="document.getElementById('inputCSV').click()">
            <i class="fas fa-folder-open"></i> Seleccionar archivo
          </button>
        </div>
        <div id="previewCSV" style="display:none;margin-top:16px;">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
            <i class="fas fa-file-csv" style="color:var(--primario);font-size:1.2rem;"></i>
            <span id="nombreArchivoCSV" style="font-size:.85rem;font-weight:600;"></span>
            <span id="countFilasCSV" class="badge-count"></span>
            <button type="button" class="btn-danger-sm ms-auto" id="btnCancelarCSV"><i class="fas fa-times"></i> Cancelar</button>
          </div>
          <button type="button" class="btn-primario" id="btnProcesarCSV"><i class="fas fa-upload"></i> Procesar e Importar</button>
        </div>
        <div id="resultadoCarga" style="display:none;margin-top:16px;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-gris" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL Ver Detalle -->
<div class="modal fade" id="modalVerBene" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title"><i class="fas fa-user me-2"></i>Detalle del Beneficiario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detalleBeneBody"></div>
      <div class="modal-footer">
        <button type="button" class="btn-primario btn-editar-desde-modal" data-id="">
          <i class="fas fa-pen me-1"></i> Editar
        </button>
        <button type="button" class="btn-gris" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>
