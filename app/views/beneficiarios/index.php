<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/beneficiarios.js"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-users" style="color:var(--primario);margin-right:8px;"></i>Beneficiarios
      <small>Registro y gestión de beneficiarios — <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?></small>
    </div>
    <div class="mode-tabs">
      <button class="mode-tab active" onclick="switchTab('individual')">
        <i class="fas fa-user-plus" style="font-size:.75rem;margin-right:5px;"></i>Registro Individual
      </button>
      <button class="mode-tab" onclick="switchTab('masivo')">
        <i class="fas fa-file-csv" style="font-size:.75rem;margin-right:5px;"></i>Carga Masiva
      </button>
      <button class="mode-tab" onclick="switchTab('listado')">
        <i class="fas fa-list" style="font-size:.75rem;margin-right:5px;"></i>Listado
      </button>
    </div>
  </div>

  <!-- Mini stats -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="ms-val"><?= $resumen['total'] ?></div>
        <div class="ms-lbl"><i class="fas fa-users" style="color:var(--primario);margin-right:4px;"></i>Total</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat blue">
        <div class="ms-val"><?= $resumen['hombres'] ?></div>
        <div class="ms-lbl"><i class="fas fa-mars" style="color:#3b82f6;margin-right:4px;"></i>Hombres</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#db2777;">
        <div class="ms-val"><?= $resumen['mujeres'] ?></div>
        <div class="ms-lbl"><i class="fas fa-venus" style="color:#db2777;margin-right:4px;"></i>Mujeres</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat gold">
        <div class="ms-val"><?= $resumen['orgs'] ?></div>
        <div class="ms-lbl"><i class="fas fa-building-wheat" style="color:#f5a623;margin-right:4px;"></i>Organizaciones</div>
      </div>
    </div>
  </div>

  <!-- TAB INDIVIDUAL -->
  <div id="tab-individual">
    <div class="card-box">
      <div class="card-box-header">
        <h6><i class="fas fa-user-plus"></i> Datos del Beneficiario</h6>
      </div>
      <div class="card-box-body">
        <form id="formBeneficiario" novalidate>
          <input type="hidden" id="beneId" name="id_beneficiario" value="0"/>

          <div class="form-section-title"><i class="fas fa-id-card me-1"></i>Datos Personales</div>
          <div class="row g-3 mb-4">
            <div class="col-md-3">
              <label class="form-label-b">Nombre <span class="req">*</span></label>
              <input type="text" class="fc" id="bNombre" name="nombre" placeholder="Primer nombre"/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Apellido <span class="req">*</span></label>
              <input type="text" class="fc" id="bApellido" name="apellido" placeholder="Primer apellido"/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">DNI / Identidad</label>
              <input type="text" class="fc input-dni" id="bDni" name="dni"
                     placeholder="0801-AAAA-NNNNN" maxlength="15"/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Fecha de Nacimiento</label>
              <input type="date" class="fc" id="bFechaNac" name="fecha_nacimiento"/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Sexo <span class="req">*</span></label>
              <select class="fs" id="bSexo" name="sexo" required>
                <option value="">— Seleccione —</option>
                <option value="M">Masculino</option>
                <option value="F">Femenino</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Teléfono</label>
              <input type="text" class="fc input-tel" id="bTelefono" name="telefono" placeholder="9999-9999"/>
            </div>
          </div>

          <div class="form-section-title"><i class="fas fa-location-dot me-1"></i>Ubicación</div>
          <div class="row g-3 mb-4">
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
              <input type="text" class="fc" id="bAldea" name="aldea" placeholder="Ej. El Porvenir"/>
            </div>
            <div class="col-md-5">
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
      <div style="padding:14px 18px;display:flex;gap:10px;justify-content:flex-end;border-top:1px solid #f0f0f0;">
        <button type="button" class="btn-gris" id="btnLimpiarBene"><i class="fas fa-rotate-left"></i> Limpiar</button>
        <button type="button" class="btn-primario" id="btnGuardarBene"><i class="fas fa-floppy-disk"></i> Guardar Beneficiario</button>
      </div>
    </div>
  </div>

  <!-- TAB MASIVO -->
  <div id="tab-masivo" style="display:none;">
    <div class="card-box">
      <div class="card-box-header"><h6><i class="fas fa-file-csv"></i> Carga Masiva de Beneficiarios</h6></div>
      <div class="card-box-body">
        <p style="font-size:.82rem;color:#555;margin-bottom:8px;">Columnas requeridas en el CSV:</p>
        <div style="display:flex;gap:6px;flex-wrap:wrap;padding:10px 0;">
          <?php foreach (['nombre','apellido','dni','fecha_nacimiento','sexo','id_departamento','id_municipio','aldea','id_organizacion','telefono'] as $col): ?>
          <span style="background:#e8edf5;color:var(--primario);padding:4px 10px;border-radius:5px;font-size:.73rem;font-weight:600;"><?= $col ?></span>
          <?php endforeach; ?>
        </div>
        <div class="upload-zone" id="uploadZone" style="margin-top:12px;">
          <input type="file" id="inputCSV" accept=".csv" style="display:none;"/>
          <i class="fas fa-file-csv" style="font-size:2.5rem;color:#b0bec5;display:block;margin-bottom:10px;"></i>
          <h6 style="font-size:.9rem;font-weight:700;color:#444;margin-bottom:4px;">Arrastra tu archivo CSV aquí</h6>
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
    </div>
  </div>

  <!-- TAB LISTADO -->
  <div id="tab-listado" style="display:none;">
    <div class="card-box">
      <div class="card-box-header">
        <h6><i class="fas fa-list-ul"></i> Listado de Beneficiarios</h6>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
          <select class="fs" id="filtroOrg" style="width:170px;padding:6px 10px;">
            <option value="">Todas las organizaciones</option>
            <?php foreach ($organizaciones as $org): ?>
            <option value="<?= $org['id_organizacion'] ?>"><?= htmlspecialchars($org['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
          <select class="fs" id="filtroDepBene" style="width:165px;padding:6px 10px;">
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
          <button class="btn-outline" id="btnFiltrarBene" style="padding:6px 14px;"><i class="fas fa-filter"></i> Filtrar</button>
        </div>
      </div>
      <div style="padding:16px;overflow-x:auto;">
        <table id="tablaBeneficiarios" class="sag-table" style="width:100%;">
          <thead>
            <tr>
              <th>#</th><th>Nombre Completo</th><th>DNI</th><th>Edad</th>
              <th>Sexo</th><th>Organización</th><th>Ubicación</th>
              <th>Teléfono</th><th style="width:110px;">Acciones</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>

</div><!-- /content -->

<!-- Modal Ver Detalle -->
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
