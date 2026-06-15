<?php
$cssExtra = '<style>
/* ── SECTION PANELS ── */
.section-panel{display:none;}
.section-panel.active{display:block;}

/* ── STAT-CARD extras ── */
.stat-val{font-size:1.65rem;font-weight:700;line-height:1;color:#1a1a1a;}
.stat-lbl{font-size:.72rem;color:#888;margin-top:3px;}
.stat-delta{font-size:.7rem;margin-top:4px;}
.stat-delta.up{color:#22c55e;}
.stat-delta.down{color:#ef4444;}
.stat-card .stat-icon{background:#eef0f7;color:var(--primario);}
.stat-card.gold .stat-icon{background:#fff8e6;color:#d97706;}
.stat-card.teal .stat-icon{background:#f0fdfa;color:#14b8a6;}
.stat-card.purple .stat-icon{background:#f5f3ff;color:#8b5cf6;}

/* ── FILTROS BAR ── */
.filtros-bar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:12px 18px;background:#f8f9fb;border-bottom:1px solid #f0f0f0;}
.fc-sm{padding:6px 10px;border:1.5px solid var(--borde);border-radius:7px;font-size:.8rem;color:var(--texto);background:#fff;outline:none;}
.fc-sm:focus{border-color:var(--primario);}
.btn-filtro{padding:6px 14px;border-radius:7px;border:none;font-size:.8rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all .15s;}
.btn-filtro.primary{background:var(--primario);color:#fff;}
.btn-filtro.primary:hover{background:var(--primario-oscuro);}
.btn-filtro.secondary{background:#fff;color:#555;border:1.5px solid #e0e0e0;}
.btn-filtro.secondary:hover{border-color:var(--primario);color:var(--primario);}
.btn-filtro.success{background:#16a34a;color:#fff;}

/* ── DATA TABLE ── */
.data-table{width:100%;border-collapse:collapse;font-size:.82rem;}
.data-table thead th{background:#f8f9fa;color:#555;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;padding:10px 14px;border-bottom:2px solid #eee;white-space:nowrap;}
.data-table tbody tr{border-bottom:1px solid #f5f5f5;transition:background .1s;}
.data-table tbody tr:hover{background:#f8faff;}
.data-table tbody td{padding:10px 14px;vertical-align:middle;}

/* ── BADGE PILL ── */
.badge-pill{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;}
.bp-blue{background:#dbeafe;color:#1d4ed8;}
.bp-green{background:#d1fae5;color:#065f46;}
.bp-yellow{background:#fef9c3;color:#854d0e;}
.bp-purple{background:#ede9fe;color:#5b21b6;}
.bp-red{background:#fee2e2;color:#991b1b;}
.bp-gray{background:#f1f5f9;color:#475569;}
.bp-teal{background:#ccfbf1;color:#0f766e;}

/* ── RANKING ── */
.rank-row{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f5f5f5;}
.rank-row:last-child{border-bottom:none;}
.rank-num{width:22px;height:22px;border-radius:50%;background:var(--primario);color:#fff;font-size:.65rem;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.rank-label{flex:1;font-size:.82rem;color:#333;}
.rank-val{font-size:.8rem;font-weight:700;color:var(--primario);}
.prog-bar-wrap{background:#e5e7eb;border-radius:4px;height:7px;flex:1;margin:0 8px;}
.prog-bar-fill{height:7px;border-radius:4px;background:var(--primario);transition:width .4s;}

/* ── CHART ── */
.chart-container{position:relative;width:100%;}
.mini-stat-box{border-radius:10px;padding:14px;text-align:center;}
.mini-stat-val{font-size:1.8rem;font-weight:800;line-height:1.1;}
.mini-stat-lbl{font-size:.78rem;color:#666;margin-top:3px;}
</style>';
?>
<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/estadisticas.js"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">

  <!-- Encabezado -->
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="page-title">
      <i class="fas fa-chart-bar me-2" style="color:var(--primario);"></i>Estadísticas y Reportes
      <small>Indicadores consolidados — <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?></small>
    </div>
    <div class="d-flex gap-2">
      <button class="btn-filtro secondary" id="btnActualizar">
        <i class="fas fa-rotate-right"></i> Actualizar
      </button>
    </div>
  </div>

  <!-- KPI CARDS -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="stat-card">
        <div class="stat-icon"><i class="fas fa-users"></i></div>
        <div>
          <div class="stat-val" id="kpiBeneficiarios">—</div>
          <div class="stat-lbl">Beneficiarios activos</div>
          <div class="stat-delta up" id="kpiBeneDelta"></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card gold">
        <div class="stat-icon"><i class="fas fa-building"></i></div>
        <div>
          <div class="stat-val" id="kpiOrganizaciones">—</div>
          <div class="stat-lbl">Organizaciones activas</div>
          <div class="stat-delta up" id="kpiOrgDelta"></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card teal">
        <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
        <div>
          <div class="stat-val" id="kpiCapacitaciones">—</div>
          <div class="stat-lbl">Capacitaciones realizadas</div>
          <div class="stat-delta" id="kpiCapDelta"></div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card purple">
        <div class="stat-icon"><i class="fas fa-handshake"></i></div>
        <div>
          <div class="stat-val" id="kpiAsistencias">—</div>
          <div class="stat-lbl">Asistencias técnicas</div>
          <div class="stat-delta" id="kpiAtDelta"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- TABS DE MÓDULO -->
  <div class="card-box mb-3">
    <div class="card-box-header">
      <h6><i class="fas fa-layer-group"></i> Vista por módulo</h6>
      <div class="mode-tabs" style="flex-wrap:wrap;">
        <button class="mode-tab active" data-sec="resumen">Resumen</button>
        <button class="mode-tab" data-sec="beneficiarios">Beneficiarios</button>
        <button class="mode-tab" data-sec="capacitaciones">Capacitaciones</button>
        <button class="mode-tab" data-sec="asistencias">Asistencia Técnica</button>
        <button class="mode-tab" data-sec="organizaciones">Organizaciones</button>
      </div>
    </div>

    <!-- Filtros -->
    <div class="filtros-bar">
      <label style="font-size:.8rem;font-weight:600;color:#555;">Año:</label>
      <select class="fc-sm" id="filtAnio">
        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
        <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
      <button class="btn-filtro primary" id="btnFiltrar"><i class="fas fa-filter"></i> Filtrar</button>
      <span id="lblActualizacion" style="margin-left:auto;font-size:.75rem;color:#999;"></span>
    </div>

    <div class="card-box-body">

      <!-- ═══ PANEL RESUMEN ═══ -->
      <div class="section-panel active" id="panelResumen">
        <div class="row g-3">
          <div class="col-md-7">
            <div style="margin-bottom:10px;font-size:.85rem;font-weight:700;color:#333;">
              <i class="fas fa-chart-column me-1" style="color:var(--primario);"></i> Capacitaciones por mes
            </div>
            <div class="chart-container" style="height:240px;">
              <canvas id="chartActividad"></canvas>
            </div>
          </div>
          <div class="col-md-5">
            <div style="margin-bottom:10px;font-size:.85rem;font-weight:700;color:#333;">
              Beneficiarios por sexo
            </div>
            <div class="chart-container" style="height:240px;">
              <canvas id="chartSexoResumen"></canvas>
            </div>
          </div>
        </div>
        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <div style="margin-bottom:10px;font-size:.85rem;font-weight:700;color:#333;">
              <i class="fas fa-map-location-dot me-1" style="color:var(--primario);"></i> Top departamentos — Beneficiarios
            </div>
            <div id="rankingDptos"></div>
          </div>
          <div class="col-md-6">
            <div style="margin-bottom:10px;font-size:.85rem;font-weight:700;color:#333;">
              <i class="fas fa-building me-1" style="color:var(--primario);"></i> Organizaciones por tipo
            </div>
            <div class="chart-container" style="height:200px;">
              <canvas id="chartOrgTipoResumen"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ PANEL BENEFICIARIOS ═══ -->
      <div class="section-panel" id="panelBeneficiarios">
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <div class="mini-stat-box" style="background:#f8f9fb;">
              <div class="mini-stat-val" style="color:var(--primario);" id="bTotalReg">—</div>
              <div class="mini-stat-lbl">Total registrados</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mini-stat-box" style="background:#f0fdf4;">
              <div class="mini-stat-val" style="color:#16a34a;" id="bTotalActivos">—</div>
              <div class="mini-stat-lbl">Activos</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mini-stat-box" style="background:#fffbeb;">
              <div class="mini-stat-val" style="color:#d97706;" id="bTotalDptos">—</div>
              <div class="mini-stat-lbl">Departamentos cubiertos</div>
            </div>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-8">
            <div style="font-size:.85rem;font-weight:700;color:#333;margin-bottom:10px;">
              <i class="fas fa-chart-bar me-1" style="color:var(--primario);"></i> Beneficiarios por departamento (Top 10)
            </div>
            <div class="chart-container" style="height:280px;">
              <canvas id="chartBeneDpto"></canvas>
            </div>
          </div>
          <div class="col-md-4">
            <div style="font-size:.85rem;font-weight:700;color:#333;margin-bottom:10px;">
              Por sexo
            </div>
            <div class="chart-container" style="height:280px;">
              <canvas id="chartBeneSexo"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ PANEL CAPACITACIONES ═══ -->
      <div class="section-panel" id="panelCapacitaciones">
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <div class="mini-stat-box" style="background:#f8f9fb;">
              <div class="mini-stat-val" style="color:var(--primario);" id="cTotalEventos">—</div>
              <div class="mini-stat-lbl">Eventos realizados</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mini-stat-box" style="background:#f0fdf4;">
              <div class="mini-stat-val" style="color:#16a34a;" id="cTotalPart">—</div>
              <div class="mini-stat-lbl">Participantes totales</div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mini-stat-box" style="background:#eff6ff;">
              <div class="mini-stat-val" style="color:#1d4ed8;" id="cPromPart">—</div>
              <div class="mini-stat-lbl">Promedio part./evento</div>
            </div>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-7">
            <div style="font-size:.85rem;font-weight:700;color:#333;margin-bottom:10px;">
              <i class="fas fa-calendar me-1" style="color:var(--primario);"></i> Eventos por mes — <span id="anioCapLabel"></span>
            </div>
            <div class="chart-container" style="height:260px;">
              <canvas id="chartCapMes"></canvas>
            </div>
          </div>
          <div class="col-md-5">
            <div style="font-size:.85rem;font-weight:700;color:#333;margin-bottom:10px;">
              <i class="fas fa-users me-1" style="color:var(--primario);"></i> Participantes por mes — <span id="anioCapPart"></span>
            </div>
            <div class="chart-container" style="height:260px;">
              <canvas id="chartCapPart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ PANEL ASISTENCIA TÉCNICA ═══ -->
      <div class="section-panel" id="panelAsistencias">
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="mini-stat-box" style="background:#fdf4ff;">
              <div class="mini-stat-val" style="color:#8b5cf6;" id="aTotalAt">—</div>
              <div class="mini-stat-lbl">Asistencias realizadas</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mini-stat-box" style="background:#f8f9fb;">
              <div class="mini-stat-val" style="color:var(--primario);" id="aTiposCount">—</div>
              <div class="mini-stat-lbl">Tipos de visita registrados</div>
            </div>
          </div>
        </div>
        <div class="row g-3">
          <div class="col-md-7">
            <div style="font-size:.85rem;font-weight:700;color:#333;margin-bottom:10px;">
              <i class="fas fa-calendar me-1" style="color:var(--primario);"></i> Asistencias por mes — <span id="anioAtLabel"></span>
            </div>
            <div class="chart-container" style="height:260px;">
              <canvas id="chartAtMes"></canvas>
            </div>
          </div>
          <div class="col-md-5">
            <div style="font-size:.85rem;font-weight:700;color:#333;margin-bottom:10px;">
              <i class="fas fa-list-check me-1" style="color:var(--primario);"></i> Por tipo de visita
            </div>
            <div class="chart-container" style="height:260px;">
              <canvas id="chartAtTipo"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- ═══ PANEL ORGANIZACIONES ═══ -->
      <div class="section-panel" id="panelOrganizaciones">
        <div class="row g-3">
          <div class="col-md-6">
            <div style="font-size:.85rem;font-weight:700;color:#333;margin-bottom:10px;">
              <i class="fas fa-building me-1" style="color:var(--primario);"></i> Por tipo de organización
            </div>
            <div class="chart-container" style="height:280px;">
              <canvas id="chartOrgTipo"></canvas>
            </div>
          </div>
          <div class="col-md-6">
            <div style="font-size:.85rem;font-weight:700;color:#333;margin-bottom:10px;">
              <i class="fas fa-toggle-on me-1" style="color:var(--primario);"></i> Por estado
            </div>
            <div class="chart-container" style="height:280px;">
              <canvas id="chartOrgEstado"></canvas>
            </div>
          </div>
        </div>
        <div class="mt-3">
          <div style="font-size:.85rem;font-weight:700;color:#333;margin-bottom:10px;">
            <i class="fas fa-map-marker-alt me-1" style="color:var(--primario);"></i> Departamentos con mayor cobertura
          </div>
          <div id="rankingDptosOrg"></div>
        </div>
      </div>

    </div><!-- card-box-body -->
  </div><!-- card-box -->

</div><!-- /content -->

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>
