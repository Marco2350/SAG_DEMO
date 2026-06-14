<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/riesgos_fp.js?v=' . APP_VERSION . '"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">
  <div class="crumb-bar">
    <i class="fas fa-house"></i>
    <span>Inicio</span>
    <i class="fas fa-chevron-right crumb-sep"></i>
    <span class="crumb-pill"><i class="fas fa-triangle-exclamation"></i> Riesgos</span>
  </div>

  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-triangle-exclamation" style="color:var(--primario);margin-right:8px;"></i>Matriz de Riesgos
      <small>FPROG 2026 — probabilidad × impacto + medidas de mitigación</small>
    </div>
    <div>
      <button class="btn-primario" id="btnNuevoRiesgo">
        <i class="fas fa-plus"></i> Nuevo Riesgo
      </button>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="ms-val"><?= (int)$resumen['total'] ?></div>
        <div class="ms-lbl"><i class="fas fa-list" style="color:var(--primario);margin-right:4px;"></i>Total</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#dc2626;">
        <div class="ms-val" style="color:#dc2626;"><?= (int)$resumen['criticos'] ?></div>
        <div class="ms-lbl"><i class="fas fa-bolt" style="color:#dc2626;margin-right:4px;"></i>Críticos</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#3b82f6;">
        <div class="ms-val" style="color:#3b82f6;"><?= (int)$resumen['en_mitigacion'] ?></div>
        <div class="ms-lbl"><i class="fas fa-shield" style="color:#3b82f6;margin-right:4px;"></i>En Mitigación</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#16a34a;">
        <div class="ms-val" style="color:#16a34a;"><?= (int)$resumen['superados'] ?></div>
        <div class="ms-lbl"><i class="fas fa-circle-check" style="color:#16a34a;margin-right:4px;"></i>Superados</div>
      </div>
    </div>
  </div>

  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-list-ul"></i> Listado de Riesgos</h6>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <select class="fs" id="filtroCategoria" style="width:130px;padding:6px 10px;">
          <option value="">Todas las categorías</option>
          <option value="operativo">Operativo</option>
          <option value="financiero">Financiero</option>
          <option value="tecnico">Técnico</option>
          <option value="politico">Político</option>
          <option value="legal">Legal</option>
          <option value="ambiental">Ambiental</option>
          <option value="otro">Otro</option>
        </select>
        <select class="fs" id="filtroProb" style="width:120px;padding:6px 10px;">
          <option value="">Toda probabilidad</option>
          <option value="baja">Baja</option>
          <option value="media">Media</option>
          <option value="alta">Alta</option>
        </select>
        <select class="fs" id="filtroImpacto" style="width:120px;padding:6px 10px;">
          <option value="">Todo impacto</option>
          <option value="bajo">Bajo</option>
          <option value="medio">Medio</option>
          <option value="alto">Alto</option>
        </select>
        <select class="fs" id="filtroEstado" style="width:140px;padding:6px 10px;">
          <option value="">Todos los estados</option>
          <option value="identificado">Identificado</option>
          <option value="mitigacion">En Mitigación</option>
          <option value="materializado">Materializado</option>
          <option value="superado">Superado</option>
          <option value="cerrado">Cerrado</option>
        </select>
        <input type="text" class="fs" id="filtroBuscar" placeholder="Buscar..." style="width:180px;padding:6px 10px;">
      </div>
    </div>
    <div class="card-box-body">
      <table id="tablaRiesgos" class="table sag-table" style="width:100%;">
        <thead>
          <tr>
            <th>Categoría</th>
            <th>Descripción del riesgo</th>
            <th>Prob.</th>
            <th>Impacto</th>
            <th>Nivel</th>
            <th>Mitigación</th>
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

<div class="modal fade" id="modalRiesgo" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formRiesgo">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Controller::csrfToken()) ?>">
        <input type="hidden" name="id_riesgo" id="id_riesgo" value="0">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-triangle-exclamation"></i> <span id="modalRiesgoTitle">Nuevo Riesgo</span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Categoría <span style="color:#dc2626;">*</span></label>
              <select class="form-select" name="categoria" required>
                <option value="operativo" selected>Operativo</option>
                <option value="financiero">Financiero</option>
                <option value="tecnico">Técnico</option>
                <option value="politico">Político</option>
                <option value="legal">Legal</option>
                <option value="ambiental">Ambiental</option>
                <option value="otro">Otro</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Probabilidad <span style="color:#dc2626;">*</span></label>
              <select class="form-select" name="probabilidad" required>
                <option value="baja">Baja</option>
                <option value="media" selected>Media</option>
                <option value="alta">Alta</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Impacto <span style="color:#dc2626;">*</span></label>
              <select class="form-select" name="impacto" required>
                <option value="bajo">Bajo</option>
                <option value="medio" selected>Medio</option>
                <option value="alto">Alto</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Descripción del riesgo <span style="color:#dc2626;">*</span></label>
              <textarea class="form-control" name="descripcion" rows="2" required maxlength="2000"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Medida de mitigación</label>
              <textarea class="form-control" name="medida_mitigacion" rows="2"></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Responsable</label>
              <input type="text" class="form-control" name="responsable" maxlength="200">
            </div>
            <div class="col-md-6">
              <label class="form-label">Observaciones</label>
              <input type="text" class="form-control" name="observaciones">
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
