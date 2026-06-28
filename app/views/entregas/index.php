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
.avance-bar{height:9px;background:#e5e7eb;border-radius:999px;overflow:hidden;margin-top:5px;}
.avance-bar>span{display:block;height:100%;background:linear-gradient(90deg,#16a34a,#0d9488);border-radius:999px;}
.avance-depto-card{background:#fff;border:1.5px solid var(--borde);border-radius:10px;padding:14px 16px;height:100%;}
.avance-depto-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-top:12px;padding-top:10px;border-top:1px dashed #e5e7eb;}
.avance-depto-k{font-size:.62rem;color:#888;text-transform:uppercase;}
.avance-depto-v{font-size:1.05rem;font-weight:800;line-height:1.15;}
.avance-detail-wrap{margin-top:14px;max-height:58vh;overflow:auto;padding-right:4px;}
.avance-row{background:#fff;border:1.5px solid var(--borde);border-radius:10px;margin-bottom:8px;overflow:hidden;}
.avance-row .avance-depto-card{border:0;border-radius:0;}
.avance-row-head{width:100%;border:none;background:#fff;padding:12px 14px;display:grid;grid-template-columns:1.2fr .75fr .75fr .75fr .75fr 28px;gap:12px;align-items:center;text-align:left;cursor:pointer;}
.avance-row-head:hover{background:#f8fafc;}
.avance-row-title{font-weight:800;color:#111827;}
.avance-row-sub{font-size:.72rem;color:#6b7280;margin-top:2px;}
.avance-row-metric .k{font-size:.62rem;color:#888;text-transform:uppercase;}
.avance-row-metric .v{font-size:1rem;font-weight:800;line-height:1.15;}
.avance-row-body{display:none;border-top:1px dashed #e5e7eb;padding:12px 14px;background:#fff;}
.avance-row.open .avance-row-body{display:block;}
.avance-row.open .avance-chevron{transform:rotate(180deg);}
.avance-chevron{transition:transform .15s;color:#6b7280;text-align:right;}
.avance-products-list{max-height:180px;overflow:auto;padding-right:4px;}
@media (max-width: 900px){.avance-row-head{grid-template-columns:1fr 1fr;}.avance-chevron{text-align:left;}}

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
      <!-- FASE 3: Indicador de última sincronización -->
      <?php if (!empty($ultimaSyncedAt)):
        $tsSync   = strtotime($ultimaSyncedAt);
        $segDif   = max(0, time() - $tsSync);
        if     ($segDif < 60)        $hace = 'hace ' . $segDif . ' s';
        elseif ($segDif < 3600)      $hace = 'hace ' . floor($segDif/60) . ' min';
        elseif ($segDif < 86400)     $hace = 'hace ' . floor($segDif/3600) . ' h';
        elseif ($segDif < 86400 * 2) $hace = 'ayer';
        elseif ($segDif < 86400 * 7) $hace = 'hace ' . floor($segDif/86400) . ' días';
        else                          $hace = 'hace ' . floor($segDif/86400) . ' días';
        $fechaTxt = date('d/m/Y H:i', $tsSync);
        // Color del chip según frescura: < 24h verde · < 7 días amarillo · más rojo
        if    ($segDif < 86400)       $colorChip = ['#dcfce7','#166534'];
        elseif($segDif < 86400 * 7)   $colorChip = ['#fef3c7','#92400e'];
        else                          $colorChip = ['#fee2e2','#991b1b'];
      ?>
      <div title="Última sincronización: <?= $fechaTxt ?>" style="display:inline-flex;align-items:center;gap:6px;background:<?= $colorChip[0] ?>;color:<?= $colorChip[1] ?>;padding:6px 12px;border-radius:8px;font-size:.78rem;font-weight:600;">
        <i class="fas fa-clock"></i> Última sync: <strong><?= $hace ?></strong>
      </div>
      <?php else: ?>
      <div style="display:inline-flex;align-items:center;gap:6px;background:#f1f5f9;color:#475569;padding:6px 12px;border-radius:8px;font-size:.78rem;font-weight:600;">
        <i class="fas fa-clock"></i> Sin sincronizar aún
      </div>
      <?php endif; ?>
      <!-- Grupo: botón principal + opciones avanzadas -->
      <div class="btn-group" role="group">
        <button class="btn-sync" id="btnSincronizarTrazaragro" style="background:#1e3a8a;border-top-right-radius:0;border-bottom-right-radius:0;" title="Clic = incremental · Shift+Clic = histórico completo desde 2026-04-01">
          <i class="fas fa-rotate"></i> Sincronizar con Trazaragro
        </button>
        <button type="button" class="btn-sync dropdown-toggle dropdown-toggle-split" id="btnSyncOpciones" data-bs-toggle="dropdown" aria-expanded="false" style="background:#1e3a8a;border-left:1px solid rgba(255,255,255,.25);border-top-left-radius:0;border-bottom-left-radius:0;padding:0 12px;" title="Opciones de sincronización">
          <span class="visually-hidden">Opciones</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" style="font-size:.88rem;min-width:260px;">
          <li><h6 class="dropdown-header" style="font-size:.72rem;text-transform:uppercase;">Sincronización personalizada</h6></li>
          <li><a class="dropdown-item" href="#" data-sync-modo="guiasa"><i class="fas fa-file-invoice me-2" style="color:#9a3412;width:18px;"></i> Por GUIASA específica</a></li>
          <li><a class="dropdown-item" href="#" data-sync-modo="bodega"><i class="fas fa-warehouse me-2" style="color:#1e40af;width:18px;"></i> Por bodega (CUE)</a></li>
          <li><a class="dropdown-item" href="#" data-sync-modo="rango"><i class="fas fa-calendar-range me-2" style="color:#7c3aed;width:18px;"></i> Por rango de fechas</a></li>
          <li><a class="dropdown-item" href="#" data-sync-modo="departamento"><i class="fas fa-map-location-dot me-2" style="color:#16a34a;width:18px;"></i> Por departamento</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="#" data-sync-modo="historico"><i class="fas fa-clock-rotate-left me-2" style="color:#dc2626;width:18px;"></i> Histórico completo desde 2026-04-01</a></li>
        </ul>
      </div>
      <a class="btn-sync" href="<?= BASE_URL ?>/entregas/exportar" style="background:#16a34a;" title="Descargar CSV (formato OIRSA)">
        <i class="fas fa-file-csv"></i> Descargar reporte
      </a>
    </div>

    <!-- ══ Modal: Sincronización personalizada ══ -->
    <div class="modal fade" id="modalSyncPers" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header" style="background:#1e3a8a;color:#fff;">
            <h5 class="modal-title"><i class="fas fa-rotate me-2"></i> <span id="syncPersTitulo">Sincronización personalizada</span></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p id="syncPersDesc" style="font-size:.88rem;color:#475569;"></p>

            <!-- Campos por modo -->
            <div id="syncCampoGuiasa" style="display:none;">
              <label class="form-label" style="font-weight:600;">Número de GUIASA</label>
              <input type="text" class="form-control" id="syncInputGuiasa" placeholder="Ej: EH0000210" autocomplete="off">
              <small style="color:#64748b;">Trae todos los movimientos de OIRSA que contengan esta guía. Útil para corregir guías modificadas.</small>
            </div>
            <div id="syncCampoBodega" style="display:none;">
              <label class="form-label" style="font-weight:600;">CUE de la bodega</label>
              <input type="text" class="form-control" id="syncInputBodega" placeholder="Ej: CUE-001-23" autocomplete="off">
              <small style="color:#64748b;">Trae movimientos donde esta bodega sea origen o destino.</small>
            </div>
            <div id="syncCampoDepto" style="display:none;">
              <label class="form-label" style="font-weight:600;">Departamento</label>
              <select class="form-control" id="syncInputDepto">
                <option value="">— Seleccione un departamento —</option>
                <option>Atlántida</option>
                <option>Choluteca</option>
                <option>Colón</option>
                <option>Comayagua</option>
                <option>Copán</option>
                <option>Cortés</option>
                <option>El Paraíso</option>
                <option>Francisco Morazán</option>
                <option>Gracias a Dios</option>
                <option>Intibucá</option>
                <option>Islas de la Bahía</option>
                <option>La Paz</option>
                <option>Lempira</option>
                <option>Ocotepeque</option>
                <option>Olancho</option>
                <option>Santa Bárbara</option>
                <option>Valle</option>
                <option>Yoro</option>
              </select>
              <small style="color:#64748b;">Trae movimientos donde el departamento de origen o destino contenga este nombre.</small>
            </div>
            <div id="syncCampoRango" style="display:none;">
              <div class="row g-2">
                <div class="col-6">
                  <label class="form-label" style="font-weight:600;">Desde</label>
                  <input type="date" class="form-control" id="syncInputDesde" min="2026-04-01">
                </div>
                <div class="col-6">
                  <label class="form-label" style="font-weight:600;">Hasta</label>
                  <input type="date" class="form-control" id="syncInputHasta">
                </div>
              </div>
              <small style="color:#64748b;">No se puede ir antes del 01/04/2026 (inicio de los programas).</small>
            </div>
            <div id="syncCampoHistorico" style="display:none;">
              <div style="background:#fee2e2;color:#991b1b;border-left:4px solid #dc2626;padding:12px 14px;border-radius:6px;font-size:.88rem;">
                <strong>⚠️ Cuidado.</strong> Esta opción <strong>borra todos los movimientos del programa</strong> y los re-descarga completos desde el 01/04/2026. Puede tardar varios minutos.
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnSyncPersConfirmar" style="background:#1e3a8a;border-color:#1e3a8a;">
              <i class="fas fa-rotate me-1"></i> Sincronizar
            </button>
          </div>
        </div>
      </div>
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
    <i class="fas fa-circle-info"></i>
    <div>
      <strong>Clic normal</strong> = sync incremental · <strong>Shift+Clic</strong> = limpia BD y re-sincroniza desde cero.
    </div>
  </div>
  <?php endif; ?>

  <!-- ══ TABS DE NAVEGACIÓN ══ -->
  <div class="mode-tabs" style="margin-bottom:14px;">
    <button class="mode-tab active" onclick="switchEntregasTab('resumen')">
      <i class="fas fa-chart-pie"></i> Resumen
    </button>
    <button class="mode-tab" onclick="switchEntregasTab('movimientos')">
      <i class="fas fa-list-ul"></i> Movimientos <small style="opacity:.7;">(<?= count($movimientos ?? []) ?>)</small>
    </button>
    <button class="mode-tab" onclick="switchEntregasTab('departamentos')">
      <i class="fas fa-map-location-dot"></i> Por Departamento <small style="opacity:.7;">(<?= count($reporteDepartamentos ?? []) ?>)</small>
    </button>
    <button class="mode-tab" onclick="switchEntregasTab('productores')">
      <i class="fas fa-user-tag"></i> Por Productor <small style="opacity:.7;">(<?= count($reporteProductores ?? []) ?>)</small>
    </button>
    <button class="mode-tab" onclick="switchEntregasTab('bodegas')">
      <i class="fas fa-warehouse"></i> Por Bodega <small style="opacity:.7;">(<?= count($reporteBodegas ?? []) ?>)</small>
    </button>
    <button class="mode-tab" onclick="switchEntregasTab('anomalias')" <?= !empty($anomalias) ? 'style="color:#92400e;"' : '' ?>>
      <i class="fas fa-triangle-exclamation"></i> Anomalías
      <?php if (!empty($anomalias)): ?>
        <span style="background:#dc2626;color:#fff;padding:1px 7px;border-radius:10px;font-size:.7rem;margin-left:4px;font-weight:700;"><?= count($anomalias) ?></span>
      <?php else: ?>
        <small style="opacity:.7;">(0)</small>
      <?php endif; ?>
    </button>
  </div>

  <!-- ══ TAB: RESUMEN ══ -->
  <div id="tab-ent-resumen">

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
    <?php if (($kpis['con_alertas'] ?? 0) > 0): ?>
    <div class="col-6 col-md-3 col-lg-2">
      <div class="kpi-ent" style="border-color:#f59e0b;border-width:2px;">
        <div class="kpi-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-triangle-exclamation"></i></div>
        <div><div class="kpi-val" style="color:#d97706;"><?= number_format($kpis['con_alertas']) ?></div><div class="kpi-lbl">Con alertas</div></div>
      </div>
    </div>
    <?php endif; ?>
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

  </div> <!-- /tab-ent-resumen -->

  <!-- ══ TAB: POR DEPARTAMENTO ══ -->
  <div id="tab-ent-departamentos" style="display:none;">
    <?php if (empty($reporteDepartamentos)): ?>
      <div style="text-align:center;padding:60px 20px;color:#888;background:#fff;border:1.5px solid var(--borde);border-radius:10px;">
        <i class="fas fa-map-location-dot" style="font-size:2rem;margin-bottom:10px;display:block;color:#bbb;"></i>
        Sin departamentos con entregas registradas. Sincroniza con Trazaragro para ver el avance territorial.
      </div>
    <?php else: ?>
      <div style="margin-bottom:10px;font-size:.85rem;color:#666;">
        <i class="fas fa-info-circle"></i>
        Avance calculado con movimientos OIRSA: <strong>entradas a bodega (113 + 112 destino)</strong> contra <strong>salidas desde bodega (111 + 112 origen)</strong>, sumando todas las bodegas del departamento.
      </div>

      <div style="background:#fff;border:1.5px solid var(--borde);border-radius:10px;overflow:auto;margin-bottom:14px;">
        <table class="tbl-desg" style="min-width:960px;">
          <thead>
            <tr>
              <th>Departamento</th>
              <th class="num">Avance</th>
              <th class="num">Entradas OIRSA</th>
              <th class="num">Salidas OIRSA</th>
              <th class="num">Saldo</th>
              <th class="num">Bodegas</th>
              <th class="num">GUIASA</th>
              <th class="num">Beneficiarios</th>
              <th class="num">Mov. entrega</th>
              <th>Último movimiento</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reporteDepartamentos as $d):
              $pctReal = (float)($d['avance_pct'] ?? 0);
              $pctBar = max(0, min(100, $pctReal));
              $saldo = (float)($d['saldo_inventario'] ?? 0);
            ?>
            <tr>
              <td><strong><?= htmlspecialchars($d['departamento']) ?></strong></td>
              <td class="num" style="min-width:150px;">
                <strong style="color:<?= $saldo < 0 ? '#dc2626' : '#0d9488' ?>;"><?= number_format($pctReal, 1) ?>%</strong>
                <div class="avance-bar"><span style="width:<?= $pctBar ?>%;background:<?= $saldo < 0 ? 'linear-gradient(90deg,#f97316,#dc2626)' : 'linear-gradient(90deg,#16a34a,#0d9488)' ?>;"></span></div>
              </td>
              <td class="num"><?= number_format($d['inventario_registrado'], 0) ?></td>
              <td class="num" style="color:#16a34a;"><?= number_format($d['cantidad_entregada'], 0) ?></td>
              <td class="num" style="color:<?= $saldo < 0 ? '#dc2626' : '#d97706' ?>;"><?= number_format($saldo, 0) ?></td>
              <td class="num"><?= number_format($d['bodegas'] ?? $d['municipios'] ?? 0) ?></td>
              <td class="num"><?= number_format($d['manifiestos_unicos']) ?></td>
              <td class="num"><?= number_format($d['beneficiarios_unicos']) ?></td>
              <td class="num"><?= number_format($d['movimientos']) ?></td>
              <td><?= htmlspecialchars(substr((string)$d['ultimo_movimiento'], 0, 10) ?: '—') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="margin:4px 0 8px;font-size:.82rem;color:#555;font-weight:700;">
        <i class="fas fa-list-check"></i> Detalle por departamento
        <span style="font-weight:400;color:#888;">— revisar desplazando la lista</span>
      </div>

      <div class="avance-detail-wrap">
        <?php foreach ($reporteDepartamentos as $d):
          $pctReal = (float)($d['avance_pct'] ?? 0);
          $pctBar = max(0, min(100, $pctReal));
          $saldo = (float)($d['saldo_inventario'] ?? 0);
        ?>
        <div class="avance-row">
          <div class="avance-depto-card">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
              <div style="min-width:0;">
                <strong style="font-size:.95rem;color:#1a1a1a;display:block;"><?= htmlspecialchars($d['departamento']) ?></strong>
                <div style="font-size:.72rem;color:#888;margin-top:2px;">
                  <?= number_format($d['bodegas'] ?? $d['municipios'] ?? 0) ?> bodega(s) · Último: <?= htmlspecialchars(substr((string)$d['ultimo_movimiento'], 0, 10) ?: '—') ?>
                </div>
              </div>
              <div style="text-align:right;flex-shrink:0;">
                <div style="font-size:1.25rem;font-weight:900;color:<?= $saldo < 0 ? '#dc2626' : '#0d9488' ?>;line-height:1;"><?= number_format($pctReal, 1) ?>%</div>
                <div style="font-size:.62rem;color:#888;text-transform:uppercase;">avance</div>
              </div>
            </div>

            <div class="avance-bar" title="<?= number_format($pctReal, 1) ?>% de salidas contra entradas OIRSA">
              <span style="width:<?= $pctBar ?>%;background:<?= $saldo < 0 ? 'linear-gradient(90deg,#f97316,#dc2626)' : 'linear-gradient(90deg,#16a34a,#0d9488)' ?>;"></span>
            </div>

            <div class="avance-depto-grid">
              <div>
                <div class="avance-depto-k">Entradas</div>
                <div class="avance-depto-v" style="color:#1e40af;"><?= number_format($d['inventario_registrado'], 0) ?></div>
              </div>
              <div>
                <div class="avance-depto-k">Salidas</div>
                <div class="avance-depto-v" style="color:#16a34a;"><?= number_format($d['cantidad_entregada'], 0) ?></div>
              </div>
              <div>
                <div class="avance-depto-k">Saldo</div>
                <div class="avance-depto-v" style="color:<?= $saldo < 0 ? '#dc2626' : '#d97706' ?>;"><?= number_format($saldo, 0) ?></div>
              </div>
              <div>
                <div class="avance-depto-k">GUIASA</div>
                <div class="avance-depto-v" style="color:#9a3412;"><?= number_format($d['manifiestos_unicos']) ?></div>
              </div>
            </div>

            <?php if ($saldo < 0): ?>
            <div style="margin-top:10px;background:#fee2e2;color:#991b1b;border-radius:7px;padding:7px 9px;font-size:.72rem;font-weight:700;">
              <i class="fas fa-triangle-exclamation"></i>
              Las salidas superan las entradas registradas por <?= number_format(abs($saldo), 0) ?> unidad(es).
            </div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:10px;font-size:.74rem;">
              <div style="color:#555;">
                <i class="fas fa-users" style="color:var(--primario);"></i>
                Beneficiarios: <strong><?= number_format($d['beneficiarios_unicos']) ?></strong>
              </div>
              <div style="color:#555;text-align:right;">
                <i class="fas fa-file-invoice" style="color:#7c3aed;"></i>
                Mov. entrega: <strong><?= number_format($d['movimientos']) ?></strong>
              </div>
            </div>

            <?php if (!empty($d['top_objetos'])): ?>
            <div style="margin-top:10px;padding-top:8px;border-top:1px dashed #e5e7eb;">
              <div style="font-size:.62rem;color:#888;text-transform:uppercase;margin-bottom:4px;">Productos entregados</div>
              <div class="avance-products-list">
              <?php foreach ($d['top_objetos'] as $to): ?>
              <div style="display:flex;justify-content:space-between;font-size:.74rem;padding:2px 0;">
                <span style="color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;min-width:0;margin-right:6px;"><?= htmlspecialchars($to['objeto']) ?></span>
                <strong style="color:#0f766e;flex-shrink:0;"><?= number_format($to['cantidad']) ?></strong>
              </div>
              <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>
  </div>

  <!-- ══ TAB: MOVIMIENTOS ══ -->
  <div id="tab-ent-movimientos" style="display:none;">

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

  </div> <!-- /tab-ent-movimientos -->

  <!-- ══ TAB: POR PRODUCTOR ══ -->
  <div id="tab-ent-productores" style="display:none;">
    <?php if (empty($reporteProductores)): ?>
      <div style="text-align:center;padding:60px 20px;color:#888;background:#fff;border:1.5px solid var(--borde);border-radius:10px;">
        <i class="fas fa-user-tag" style="font-size:2rem;margin-bottom:10px;display:block;color:#bbb;"></i>
        Sin productores aún. Sincroniza con Trazaragro para ver el reporte.
      </div>
    <?php else: ?>
      <div style="margin-bottom:10px;font-size:.85rem;color:#666;">
        <i class="fas fa-info-circle"></i>
        <strong><?= count($reporteProductores) ?> productor(es)</strong> con entregas registradas. Tarjeta con fondo amarillento = el productor tiene alguna alerta.
      </div>
      <div class="ent-filtros" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
        <div>
          <div class="fl">Departamento</div>
          <select class="fc" id="fpDepto"><option value="">Todos</option>
            <?php foreach (($catalogos['deptos'] ?? []) as $d): ?>
              <option><?= htmlspecialchars($d) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <div class="fl">Estado padrón</div>
          <select class="fc" id="fpPadron">
            <option value="">Todos</option>
            <option value="en_padron">En padrón</option>
            <option value="no_padron">No en padrón</option>
            <option value="sin_dni">Sin DNI</option>
          </select>
        </div>
        <div>
          <div class="fl">Buscar productor / DNI / GUIASA / producto</div>
          <input type="text" class="fc" id="fpBusca" placeholder="Texto libre..."/>
        </div>
        <div style="display:flex;align-items:flex-end;">
          <button class="fc" id="btnFpLimpiar" style="background:#eef0f7;cursor:pointer;font-weight:700;">
            <i class="fas fa-broom"></i> Limpiar
          </button>
        </div>
      </div>
      <div id="fpContador" style="margin:-4px 0 10px;font-size:.78rem;color:#666;text-align:right;"></div>
      <div id="listaProductores" style="background:#fff;border:1.5px solid var(--borde);border-radius:10px;overflow:hidden;">
      <?php foreach ($reporteProductores as $p): ?>
      <?php
        $prodSearch = strtolower(trim(implode(' ', array_filter([
            $p['nombre'] ?? '', $p['dni'] ?? '', $p['departamento'] ?? '',
            $p['municipio'] ?? '', $p['establecimiento'] ?? '',
            implode(' ', array_map(fn($o) => (($o['objeto'] ?? '') . ' ' . ($o['guiasa'] ?? '') . ' ' . ($o['codigo_traza'] ?? '')), $p['objetos'] ?? [])),
        ]))));
      ?>
      <div class="prod-card"
           data-depto="<?= htmlspecialchars($p['departamento']) ?>"
           data-padron="<?= htmlspecialchars($p['validacion']) ?>"
           data-search="<?= htmlspecialchars($prodSearch) ?>"
           style="padding:14px 16px;border-bottom:2px solid #f1f5f9;background:<?= $p['tiene_alerta'] ? '#fffbeb' : '#fff' ?>;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px;">
          <div style="flex:1;min-width:240px;">
            <strong style="font-size:.95rem;color:#1a1a1a;"><?= htmlspecialchars($p['nombre']) ?></strong>
            <?php if ($p['validacion'] === 'no_padron'): ?>
              <span style="background:#fed7aa;color:#9a3412;padding:2px 8px;border-radius:20px;font-size:.65rem;font-weight:700;margin-left:6px;">⚠ NO EN PADRÓN</span>
            <?php elseif ($p['validacion'] === 'en_padron'): ?>
              <span style="background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:20px;font-size:.65rem;font-weight:700;margin-left:6px;">✓ EN PADRÓN</span>
            <?php endif; ?>
            <div style="margin-top:3px;font-size:.78rem;color:#666;">
              <i class="fas fa-id-card" style="margin-right:3px;"></i>DNI: <strong><?= htmlspecialchars($p['dni']) ?></strong>
              <span style="margin:0 8px;color:#bbb;">·</span>
              <i class="fas fa-map-marker-alt" style="margin-right:3px;"></i>
              <?= htmlspecialchars($p['departamento']) ?><?= $p['municipio'] ? ' / ' . htmlspecialchars($p['municipio']) : '' ?>
              <?php if ($p['establecimiento']): ?>
                <span style="margin:0 8px;color:#bbb;">·</span>
                <i class="fas fa-house" style="margin-right:3px;"></i><?= htmlspecialchars($p['establecimiento']) ?>
              <?php endif; ?>
            </div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:1.2rem;font-weight:800;color:#16a34a;line-height:1;"><?= $p['num_objetos'] ?></div>
            <div style="font-size:.7rem;color:#666;text-transform:uppercase;">objetos</div>
            <div style="font-size:.7rem;color:#888;margin-top:3px;">
              <?= $p['num_manifiestos'] ?> GUIASA(s) · <span style="color:#16a34a;"><?= $p['entregados'] ?> entreg.</span> · <span style="color:#d97706;"><?= $p['pendientes'] ?> pend.</span>
            </div>
            <?php if (!empty($p['dni']) && $p['dni'] !== '(sin DNI)'): ?>
            <a href="<?= BASE_URL ?>/entregas/acta?dni=<?= urlencode($p['dni']) ?>" target="_blank"
               style="display:inline-block;margin-top:8px;padding:5px 12px;background:#0d9488;color:#fff;border-radius:6px;text-decoration:none;font-size:.72rem;font-weight:700;"
               title="Generar acta imprimible / PDF">
              <i class="fas fa-file-pdf"></i> Acta / PDF
            </a>
            <?php endif; ?>
          </div>
        </div>

        <table style="width:100%;margin-top:10px;font-size:.76rem;border-collapse:collapse;">
          <thead>
            <tr style="border-bottom:1.5px solid #e5e7eb;color:#555;text-transform:uppercase;font-size:.65rem;">
              <th style="text-align:left;padding:5px 6px;">Objeto trazable</th>
              <th style="text-align:left;padding:5px 6px;">Cód. trazabilidad</th>
              <th style="text-align:left;padding:5px 6px;">GUIASA</th>
              <th style="text-align:left;padding:5px 6px;">Fecha</th>
              <th style="text-align:right;padding:5px 6px;">Cantidad</th>
              <th style="text-align:left;padding:5px 6px;">Autorizó</th>
              <th style="text-align:center;padding:5px 6px;">Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($p['objetos'] as $o): ?>
            <tr style="border-bottom:1px dashed #f3f4f6;">
              <td style="padding:5px 6px;"><strong><?= htmlspecialchars($o['objeto']) ?></strong></td>
              <td style="padding:5px 6px;">
                <?php if ($o['codigo_traza']): ?>
                  <strong style="color:#0f766e;"><?= htmlspecialchars($o['codigo_traza']) ?></strong>
                <?php else: ?>
                  <em style="color:#bbb;">— sin código —</em>
                <?php endif; ?>
              </td>
              <td style="padding:5px 6px;"><?= htmlspecialchars($o['guiasa']) ?></td>
              <td style="padding:5px 6px;"><?= htmlspecialchars(substr((string)$o['fecha'], 0, 10)) ?></td>
              <td style="padding:5px 6px;text-align:right;font-weight:600;"><?= number_format($o['cantidad'], 0) ?> <?= htmlspecialchars($o['unidad']) ?></td>
              <td style="padding:5px 6px;color:#0d9488;"><?= htmlspecialchars($o['autoriza']) ?></td>
              <td style="padding:5px 6px;text-align:center;">
                <span class="est-badge est-<?= htmlspecialchars($o['estado']) ?>"><?= $o['estado'] === 'entregado' ? '✓ Entregado' : 'Pendiente' ?></span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ══ TAB: POR BODEGA ══ -->
  <div id="tab-ent-bodegas" style="display:none;">
    <?php if (empty($reporteBodegas)): ?>
      <div style="text-align:center;padding:60px 20px;color:#888;background:#fff;border:1.5px solid var(--borde);border-radius:10px;">
        <i class="fas fa-warehouse" style="font-size:2rem;margin-bottom:10px;display:block;color:#bbb;"></i>
        Sin bodegas registradas en los movimientos. Sincroniza con Trazaragro.
      </div>
    <?php else: ?>
      <div style="margin-bottom:10px;font-size:.85rem;color:#666;">
        <i class="fas fa-info-circle"></i>
        <strong><?= count($reporteBodegas) ?> bodega(s) de origen</strong> que han despachado insumos. Ordenadas por volumen.
      </div>
      <div class="row g-2">
        <?php foreach ($reporteBodegas as $b): ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div style="background:#fff;border:1.5px solid var(--borde);border-radius:10px;padding:14px 16px;height:100%;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
              <div style="width:38px;height:38px;background:#fef3c7;color:#d97706;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">
                <i class="fas fa-warehouse"></i>
              </div>
              <div style="flex:1;min-width:0;">
                <strong style="font-size:.88rem;display:block;color:#1a1a1a;"><?= htmlspecialchars($b['bodega']) ?></strong>
                <div style="font-size:.7rem;color:#888;">
                  <?php if ($b['departamento']): ?><?= htmlspecialchars($b['departamento']) ?> · <?php endif; ?>
                  <?php if ($b['cue']): ?>CUE: <?= htmlspecialchars($b['cue']) ?><?php endif; ?>
                </div>
              </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;padding:10px 0;border-top:1px dashed #e5e7eb;border-bottom:1px dashed #e5e7eb;font-size:.75rem;">
              <div>
                <div style="color:#888;text-transform:uppercase;font-size:.62rem;">Movimientos</div>
                <strong style="font-size:1.1rem;color:#1e40af;"><?= number_format($b['movimientos']) ?></strong>
              </div>
              <div>
                <div style="color:#888;text-transform:uppercase;font-size:.62rem;">Beneficiarios</div>
                <strong style="font-size:1.1rem;color:#16a34a;"><?= number_format($b['beneficiarios_unicos']) ?></strong>
              </div>
              <div>
                <div style="color:#888;text-transform:uppercase;font-size:.62rem;">Manifiestos</div>
                <strong style="font-size:1.1rem;color:#7c3aed;"><?= number_format($b['manifiestos_unicos']) ?></strong>
              </div>
              <div>
                <div style="color:#888;text-transform:uppercase;font-size:.62rem;">Unidades</div>
                <strong style="font-size:1.1rem;color:#9a3412;"><?= number_format($b['cantidad_total'], 0) ?></strong>
              </div>
            </div>

            <div style="margin-top:10px;font-size:.7rem;">
              <div style="color:#16a34a;font-weight:600;">
                <i class="fas fa-circle-check"></i> <?= $b['entregados'] ?> entregados
              </div>
              <div style="color:#d97706;font-weight:600;">
                <i class="fas fa-clock"></i> <?= $b['pendientes'] ?> pendientes
              </div>
            </div>

            <?php if (!empty($b['top_objetos'])): ?>
            <div style="margin-top:10px;padding-top:8px;border-top:1px dashed #e5e7eb;">
              <div style="font-size:.62rem;color:#888;text-transform:uppercase;margin-bottom:4px;">Top objetos despachados</div>
              <?php foreach ($b['top_objetos'] as $to): ?>
              <div style="display:flex;justify-content:space-between;font-size:.74rem;padding:2px 0;">
                <span style="color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1;min-width:0;margin-right:6px;"><?= htmlspecialchars($to['objeto']) ?></span>
                <strong style="color:#0f766e;flex-shrink:0;"><?= $to['cantidad'] ?></strong>
              </div>
              <?php endforeach; ?>
              <?php if ($b['objetos_unicos'] > 3): ?>
                <small style="color:#999;font-size:.65rem;">+ <?= $b['objetos_unicos'] - 3 ?> tipo(s) más</small>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ══ TAB: ANOMALÍAS ══ -->
  <div id="tab-ent-anomalias" style="display:none;">
    <?php if (empty($anomalias)): ?>
      <div style="text-align:center;padding:60px 20px;color:#16a34a;background:#fff;border:1.5px solid var(--borde);border-radius:10px;">
        <i class="fas fa-circle-check" style="font-size:2.5rem;margin-bottom:10px;display:block;"></i>
        <strong style="font-size:1rem;">Sin anomalías detectadas</strong>
        <div style="margin-top:6px;font-size:.85rem;color:#666;">Todos los movimientos sincronizados están en orden.</div>
      </div>
    <?php else: ?>
      <div style="margin-bottom:10px;font-size:.85rem;color:#666;">
        <i class="fas fa-info-circle"></i>
        Se detectaron <strong><?= count($anomalias) ?> alerta(s)</strong> que requieren revisión manual. Categorías: <strong>Sin DNI</strong> (movimiento sin productor identificado), <strong>No en padrón</strong> (DNI no encontrado en sag_beneficiarios), <strong>Duplicado</strong> (mismo productor recibió mismo objeto múltiples veces), <strong>Cantidad</strong> (cero o no especificada), <strong>Estado</strong> (entregado sin objeto trazable).
      </div>
      <div style="background:#fff;border:1.5px solid #f59e0b;border-radius:10px;overflow:auto;">
      <table class="tbl-desg" style="min-width:900px;">
        <thead>
          <tr>
            <th style="width:25%;">Beneficiario</th>
            <th>GUIASA</th>
            <th>Objeto trazable</th>
            <th>Tipo</th>
            <th>Detalle</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($anomalias as $a):
            $tipoBadgeCls = match($a['tipo']) {
                'sin_dni'    => 'background:#fee2e2;color:#991b1b;',
                'no_padron'  => 'background:#fed7aa;color:#9a3412;',
                'duplicado'  => 'background:#fef3c7;color:#92400e;',
                'cantidad'   => 'background:#dbeafe;color:#1e40af;',
                'estado'     => 'background:#fce7f3;color:#9d174d;',
                default      => 'background:#e5e7eb;color:#374151;',
            };
            $tipoLbl = match($a['tipo']) {
                'sin_dni'    => 'Sin DNI',
                'no_padron'  => 'No en padrón',
                'duplicado'  => 'Duplicado',
                'cantidad'   => 'Cantidad',
                'estado'     => 'Estado',
                default      => 'Otro',
            };
          ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($a['nombre']) ?></strong>
              <?php if ($a['dni']): ?><br><small style="color:#666;">DNI: <?= htmlspecialchars($a['dni']) ?></small><?php endif; ?>
            </td>
            <td><?= htmlspecialchars($a['guiasa']) ?></td>
            <td><?= htmlspecialchars($a['objeto'] ?: '—') ?></td>
            <td><span style="<?= $tipoBadgeCls ?>padding:2px 8px;border-radius:20px;font-size:.7rem;font-weight:700;white-space:nowrap;"><?= $tipoLbl ?></span></td>
            <td style="color:#92400e;"><?= htmlspecialchars($a['alerta']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    <?php endif; ?>
  </div>

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
// asset() agrega ?v={mtime} para evitar caché del JS viejo
$jsExtra = '<script src="' . asset('public/assets/js/modules/entregas.js') . '"></script>';
require ROOT_PATH . '/app/views/layouts/footer.php';
?>
