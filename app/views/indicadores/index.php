<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/indicadores.js?v=' . APP_VERSION . '"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">

  <div class="crumb-bar">
    <i class="fas fa-house"></i>
    <span>Inicio</span>
    <i class="fas fa-chevron-right crumb-sep"></i>
    <span class="crumb-pill"><i class="fas fa-chart-line"></i> Indicadores</span>
  </div>

  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-chart-line" style="color:var(--primario);margin-right:8px;"></i>Indicadores del Programa
      <small>Seguimiento y medición — <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?></small>
    </div>
    <div>
      <button class="btn-primario" id="btnNuevoIndicador">
        <i class="fas fa-plus"></i> Nuevo Indicador
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
        <div class="ms-val" style="color:#3b82f6;"><?= (int)$resumen['producto'] ?></div>
        <div class="ms-lbl"><i class="fas fa-cube" style="color:#3b82f6;margin-right:4px;"></i>Producto</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#8b5cf6;">
        <div class="ms-val" style="color:#8b5cf6;"><?= (int)$resumen['resultado'] ?></div>
        <div class="ms-lbl"><i class="fas fa-check-double" style="color:#8b5cf6;margin-right:4px;"></i>Resultado</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#16a34a;">
        <div class="ms-val" style="color:#16a34a;"><?= (int)$resumen['impacto'] ?></div>
        <div class="ms-lbl"><i class="fas fa-bullseye" style="color:#16a34a;margin-right:4px;"></i>Impacto</div>
      </div>
    </div>
  </div>

  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-list-ul"></i> Listado de Indicadores</h6>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <select class="fs" id="filtroTipo" style="width:140px;padding:6px 10px;">
          <option value="">Todos los tipos</option>
          <option value="producto">Producto</option>
          <option value="resultado">Resultado</option>
          <option value="impacto">Impacto</option>
          <option value="proceso">Proceso</option>
          <option value="otro">Otro</option>
        </select>
        <select class="fs" id="filtroEstado" style="width:140px;padding:6px 10px;">
          <option value="">Todos los estados</option>
          <option value="activo">Activo</option>
          <option value="suspendido">Suspendido</option>
          <option value="reformulado">Reformulado</option>
          <option value="retirado">Retirado</option>
        </select>
        <select class="fs" id="filtroMeta" style="width:180px;padding:6px 10px;">
          <option value="">Todas las metas</option>
          <?php foreach ($metas as $m): ?>
            <option value="<?= (int)$m['id_meta'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
          <?php endforeach; ?>
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
      <table id="tablaIndicadores" class="table sag-table" style="width:100%;">
        <thead>
          <tr>
            <th>Código</th>
            <th>Nombre</th>
            <th>Tipo</th>
            <?php if (!empty($componentes)): ?><th>Componente</th><?php endif; ?>
            <th>Meta vinculada</th>
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
     MODAL — Nuevo / Editar Indicador
     ════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalIndicador" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formIndicador">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Controller::csrfToken()) ?>">
        <input type="hidden" name="id_indicador" id="id_indicador" value="0">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-chart-line"></i> <span id="modalIndTitle">Nuevo Indicador</span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Código</label>
              <input type="text" class="form-control" name="codigo" maxlength="40" placeholder="IND-2026-001">
            </div>
            <div class="col-md-8">
              <label class="form-label">Nombre <span style="color:#dc2626;">*</span></label>
              <input type="text" class="form-control" name="nombre" maxlength="200" required>
            </div>
            <div class="col-12">
              <label class="form-label">Descripción</label>
              <textarea class="form-control" name="descripcion" rows="2"></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Tipo <span style="color:#dc2626;">*</span></label>
              <select class="form-select" name="tipo" required>
                <option value="producto">Producto</option>
                <option value="resultado">Resultado</option>
                <option value="impacto">Impacto</option>
                <option value="proceso">Proceso</option>
                <option value="otro">Otro</option>
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label">Meta vinculada (opcional)</label>
              <select class="form-select" name="id_meta">
                <option value="0">— Sin vincular —</option>
                <?php foreach ($metas as $m): ?>
                  <option value="<?= (int)$m['id_meta'] ?>"><?= htmlspecialchars(($m['codigo'] ? $m['codigo'].' · ' : '') . $m['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
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
            <div class="col-md-4">
              <label class="form-label">Unidad de medida</label>
              <input type="text" class="form-control" name="unidad_medida" maxlength="80">
            </div>
            <div class="col-md-4">
              <label class="form-label">Línea base</label>
              <input type="number" step="0.0001" class="form-control" name="linea_base">
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
              <label class="form-label">Frecuencia <span style="color:#dc2626;">*</span></label>
              <select class="form-select" name="frecuencia_medicion" required>
                <option value="diaria">Diaria</option>
                <option value="semanal">Semanal</option>
                <option value="mensual" selected>Mensual</option>
                <option value="trimestral">Trimestral</option>
                <option value="semestral">Semestral</option>
                <option value="anual">Anual</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Última medición</label>
              <input type="date" class="form-control" name="ultima_medicion">
            </div>
            <div class="col-md-6">
              <label class="form-label">Responsable</label>
              <input type="text" class="form-control" name="responsable" maxlength="200">
            </div>
            <div class="col-md-6">
              <label class="form-label">Fuente de datos</label>
              <input type="text" class="form-control" name="fuente_datos" maxlength="255">
            </div>
            <div class="col-12">
              <label class="form-label">Fórmula de cálculo</label>
              <textarea class="form-control" name="formula" rows="2"></textarea>
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
