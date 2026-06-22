<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/componentes_fp.js?v=' . APP_VERSION . '"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">

  <div class="crumb-bar">
    <i class="fas fa-house"></i>
    <span>Inicio</span>
    <i class="fas fa-chevron-right crumb-sep"></i>
    <span class="crumb-pill"><i class="fas fa-layer-group"></i> Componentes</span>
  </div>

  <div class="page-header">
    <div class="page-title">
      <i class="fas fa-layer-group" style="color:var(--primario);margin-right:8px;"></i>Componentes del Programa
      <small>Fortalecimiento de Programas y Proyectos SAG 2026 — <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? 'FPROG') ?></small>
    </div>
    <div>
      <button class="btn-primario" id="btnNuevoComponente">
        <i class="fas fa-plus"></i> Nuevo Componente
      </button>
    </div>
  </div>

  <!-- Mini stats -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="mini-stat">
        <div class="ms-val" data-stat="total"><?= (int)$resumen['total'] ?></div>
        <div class="ms-lbl"><i class="fas fa-layer-group" style="color:var(--primario);margin-right:4px;"></i>Componentes</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#3b82f6;">
        <div class="ms-val" style="color:#3b82f6;" data-stat="en_ejecucion"><?= (int)$resumen['en_ejecucion'] ?></div>
        <div class="ms-lbl"><i class="fas fa-spinner" style="color:#3b82f6;margin-right:4px;"></i>En Ejecución</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#16a34a;">
        <div class="ms-val" style="color:#16a34a;" data-stat="completados"><?= (int)$resumen['completados'] ?></div>
        <div class="ms-lbl"><i class="fas fa-circle-check" style="color:#16a34a;margin-right:4px;"></i>Completados</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="mini-stat" style="border-left-color:#f59e0b;">
        <div class="ms-val" style="color:#f59e0b;" data-stat="pct_ejecucion" data-stat-fmt="pct1"><?= number_format((float)$resumen['pct_ejecucion'], 1) ?>%</div>
        <div class="ms-lbl"><i class="fas fa-chart-line" style="color:#f59e0b;margin-right:4px;"></i>Ejecución Presup.</div>
      </div>
    </div>
  </div>

  <!-- Resumen financiero -->
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <div class="card-box">
        <div class="card-box-header">
          <h6><i class="fas fa-coins"></i> Presupuesto del Programa (L.)</h6>
        </div>
        <div class="card-box-body" style="padding:12px 14px;">
          <div style="display:flex;justify-content:space-between;">
            <div>
              <div style="font-size:.7rem;color:#6b7280;">Asignado</div>
              <div style="font-size:1.1rem;font-weight:700;">L. <span data-stat="presupuesto_total" data-stat-fmt="money2"><?= number_format((float)$resumen['presupuesto_total'], 2) ?></span></div>
            </div>
            <div>
              <div style="font-size:.7rem;color:#6b7280;">Ejecutado</div>
              <div style="font-size:1.1rem;font-weight:700;color:#16a34a;">L. <span data-stat="ejecutado_total" data-stat-fmt="money2"><?= number_format((float)$resumen['ejecutado_total'], 2) ?></span></div>
            </div>
          </div>
          <div style="margin-top:8px;background:#f3f4f6;border-radius:5px;height:8px;overflow:hidden;">
            <div style="background:#16a34a;height:100%;width:<?= min(100, (float)$resumen['pct_ejecucion']) ?>%;"></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card-box">
        <div class="card-box-header">
          <h6><i class="fas fa-list-check"></i> Estado de Componentes</h6>
        </div>
        <div class="card-box-body" style="padding:12px 14px;font-size:.82rem;">
          <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <div><span style="color:#f59e0b;">●</span> Planificados: <strong data-stat="planificados"><?= (int)$resumen['planificados'] ?></strong></div>
            <div><span style="color:#3b82f6;">●</span> En ejecución: <strong data-stat="en_ejecucion"><?= (int)$resumen['en_ejecucion'] ?></strong></div>
            <div><span style="color:#16a34a;">●</span> Completados: <strong data-stat="completados"><?= (int)$resumen['completados'] ?></strong></div>
            <div><span style="color:#6b7280;">●</span> Suspendidos: <strong data-stat="suspendidos"><?= (int)$resumen['suspendidos'] ?></strong></div>
            <div><span style="color:#dc2626;">●</span> Cancelados: <strong data-stat="cancelados"><?= (int)$resumen['cancelados'] ?></strong></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-list-ul"></i> Listado de Componentes</h6>
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <select class="fs" id="filtroEstado" style="width:160px;padding:6px 10px;">
          <option value="">Todos los estados</option>
          <option value="planificado">Planificado</option>
          <option value="en_ejecucion">En Ejecución</option>
          <option value="completado">Completado</option>
          <option value="suspendido">Suspendido</option>
          <option value="cancelado">Cancelado</option>
        </select>
        <select class="fs" id="filtroCategoria" style="width:150px;padding:6px 10px;">
          <option value="">Todas las categorías</option>
          <option value="agricola">Agrícola</option>
          <option value="pecuario">Pecuario</option>
          <option value="transversal">Transversal</option>
          <option value="infraestructura">Infraestructura</option>
          <option value="otro">Otro</option>
        </select>
        <input type="text" class="fs" id="filtroBuscar" placeholder="Buscar..." style="width:180px;padding:6px 10px;">
      </div>
    </div>
    <div class="card-box-body">
      <table id="tablaComponentes" class="table sag-table" style="width:100%;">
        <thead>
          <tr>
            <th>#</th>
            <th>Componente</th>
            <th>Categoría</th>
            <th>Presupuesto</th>
            <th>Ejecutado</th>
            <th>% Ejec</th>
            <th>Meta</th>
            <th>Avance</th>
            <th>% Avance</th>
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
     MODAL — Nuevo / Editar Componente
     ════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalComponente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formComponente">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Controller::csrfToken()) ?>">
        <input type="hidden" name="id_componente" id="id_componente" value="0">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-layer-group"></i> <span id="modalCompTitle">Nuevo Componente</span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-2">
              <label class="form-label">Número (Romano)</label>
              <input type="text" class="form-control" name="numero_romano" maxlength="8" placeholder="I, II, III...">
            </div>
            <div class="col-md-3">
              <label class="form-label">Código</label>
              <input type="text" class="form-control" name="codigo" maxlength="40" placeholder="opcional">
            </div>
            <div class="col-md-7">
              <label class="form-label">Nombre del componente <span style="color:#dc2626;">*</span></label>
              <input type="text" class="form-control" name="nombre" maxlength="200" required>
            </div>
            <div class="col-12">
              <label class="form-label">Descripción</label>
              <textarea class="form-control" name="descripcion" rows="2"></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Categoría <span style="color:#dc2626;">*</span></label>
              <select class="form-select" name="categoria" required>
                <option value="agricola">Agrícola</option>
                <option value="pecuario">Pecuario</option>
                <option value="transversal">Transversal</option>
                <option value="infraestructura">Infraestructura</option>
                <option value="otro" selected>Otro</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Responsable</label>
              <input type="text" class="form-control" name="responsable" maxlength="200">
            </div>
            <div class="col-md-4">
              <label class="form-label">Moneda</label>
              <input type="text" class="form-control" name="moneda" maxlength="8" value="HNL">
            </div>
            <div class="col-md-4">
              <label class="form-label">Presupuesto asignado <span style="color:#dc2626;">*</span></label>
              <input type="number" step="0.01" min="0" class="form-control" name="presupuesto_asignado" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Presupuesto ejecutado</label>
              <input type="number" step="0.01" min="0" class="form-control" name="presupuesto_ejecutado" value="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Medio de verificación</label>
              <input type="text" class="form-control" name="medio_verificacion" maxlength="255">
            </div>
            <div class="col-md-4">
              <label class="form-label">Unidad de medida (meta)</label>
              <input type="text" class="form-control" name="meta_unidad" maxlength="80" placeholder="Ha, productores, m²...">
            </div>
            <div class="col-md-4">
              <label class="form-label">Meta (valor)</label>
              <input type="number" step="0.0001" min="0" class="form-control" name="meta_valor" value="0">
            </div>
            <div class="col-md-4">
              <label class="form-label">Avance (valor)</label>
              <input type="number" step="0.0001" min="0" class="form-control" name="avance_valor" value="0">
            </div>
            <div class="col-md-6">
              <label class="form-label">Fecha de inicio</label>
              <input type="date" class="form-control" name="fecha_inicio">
            </div>
            <div class="col-md-6">
              <label class="form-label">Fecha de fin</label>
              <input type="date" class="form-control" name="fecha_fin">
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
