<?php
$cssExtra = '<style>
/* ── PRESUPUESTO MODULE STYLES ─────────────────────────────── */
.section-panel{display:none;}.section-panel.active{display:block;}

/* KPI Cards */
.kpi-pres{background:#fff;border-radius:12px;border:1.5px solid var(--borde);padding:18px 20px;display:flex;align-items:center;gap:14px;}
.kpi-pres .kpi-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;}
.kpi-pres .kpi-val{font-size:1.4rem;font-weight:800;line-height:1;}
.kpi-pres .kpi-lbl{font-size:.74rem;color:#888;margin-top:3px;}

/* Líneas presupuestarias */
.linea-row{background:#fff;border:1.5px solid var(--borde);border-radius:10px;padding:14px 16px;margin-bottom:10px;transition:box-shadow .2s;}
.linea-row:hover{box-shadow:0 4px 14px rgba(84,102,142,.1);}
.linea-codigo{font-size:.72rem;font-weight:700;background:#eef0f7;color:var(--primario);padding:2px 8px;border-radius:10px;margin-right:8px;}
.linea-nombre{font-size:.9rem;font-weight:700;color:#222;}
.linea-tipo{font-size:.72rem;color:#888;}
.prog-budget{height:8px;background:#e5e7eb;border-radius:4px;overflow:hidden;margin:8px 0 4px;}
.prog-budget-fill{height:8px;border-radius:4px;transition:width .4s;}
.prog-ejecutado{background:#16a34a;}
.prog-comprometido{background:#f59e0b;}
.montos-row{display:flex;gap:14px;flex-wrap:wrap;margin-top:6px;}
.monto-item{font-size:.75rem;color:#555;}
.monto-item strong{font-size:.82rem;color:#222;}

/* Estados badge */
.badge-pres{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.69rem;font-weight:700;}
.bp-borrador{background:#f1f5f9;color:#64748b;}
.bp-activo{background:#d1fae5;color:#065f46;}
.bp-cerrado{background:#fee2e2;color:#991b1b;}
.bp-pendiente{background:#fef9c3;color:#854d0e;}
.bp-visto{background:#dbeafe;color:#1e40af;}
.bp-aprobada{background:#d1fae5;color:#065f46;}
.bp-rechazada{background:#fee2e2;color:#991b1b;}
.bp-liquidada{background:#f3e8ff;color:#6b21a8;}
.bp-ejecutada{background:#d1fae5;color:#065f46;}
.bp-solicitada{background:#fef9c3;color:#854d0e;}
.bp-cotizando{background:#dbeafe;color:#1e40af;}
.bp-anulada{background:#f1f5f9;color:#64748b;}

/* Tabla de datos */
.data-table{width:100%;border-collapse:collapse;font-size:.8rem;}
.data-table thead th{background:var(--primario);color:#fff;padding:9px 12px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap;text-align:left;}
.data-table tbody tr{border-bottom:1px solid #f0f0f0;}
.data-table tbody tr:hover{background:#f8faff;}
.data-table tbody td{padding:8px 12px;vertical-align:middle;}
.data-table tbody tr:nth-child(even){background:#fafbfc;}

/* Modales */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1050;align-items:center;justify-content:center;padding:16px;}
.modal-overlay.show{display:flex;}
.modal-box{background:#fff;border-radius:14px;width:100%;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.2);}
.modal-box.modal-lg{max-width:780px;}
.modal-box.modal-md{max-width:560px;}
.modal-box.modal-sm{max-width:420px;}
.modal-head{padding:16px 20px;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between;}
.modal-head h5{font-size:.96rem;font-weight:700;color:#1a1a1a;margin:0;}
.modal-body-inner{padding:20px;overflow-y:auto;flex:1;}
.modal-foot{padding:12px 20px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:8px;}
.btn-close-x{background:none;border:none;font-size:1.2rem;color:#999;cursor:pointer;line-height:1;padding:0;}
.btn-close-x:hover{color:#333;}

/* Form elements */
.fl-label{font-size:.74rem;font-weight:700;color:#555;margin-bottom:4px;}
.fc{width:100%;padding:7px 10px;border:1.5px solid var(--borde);border-radius:7px;font-size:.82rem;color:var(--texto);background:#fff;outline:none;box-sizing:border-box;}
.fc:focus{border-color:var(--primario);}
.req{color:#dc2626;}
.form-section{font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--primario);border-bottom:1.5px solid var(--primario);padding-bottom:4px;margin:16px 0 12px;}

/* Buttons */
.btn-prim{background:var(--primario);color:#fff;border:none;padding:8px 18px;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:opacity .15s;}
.btn-prim:hover{opacity:.88;}
.btn-sec{background:#f0f2f8;color:var(--primario);border:1.5px solid var(--primario);padding:7px 16px;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .15s;}
.btn-sec:hover{background:var(--primario);color:#fff;}
.btn-danger{background:#dc2626;color:#fff;border:none;padding:7px 14px;border-radius:8px;font-size:.8rem;font-weight:600;cursor:pointer;}
.btn-gris{background:#f1f5f9;color:#475569;border:1.5px solid #dde1e9;padding:7px 16px;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer;}
.btn-sm-icon{width:30px;height:30px;border-radius:7px;border:1.5px solid var(--borde);background:#fff;color:#555;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:.8rem;transition:all .15s;}
.btn-sm-icon:hover{border-color:var(--primario);color:var(--primario);}
.btn-approve{background:#16a34a;color:#fff;border:none;padding:5px 12px;border-radius:7px;font-size:.76rem;font-weight:700;cursor:pointer;}
.btn-reject{background:#dc2626;color:#fff;border:none;padding:5px 12px;border-radius:7px;font-size:.76rem;font-weight:700;cursor:pointer;}

/* Timeline de estados */
.estado-flow{display:flex;align-items:center;gap:4px;flex-wrap:wrap;margin:8px 0;}
.ef-step{padding:4px 10px;border-radius:20px;font-size:.68rem;font-weight:700;background:#f1f5f9;color:#94a3b8;}
.ef-step.done{background:#d1fae5;color:#065f46;}
.ef-step.current{background:var(--primario);color:#fff;}
.ef-arrow{color:#cbd5e1;font-size:.7rem;}

/* Alert boxes */
.alert-info{background:#eff6ff;border:1.5px solid #93c5fd;border-radius:10px;padding:10px 14px;font-size:.82rem;color:#1e40af;margin-bottom:12px;}
.alert-warn{background:#fffbeb;border:1.5px solid #fcd34d;border-radius:10px;padding:10px 14px;font-size:.82rem;color:#92400e;margin-bottom:12px;}

/* Resumen bar */
.presup-resumen-bar{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;background:#f8f9fb;border-radius:12px;padding:14px;margin-bottom:16px;}
.prb-item{text-align:center;}
.prb-val{font-size:1.1rem;font-weight:800;}
.prb-lbl{font-size:.7rem;color:#888;margin-top:2px;}
</style>';
?>
<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/presupuesto.js"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<?php
// Datos PHP para JS
$pdata = $presupuesto ? json_encode($presupuesto, JSON_UNESCAPED_UNICODE) : 'null';
$ldata = json_encode($lineas, JSON_UNESCAPED_UNICODE);
$plist = json_encode($presupuestos, JSON_UNESCAPED_UNICODE);
$perms = json_encode(['esAdmin'=>$esAdmin,'esJefe'=>$esJefe,'rol'=>$rolActual], JSON_UNESCAPED_UNICODE);
?>
<script>
const SAG_PRES = {
    presupuesto: <?= $pdata ?>,
    lineas: <?= $ldata ?>,
    presupuestos: <?= $plist ?>,
    perms: <?= $perms ?>,
    kpis: <?= json_encode($kpis) ?>,
    conteos: <?= json_encode($conteos) ?>
};
</script>

<div class="content">

  <!-- Encabezado -->
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="page-title">
      <i class="fas fa-scale-balanced me-2" style="color:var(--primario);"></i>Ejecución Presupuestaria
      <small><?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?> &mdash; Gestión Financiera</small>
    </div>
    <?php if ($esAdmin): ?>
    <button class="btn-prim" id="btnNuevoPresupuesto">
      <i class="fas fa-plus"></i> Nuevo Presupuesto
    </button>
    <?php endif; ?>
  </div>

  <?php if (!$presupuesto): ?>
  <!-- Sin presupuesto activo -->
  <div class="alert-warn d-flex align-items-center gap-2">
    <i class="fas fa-triangle-exclamation fa-lg"></i>
    <div>No hay un presupuesto activo para este programa.
    <?php if ($esAdmin): ?> Cree y autorice uno para habilitar todas las funciones.<?php endif; ?></div>
  </div>
  <?php else: ?>
  <!-- Presupuesto activo — KPIs -->
  <div class="alert-info mb-3">
    <i class="fas fa-circle-check me-1"></i>
    <strong>Presupuesto activo:</strong> <?= htmlspecialchars($presupuesto['nombre']) ?>
    &mdash; Año <?= $presupuesto['anio'] ?>
    &mdash; Moneda: <strong><?= $presupuesto['moneda'] ?></strong>
    <?php if ($presupuesto['moneda'] === 'USD'): ?>
    (Tipo de cambio: L. <?= number_format($presupuesto['tipo_cambio'],4) ?>)
    <?php endif; ?>
    <?php if ($esAdmin): ?>
    <a href="#" id="linkVerLineas" style="margin-left:10px;font-size:.8rem;">Ver líneas <i class="fas fa-arrow-right"></i></a>
    <?php endif; ?>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="kpi-pres">
        <div class="kpi-icon" style="background:#eef0f7;color:var(--primario);"><i class="fas fa-coins"></i></div>
        <div>
          <div class="kpi-val" id="kpiAprobado"><?= number_format($kpis['aprobado'],2) ?></div>
          <div class="kpi-lbl">Presupuesto Aprobado</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="kpi-pres">
        <div class="kpi-icon" style="background:#fef9c3;color:#d97706;"><i class="fas fa-clock"></i></div>
        <div>
          <div class="kpi-val" style="color:#d97706;" id="kpiComprometido"><?= number_format($kpis['comprometido'],2) ?></div>
          <div class="kpi-lbl">Comprometido</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="kpi-pres">
        <div class="kpi-icon" style="background:#d1fae5;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
        <div>
          <div class="kpi-val" style="color:#16a34a;" id="kpiEjecutado"><?= number_format($kpis['ejecutado'],2) ?></div>
          <div class="kpi-lbl">Ejecutado</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="kpi-pres">
        <div class="kpi-icon" style="background:#f3e8ff;color:#8b5cf6;"><i class="fas fa-piggy-bank"></i></div>
        <div>
          <div class="kpi-val" style="color:#8b5cf6;" id="kpiSaldo"><?= number_format($kpis['saldo'],2) ?></div>
          <div class="kpi-lbl">Saldo Disponible</div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ALERTAS DE PENDIENTES (para jefe/admin) -->
  <?php if ($esJefe && ($conteos['compras_pendientes'] > 0 || $conteos['viaticos_pendientes'] > 0 || $conteos['viaticos_visto'] > 0)): ?>
  <div style="background:#fff;border:1.5px solid #fcd34d;border-radius:10px;padding:12px 16px;margin-bottom:14px;display:flex;flex-wrap:wrap;gap:14px;align-items:center;">
    <i class="fas fa-bell" style="color:#d97706;"></i>
    <?php if ($conteos['compras_pendientes']): ?>
    <span style="font-size:.82rem;">Compras pendientes de revisión: <strong style="color:#d97706;"><?= $conteos['compras_pendientes'] ?></strong></span>
    <?php endif; ?>
    <?php if ($conteos['viaticos_pendientes']): ?>
    <span style="font-size:.82rem;">Viáticos esperando visto bueno: <strong style="color:#d97706;"><?= $conteos['viaticos_pendientes'] ?></strong></span>
    <?php endif; ?>
    <?php if ($conteos['viaticos_visto'] && $esAdmin): ?>
    <span style="font-size:.82rem;">Viáticos con visto bueno (pendientes de aprobación): <strong style="color:var(--primario);"><?= $conteos['viaticos_visto'] ?></strong></span>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- PESTAÑAS DEL MÓDULO -->
  <div class="card-box">
    <div class="mode-tabs" style="border-bottom:1px solid #eee;">
      <button class="mode-tab active" data-tab="Presupuesto"><i class="fas fa-chart-pie me-1"></i>Presupuesto</button>
      <button class="mode-tab" data-tab="Compras"><i class="fas fa-cart-shopping me-1"></i>Compras</button>
      <button class="mode-tab" data-tab="Viaticos"><i class="fas fa-road me-1"></i>Viáticos</button>
      <button class="mode-tab" data-tab="Gastos"><i class="fas fa-receipt me-1"></i>Gastos Varios</button>
      <button class="mode-tab" data-tab="Documentos"><i class="fas fa-folder-open me-1"></i>Documentos</button>
    </div>

    <!-- ─── PANEL: PRESUPUESTO / LÍNEAS ──────────────────── -->
    <div class="section-panel active" id="tabPresupuesto">
      <div class="card-box-body">

        <!-- Lista de todos los presupuestos -->
        <div class="form-section">Todos los Presupuestos</div>
        <div style="overflow-x:auto;margin-bottom:20px;">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th><th>Nombre</th><th>Año</th><th>Moneda</th>
                <th>Monto Total</th><th>Estado</th><th>Autorizado por</th><th>Fecha Autorización</th>
                <?php if ($esAdmin): ?><th>Acciones</th><?php endif; ?>
              </tr>
            </thead>
            <tbody id="tbodyPresupuestos">
              <?php if (empty($presupuestos)): ?>
              <tr><td colspan="9" style="text-align:center;padding:20px;color:#aaa;">No hay presupuestos registrados aún.</td></tr>
              <?php else: ?>
              <?php foreach ($presupuestos as $i => $p): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td>
                  <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                  <?php if ($p['estado'] === 'activo'): ?>
                  <span style="font-size:.68rem;background:#d1fae5;color:#065f46;padding:2px 7px;border-radius:10px;margin-left:6px;font-weight:700;">ACTIVO</span>
                  <?php endif; ?>
                </td>
                <td><?= $p['anio'] ?></td>
                <td><?= $p['moneda'] ?></td>
                <td><strong>L. <?= number_format($p['monto_total'],2) ?></strong></td>
                <td>
                  <span class="badge-pres bp-<?= $p['estado'] === 'activo' ? 'aprobada' : ($p['estado'] === 'cerrado' ? 'cerrado' : 'borrador') ?>">
                    <?= ucfirst($p['estado']) ?>
                  </span>
                </td>
                <td style="font-size:.78rem;"><?= htmlspecialchars($p['nombre_autorizador'] ?? '—') ?></td>
                <td style="font-size:.78rem;"><?= $p['fecha_autorizacion'] ? date('d/m/Y H:i', strtotime($p['fecha_autorizacion'])) : '—' ?></td>
                <?php if ($esAdmin): ?>
                <td>
                  <button class="btn-sm-icon btn-edit-presupuesto" data-id="<?= $p['id_presupuesto'] ?>" title="Editar"><i class="fas fa-pen"></i></button>
                  <?php if ($p['estado'] === 'borrador'): ?>
                  <button class="btn-sm-icon ms-1 btn-activar-presupuesto" data-id="<?= $p['id_presupuesto'] ?>" data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>" title="Activar" style="color:#16a34a;border-color:#16a34a;">
                    <i class="fas fa-circle-check"></i>
                  </button>
                  <?php endif; ?>
                  <button class="btn-sm-icon ms-1 btn-ver-lineas" data-id="<?= $p['id_presupuesto'] ?>" data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES) ?>" title="Ver/Editar líneas" style="color:var(--primario);border-color:var(--primario);">
                    <i class="fas fa-list"></i>
                  </button>
                </td>
                <?php endif; ?>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Líneas del presupuesto seleccionado -->
        <div id="seccionLineas" style="display:none;">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="form-section" style="margin:0;" id="tituloLineasSec">Líneas Presupuestarias</div>
            <?php if ($esAdmin): ?>
            <button class="btn-prim" id="btnNuevaLinea" style="font-size:.78rem;padding:6px 14px;">
              <i class="fas fa-plus"></i> Agregar Línea
            </button>
            <?php endif; ?>
          </div>
          <div id="lineasRender"></div>
        </div>

      </div>
    </div>

    <!-- ─── PANEL: COMPRAS ────────────────────────────────── -->
    <div class="section-panel" id="tabCompras">
      <div class="card-box-body">
        <div class="d-flex gap-2 mb-3 flex-wrap">
          <button class="btn-prim" id="btnNuevaCompra"><i class="fas fa-plus"></i> Nueva Solicitud de Compra</button>
          <select class="fc" id="filtroEstadoCompra" style="max-width:180px;">
            <option value="">Todos los estados</option>
            <option value="borrador">Borrador</option>
            <option value="solicitada">Solicitada</option>
            <option value="cotizando">En cotización</option>
            <option value="aprobada">Aprobada</option>
            <option value="ejecutada">Ejecutada</option>
            <option value="anulada">Anulada</option>
          </select>
          <button class="btn-sec" id="btnFiltrarCompras"><i class="fas fa-filter"></i> Filtrar</button>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th><th>No. Solicitud</th><th>Descripción</th><th>Línea</th>
                <th>Monto Est.</th><th>Proveedor</th><th>Solicitante</th>
                <th>Fecha</th><th>Estado</th><th>Acciones</th>
              </tr>
            </thead>
            <tbody id="tbodyCompras"><tr><td colspan="10" style="text-align:center;padding:20px;color:#aaa;">Cargando…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ─── PANEL: VIÁTICOS ───────────────────────────────── -->
    <div class="section-panel" id="tabViaticos">
      <div class="card-box-body">
        <div class="d-flex gap-2 mb-3 flex-wrap">
          <button class="btn-prim" id="btnNuevoViatico"><i class="fas fa-plus"></i> Nueva Solicitud de Viáticos</button>
          <select class="fc" id="filtroEstadoViatico" style="max-width:200px;">
            <option value="">Todos los estados</option>
            <option value="pendiente">Pendiente</option>
            <option value="visto_bueno">Con visto bueno</option>
            <option value="aprobada">Aprobada</option>
            <option value="rechazada">Rechazada</option>
            <option value="liquidada">Liquidada</option>
          </select>
          <button class="btn-sec" id="btnFiltrarViaticos"><i class="fas fa-filter"></i> Filtrar</button>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th><th>No.</th><th>Solicitante</th><th>Destino</th>
                <th>Fechas</th><th>Días</th><th>Monto</th><th>Estado</th><th>Acciones</th>
              </tr>
            </thead>
            <tbody id="tbodyViaticos"><tr><td colspan="9" style="text-align:center;padding:20px;color:#aaa;">Cargando…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ─── PANEL: GASTOS VARIOS ──────────────────────────── -->
    <div class="section-panel" id="tabGastos">
      <div class="card-box-body">
        <div class="d-flex gap-2 mb-3">
          <button class="btn-prim" id="btnNuevoGasto"><i class="fas fa-plus"></i> Registrar Gasto</button>
        </div>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th><th>Descripción</th><th>Tipo</th><th>Línea</th>
                <th>Monto</th><th>Fecha</th><th>Beneficiario/Proveedor</th>
                <th>No. Documento</th><th>Estado</th><th>Acciones</th>
              </tr>
            </thead>
            <tbody id="tbodyGastos"><tr><td colspan="10" style="text-align:center;padding:20px;color:#aaa;">Cargando…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ─── PANEL: DOCUMENTOS ─────────────────────────────── -->
    <div class="section-panel" id="tabDocumentos">
      <div class="card-box-body">
        <?php if ($esAdmin): ?>
        <div class="d-flex gap-2 mb-3">
          <button class="btn-prim" id="btnNuevoDoc"><i class="fas fa-upload"></i> Subir Documento</button>
        </div>
        <?php endif; ?>
        <div style="overflow-x:auto;">
          <table class="data-table">
            <thead>
              <tr>
                <th>#</th><th>Tipo</th><th>Nombre</th><th>Descripción</th>
                <th>Fecha</th><th>Tamaño</th><th>Vigente</th><th>Subido por</th><th>Acciones</th>
              </tr>
            </thead>
            <tbody id="tbodyDocumentos"><tr><td colspan="9" style="text-align:center;padding:20px;color:#aaa;">Cargando…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div><!-- /card-box -->

</div><!-- /content -->

<!-- ══════════════ MODALES ══════════════ -->

<!-- Modal Presupuesto -->
<div class="modal-overlay" id="modalPresupuesto">
  <div class="modal-box modal-lg">
    <div class="modal-head">
      <h5 id="tituloPresupuesto"><i class="fas fa-coins me-2"></i>Nuevo Presupuesto</h5>
      <button class="btn-close-x" onclick="cerrarModal('modalPresupuesto')">×</button>
    </div>
    <div class="modal-body-inner">
      <form id="formPresupuesto" enctype="multipart/form-data">
        <input type="hidden" name="id_presupuesto" id="fPresId" value="0"/>
        <div class="row g-3">
          <div class="col-md-8">
            <div class="fl-label">Nombre del Presupuesto <span class="req">*</span></div>
            <input type="text" name="nombre" id="fPresNombre" class="fc" placeholder="Ej. Presupuesto Operativo 2025"/>
          </div>
          <div class="col-md-4">
            <div class="fl-label">Año <span class="req">*</span></div>
            <input type="number" name="anio" id="fPresAnio" class="fc" value="<?= date('Y') ?>" min="2020" max="2040"/>
          </div>
          <div class="col-12">
            <div class="fl-label">Descripción / Contexto</div>
            <textarea name="descripcion" id="fPresDesc" class="fc" rows="2" placeholder="Contexto del presupuesto…"></textarea>
          </div>
          <div class="col-md-4">
            <div class="fl-label">Moneda <span class="req">*</span></div>
            <select name="moneda" id="fPresMoneda" class="fc">
              <option value="HNL">Lempiras (HNL)</option>
              <option value="USD">Dólares (USD)</option>
            </select>
          </div>
          <div class="col-md-4" id="rowTipoCambio" style="display:none;">
            <div class="fl-label">Tipo de Cambio (L. por $1)</div>
            <input type="number" name="tipo_cambio" id="fPresTipoCambio" class="fc" step="0.0001" value="24.5000"/>
          </div>
          <div class="col-md-4">
            <div class="fl-label">Estado</div>
            <select name="estado" id="fPresEstado" class="fc">
              <option value="borrador">Borrador</option>
              <option value="activo">Activo</option>
              <option value="cerrado">Cerrado</option>
            </select>
          </div>
          <div class="col-12">
            <div class="fl-label">Documento de Respaldo (acta, acuerdo, etc.)</div>
            <input type="file" name="doc_respaldo" class="fc" accept=".pdf,.doc,.docx"/>
            <div id="docRespaldoActual" style="font-size:.76rem;color:#888;margin-top:4px;"></div>
          </div>
          <div class="col-12">
            <div class="fl-label">Observaciones</div>
            <textarea name="observaciones" id="fPresObs" class="fc" rows="2"></textarea>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-foot">
      <button class="btn-gris" onclick="cerrarModal('modalPresupuesto')">Cancelar</button>
      <button class="btn-prim" id="btnGuardarPresupuesto"><i class="fas fa-floppy-disk me-1"></i>Guardar</button>
    </div>
  </div>
</div>

<!-- Modal Autorizar Presupuesto -->
<div class="modal-overlay" id="modalAutorizar">
  <div class="modal-box modal-sm">
    <div class="modal-head">
      <h5><i class="fas fa-stamp me-2"></i>Autorizar Presupuesto</h5>
      <button class="btn-close-x" onclick="cerrarModal('modalAutorizar')">×</button>
    </div>
    <div class="modal-body-inner">
      <form id="formAutorizar" enctype="multipart/form-data">
        <input type="hidden" name="id" id="fAutorizarId"/>
        <div class="alert-warn"><i class="fas fa-triangle-exclamation me-1"></i>Esto activará este presupuesto y cerrará cualquier otro activo. Esta acción requiere su firma como coordinador.</div>
        <div class="fl-label">Documento de Autorización (acta de comité) <span class="req">*</span></div>
        <input type="file" name="doc_autorizacion" class="fc" accept=".pdf,.doc,.docx"/>
      </form>
    </div>
    <div class="modal-foot">
      <button class="btn-gris" onclick="cerrarModal('modalAutorizar')">Cancelar</button>
      <button class="btn-prim" id="btnConfirmarAutorizar" style="background:#16a34a;"><i class="fas fa-check me-1"></i>Autorizar y Activar</button>
    </div>
  </div>
</div>

<!-- Modal Línea Presupuestaria -->
<div class="modal-overlay" id="modalLinea">
  <div class="modal-box modal-md">
    <div class="modal-head">
      <h5 id="tituloLinea"><i class="fas fa-layer-group me-2"></i>Nueva Línea Presupuestaria</h5>
      <button class="btn-close-x" onclick="cerrarModal('modalLinea')">×</button>
    </div>
    <div class="modal-body-inner">
      <form id="formLinea">
        <input type="hidden" name="id_linea" id="fLineaId" value="0"/>
        <input type="hidden" name="id_presupuesto" id="fLineaPresId"/>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="fl-label">Código</div>
            <input type="text" name="codigo" id="fLineaCodigo" class="fc" placeholder="Ej. L-01"/>
          </div>
          <div class="col-md-8">
            <div class="fl-label">Nombre <span class="req">*</span></div>
            <input type="text" name="nombre" id="fLineaNombre" class="fc" placeholder="Nombre de la línea"/>
          </div>
          <div class="col-md-6">
            <div class="fl-label">Tipo</div>
            <select name="tipo" id="fLineaTipo" class="fc">
              <option value="compras">Compras</option>
              <option value="viaticos">Viáticos</option>
              <option value="gastos">Gastos Operativos</option>
              <option value="personal">Personal</option>
              <option value="equipos">Equipos</option>
              <option value="servicios">Servicios</option>
              <option value="otro">Otro</option>
            </select>
          </div>
          <div class="col-md-6">
            <div class="fl-label">Monto Aprobado <span class="req">*</span></div>
            <input type="number" name="monto_aprobado" id="fLineaMonto" class="fc" step="0.01" placeholder="0.00"/>
          </div>
          <div class="col-md-4">
            <div class="fl-label">Orden</div>
            <input type="number" name="orden" id="fLineaOrden" class="fc" value="0" min="0"/>
          </div>
          <div class="col-12">
            <div class="fl-label">Descripción</div>
            <textarea name="descripcion" id="fLineaDesc" class="fc" rows="2"></textarea>
          </div>
          <div class="col-12" id="rowJustAjuste" style="display:none;">
            <div class="fl-label">Justificación del Ajuste <span class="req">*</span></div>
            <textarea name="justificacion_ajuste" id="fLineaJust" class="fc" rows="2" placeholder="Explique el motivo del cambio de monto…"></textarea>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-foot">
      <button class="btn-gris" onclick="cerrarModal('modalLinea')">Cancelar</button>
      <button class="btn-prim" id="btnGuardarLinea"><i class="fas fa-floppy-disk me-1"></i>Guardar</button>
    </div>
  </div>
</div>

<!-- Modal Compra -->
<div class="modal-overlay" id="modalCompra">
  <div class="modal-box modal-lg">
    <div class="modal-head">
      <h5 id="tituloCompra"><i class="fas fa-cart-shopping me-2"></i>Solicitud de Compra</h5>
      <button class="btn-close-x" onclick="cerrarModal('modalCompra')">×</button>
    </div>
    <div class="modal-body-inner">
      <form id="formCompra" enctype="multipart/form-data">
        <input type="hidden" name="id_compra" id="fCompraId" value="0"/>
        <div class="form-section">Información General</div>
        <div class="row g-3">
          <div class="col-md-8">
            <div class="fl-label">Descripción de lo que se compra <span class="req">*</span></div>
            <textarea name="descripcion" id="fCompraDesc" class="fc" rows="2" placeholder="Describe los bienes o servicios a adquirir…"></textarea>
          </div>
          <div class="col-md-4">
            <div class="fl-label">Línea Presupuestaria</div>
            <select name="id_linea" id="fCompraLinea" class="fc">
              <option value="">— Sin asignar —</option>
            </select>
          </div>
          <div class="col-md-8">
            <div class="fl-label">Justificación</div>
            <textarea name="justificacion" id="fCompraJust" class="fc" rows="2" placeholder="Por qué se necesita esta compra…"></textarea>
          </div>
          <div class="col-md-4">
            <div class="fl-label">Fecha de Solicitud</div>
            <input type="date" name="fecha_solicitud" id="fCompraFecha" class="fc"/>
          </div>
        </div>
        <div class="form-section">Montos y Proveedor</div>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="fl-label">Monto Estimado <span class="req">*</span></div>
            <input type="number" name="monto_estimado" id="fCompraMonto" class="fc" step="0.01" placeholder="0.00"/>
          </div>
          <div class="col-md-4">
            <div class="fl-label">Moneda</div>
            <select name="moneda" id="fCompraMoneda" class="fc">
              <option value="HNL">Lempiras (HNL)</option>
              <option value="USD">Dólares (USD)</option>
            </select>
          </div>
          <div class="col-md-4">
            <div class="fl-label">Proveedor</div>
            <input type="text" name="proveedor" id="fCompraProveedor" class="fc" placeholder="Nombre del proveedor"/>
          </div>
          <div class="col-md-4">
            <div class="fl-label">No. Factura</div>
            <input type="text" name="numero_factura" id="fCompraFactura" class="fc" placeholder="Número de factura"/>
          </div>
        </div>
        <div class="form-section">Documentos Adjuntos</div>
        <div class="row g-3">
          <div class="col-md-4">
            <div class="fl-label">Solicitud (PDF)</div>
            <input type="file" name="archivo_solicitud" class="fc" accept=".pdf,.jpg,.png"/>
          </div>
          <div class="col-md-4">
            <div class="fl-label">Cotización (PDF)</div>
            <input type="file" name="archivo_cotizacion" class="fc" accept=".pdf,.jpg,.png,.xlsx"/>
          </div>
          <div class="col-md-4">
            <div class="fl-label">Factura</div>
            <input type="file" name="archivo_factura" class="fc" accept=".pdf,.jpg,.png"/>
          </div>
        </div>
        <div class="col-12 mt-2">
          <div class="fl-label">Observaciones</div>
          <textarea name="observaciones" id="fCompraObs" class="fc" rows="2"></textarea>
        </div>
      </form>
    </div>
    <div class="modal-foot">
      <button class="btn-gris" onclick="cerrarModal('modalCompra')">Cancelar</button>
      <button class="btn-prim" id="btnGuardarCompra"><i class="fas fa-floppy-disk me-1"></i>Guardar</button>
    </div>
  </div>
</div>

<!-- Modal Cambiar Estado Compra -->
<div class="modal-overlay" id="modalEstadoCompra">
  <div class="modal-box modal-sm">
    <div class="modal-head">
      <h5><i class="fas fa-arrows-rotate me-2"></i>Actualizar Estado</h5>
      <button class="btn-close-x" onclick="cerrarModal('modalEstadoCompra')">×</button>
    </div>
    <div class="modal-body-inner">
      <input type="hidden" id="fECompraId"/>
      <div class="fl-label">Nuevo Estado</div>
      <select class="fc mb-3" id="fECompraEstado">
        <option value="solicitada">Solicitada</option>
        <option value="cotizando">En Cotización</option>
        <option value="aprobada">Aprobada</option>
        <option value="ejecutada">Ejecutada</option>
        <option value="anulada">Anulada</option>
      </select>
      <div id="rowMontoAdj" style="display:none;">
        <div class="fl-label">Monto Adjudicado (ejecutado)</div>
        <input type="number" class="fc mb-2" id="fECompraMonto" step="0.01" placeholder="0.00"/>
      </div>
      <div class="fl-label">Observación</div>
      <textarea class="fc" id="fECompraObs" rows="2" placeholder="Comentario del cambio de estado…"></textarea>
    </div>
    <div class="modal-foot">
      <button class="btn-gris" onclick="cerrarModal('modalEstadoCompra')">Cancelar</button>
      <button class="btn-prim" id="btnConfirmarEstadoCompra"><i class="fas fa-check me-1"></i>Confirmar</button>
    </div>
  </div>
</div>

<!-- Modal Viático -->
<div class="modal-overlay" id="modalViatico">
  <div class="modal-box modal-lg">
    <div class="modal-head">
      <h5 id="tituloViatico"><i class="fas fa-road me-2"></i>Solicitud de Viáticos</h5>
      <button class="btn-close-x" onclick="cerrarModal('modalViatico')">×</button>
    </div>
    <div class="modal-body-inner">
      <form id="formViatico" enctype="multipart/form-data">
        <input type="hidden" name="id_solicitud" id="fViaId" value="0"/>
        <div class="form-section">Información del Viaje</div>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="fl-label">Destino <span class="req">*</span></div>
            <input type="text" name="destino" id="fViaDestino" class="fc" placeholder="Ciudad, Departamento o País"/>
          </div>
          <div class="col-md-6">
            <div class="fl-label">Línea Presupuestaria</div>
            <select name="id_linea" id="fViaLinea" class="fc">
              <option value="">— Sin asignar —</option>
            </select>
          </div>
          <div class="col-12">
            <div class="fl-label">Objetivo / Motivo del Viaje <span class="req">*</span></div>
            <textarea name="objetivo" id="fViaObjetivo" class="fc" rows="2" placeholder="Describa el propósito de la comisión…"></textarea>
          </div>
          <div class="col-md-4">
            <div class="fl-label">Fecha de Salida <span class="req">*</span></div>
            <input type="date" name="fecha_salida" id="fViaSalida" class="fc"/>
          </div>
          <div class="col-md-4">
            <div class="fl-label">Fecha de Retorno <span class="req">*</span></div>
            <input type="date" name="fecha_retorno" id="fViaRetorno" class="fc"/>
          </div>
          <div class="col-md-4">
            <div class="fl-label">Moneda</div>
            <select name="moneda" id="fViaMoneda" class="fc">
              <option value="HNL">Lempiras (HNL)</option>
              <option value="USD">Dólares (USD)</option>
            </select>
          </div>
        </div>
        <div class="form-section">Desglose de Viáticos</div>
        <div class="row g-3">
          <div class="col-md-3">
            <div class="fl-label">Hospedaje</div>
            <input type="number" name="hospedaje" id="fViaHosp" class="fc viatico-input" step="0.01" value="0"/>
          </div>
          <div class="col-md-3">
            <div class="fl-label">Alimentación</div>
            <input type="number" name="alimentacion" id="fViaAlim" class="fc viatico-input" step="0.01" value="0"/>
          </div>
          <div class="col-md-3">
            <div class="fl-label">Transporte</div>
            <input type="number" name="transporte" id="fViaTrans" class="fc viatico-input" step="0.01" value="0"/>
          </div>
          <div class="col-md-3">
            <div class="fl-label">Otros</div>
            <input type="number" name="otros" id="fViaOtros" class="fc viatico-input" step="0.01" value="0"/>
          </div>
          <div class="col-12">
            <div style="background:#f0f2f8;border-radius:8px;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;">
              <span style="font-size:.84rem;color:#555;">Total Solicitado:</span>
              <span style="font-size:1.1rem;font-weight:800;color:var(--primario);" id="viaMontoTotal">L. 0.00</span>
            </div>
          </div>
          <div class="col-12">
            <div class="fl-label">Documento de Solicitud (formato firmado)</div>
            <input type="file" name="archivo_solicitud" class="fc" accept=".pdf,.jpg,.png"/>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-foot">
      <button class="btn-gris" onclick="cerrarModal('modalViatico')">Cancelar</button>
      <button class="btn-prim" id="btnGuardarViatico"><i class="fas fa-floppy-disk me-1"></i>Guardar Solicitud</button>
    </div>
  </div>
</div>

<!-- Modal Visto Bueno -->
<div class="modal-overlay" id="modalVisto">
  <div class="modal-box modal-sm">
    <div class="modal-head">
      <h5><i class="fas fa-pen-to-square me-2"></i>Dar Visto Bueno</h5>
      <button class="btn-close-x" onclick="cerrarModal('modalVisto')">×</button>
    </div>
    <div class="modal-body-inner">
      <input type="hidden" id="fVistoId"/>
      <div class="alert-info mb-3" id="fVistoResumen"></div>
      <div class="fl-label">Observación (opcional)</div>
      <textarea class="fc" id="fVistoObs" rows="3" placeholder="Comentario del visto bueno…"></textarea>
    </div>
    <div class="modal-foot">
      <button class="btn-gris" onclick="cerrarModal('modalVisto')">Cancelar</button>
      <button class="btn-approve" id="btnConfirmarVisto"><i class="fas fa-check me-1"></i>Dar Visto Bueno</button>
    </div>
  </div>
</div>

<!-- Modal Aprobar/Rechazar Viático -->
<div class="modal-overlay" id="modalAprobar">
  <div class="modal-box modal-sm">
    <div class="modal-head">
      <h5 id="tituloAprobar"><i class="fas fa-stamp me-2"></i>Aprobar Solicitud</h5>
      <button class="btn-close-x" onclick="cerrarModal('modalAprobar')">×</button>
    </div>
    <div class="modal-body-inner">
      <input type="hidden" id="fAprobarId"/>
      <input type="hidden" id="fAprobarAccion"/>
      <div class="alert-info mb-2" id="fAprobarResumen"></div>
      <div class="fl-label">Observación / Resolución <span class="req">*</span></div>
      <textarea class="fc" id="fAprobarObs" rows="3" placeholder="Fundamento de la decisión…"></textarea>
    </div>
    <div class="modal-foot">
      <button class="btn-gris" onclick="cerrarModal('modalAprobar')">Cancelar</button>
      <button class="btn-prim" id="btnConfirmarAprobar"><i class="fas fa-check me-1"></i>Confirmar</button>
    </div>
  </div>
</div>

<!-- Modal Liquidar Viático -->
<div class="modal-overlay" id="modalLiquidar">
  <div class="modal-box modal-sm">
    <div class="modal-head">
      <h5><i class="fas fa-file-invoice-dollar me-2"></i>Liquidar Viáticos</h5>
      <button class="btn-close-x" onclick="cerrarModal('modalLiquidar')">×</button>
    </div>
    <div class="modal-body-inner">
      <form id="formLiquidar" enctype="multipart/form-data">
        <input type="hidden" name="id" id="fLiqId"/>
        <div class="fl-label">Monto Ejecutado Real <span class="req">*</span></div>
        <input type="number" name="monto_ejecutado" id="fLiqMonto" class="fc mb-2" step="0.01" placeholder="0.00"/>
        <div class="fl-label">Fecha de Liquidación</div>
        <input type="date" name="fecha_liquidacion" id="fLiqFecha" class="fc mb-2"/>
        <div class="fl-label">Comprobantes / Facturas (PDF)</div>
        <input type="file" name="archivo_liquidacion" class="fc" accept=".pdf,.jpg,.png"/>
      </form>
    </div>
    <div class="modal-foot">
      <button class="btn-gris" onclick="cerrarModal('modalLiquidar')">Cancelar</button>
      <button class="btn-prim" id="btnConfirmarLiquidar"><i class="fas fa-check me-1"></i>Liquidar</button>
    </div>
  </div>
</div>

<!-- Modal Gasto -->
<div class="modal-overlay" id="modalGasto">
  <div class="modal-box modal-md">
    <div class="modal-head">
      <h5 id="tituloGasto"><i class="fas fa-receipt me-2"></i>Registrar Gasto</h5>
      <button class="btn-close-x" onclick="cerrarModal('modalGasto')">×</button>
    </div>
    <div class="modal-body-inner">
      <form id="formGasto" enctype="multipart/form-data">
        <input type="hidden" name="id_gasto" id="fGastoId" value="0"/>
        <div class="row g-3">
          <div class="col-12">
            <div class="fl-label">Descripción <span class="req">*</span></div>
            <input type="text" name="descripcion" id="fGastoDesc" class="fc" placeholder="Descripción del gasto"/>
          </div>
          <div class="col-md-6">
            <div class="fl-label">Tipo</div>
            <select name="tipo" id="fGastoTipo" class="fc">
              <option value="operativo">Operativo</option>
              <option value="administrativo">Administrativo</option>
              <option value="logistico">Logístico</option>
              <option value="otro">Otro</option>
            </select>
          </div>
          <div class="col-md-6">
            <div class="fl-label">Línea Presupuestaria</div>
            <select name="id_linea" id="fGastoLinea" class="fc">
              <option value="">— Sin asignar —</option>
            </select>
          </div>
          <div class="col-md-4">
            <div class="fl-label">Monto <span class="req">*</span></div>
            <input type="number" name="monto" id="fGastoMonto" class="fc" step="0.01" placeholder="0.00"/>
          </div>
          <div class="col-md-4">
            <div class="fl-label">Moneda</div>
            <select name="moneda" id="fGastoMoneda" class="fc">
              <option value="HNL">Lempiras</option>
              <option value="USD">Dólares</option>
            </select>
          </div>
          <div class="col-md-4">
            <div class="fl-label">Fecha del Gasto <span class="req">*</span></div>
            <input type="date" name="fecha_gasto" id="fGastoFecha" class="fc"/>
          </div>
          <div class="col-md-6">
            <div class="fl-label">Beneficiario / Proveedor</div>
            <input type="text" name="beneficiario" id="fGastoBenef" class="fc" placeholder="Nombre"/>
          </div>
          <div class="col-md-6">
            <div class="fl-label">No. Documento / Factura</div>
            <input type="text" name="numero_documento" id="fGastoNumDoc" class="fc" placeholder="Número"/>
          </div>
          <div class="col-12">
            <div class="fl-label">Comprobante (PDF, imagen)</div>
            <input type="file" name="archivo" class="fc" accept=".pdf,.jpg,.jpeg,.png"/>
          </div>
          <div class="col-12">
            <div class="fl-label">Observaciones</div>
            <textarea name="observaciones" id="fGastoObs" class="fc" rows="2"></textarea>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-foot">
      <button class="btn-gris" onclick="cerrarModal('modalGasto')">Cancelar</button>
      <button class="btn-prim" id="btnGuardarGasto"><i class="fas fa-floppy-disk me-1"></i>Guardar</button>
    </div>
  </div>
</div>

<!-- Modal Documento -->
<div class="modal-overlay" id="modalDocumento">
  <div class="modal-box modal-md">
    <div class="modal-head">
      <h5><i class="fas fa-upload me-2"></i>Subir Documento</h5>
      <button class="btn-close-x" onclick="cerrarModal('modalDocumento')">×</button>
    </div>
    <div class="modal-body-inner">
      <form id="formDocumento" enctype="multipart/form-data">
        <input type="hidden" name="id_documento" id="fDocId" value="0"/>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="fl-label">Tipo de Documento <span class="req">*</span></div>
            <select name="tipo" id="fDocTipo" class="fc">
              <option value="carta_entendimiento">Carta de Entendimiento</option>
              <option value="perfil_programa">Perfil del Programa</option>
              <option value="acuerdo">Acuerdo</option>
              <option value="convenio">Convenio</option>
              <option value="resolucion">Resolución</option>
              <option value="acta">Acta</option>
              <option value="otro">Otro</option>
            </select>
          </div>
          <div class="col-md-6">
            <div class="fl-label">Fecha del Documento</div>
            <input type="date" name="fecha_documento" id="fDocFecha" class="fc"/>
          </div>
          <div class="col-12">
            <div class="fl-label">Nombre del Documento <span class="req">*</span></div>
            <input type="text" name="nombre" id="fDocNombre" class="fc" placeholder="Nombre descriptivo del documento"/>
          </div>
          <div class="col-12">
            <div class="fl-label">Descripción</div>
            <textarea name="descripcion" id="fDocDesc" class="fc" rows="2" placeholder="Contenido o alcance del documento…"></textarea>
          </div>
          <div class="col-md-8">
            <div class="fl-label">Archivo <span class="req">*</span></div>
            <input type="file" name="archivo_doc" id="fDocArchivo" class="fc" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.png"/>
          </div>
          <div class="col-md-4">
            <div class="fl-label">¿Vigente?</div>
            <select name="vigente" id="fDocVigente" class="fc">
              <option value="1">Sí</option>
              <option value="0">No</option>
            </select>
          </div>
        </div>
      </form>
    </div>
    <div class="modal-foot">
      <button class="btn-gris" onclick="cerrarModal('modalDocumento')">Cancelar</button>
      <button class="btn-prim" id="btnGuardarDoc"><i class="fas fa-upload me-1"></i>Subir Documento</button>
    </div>
  </div>
</div>

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>
