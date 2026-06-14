<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/cronograma_fp.js?v=' . APP_VERSION . '"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">
  <div class="crumb-bar">
    <i class="fas fa-house"></i>
    <span>Inicio</span>
    <i class="fas fa-chevron-right crumb-sep"></i>
    <span class="crumb-pill"><i class="fas fa-calendar-days"></i> Cronograma</span>
  </div>

  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-calendar-days" style="color:var(--primario);margin-right:8px;"></i>Cronograma de Implementación
      <small>FPROG 2026 — Junio a Diciembre 2026</small>
    </div>
    <div>
      <button class="btn-primario" id="btnNuevaActividad">
        <i class="fas fa-plus"></i> Nueva Actividad
      </button>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="ms-val"><?= (int)$resumen['total'] ?></div>
        <div class="ms-lbl"><i class="fas fa-list-check" style="color:var(--primario);margin-right:4px;"></i>Total</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#3b82f6;">
        <div class="ms-val" style="color:#3b82f6;"><?= (int)$resumen['en_curso'] ?></div>
        <div class="ms-lbl"><i class="fas fa-spinner" style="color:#3b82f6;margin-right:4px;"></i>En Curso</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#16a34a;">
        <div class="ms-val" style="color:#16a34a;"><?= (int)$resumen['completadas'] ?></div>
        <div class="ms-lbl"><i class="fas fa-circle-check" style="color:#16a34a;margin-right:4px;"></i>Completadas</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#dc2626;">
        <div class="ms-val" style="color:#dc2626;"><?= (int)$resumen['retrasadas'] ?></div>
        <div class="ms-lbl"><i class="fas fa-clock-rotate-left" style="color:#dc2626;margin-right:4px;"></i>Retrasadas</div>
      </div>
    </div>
  </div>

  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-list-ul"></i> Actividades del Cronograma</h6>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <select class="fs" id="filtroEstado" style="width:140px;padding:6px 10px;">
          <option value="">Todos los estados</option>
          <option value="pendiente">Pendiente</option>
          <option value="en_curso">En Curso</option>
          <option value="completada">Completada</option>
          <option value="retrasada">Retrasada</option>
          <option value="cancelada">Cancelada</option>
        </select>
        <?php if (!empty($componentes)): ?>
        <select class="fs" id="filtroComponente" style="width:200px;padding:6px 10px;">
          <option value="">Todos los componentes</option>
          <?php foreach ($componentes as $c): ?>
            <option value="<?= (int)$c['id_componente'] ?>">
              <?= htmlspecialchars(($c['numero_romano'] ? $c['numero_romano'].'. ' : '') . $c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <input type="text" class="fs" id="filtroBuscar" placeholder="Buscar..." style="width:180px;padding:6px 10px;">
      </div>
    </div>
    <div class="card-box-body">
      <table id="tablaCronograma" class="table table-striped table-hover" style="width:100%;font-size:.85rem;">
        <thead>
          <tr>
            <th>#</th>
            <th>Actividad</th>
            <th>Componente</th>
            <th class="text-center">JUN</th>
            <th class="text-center">JUL</th>
            <th class="text-center">AGO</th>
            <th class="text-center">SEP</th>
            <th class="text-center">OCT</th>
            <th class="text-center">NOV</th>
            <th class="text-center">DIC</th>
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

<div class="modal fade" id="modalActividad" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formActividad">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Controller::csrfToken()) ?>">
        <input type="hidden" name="id_actividad" id="id_actividad" value="0">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-calendar-days"></i> <span id="modalActTitle">Nueva Actividad</span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-2">
              <label class="form-label">Orden</label>
              <input type="number" class="form-control" name="numero_orden" min="0" value="0">
            </div>
            <div class="col-md-10">
              <label class="form-label">Actividad <span style="color:#dc2626;">*</span></label>
              <input type="text" class="form-control" name="actividad" maxlength="300" required>
            </div>
            <div class="col-12">
              <label class="form-label">Componente vinculado (opcional)</label>
              <select class="form-select" name="id_componente">
                <option value="0">— Sin vincular —</option>
                <?php foreach ($componentes as $c): ?>
                  <option value="<?= (int)$c['id_componente'] ?>">
                    <?= htmlspecialchars(($c['numero_romano'] ? $c['numero_romano'].'. ' : '') . $c['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Descripción</label>
              <textarea class="form-control" name="descripcion" rows="2"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Meses de ejecución</label>
              <div style="display:flex;flex-wrap:wrap;gap:14px;padding:8px;background:#f9fafb;border-radius:6px;">
                <?php foreach (['jun'=>'Junio','jul'=>'Julio','ago'=>'Agosto','sep'=>'Septiembre','oct'=>'Octubre','nov'=>'Noviembre','dic'=>'Diciembre'] as $k => $label): ?>
                <label style="display:flex;align-items:center;gap:6px;">
                  <input type="checkbox" name="mes_<?= $k ?>" value="1">
                  <span><?= $label ?></span>
                </label>
                <?php endforeach; ?>
              </div>
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
