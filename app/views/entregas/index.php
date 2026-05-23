<?php
$cssExtra = '<style>
/* ── ENTREGAS / MOVIMIENTOS TRAZARAGRO ───────────────────────── */
.kpi-ent{background:#fff;border-radius:12px;border:1.5px solid var(--borde);padding:14px 16px;display:flex;align-items:center;gap:12px;transition:transform .15s;}
.kpi-ent:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(84,102,142,.08);}
.kpi-ent .kpi-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
.kpi-ent .kpi-val{font-size:1.35rem;font-weight:800;line-height:1;}
.kpi-ent .kpi-lbl{font-size:.7rem;color:#888;margin-top:3px;text-transform:uppercase;letter-spacing:.3px;}

/* Banner informativo */
.info-banner{background:linear-gradient(90deg,#d1fae5,#ecfdf5);border:1.5px solid #16a34a;border-radius:10px;padding:10px 16px;margin-bottom:14px;display:flex;align-items:center;gap:10px;font-size:.82rem;color:#065f46;}
.info-banner.empty{background:linear-gradient(90deg,#fef3c7,#fef9c3);border-color:#f59e0b;color:#78350f;}
.info-banner i{font-size:1.05rem;}

/* Botones */
.btn-sync{background:#0d9488;color:#fff;border:none;padding:10px 18px;border-radius:9px;font-size:.84rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all .15s;text-decoration:none;}
.btn-sync:hover{background:#0f766e;color:#fff;}
.btn-sync.is-loading{opacity:.7;cursor:wait;}
.btn-sync .fa-rotate{transition:transform .6s;}
.btn-sync.is-loading .fa-rotate{animation:spin 1s linear infinite;}
@keyframes spin{to{transform:rotate(360deg);}}

/* Filtros */
.ent-filtros{background:#f8fafc;border:1.5px solid var(--borde);border-radius:10px;padding:12px 14px;margin-bottom:14px;display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;}
.ent-filtros .fl{font-size:.68rem;font-weight:700;color:#555;margin-bottom:3px;text-transform:uppercase;letter-spacing:.3px;}
.ent-filtros .fc{padding:6px 9px;border:1.5px solid var(--borde);border-radius:6px;font-size:.78rem;width:100%;background:#fff;}
.ent-filtros .fc:focus{outline:none;border-color:var(--primario);}

/* Tabla estilo OIRSA */
.tbl-oirsa-wrap{background:#fff;border:1.5px solid var(--borde);border-radius:10px;overflow:auto;max-height:70vh;}
.tbl-oirsa{width:100%;border-collapse:collapse;font-size:.74rem;min-width:1800px;}
.tbl-oirsa thead{position:sticky;top:0;z-index:5;}
.tbl-oirsa thead th{background:#e8742c;color:#fff;padding:8px 8px;font-size:.65rem;font-weight:700;text-transform:uppercase;text-align:left;white-space:nowrap;border-right:1px solid rgba(255,255,255,.2);}
.tbl-oirsa thead th.grp-origen{background:#1e3a8a;}
.tbl-oirsa thead th.grp-destino{background:#0d9488;}
.tbl-oirsa tbody tr{border-bottom:1px solid #f1f5f9;cursor:pointer;}
.tbl-oirsa tbody tr:hover{background:#fff8f3;}
.tbl-oirsa tbody tr:nth-child(even){background:#fafbfc;}
.tbl-oirsa tbody tr:nth-child(even):hover{background:#fff3e8;}
.tbl-oirsa tbody td{padding:7px 8px;vertical-align:top;white-space:nowrap;border-right:1px solid #f1f5f9;}
.tbl-oirsa tbody td.wrap{white-space:normal;max-width:220px;}
.tbl-oirsa .cantidad{text-align:right;font-weight:700;}
.tbl-oirsa .sub{color:#888;font-size:.7rem;}

/* Estado badges */
.est-badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:.65rem;font-weight:700;white-space:nowrap;}
.est-entregado{background:#d1fae5;color:#065f46;}
.est-pendiente{background:#fef3c7;color:#92400e;}
.est-observado{background:#fed7aa;color:#9a3412;}
.est-anulado  {background:#fee2e2;color:#991b1b;}

/* Desglose por objeto */
.desglose-card{background:#fff;border:1.5px solid var(--borde);border-radius:12px;margin-bottom:14px;overflow:hidden;}
.desglose-head{background:#f8fafc;padding:10px 14px;display:flex;align-items:center;justify-content:space-between;cursor:pointer;border-bottom:1px solid var(--borde);}
.desglose-head h6{margin:0;font-size:.85rem;font-weight:700;color:#1a1a1a;}
.desglose-body{padding:0;}
.tbl-desg{width:100%;border-collapse:collapse;font-size:.78rem;}
.tbl-desg th{background:var(--primario);color:#fff;padding:7px 10px;font-size:.65rem;text-transform:uppercase;font-weight:700;text-align:left;}
.tbl-desg th.num{text-align:right;}
.tbl-desg td{padding:6px 10px;border-bottom:1px solid #f1f5f9;}
.tbl-desg td.num{text-align:right;font-weight:600;}
.tbl-desg tr:nth-child(even){background:#fafbfc;}

/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1050;align-items:center;justify-content:center;padding:16px;}
.modal-overlay.show{display:flex;}
.modal-box{background:#fff;border-radius:14px;width:100%;max-width:920px;max-height:92vh;display:flex;flex-direction:column;box-shadow:0 25px 60px rgba(0,0,0,.25);}
.modal-head{padding:14px 20px;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between;}
.modal-head h5{font-size:1rem;font-weight:700;color:#1a1a1a;margin:0;}
.modal-body-inner{padding:18px 20px;overflow-y:auto;flex:1;}
.modal-foot{padding:12px 20px;border-top:1px solid #eee;display:flex;justify-content:flex-end;gap:8px;}
.btn-close-x{background:none;border:none;font-size:1.3rem;color:#999;cursor:pointer;line-height:1;padding:0;}
.btn-close-x:hover{color:#333;}
.det-grid{display:grid;grid-template-columns:140px 1fr;gap:8px 14px;font-size:.84rem;}
.det-grid label{color:#666;font-weight:600;}
.det-grid .v{color:#1a1a1a;}
.det-section{margin-bottom:18px;}
.det-section-title{font-size:.74rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--primario);border-bottom:1.5px solid var(--primario);padding-bottom:4px;margin-bottom:10px;}
</style>';

$progSigla = $_SESSION['programa']['sigla']  ?? '';
$progColor = $_SESSION['programa']['color']  ?? '#54668E';
$prog      = $_SESSION['programa'] ?? [];

require ROOT_PATH . '/app/views/layouts/header.php';
require ROOT_PATH . '/app/views/layouts/sidebar.php';
require ROOT_PATH . '/app/views/layouts/topbar.php';

// Pasar movimientos a JS para el filtrado client-side
$pMovs = json_encode($movimientos ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_APOS);
?>

<!-- ══ CONTENT ══ -->
<div class="content">

  <!-- Page Header -->
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <div class="page-title">
        <i class="fas fa-truck-ramp-box" style="color:#e8742c;"></i>
        Entregas de Incentivos
        <small><?= htmlspecialchars($progSigla) ?> &mdash; Movimientos sincronizados desde Trazaragro (OIRSA)</small>
      </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <button class="btn-sync" id="btnSincronizarTrazaragro" style="background:#1e3a8a;" title="Clic = incremental · Shift+Clic = limpia BD y re-sincroniza desde cero">
        <i class="fas fa-rotate"></i> Sincronizar con Trazaragro
      </button>
      <a class="btn-sync" href="<?= BASE_URL ?>/entregas/exportar" style="background:#16a34a;" title="Descargar CSV (formato OIRSA)">
        <i class="fas fa-file-csv"></i> Descargar reporte
      </a>
    </div>
  </div>

  <!-- Banner informativo -->
  <?php if (empty($movimientos)): ?>
  <div class="info-banner empty">
    <i class="fas fa-circle-info"></i>
    <div>
      <strong>Sin movimientos aún.</strong> Da clic en <strong>"Sincronizar con Trazaragro"</strong> para traer los manifiestos de OIRSA correspondientes al rubro de <?= htmlspecialchars($progSigla) ?>.
    </div>
  </div>
  <?php else: ?>
  <div class="info-banner">
    <i class="fas fa-circle-check"></i>
    <div>
      <strong>Datos reales de OIRSA</strong> &mdash; <?= count($movimientos) ?> movimientos sincronizados.
      <strong>Clic normal</strong> = sync incremental · <strong>Shift+Clic</strong> = limpia BD y re-sincroniza desde cero.
    </div>
  </div>
  <?php endif; ?>

  <!-- KPIs principales -->
  <div class="row g-2 mb-3">
    <div class="col-6 col-md-3 col-lg-2">
      <div class="kpi-ent">
        <div class="kpi-icon" style="background:#fef3c7;color:#e8742c;"><i class="fas fa-list-check"></i></div>
        <div><div class="kpi-val"><?= number_format($kpis['total_movimientos']) ?></div><div class="kpi-lbl">Movimientos</div></div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
      <div class="kpi-ent">
        <div class="kpi-icon" style="background:#d1fae5;color:#16a34a;"><i class="fas fa-circle-check"></i></div>
        <div><div class="kpi-val" style="color:#16a34a;"><?= number_format($kpis['entregados']) ?></div><div class="kpi-lbl">Entregados</div></div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
      <div class="kpi-ent">
        <div class="kpi-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-clock"></i></div>
        <div><div class="kpi-val" style="color:#d97706;"><?= number_format($kpis['pendientes']) ?></div><div class="kpi-lbl">Pendientes</div></div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
      <div class="kpi-ent">
        <div class="kpi-icon" style="background:#dbeafe;color:#1e40af;"><i class="fas fa-file-invoice"></i></div>
        <div><div class="kpi-val" style="color:#1e40af;"><?= number_format($kpis['manifiestos_unicos']) ?></div><div class="kpi-lbl">Manifiestos (GUIASA)</div></div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
      <div class="kpi-ent">
        <div class="kpi-icon" style="background:#eef0f7;color:var(--primario);"><i class="fas fa-users"></i></div>
        <div><div class="kpi-val"><?= number_format($kpis['beneficiarios_unicos']) ?></div><div class="kpi-lbl">Beneficiarios</div></div>
      </div>
    </div>
    <div class="col-6 col-md-3 col-lg-2">
      <div class="kpi-ent">
        <div class="kpi-icon" style="background:#fed7aa;color:#9a3412;"><i class="fas fa-boxes-stacked"></i></div>
        <div><div class="kpi-val" style="color:#9a3412;"><?= number_format($kpis['cantidad_total'], 0) ?></div><div class="kpi-lbl">Total unidades</div></div>
      </div>
    </div>
  </div>

  <!-- Desglose por objeto trazable -->
  <?php if (!empty($kpis['desglose_objetos'])): ?>
  <div class="desglose-card">
    <div class="desglose-head" onclick="document.getElementById('desgloseBody').classList.toggle('d-none')">
      <h6><i class="fas fa-layer-group" style="margin-right:6px;color:#e8742c;"></i>
          Insumos sincronizados — <?= count($kpis['desglose_objetos']) ?> tipo(s) de producto</h6>
      <small style="color:#666;">Clic para mostrar/ocultar</small>
    </div>
    <div id="desgloseBody" class="desglose-body">
      <table class="tbl-desg">
        <thead>
          <tr>
            <th style="width:55%;">Objeto trazable</th>
            <th style="text-align:center;">Unidad</th>
            <th class="num">Movimientos</th>
            <th class="num">Entregados</th>
            <th class="num">Pendientes</th>
            <th class="num">Cantidad total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($kpis['desglose_objetos'] as $o): ?>
          <tr>
            <td><?= htmlspecialchars($o['objeto']) ?></td>
            <td style="text-align:center;color:#666;"><?= htmlspecialchars($o['unidad']) ?></td>
            <td class="num"><?= number_format($o['movimientos']) ?></td>
            <td class="num" style="color:#16a34a;"><?= number_format($o['entregados']) ?></td>
            <td class="num" style="color:#d97706;"><?= number_format($o['pendientes']) ?></td>
            <td class="num"><?= number_format($o['cantidad'], 0) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Filtros -->
  <div class="ent-filtros">
    <div>
      <div class="fl">Rubro</div>
      <select class="fc" id="fRubro"><option value="">Todos</option>
        <?php foreach (($catalogos['rubros'] ?? []) as $r): ?>
          <option><?= htmlspecialchars($r) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <div class="fl">Tipo movimiento</div>
      <select class="fc" id="fTipo"><option value="">Todos</option>
        <?php foreach (($catalogos['tipos'] ?? []) as $t): ?>
          <option><?= htmlspecialchars($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <div class="fl">Objeto trazable</div>
      <select class="fc" id="fObjeto"><option value="">Todos</option>
        <?php foreach (($catalogos['objetos'] ?? []) as $o): ?>
          <option><?= htmlspecialchars($o) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <div class="fl">Departamento (destino)</div>
      <select class="fc" id="fDepto"><option value="">Todos</option>
        <?php foreach (($catalogos['deptos'] ?? []) as $d): ?>
          <option><?= htmlspecialchars($d) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <div class="fl">Estado</div>
      <select class="fc" id="fEstado">
        <option value="">Todos</option>
        <option value="entregado">Entregado</option>
        <option value="pendiente">Pendiente</option>
        <option value="observado">Observado</option>
      </select>
    </div>
    <div>
      <div class="fl">Desde</div>
      <input type="date" class="fc" id="fDesde"/>
    </div>
    <div>
      <div class="fl">Hasta</div>
      <input type="date" class="fc" id="fHasta"/>
    </div>
    <div>
      <div class="fl">Buscar (DNI/nombre/GUIASA)</div>
      <input type="text" class="fc" id="fBusca" placeholder="Texto libre"/>
    </div>
    <div style="display:flex;align-items:flex-end;">
      <button class="fc" id="btnLimpiarFiltros" style="background:#eef0f7;cursor:pointer;font-weight:700;">
        <i class="fas fa-broom"></i> Limpiar
      </button>
    </div>
  </div>

  <!-- Tabla principal estilo OIRSA -->
  <div class="tbl-oirsa-wrap">
    <table class="tbl-oirsa" id="tblOirsa">
      <thead>
        <tr>
          <th>Rubro</th>
          <th>Tipo de movimiento</th>
          <th>Objeto trazable</th>
          <th>Código de Trazabilidad</th>
          <th>GUIASA No.</th>
          <th>Código de autorización</th>
          <th>Fecha</th>
          <th class="grp-origen">Origen — Persona</th>
          <th class="grp-origen">Origen — Establecimiento</th>
          <th class="grp-origen">Origen — Depto</th>
          <th class="grp-destino">Destino — Persona</th>
          <th class="grp-destino">Destino — DNI</th>
          <th class="grp-destino">Destino — Establecimiento</th>
          <th class="grp-destino">Destino — Depto / Municipio</th>
          <th style="text-align:right;">Cantidad</th>
          <th>Unidad</th>
          <th>Autorizado por</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody id="tbodyOirsa">
        <!-- renderizado por JS -->
      </tbody>
    </table>
  </div>
  <div id="contadorFiltrados" style="margin-top:8px;font-size:.78rem;color:#666;text-align:right;"></div>

</div>

<!-- ══ Modal Detalle ══ -->
<div class="modal-overlay" id="modalDetalle">
  <div class="modal-box">
    <div class="modal-head">
      <h5><i class="fas fa-file-lines"></i> Detalle del Movimiento</h5>
      <button class="btn-close-x" onclick="cerrarDetalle()">×</button>
    </div>
    <div class="modal-body-inner" id="detBody">
      <!-- llenado por JS -->
    </div>
    <div class="modal-foot">
      <button class="btn-sync" style="background:#6b7280;" onclick="cerrarDetalle()">Cerrar</button>
    </div>
  </div>
</div>

<script>
window.OIRSA_MOVS = <?= $pMovs ?>;
</script>

<?php
$jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/entregas.js"></script>';
require ROOT_PATH . '/app/views/layouts/footer.php';
?>
