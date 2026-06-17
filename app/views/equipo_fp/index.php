<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/equipo_fp.js?v=' . APP_VERSION . '"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">
  <div class="crumb-bar">
    <i class="fas fa-house"></i>
    <span>Inicio</span>
    <i class="fas fa-chevron-right crumb-sep"></i>
    <span class="crumb-pill"><i class="fas fa-users-gear"></i> Equipo Técnico</span>
  </div>

  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-users-gear" style="color:var(--primario);margin-right:8px;"></i>Equipo Técnico
      <small>Estructura operativa del FPROG 2026 — roles, plazas y presupuesto</small>
    </div>
    <div>
      <button class="btn-primario" id="btnNuevoRol">
        <i class="fas fa-plus"></i> Nuevo Rol
      </button>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="ms-val" data-stat="total_roles"><?= (int)$resumen['total_roles'] ?></div>
        <div class="ms-lbl"><i class="fas fa-id-badge" style="color:var(--primario);margin-right:4px;"></i>Roles</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#3b82f6;">
        <div class="ms-val" style="color:#3b82f6;" data-stat="total_plazas"><?= (int)$resumen['total_plazas'] ?></div>
        <div class="ms-lbl"><i class="fas fa-user-tie" style="color:#3b82f6;margin-right:4px;"></i>Plazas</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#16a34a;">
        <div class="ms-val" style="color:#16a34a;" data-stat="contratados"><?= (int)$resumen['contratados'] ?></div>
        <div class="ms-lbl"><i class="fas fa-circle-check" style="color:#16a34a;margin-right:4px;"></i>Contratados</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#f59e0b;">
        <div class="ms-val" style="color:#f59e0b;" data-stat="presupuesto_total" data-stat-fmt="moneyL0">L. <?= number_format((float)$resumen['presupuesto_total'], 0) ?></div>
        <div class="ms-lbl"><i class="fas fa-coins" style="color:#f59e0b;margin-right:4px;"></i>Presupuesto RH</div>
      </div>
    </div>
  </div>

  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-list-ul"></i> Roles del Equipo</h6>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <select class="fs" id="filtroEstado" style="width:160px;padding:6px 10px;">
          <option value="">Todos los estados</option>
          <option value="vacante">Vacante</option>
          <option value="en_proceso">En Proceso</option>
          <option value="contratado">Contratado</option>
          <option value="baja">Baja</option>
        </select>
        <input type="text" class="fs" id="filtroBuscar" placeholder="Buscar..." style="width:180px;padding:6px 10px;">
      </div>
    </div>
    <div class="card-box-body">
      <table id="tablaEquipo" class="table sag-table" style="width:100%;">
        <thead>
          <tr>
            <th>Rol</th>
            <th>Cant.</th>
            <th>Ámbito</th>
            <th>Presupuesto</th>
            <th>% Total</th>
            <th>Responsable</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEquipo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formEquipo">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Controller::csrfToken()) ?>">
        <input type="hidden" name="id_equipo" id="id_equipo" value="0">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-users-gear"></i> <span id="modalEquipoTitle">Nuevo Rol</span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-9">
              <label class="form-label">Rol / Cargo <span style="color:#dc2626;">*</span></label>
              <input type="text" class="form-control" name="rol" maxlength="200" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Cantidad de plazas</label>
              <input type="number" class="form-control" name="cantidad" min="1" value="1">
            </div>
            <div class="col-12">
              <label class="form-label">Descripción del rol</label>
              <textarea class="form-control" name="descripcion" rows="2"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Ámbito (zona / componente)</label>
              <input type="text" class="form-control" name="ambito" maxlength="120" placeholder="Norte, Sur, Componente IV...">
            </div>
            <div class="col-md-6">
              <label class="form-label">Responsable / nombre</label>
              <input type="text" class="form-control" name="responsable" maxlength="200">
            </div>
            <div class="col-md-6">
              <label class="form-label">Presupuesto asignado (L.)</label>
              <input type="number" step="0.01" min="0" class="form-control" name="presupuesto_asignado">
            </div>
            <div class="col-md-6">
              <label class="form-label">% del presupuesto total</label>
              <input type="number" step="0.01" min="0" max="100" class="form-control" name="porcentaje_presupuesto">
            </div>
            <div class="col-12">
              <label class="form-label">Observaciones</label>
              <textarea class="form-control" name="observaciones" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn-primario"><i class="fas fa-save"></i> Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>
