<?php
$cssExtra = '<style>
/* ── MOVILIZACIONES OIRSA ─────────────────────────── */
.mov-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:14px;}
.mov-kpi{background:#fff;border:1.5px solid var(--borde);border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px;}
.mov-kpi .ki{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
.mov-kpi .kv{font-size:1.35rem;font-weight:800;line-height:1;}
.mov-kpi .kl{font-size:.7rem;color:#888;margin-top:3px;text-transform:uppercase;letter-spacing:.3px;}

.mov-tabs{display:flex;gap:6px;background:var(--gris);padding:5px;border-radius:12px;margin-bottom:18px;width:fit-content;}
.mov-tab{padding:10px 22px;font-size:.88rem;font-weight:600;color:var(--texto-sec);background:transparent;border:none;cursor:pointer;border-radius:8px;transition:all .15s;display:flex;align-items:center;gap:8px;}
.mov-tab.active{background:#fff;color:var(--primario);box-shadow:var(--shadow-xs);}
.mov-tab:not(.active):hover{color:var(--texto);}
.mov-panel{display:none;}
.mov-panel.active{display:block;}

.mov-filtros{background:#f8fafc;border:1.5px solid var(--borde);border-radius:10px;padding:12px 14px;margin-bottom:14px;display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;}
.mov-filtros .fl{font-size:.68rem;font-weight:700;color:#555;margin-bottom:3px;text-transform:uppercase;letter-spacing:.3px;}
.mov-filtros .fc{padding:6px 9px;border:1.5px solid var(--borde);border-radius:6px;font-size:.78rem;width:100%;background:#fff;}
.mov-filtros .fc:focus{outline:none;border-color:var(--primario);}

.mov-tbl-wrap{background:#fff;border:1.5px solid var(--borde);border-radius:10px;overflow:auto;}
.mov-tbl{width:100%;border-collapse:collapse;font-size:.78rem;min-width:1500px;}
.mov-tbl thead th{background:#0d9488;color:#fff;padding:8px 10px;font-size:.65rem;font-weight:700;text-transform:uppercase;text-align:left;white-space:nowrap;border-right:1px solid rgba(255,255,255,.2);}
.mov-tbl thead th.recep{background:#1e40af;}
.mov-tbl thead th.ent{background:#e8742c;}
.mov-tbl tbody tr{border-bottom:1px solid #f1f5f9;}
.mov-tbl tbody tr:hover{background:#f8fafc;}
.mov-tbl tbody tr:nth-child(even){background:#fafbfc;}
.mov-tbl tbody tr:nth-child(even):hover{background:#f1f5f9;}
.mov-tbl tbody td{padding:7px 10px;vertical-align:top;white-space:nowrap;border-right:1px solid #f1f5f9;}
.mov-tbl tbody td.wrap{white-space:normal;max-width:220px;}

.tipo-badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:.65rem;font-weight:700;white-space:nowrap;}
.tipo-recep{background:#dbeafe;color:#1e40af;}
.tipo-ent  {background:#fff7ed;color:#9a3412;}
.tipo-tras {background:#ede9fe;color:#7c3aed;}

.dataTables_wrapper{font-size:.78rem;}
.dataTables_wrapper .dataTables_paginate .paginate_button{padding:3px 10px;margin:0 2px;border-radius:5px;}
.dataTables_wrapper .dataTables_paginate .paginate_button.current{background:#0d9488 !important;border-color:#0d9488 !important;color:#fff !important;}
.dataTables_wrapper .dataTables_info,.dataTables_wrapper .dataTables_length{font-size:.78rem;color:#555;}
</style>';

$prog       = $_SESSION['programa'] ?? [];
$progSigla  = $prog['sigla'] ?? '';

require ROOT_PATH . '/app/views/layouts/header.php';
require ROOT_PATH . '/app/views/layouts/sidebar.php';
require ROOT_PATH . '/app/views/layouts/topbar.php';

$totalGlobal = (int)($kpis['recep_total'] + $kpis['ent_total'] + $kpis['tras_total']);
?>

<div class="content">

  <!-- Page header -->
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <div class="page-title">
        <i class="fas fa-arrows-turn-to-dots" style="color:#0d9488;"></i>
        Movilizaciones OIRSA
        <small><?= htmlspecialchars($progSigla) ?> &mdash; Reporte consolidado de Trazaragro (Recepciones, Traslados y Entregas)</small>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
      <button type="button" class="btn btn-sm btn-primary" id="btnSyncMovilizaciones">
        <i class="fas fa-rotate"></i> Actualizar sincronización
      </button>
      <span style="font-size:.78rem;color:#666;">Actualiza recepciones, traslados y entregas desde OIRSA.</span>
    </div>
  </div>

  <!-- KPIs globales -->
  <div class="mov-kpis">
    <div class="mov-kpi">
      <div class="ki" style="background:#dbeafe;color:#1e40af;"><i class="fas fa-truck-arrow-right"></i></div>
      <div><div class="kv" style="color:#1e40af;"><?= number_format($kpis['recep_total']) ?></div><div class="kl">Recepciones</div></div>
    </div>
    <div class="mov-kpi">
      <div class="ki" style="background:#fff7ed;color:#e8742c;"><i class="fas fa-people-carry-box"></i></div>
      <div><div class="kv" style="color:#e8742c;"><?= number_format($kpis['ent_total']) ?></div><div class="kl">Entregas</div></div>
    </div>
    <div class="mov-kpi">
      <div class="ki" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-right-left"></i></div>
      <div><div class="kv" style="color:#7c3aed;"><?= number_format($kpis['tras_total']) ?></div><div class="kl">Traslados</div></div>
    </div>
    <div class="mov-kpi">
      <div class="ki" style="background:#fef3c7;color:#9a3412;"><i class="fas fa-handshake"></i></div>
      <div><div class="kv"><?= number_format($kpis['proveedores']) ?></div><div class="kl">Proveedores</div></div>
    </div>
    <div class="mov-kpi">
      <div class="ki" style="background:#eef0f7;color:var(--primario);"><i class="fas fa-warehouse"></i></div>
      <div><div class="kv"><?= number_format($kpis['bodegas']) ?></div><div class="kl">Bodegas</div></div>
    </div>
    <div class="mov-kpi">
      <div class="ki" style="background:#d1fae5;color:#16a34a;"><i class="fas fa-boxes-stacked"></i></div>
      <div><div class="kv"><?= number_format($kpis['productos']) ?></div><div class="kl">Productos</div></div>
    </div>
    <div class="mov-kpi">
      <div class="ki" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-file-invoice"></i></div>
      <div><div class="kv"><?= number_format($kpis['manifiestos']) ?></div><div class="kl">Manifiestos (GUIASA)</div></div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="mov-tabs">
    <button class="mov-tab active" data-tab="movimientos">
      <i class="fas fa-list-ul"></i> Movimientos <small style="opacity:.7;">(<?= number_format($totalGlobal) ?>)</small>
    </button>
    <button class="mov-tab" data-tab="por-bodega">
      <i class="fas fa-warehouse"></i> Por Bodega
    </button>
    <button class="mov-tab" data-tab="por-proveedor">
      <i class="fas fa-handshake"></i> Por Proveedor
    </button>
    <button class="mov-tab" data-tab="por-producto">
      <i class="fas fa-boxes-stacked"></i> Por Producto
    </button>
    <button class="mov-tab" data-tab="auditoria" style="color:#92400e;">
      <i class="fas fa-triangle-exclamation"></i> Auditoría
    </button>
  </div>

  <!-- TAB: Movimientos -->
  <div class="mov-panel active" id="mov-tab-movimientos">

    <!-- Filtros -->
    <div class="mov-filtros">
      <div>
        <div class="fl">Tipo de movimiento</div>
        <select class="fc" id="mfTipo">
          <option value="">Todos (recepciones + entregas + traslados)</option>
          <option value="recepcion">Solo Recepciones (Proveedor → Bodega)</option>
          <option value="entrega">Solo Entregas (Bodega → Productor)</option>
          <option value="traslado">Solo Traslados (Bodega → Bodega)</option>
        </select>
      </div>
      <div>
        <div class="fl">Proveedor</div>
        <select class="fc" id="mfProveedor"><option value="">Todos</option>
          <?php foreach (($filtros['proveedores'] ?? []) as $p): ?>
            <option><?= htmlspecialchars($p) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <div class="fl">Bodega</div>
        <select class="fc" id="mfBodega"><option value="">Todas</option>
          <?php foreach (($filtros['bodegas'] ?? []) as $b): ?>
            <option><?= htmlspecialchars($b) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <div class="fl">Producto</div>
        <select class="fc" id="mfProducto"><option value="">Todos</option>
          <?php foreach (($filtros['productos'] ?? []) as $prod): ?>
            <option><?= htmlspecialchars($prod) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <div class="fl">Desde</div>
        <input type="date" class="fc" id="mfDesde"/>
      </div>
      <div>
        <div class="fl">Hasta</div>
        <input type="date" class="fc" id="mfHasta"/>
      </div>
      <div>
        <div class="fl">GUIASA</div>
        <input type="text" class="fc" id="mfGuiasa" placeholder="Ej. EH0000408"/>
      </div>
      <div style="display:flex;align-items:flex-end;">
        <button class="fc" id="btnMfLimpiar" style="background:#eef0f7;cursor:pointer;font-weight:700;">
          <i class="fas fa-broom"></i> Limpiar
        </button>
      </div>
    </div>

    <!-- Tabla -->
    <div class="mov-tbl-wrap">
      <table class="mov-tbl" id="tblMovilizaciones" style="width:100%;">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Origen (Proveedor o Bodega)</th>
            <th>Destino (Bodega o Productor)</th>
            <th>Producto / objeto trazable</th>
            <th>GUIASA</th>
            <th style="text-align:right;">Cantidad</th>
            <th>Unidad</th>
            <th>Cód. trazabilidad</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  <!-- TAB: Por Bodega -->
  <div class="mov-panel" id="mov-tab-por-bodega">
    <div id="mov-resBodega">
      <div style="text-align:center;padding:60px 20px;color:#888;background:#fff;border:1.5px solid var(--borde);border-radius:10px;">
        Cargando bodegas...
      </div>
    </div>
  </div>

  <!-- TAB: Por Proveedor -->
  <div class="mov-panel" id="mov-tab-por-proveedor">
    <div id="mov-resProveedor">
      <div style="text-align:center;padding:60px 20px;color:#888;background:#fff;border:1.5px solid var(--borde);border-radius:10px;">
        Cargando proveedores...
      </div>
    </div>
  </div>

  <!-- TAB: Por Producto -->
  <div class="mov-panel" id="mov-tab-por-producto">
    <div id="mov-resProducto">
      <div style="text-align:center;padding:60px 20px;color:#888;background:#fff;border:1.5px solid var(--borde);border-radius:10px;">
        Cargando productos...
      </div>
    </div>
  </div>

  <!-- TAB: Auditoría -->
  <div class="mov-panel" id="mov-tab-auditoria">
    <div style="background:#fef3c7;border:1.5px solid #f59e0b;border-radius:10px;padding:12px 16px;margin-bottom:14px;font-size:.85rem;color:#78350f;">
      <i class="fas fa-circle-info"></i>
      Esta vista detecta <strong>inconsistencias</strong> en los datos sincronizados desde OIRSA.
      No corrige nada — sólo te muestra dónde mirar para auditar.
    </div>
    <div id="mov-auditoria">
      <div style="text-align:center;padding:60px 20px;color:#888;background:#fff;border:1.5px solid var(--borde);border-radius:10px;">
        Cargando auditoría...
      </div>
    </div>
  </div>

</div><!-- /content -->

<?php
$jsExtra = '<script src="' . asset('public/assets/js/modules/movilizaciones_oirsa.js') . '"></script>';
require ROOT_PATH . '/app/views/layouts/footer.php';
?>
