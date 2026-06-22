<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/organizaciones.js?v=' . filemtime(ROOT_PATH . '/public/assets/js/modules/organizaciones.js') . '"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">

  <!-- R-009: Breadcrumb visual del módulo activo -->
  <div class="crumb-bar">
    <i class="fas fa-house"></i>
    <span>Inicio</span>
    <i class="fas fa-chevron-right crumb-sep"></i>
    <span class="crumb-pill">
      <i class="fas fa-building-wheat"></i> Organizaciones
    </span>
  </div>

  <!-- Page Header -->
  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-building-wheat" style="color:var(--primario);margin-right:8px;"></i>Organizaciones
      <small>Gestión de organizaciones y cooperativas del programa <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?></small>
    </div>
    <button class="btn-primario" id="btnNueva">
      <i class="fas fa-plus"></i> Nueva Organización
    </button>
  </div>

  <!-- Mini stats -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="ms-val" data-stat="total"><?= $resumen['total'] ?></div>
        <div class="ms-lbl"><i class="fas fa-building-wheat" style="color:var(--primario);margin-right:4px;"></i>Total registradas</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#16a34a;">
        <div class="ms-val" style="color:#16a34a;" data-stat="activa"><?= $resumen['activa'] ?></div>
        <div class="ms-lbl"><i class="fas fa-circle-check" style="color:#16a34a;margin-right:4px;"></i>Activas</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat gold">
        <div class="ms-val" data-stat="pendiente"><?= $resumen['pendiente'] ?></div>
        <div class="ms-lbl"><i class="fas fa-clock" style="color:#f5a623;margin-right:4px;"></i>Pendientes</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#dc2626;">
        <div class="ms-val" style="color:#dc2626;" data-stat="inactiva"><?= $resumen['inactiva'] ?></div>
        <div class="ms-lbl"><i class="fas fa-circle-xmark" style="color:#dc2626;margin-right:4px;"></i>Inactivas</div>
      </div>
    </div>
  </div>

  <!-- Tabla -->
  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-list"></i> Listado de Organizaciones</h6>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <select class="fs" id="filtroEstado" style="width:140px;padding:6px 10px;">
          <option value="">Todos los estados</option>
          <option value="activa">Activa</option>
          <option value="pendiente">Pendiente</option>
          <option value="inactiva">Inactiva</option>
        </select>
        <select class="fs" id="filtroTipo" style="width:160px;padding:6px 10px;">
          <option value="">Todos los tipos</option>
          <?php foreach ($tipos as $t): ?>
          <option value="<?= htmlspecialchars($t['valor']) ?>"><?= htmlspecialchars($t['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <select class="fs" id="filtroDep" style="width:180px;padding:6px 10px;">
          <option value="">Todos los departamentos</option>
          <?php foreach ($departamentos as $dep): ?>
          <option value="<?= $dep['id_departamento'] ?>"><?= htmlspecialchars($dep['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn-outline" id="btnFiltrar" style="padding:6px 14px;">
          <i class="fas fa-filter"></i> Filtrar
        </button>
      </div>
    </div>
    <div style="padding:16px;overflow-x:auto;">
      <table id="tablaOrganizaciones" class="sag-table table-fit-content" style="width:100%;">
        <thead>
          <tr>
            <th>#</th><th>Nombre</th><th>Tipo</th><th>Ubicación</th>
            <th>Representante</th><th>Tel.</th><th>Productores</th>
            <th>Estado</th><th style="width:160px;">Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

</div><!-- /content -->

<!-- MODAL Crear/Editar -->
<div class="modal fade" id="modalOrganizacion" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title" id="modalOrgTitulo">
          <i class="fas fa-building-wheat me-2"></i>Nueva Organización
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formOrganizacion" novalidate>
          <input type="hidden" id="orgId" name="id_organizacion" value="0"/>

          <!-- R-011: Sección Identificación -->
          <div class="form-section-title" style="background:#f0fdf4;padding:8px 12px;border-left:4px solid #16a34a;border-radius:4px;margin-bottom:14px;">
            <i class="fas fa-id-card me-1" style="color:#16a34a;"></i>Identificación de la Organización
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label class="form-label-b">Nombre de la Organización <span class="req">*</span></label>
              <input type="text" class="fc" id="orgNombre" name="nombre" placeholder="Nombre completo de la organización" required/>
              <small id="orgNombreErr" style="color:#dc2626;display:none;font-size:.74rem;margin-top:3px;">
                <i class="fas fa-circle-exclamation"></i> Ya existe una organización con este nombre.
              </small>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Tipo de Organización <span class="req">*</span></label>
              <select class="fs" id="orgTipo" name="tipo" required>
                <option value="">— Seleccione tipo —</option>
                <?php foreach ($tipos as $t): ?>
                <option value="<?= htmlspecialchars($t['valor']) ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Ubicación -->
          <div class="form-section-title" style="background:#eff6ff;padding:8px 12px;border-left:4px solid #1e40af;border-radius:4px;margin-bottom:14px;">
            <i class="fas fa-location-dot me-1" style="color:#1e40af;"></i>Ubicación geográfica
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label-b">Departamento <span class="req">*</span></label>
              <select class="fs" id="orgDep" name="id_departamento" required>
                <option value="">— Seleccione departamento —</option>
                <?php foreach ($departamentos as $dep): ?>
                <option value="<?= $dep['id_departamento'] ?>"><?= htmlspecialchars($dep['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Municipio <span class="req">*</span></label>
              <select class="fs" id="orgMun" name="id_municipio" required>
                <!-- R-015: placeholder corregido -->
                <option value="">Seleccione municipio</option>
              </select>
              <small style="color:#888;font-size:.7rem;">298 municipios de Honduras disponibles</small>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Aldea / Comunidad</label>
              <input type="text" class="fc" id="orgAldea" name="aldea" placeholder="Ej. El Porvenir"/>
            </div>
          </div>

          <!-- R-017: Coordenadas separadas en Latitud y Longitud -->
          <div class="row g-3 mb-3">
            <div class="col-md-4">
              <label class="form-label-b">Latitud</label>
              <input type="number" step="0.0000001" class="fc" id="orgLatitud" name="latitud" placeholder="Ej. 14.0818"/>
              <small style="color:#888;font-size:.7rem;">Rango: 12.9 a 16.5 (Honduras)</small>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Longitud</label>
              <input type="number" step="0.0000001" class="fc" id="orgLongitud" name="longitud" placeholder="Ej. -87.2068"/>
              <small style="color:#888;font-size:.7rem;">Rango: -89.4 a -83.1 (Honduras)</small>
            </div>
            <div class="col-md-4">
              <label class="form-label-b">Estado <span class="req">*</span></label>
              <select class="fs" id="orgEstado" name="estado" required>
                <option value="pendiente">Pendiente</option>
                <option value="activa">Activa</option>
                <option value="inactiva">Inactiva</option>
              </select>
            </div>
          </div>

          <!-- Representante Legal -->
          <div class="form-section-title" style="background:#fef3c7;padding:8px 12px;border-left:4px solid #d97706;border-radius:4px;margin-bottom:14px;">
            <i class="fas fa-user-tie me-1" style="color:#d97706;"></i>Representante Legal
          </div>
          <div class="row g-3 mb-3">
            <!-- R-016: DNI del representante + indicador RNP -->
            <div class="col-md-4">
              <label class="form-label-b">DNI / Identidad <span class="req">*</span></label>
              <input type="text" class="fc" id="orgRepDni" name="representante_dni" placeholder="0000-0000-00000" maxlength="15" inputmode="numeric" required/>
              <small id="orgRepRnpStatus" style="color:#888;font-size:.7rem;">Validable contra RNP (cuando esté disponible)</small>
            </div>
            <div class="col-md-5">
              <label class="form-label-b">Nombre completo del Representante <span class="req">*</span></label>
              <input type="text" class="fc" id="orgRepresentante" name="representante" placeholder="Nombres y apellidos" required/>
            </div>
            <div class="col-md-3">
              <label class="form-label-b">Fecha de Registro</label>
              <input type="date" class="fc" id="orgFechaReg" name="fecha_registro"/>
            </div>
          </div>

          <!-- Contacto -->
          <div class="form-section-title" style="background:#f5f3ff;padding:8px 12px;border-left:4px solid #7c3aed;border-radius:4px;margin-bottom:14px;">
            <i class="fas fa-address-card me-1" style="color:#7c3aed;"></i>Datos de Contacto
          </div>
          <div class="row g-3 mb-2">
            <div class="col-md-6">
              <label class="form-label-b">Teléfono</label>
              <input type="text" class="fc" id="orgTelefono" name="telefono" placeholder="9999-9999" maxlength="9" inputmode="numeric"/>
            </div>
            <div class="col-md-6">
              <label class="form-label-b">Correo Electrónico</label>
              <input type="email" class="fc" id="orgEmail" name="email" placeholder="correo@organizacion.hn"/>
            </div>
          </div>

        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-gris" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn-primario" id="btnGuardarOrg">
          <i class="fas fa-floppy-disk me-1"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL Cambiar Estado -->
<div class="modal fade" id="modalEstado" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title"><i class="fas fa-toggle-on me-2"></i>Cambiar Estado</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="estadoOrgId"/>
        <label class="form-label-b">Nuevo estado:</label>
        <select class="fs mt-2" id="nuevoEstado">
          <option value="activa">Activa</option>
          <option value="pendiente">Pendiente</option>
          <option value="inactiva">Inactiva</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-gris" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn-primario" id="btnConfirmarEstado">
          <i class="fas fa-check me-1"></i> Aplicar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL Ver Miembros -->
<div class="modal fade" id="modalMiembros" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header modal-header-sag">
        <h5 class="modal-title" id="modalMiembrosTitulo">
          <i class="fas fa-users me-2"></i>Miembros de la Organización
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:0;">
        <!-- Resumen rápido -->
        <div id="miembrosResumen" style="display:flex;gap:16px;padding:14px 20px;background:#f8f9fb;border-bottom:1px solid #eee;flex-wrap:wrap;">
          <div style="font-size:.84rem;color:#555;">
            <i class="fas fa-users" style="color:var(--primario);margin-right:5px;"></i>
            Total: <strong id="miembrosTotal">0</strong> beneficiarios
          </div>
          <div style="font-size:.84rem;color:#555;">
            <i class="fas fa-circle-check" style="color:#16a34a;margin-right:5px;"></i>
            Activos: <strong id="miembrosActivos" style="color:#16a34a;">0</strong>
          </div>
          <div style="font-size:.84rem;color:#555;">
            <i class="fas fa-circle-xmark" style="color:#dc2626;margin-right:5px;"></i>
            Inactivos: <strong id="miembrosInactivos" style="color:#dc2626;">0</strong>
          </div>
        </div>
        <!-- Tabla -->
        <div style="padding:16px;overflow-x:auto;">
          <div id="miembrosLoader" style="text-align:center;padding:30px;color:#aaa;display:none;">
            <i class="fas fa-spinner fa-spin" style="font-size:1.5rem;margin-bottom:8px;display:block;"></i>
            Cargando miembros…
          </div>
          <div id="miembrosVacio" style="text-align:center;padding:30px;color:#aaa;display:none;">
            <i class="fas fa-user-slash" style="font-size:2rem;display:block;margin-bottom:10px;"></i>
            Esta organización no tiene beneficiarios registrados.
          </div>
          <table id="tablaMiembros" class="sag-table" style="width:100%;display:none;">
            <thead>
              <tr>
                <th>#</th>
                <th>Nombre Completo</th>
                <th>DNI</th>
                <th>Sexo</th>
                <th>Ubicación</th>
                <th>Teléfono</th>
                <th>Cultivo</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody id="miembrosTbody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-gris" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>
