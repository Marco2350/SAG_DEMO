<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>
<?php
$prog       = $_SESSION['programa'] ?? [];
$progSigla  = $prog['sigla']  ?? '';
$progColor  = $prog['color']  ?? '#0B5D3D';
$progIco    = $prog['icono']  ?? 'fa-seedling';
?>

<style>
  /* ── Inventarios — estilos específicos ─────────── */
  .inv-tabs { display:flex; gap:6px; background:var(--gris); padding:5px; border-radius:12px;
    margin-bottom:18px; width:fit-content; }
  .inv-tab { padding:10px 22px; font-size:.88rem; font-weight:600; color:var(--texto-sec);
    background:transparent; border:none; cursor:pointer; border-radius:8px;
    transition:all .15s; display:flex; align-items:center; gap:8px; font-family:'Inter',sans-serif; }
  .inv-tab.active { background:#fff; color:var(--primario); box-shadow:var(--shadow-xs); }
  .inv-tab:not(.active):hover { color:var(--texto); }
  .inv-panel { display:none; }
  .inv-panel.active { display:block; }

  /* Tabla de cronogramas con líneas expandibles */
  .cron-row { background:#fff; border:1px solid var(--borde-suave); border-radius:var(--r-lg);
    margin-bottom:14px; overflow:hidden; box-shadow:var(--shadow-xs); transition:box-shadow .2s; }
  .cron-row:hover { box-shadow:var(--shadow-sm); }
  .cron-head { padding:18px 22px; display:grid; grid-template-columns:auto 1fr auto auto auto;
    gap:18px; align-items:center; cursor:pointer; }
  .cron-toggle { width:30px; height:30px; border-radius:50%; background:var(--tema-light);
    color:var(--primario); display:flex; align-items:center; justify-content:center;
    font-size:.78rem; transition:transform .2s; }
  .cron-row.open .cron-toggle { transform:rotate(90deg); }
  .cron-codigo { font-family:'Poppins','Inter',sans-serif; font-weight:700; font-size:1rem;
    color:var(--texto); letter-spacing:-.01em; }
  .cron-meta { font-size:.82rem; color:var(--texto-sec); margin-top:3px; }
  .cron-monto { font-family:'Poppins','Inter',sans-serif; font-size:1.15rem; font-weight:700;
    color:var(--primario); letter-spacing:-.01em; }
  .cron-progress-wrap { width:160px; }
  .cron-progress-lbl { display:flex; justify-content:space-between; font-size:.72rem;
    color:var(--texto-sec); margin-bottom:4px; font-weight:600; }
  .cron-progress-bar { height:8px; background:var(--gris); border-radius:999px; overflow:hidden; }
  .cron-progress-fill { height:100%; background:linear-gradient(90deg, var(--primario), var(--primario-claro));
    border-radius:999px; transition:width .3s; }
  .cron-body { display:none; padding:0 22px 18px; border-top:1px solid var(--borde-suave); }
  .cron-row.open .cron-body { display:block; }
  .cron-body table { width:100%; border-collapse:collapse; font-size:.85rem; margin-top:14px; }
  .cron-body th { font-size:.7rem; text-transform:uppercase; letter-spacing:.8px;
    color:var(--texto-sec); font-weight:700; padding:10px 12px; text-align:left;
    border-bottom:1px solid var(--borde-suave); background:var(--superficie-soft); }
  .cron-body td { padding:12px; border-bottom:1px solid var(--borde-suave); }
  .cron-body tr:last-child td { border-bottom:none; }
  .cron-body .col-actions { text-align:right; }

  /* Badges estado línea */
  .lin-badge { display:inline-block; padding:4px 12px; border-radius:999px;
    font-size:.7rem; font-weight:700; font-family:'Inter',sans-serif; }
  .lb-pendiente { background:#fef3c7; color:#92400e; }
  .lb-parcial   { background:#fed7aa; color:#9a3412; }
  .lb-recibida  { background:#dcfce7; color:#166534; }
  .lb-atrasada  { background:#fee2e2; color:#991b1b; }
  .lb-cancelada { background:#e5e7eb; color:#374151; }

  .cron-estado-pill { display:inline-block; padding:5px 14px; border-radius:999px;
    font-size:.74rem; font-weight:700; font-family:'Inter',sans-serif; }
  .cep-vigente    { background:var(--tema-light); color:var(--primario); }
  .cep-borrador   { background:#fef3c7; color:#92400e; }
  .cep-finalizado { background:#e0e7ff; color:#3730a3; }
  .cep-cancelado  { background:#fee2e2; color:#991b1b; }

  /* Botón pequeño "Recibir" */
  .btn-recibir { background:var(--primario); color:#fff; border:none; padding:6px 14px;
    border-radius:8px; font-size:.78rem; font-weight:600; cursor:pointer;
    display:inline-flex; align-items:center; gap:6px; transition:background .15s;
    font-family:'Inter',sans-serif; }
  .btn-recibir:hover { background:var(--primario-oscuro); }
  .btn-recibir:disabled { background:var(--texto-mute); cursor:not-allowed; }

  /* Modal nuevo cronograma */
  .inv-modal { display:none; position:fixed; inset:0; background:rgba(15,23,42,.55);
    backdrop-filter:blur(2px); z-index:1050; align-items:flex-start; justify-content:center;
    padding:40px 16px; overflow-y:auto; }
  .inv-modal.show { display:flex; }
  .inv-modal-box { background:#fff; border-radius:var(--r-xl); width:100%; max-width:980px;
    box-shadow:var(--shadow-lg); display:flex; flex-direction:column; overflow:hidden; }
  .inv-modal-head { padding:20px 28px; border-bottom:1px solid var(--borde-suave);
    display:flex; justify-content:space-between; align-items:center; }
  .inv-modal-head h5 { font-family:'Poppins','Inter',sans-serif; font-size:1.15rem;
    font-weight:700; margin:0; color:var(--texto); letter-spacing:-.015em; }
  .inv-modal-body { padding:24px 28px; max-height:70vh; overflow-y:auto; }
  .inv-modal-foot { padding:18px 28px; border-top:1px solid var(--borde-suave);
    display:flex; justify-content:flex-end; gap:10px; background:var(--superficie-soft); }

  .lineas-table { width:100%; border-collapse:collapse; margin-top:8px; font-size:.85rem; }
  .lineas-table th { font-size:.7rem; text-transform:uppercase; letter-spacing:.8px;
    color:var(--texto-sec); font-weight:700; padding:8px 10px; text-align:left;
    border-bottom:1px solid var(--borde-suave); }
  .lineas-table td { padding:8px 6px; vertical-align:top; }
  .lineas-table .fs, .lineas-table .fc { padding:8px 10px; font-size:.85rem; }
  .lineas-table .btn-rm { background:#fef2f2; color:#dc2626; border:1px solid #fecaca;
    width:32px; height:32px; border-radius:8px; cursor:pointer; display:inline-flex;
    align-items:center; justify-content:center; transition:all .15s; }
  .lineas-table .btn-rm:hover { background:#dc2626; color:#fff; border-color:#dc2626; }
  .btn-add-linea { background:var(--tema-light); color:var(--primario); border:1.5px dashed var(--primario);
    padding:10px 16px; border-radius:10px; font-size:.84rem; font-weight:600; cursor:pointer;
    margin-top:10px; display:inline-flex; align-items:center; gap:8px; font-family:'Inter',sans-serif;
    transition:background .15s; }
  .btn-add-linea:hover { background:var(--primario); color:#fff; }

  .inv-toolbar { display:flex; justify-content:space-between; align-items:center;
    gap:12px; margin-bottom:18px; flex-wrap:wrap; }
</style>

<div class="content">

  <!-- Page header -->
  <div class="page-header">
    <div>
      <div class="page-title">
        Inventarios de Incentivos
        <small>
          <?= progIconHtml($prog, 'margin-right:4px;') ?>
          <?= htmlspecialchars($progSigla) ?> — Cronogramas, recepción y stock
        </small>
      </div>
    </div>
    <div class="breadcrumb-bar">
      <i class="fas fa-house" style="font-size:.7rem;"></i>
      &nbsp;/ <span>Inventarios</span>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
      <div class="kpi-card">
        <div class="kpi-icon"><i class="fas fa-file-contract"></i></div>
        <div>
          <div class="kpi-val"><?= number_format($kpis['cronogramas_activos']) ?></div>
          <div class="kpi-lbl">Cronogramas activos</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="kpi-card blue">
        <div class="kpi-icon"><i class="fas fa-calendar-check"></i></div>
        <div>
          <div class="kpi-val"><?= number_format($kpis['total_programado']) ?></div>
          <div class="kpi-lbl">Total programado (unidades)</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="kpi-card">
        <div class="kpi-icon"><i class="fas fa-truck-arrow-right"></i></div>
        <div>
          <div class="kpi-val"><?= number_format($kpis['total_recibido']) ?></div>
          <div class="kpi-lbl">Total recibido (<?= $kpis['avance_pct'] ?>% avance)</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="kpi-card purple">
        <div class="kpi-icon"><i class="fas fa-boxes-stacked"></i></div>
        <div>
          <div class="kpi-val"><?= number_format($kpis['stock_disponible']) ?></div>
          <div class="kpi-lbl">Stock disponible total</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Toolbar + tabs -->
  <div class="inv-toolbar">
    <div class="inv-tabs">
      <button class="inv-tab active" data-tab="cronogramas">
        <i class="fas fa-file-contract"></i> Cronogramas
      </button>
      <button class="inv-tab" data-tab="stock">
        <i class="fas fa-warehouse"></i> Stock por bodega
      </button>
      <button class="inv-tab" data-tab="kardex">
        <i class="fas fa-list-ul"></i> Kardex
      </button>
    </div>
    <?php if ($esAdmin): ?>
    <button class="btn-primario" id="btnNuevoCronograma">
      <i class="fas fa-plus"></i> Nuevo cronograma
    </button>
    <?php endif; ?>
  </div>

  <!-- ── TAB: Cronogramas ── -->
  <div class="inv-panel active" id="tab-cronogramas">
    <?php foreach ($cronogramas as $c):
      $totalProg = array_sum(array_column($c['lineas'], 'cantidad_programada'));
      $totalRec  = array_sum(array_column($c['lineas'], 'cantidad_recibida'));
      $pct       = $totalProg > 0 ? round(($totalRec/$totalProg)*100) : 0;
    ?>
    <div class="cron-row" data-id="<?= $c['id_cronograma'] ?>">
      <div class="cron-head" onclick="toggleCronograma(<?= $c['id_cronograma'] ?>)">
        <div class="cron-toggle"><i class="fas fa-chevron-right"></i></div>
        <div>
          <div class="cron-codigo"><?= htmlspecialchars($c['codigo']) ?>
            <span class="cron-estado-pill cep-<?= $c['estado'] ?>" style="margin-left:8px;"><?= ucfirst($c['estado']) ?></span>
          </div>
          <div class="cron-meta">
            <i class="fas fa-building" style="margin-right:4px;color:var(--texto-mute);"></i>
            <?= htmlspecialchars($c['proveedor']) ?>
            <?php if ($c['num_contrato']): ?>
              &nbsp;·&nbsp; Contrato: <strong><?= htmlspecialchars($c['num_contrato']) ?></strong>
            <?php endif; ?>
            <?php if ($c['num_orden_compra']): ?>
              &nbsp;·&nbsp; OC: <strong><?= htmlspecialchars($c['num_orden_compra']) ?></strong>
            <?php endif; ?>
          </div>
        </div>
        <div style="text-align:right;">
          <div class="cron-monto">L. <?= number_format($c['monto_total'], 2) ?></div>
          <div style="font-size:.72rem;color:var(--texto-mute);margin-top:2px;">
            <?= htmlspecialchars($c['fecha_inicio']) ?> → <?= htmlspecialchars($c['fecha_fin']) ?>
          </div>
        </div>
        <div class="cron-progress-wrap">
          <div class="cron-progress-lbl">
            <span><?= number_format($totalRec) ?> / <?= number_format($totalProg) ?></span>
            <span><?= $pct ?>%</span>
          </div>
          <div class="cron-progress-bar">
            <div class="cron-progress-fill" style="width:<?= $pct ?>%;"></div>
          </div>
        </div>
        <div>
          <button class="btn-outline" style="padding:6px 12px;font-size:.78rem;" onclick="event.stopPropagation();verCronograma(<?= $c['id_cronograma'] ?>)">
            <i class="fas fa-eye"></i> Ver
          </button>
        </div>
      </div>
      <div class="cron-body">
        <?php if (!empty($c['descripcion'])): ?>
          <div style="font-size:.85rem;color:var(--texto-sec);padding:12px 0 0;">
            <i class="fas fa-info-circle" style="margin-right:6px;"></i><?= htmlspecialchars($c['descripcion']) ?>
          </div>
        <?php endif; ?>
        <table>
          <thead>
            <tr>
              <th>Producto</th>
              <th>Bodega destino</th>
              <th>Fecha programada</th>
              <th>Programado</th>
              <th>Recibido</th>
              <th>Estado</th>
              <?php if ($esAdmin): ?><th class="col-actions">Acción</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($c['lineas'] as $l): ?>
            <tr>
              <td><strong><?= htmlspecialchars($l['producto']) ?></strong></td>
              <td><?= htmlspecialchars($l['bodega']) ?></td>
              <td><?= htmlspecialchars($l['fecha_programada']) ?></td>
              <td><?= number_format($l['cantidad_programada']) ?> <span style="color:var(--texto-mute);font-size:.78rem;"><?= $l['unidad'] ?></span></td>
              <td>
                <strong style="color:var(--primario);"><?= number_format($l['cantidad_recibida']) ?></strong>
                <?php if ($l['fecha_recibida']): ?>
                  <div style="font-size:.7rem;color:var(--texto-mute);">el <?= htmlspecialchars($l['fecha_recibida']) ?></div>
                <?php endif; ?>
              </td>
              <td><span class="lin-badge lb-<?= $l['estado'] ?>"><?= ucfirst($l['estado']) ?></span></td>
              <?php if ($esAdmin): ?>
              <td class="col-actions">
                <?php if (in_array($l['estado'], ['pendiente','parcial','atrasada'])): ?>
                <button class="btn-recibir" onclick="abrirRecibir(<?= $l['id_linea'] ?>, '<?= htmlspecialchars(addslashes($l['producto'])) ?>', <?= $l['cantidad_programada'] - $l['cantidad_recibida'] ?>)">
                  <i class="fas fa-truck-arrow-right"></i> Recibir
                </button>
                <?php else: ?>
                  <span style="color:var(--texto-mute);font-size:.78rem;">—</span>
                <?php endif; ?>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($cronogramas)): ?>
    <div class="card-box">
      <div class="card-box-body" style="text-align:center;padding:60px 20px;color:var(--texto-mute);">
        <i class="fas fa-file-contract" style="font-size:3rem;margin-bottom:16px;opacity:.3;"></i>
        <p style="font-size:.95rem;">Aún no hay cronogramas registrados.</p>
        <?php if ($esAdmin): ?>
        <button class="btn-primario" onclick="document.getElementById('btnNuevoCronograma').click()" style="margin-top:14px;">
          <i class="fas fa-plus"></i> Crear el primero
        </button>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── TAB: Stock por bodega ── -->
  <div class="inv-panel" id="tab-stock">
    <div class="card-box">
      <div class="card-box-header">
        <h6><i class="fas fa-warehouse"></i> Saldos actuales por bodega y producto</h6>
        <span style="font-size:.78rem;color:var(--texto-sec);">Calculado del kardex</span>
      </div>
      <div style="overflow-x:auto;">
        <table class="sag-table">
          <thead>
            <tr>
              <th>Bodega</th>
              <th>Producto</th>
              <th>Unidad</th>
              <th style="text-align:right;">Saldo</th>
              <th style="text-align:right;">Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($stock as $s): ?>
            <tr>
              <td><strong><?= htmlspecialchars($s['bodega']) ?></strong></td>
              <td><?= htmlspecialchars($s['producto']) ?></td>
              <td><?= htmlspecialchars($s['unidad']) ?></td>
              <td style="text-align:right;font-weight:700;color:var(--primario);font-size:1rem;"><?= number_format($s['saldo']) ?></td>
              <td style="text-align:right;">
                <?php if ($s['saldo'] == 0): ?>
                  <span class="lin-badge lb-atrasada">Agotado</span>
                <?php elseif ($s['saldo'] < 50): ?>
                  <span class="lin-badge lb-parcial">Bajo</span>
                <?php else: ?>
                  <span class="lin-badge lb-recibida">Disponible</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ── TAB: Kardex ── -->
  <div class="inv-panel" id="tab-kardex">
    <div class="card-box">
      <div class="card-box-header">
        <h6><i class="fas fa-list-ul"></i> Movimientos recientes</h6>
        <span style="font-size:.78rem;color:var(--texto-sec);"><?= count($kardex) ?> movimientos</span>
      </div>
      <div style="overflow-x:auto;">
        <table class="sag-table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Tipo</th>
              <th>Producto</th>
              <th>Bodega</th>
              <th style="text-align:right;">Cantidad</th>
              <th style="text-align:right;">Saldo</th>
              <th>Origen</th>
              <th>Descripción</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($kardex as $m): ?>
            <tr>
              <td style="white-space:nowrap;color:var(--texto-sec);font-size:.82rem;"><?= htmlspecialchars($m['fecha']) ?></td>
              <td>
                <?php if ($m['tipo'] === 'entrada'): ?>
                  <span class="lin-badge lb-recibida"><i class="fas fa-arrow-down" style="font-size:.65rem;margin-right:3px;"></i>Entrada</span>
                <?php elseif ($m['tipo'] === 'salida'): ?>
                  <span class="lin-badge lb-atrasada"><i class="fas fa-arrow-up" style="font-size:.65rem;margin-right:3px;"></i>Salida</span>
                <?php else: ?>
                  <span class="lin-badge lb-parcial"><i class="fas fa-sliders" style="font-size:.65rem;margin-right:3px;"></i>Ajuste</span>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($m['producto']) ?></td>
              <td style="font-size:.85rem;"><?= htmlspecialchars($m['bodega']) ?></td>
              <td style="text-align:right;font-weight:700;"><?= ($m['tipo']==='salida'?'−':'+') . number_format($m['cantidad']) ?></td>
              <td style="text-align:right;color:var(--primario);font-weight:700;"><?= number_format($m['saldo']) ?></td>
              <td style="font-size:.82rem;color:var(--texto-sec);"><?= ucfirst($m['origen']) ?></td>
              <td style="font-size:.82rem;color:var(--texto-sec);"><?= htmlspecialchars($m['descripcion']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div><!-- /content -->


<!-- ══════════════════════════════════════════════════
     MODAL: Nuevo cronograma
══════════════════════════════════════════════════ -->
<div class="inv-modal" id="modalCronograma">
  <div class="inv-modal-box">
    <div class="inv-modal-head">
      <h5><i class="fas fa-file-contract" style="color:var(--primario);margin-right:8px;"></i>Nuevo cronograma de inventario</h5>
      <button class="btn-toggle" onclick="cerrarModal()" style="background:none;"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="inv-modal-body">
      <form id="formCronograma">
        <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">

        <div class="form-section-title"><i class="fas fa-building"></i>Datos del proveedor y contrato</div>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label-b">Proveedor <span class="req">*</span></label>
            <select class="fs" name="id_proveedor" required>
              <option value="">— Seleccionar proveedor —</option>
              <?php foreach ($proveedores as $p): ?>
                <option value="<?= $p['id_proveedor'] ?>"><?= htmlspecialchars($p['nombre']) ?> (RTN: <?= htmlspecialchars($p['rtn']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label-b">N° Contrato</label>
            <input type="text" class="fc" name="num_contrato" placeholder="CT-2026-XXX">
          </div>
          <div class="col-md-3">
            <label class="form-label-b">N° Orden Compra</label>
            <input type="text" class="fc" name="num_orden_compra" placeholder="OC-2026-XXX">
          </div>
          <div class="col-md-4">
            <label class="form-label-b">Monto total (L.)</label>
            <input type="number" step="0.01" class="fc" name="monto_total" placeholder="0.00">
          </div>
          <div class="col-md-4">
            <label class="form-label-b">Fecha inicio</label>
            <input type="date" class="fc" name="fecha_inicio">
          </div>
          <div class="col-md-4">
            <label class="form-label-b">Fecha fin</label>
            <input type="date" class="fc" name="fecha_fin">
          </div>
          <div class="col-12">
            <label class="form-label-b">Descripción</label>
            <textarea class="fc" name="descripcion" rows="2" placeholder="Resumen del cronograma..."></textarea>
          </div>
        </div>

        <div class="form-section-title">
          <i class="fas fa-list"></i>Líneas del cronograma
          <span style="font-size:.7rem;font-weight:500;color:var(--texto-mute);margin-left:8px;text-transform:none;letter-spacing:0;">
            (Fechas + productos + cantidades + bodega destino)
          </span>
        </div>
        <div style="overflow-x:auto;">
        <table class="lineas-table" id="lineasTable">
          <thead>
            <tr>
              <th style="width:30%;">Producto</th>
              <th style="width:22%;">Bodega destino</th>
              <th style="width:14%;">Fecha programada</th>
              <th style="width:16%;">Cantidad</th>
              <th style="width:10%;">Unidad</th>
              <th style="width:50px;"></th>
            </tr>
          </thead>
          <tbody id="lineasTBody">
            <!-- Filas dinámicas -->
          </tbody>
        </table>
        </div>
        <button type="button" class="btn-add-linea" onclick="agregarLinea()">
          <i class="fas fa-plus"></i> Agregar línea
        </button>
      </form>
    </div>
    <div class="inv-modal-foot">
      <button class="btn-gris" onclick="cerrarModal()">Cancelar</button>
      <button class="btn-primario" id="btnGuardarCronograma">
        <i class="fas fa-floppy-disk"></i> Guardar cronograma
      </button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════
     MODAL: Recibir línea
══════════════════════════════════════════════════ -->
<div class="inv-modal" id="modalRecibir">
  <div class="inv-modal-box" style="max-width:500px;">
    <div class="inv-modal-head">
      <h5><i class="fas fa-truck-arrow-right" style="color:var(--primario);margin-right:8px;"></i>Registrar recepción</h5>
      <button class="btn-toggle" onclick="cerrarModalRecibir()" style="background:none;"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="inv-modal-body">
      <input type="hidden" id="recLineaId">
      <div style="background:var(--tema-light);padding:14px 18px;border-radius:12px;margin-bottom:18px;border-left:4px solid var(--primario);">
        <div style="font-size:.75rem;color:var(--texto-sec);font-weight:600;text-transform:uppercase;letter-spacing:.5px;">Producto</div>
        <div id="recProducto" style="font-family:'Poppins',sans-serif;font-weight:700;font-size:1.05rem;color:var(--primario);margin-top:3px;"></div>
        <div style="font-size:.78rem;color:var(--texto-sec);margin-top:4px;">Pendiente por recibir: <strong id="recPendiente">0</strong> unidades</div>
      </div>
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label-b">Cantidad recibida <span class="req">*</span></label>
          <input type="number" step="0.01" class="fc" id="recCantidad" min="0">
        </div>
        <div class="col-md-6">
          <label class="form-label-b">Fecha de recepción</label>
          <input type="date" class="fc" id="recFecha" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label-b">Responsable</label>
          <input type="text" class="fc" id="recResponsable" placeholder="Nombre del receptor"
            value="<?= htmlspecialchars(($_SESSION['user']['nombre'] ?? '') . ' ' . ($_SESSION['user']['apellido'] ?? '')) ?>">
        </div>
      </div>
    </div>
    <div class="inv-modal-foot">
      <button class="btn-gris" onclick="cerrarModalRecibir()">Cancelar</button>
      <button class="btn-primario" id="btnConfirmarRecibir">
        <i class="fas fa-check"></i> Confirmar recepción
      </button>
    </div>
  </div>
</div>


<?php
// Pasar catálogos al JS
$catalogosJs = [
  'productos'   => $productos,
  'bodegas'     => $bodegas,
  'proveedores' => $proveedores,
];
?>
<script>
window.INV_CAT = <?= json_encode($catalogosJs, JSON_UNESCAPED_UNICODE) ?>;
const BASE_URL = '<?= BASE_URL ?>';
const CSRF_TOKEN = '<?= csrf_token() ?>';
</script>
<script src="<?= asset('public/assets/js/modules/inventarios.js') ?>"></script>

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>
