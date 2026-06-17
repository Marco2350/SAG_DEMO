<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/metas.js?v=' . APP_VERSION . '"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">

  <div class="crumb-bar">
    <i class="fas fa-house"></i>
    <span>Inicio</span>
    <i class="fas fa-chevron-right crumb-sep"></i>
    <span class="crumb-pill"><i class="fas fa-bullseye"></i> Metas</span>
  </div>

  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-bullseye" style="color:var(--primario);margin-right:8px;"></i>Metas del Programa
      <small>Planificación y seguimiento — <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?></small>
    </div>
    <div>
      <button class="btn-primario" id="btnNuevaMeta">
        <i class="fas fa-plus"></i> Nueva Meta
      </button>
    </div>
  </div>

  <!-- Mini stats -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="ms-val" data-stat="total"><?= (int)$resumen['total'] ?></div>
        <div class="ms-lbl"><i class="fas fa-list-check" style="color:var(--primario);margin-right:4px;"></i>Total</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#3b82f6;">
        <div class="ms-val" style="color:#3b82f6;" data-stat="en_progreso"><?= (int)$resumen['en_progreso'] ?></div>
        <div class="ms-lbl"><i class="fas fa-spinner" style="color:#3b82f6;margin-right:4px;"></i>En Progreso</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#16a34a;">
        <div class="ms-val" style="color:#16a34a;" data-stat="cumplidas"><?= (int)$resumen['cumplidas'] ?></div>
        <div class="ms-lbl"><i class="fas fa-circle-check" style="color:#16a34a;margin-right:4px;"></i>Cumplidas</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#f59e0b;">
        <div class="ms-val" style="color:#f59e0b;" data-stat="avance_promedio" data-stat-fmt="pct1"><?= number_format((float)$resumen['avance_promedio'], 1) ?>%</div>
        <div class="ms-lbl"><i class="fas fa-chart-line" style="color:#f59e0b;margin-right:4px;"></i>Avance Promedio</div>
      </div>
    </div>
  </div>

  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-list-ul"></i> Listado de Metas</h6>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <select class="fs" id="filtroEstado" style="width:160px;padding:6px 10px;">
          <option value="">Todos los estados</option>
          <option value="planificada">Planificada</option>
          <option value="en_progreso">En Progreso</option>
          <option value="cumplida">Cumplida</option>
          <option value="no_cumplida">No Cumplida</option>
          <option value="reformulada">Reformulada</option>
        </select>
        <select class="fs" id="filtroPeriodo" style="width:140px;padding:6px 10px;">
          <option value="">Todos los periodos</option>
          <option value="anual">Anual</option>
          <option value="semestral">Semestral</option>
          <option value="trimestral">Trimestral</option>
          <option value="mensual">Mensual</option>
        </select>
        <?php if (!empty($componentes)): ?>
        <select class="fs" id="filtroComponente" style="width:200px;padding:6px 10px;" title="Componente FPROG">
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
      <table id="tablaMetas" class="table sag-table" style="width:100%;">
        <thead>
          <tr>
            <th>Código</th>
            <th>Nombre</th>
            <?php if (!empty($componentes)): ?><th>Componente</th><?php endif; ?>
            <th>Periodo</th>
            <th>Objetivo</th>
            <th>Actual</th>
            <th>Avance</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     MODAL — Nueva / Editar Meta
     ════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalMeta" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formMeta">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Controller::csrfToken()) ?>">
        <input type="hidden" name="id_meta" id="id_meta" value="0">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-bullseye"></i> <span id="modalMetaTitle">Nueva Meta</span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Código</label>
              <input type="text" class="form-control" name="codigo" maxlength="40" placeholder="M-2026-001">
            </div>
            <div class="col-md-8">
              <label class="form-label">Nombre <span style="color:#dc2626;">*</span></label>
              <input type="text" class="form-control" name="nombre" maxlength="200" required>
            </div>
            <?php if (!empty($componentes)): ?>
            <div class="col-12">
              <label class="form-label">Componente FPROG (opcional)</label>
              <select class="form-select" name="id_componente">
                <option value="0">— Sin vincular —</option>
                <?php foreach ($componentes as $c): ?>
                  <option value="<?= (int)$c['id_componente'] ?>">
                    <?= htmlspecialchars(($c['numero_romano'] ? $c['numero_romano'].'. ' : '') . $c['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <?php endif; ?>
            <div class="col-12">
              <label class="form-label">Descripción</label>
              <textarea class="form-control" name="descripcion" rows="2"></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Unidad de medida</label>
              <input type="text" class="form-control" name="unidad_medida" maxlength="80" placeholder="productores, hectáreas...">
            </div>
            <div class="col-md-4">
              <label class="form-label">Valor objetivo <span style="color:#dc2626;">*</span></label>
              <input type="number" step="0.0001" min="0" class="form-control" name="valor_objetivo" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Valor actual</label>
              <input type="number" step="0.0001" min="0" class="form-control" name="valor_actual" value="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Periodo <span style="color:#dc2626;">*</span></label>
              <select class="form-select" name="periodo" required>
                <option value="anual">Anual</option>
                <option value="semestral">Semestral</option>
                <option value="trimestral">Trimestral</option>
                <option value="mensual">Mensual</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Fecha inicio</label>
              <input type="date" class="form-control" name="fecha_inicio">
            </div>
            <div class="col-md-4">
              <label class="form-label">Fecha fin</label>
              <input type="date" class="form-control" name="fecha_fin">
            </div>
            <div class="col-md-6">
              <label class="form-label">Responsable</label>
              <input type="text" class="form-control" name="responsable" maxlength="200">
            </div>
            <div class="col-md-6">
              <label class="form-label">Fuente de verificación</label>
              <input type="text" class="form-control" name="fuente_verificacion" maxlength="255">
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
