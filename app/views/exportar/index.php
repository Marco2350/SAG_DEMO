<?php
$cssExtra = '<style>
/* ── SECTION PANEL ── */
.section-panel{display:none;}
.section-panel.active{display:block;}

/* ── EXPORT MODULE CARD ── */
.export-mod-card{background:#fff;border:1.5px solid var(--borde);border-radius:12px;padding:20px;transition:all .2s;}
.export-mod-card:hover{border-color:var(--primario);box-shadow:0 4px 16px rgba(84,102,142,.12);}
.mod-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;}
.mod-count{font-size:1.4rem;font-weight:800;color:var(--primario);}
.mod-sub{font-size:.74rem;color:#888;}

/* ── PROGRESS ── */
.prog-export{height:6px;background:#e5e7eb;border-radius:4px;overflow:hidden;margin:10px 0;}
.prog-export-fill{height:6px;background:var(--primario);border-radius:4px;}

/* ── BUTTONS EXP ── */
.btn-exp{padding:7px 14px;border-radius:8px;border:none;font-size:.8rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all .15s;}
.btn-exp:hover{opacity:.88;}
.btn-exp:disabled{opacity:.5;cursor:not-allowed;}
.btn-csv{background:#16a34a;color:#fff;}
.btn-json{background:#1d4ed8;color:#fff;}
.btn-prev{background:#f0f2f8;color:var(--primario);border:1.5px solid var(--primario);}
.btn-limpiar{background:#fff;color:#666;border:1.5px solid #ddd;}
.btn-limpiar:hover{border-color:#999;color:#333;}
.btn-todo{background:var(--primario);color:#fff;}

/* ── FILTROS GRID ── */
.filtros-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;padding:16px 18px;background:#f8f9fb;border-bottom:1px solid #f0f0f0;}
.fl-label{font-size:.74rem;font-weight:700;color:#555;margin-bottom:4px;}
.fc{width:100%;padding:7px 10px;border:1.5px solid var(--borde);border-radius:7px;font-size:.82rem;color:var(--texto);background:#fff;outline:none;}
.fc:focus{border-color:var(--primario);}

/* ── PREVIEW TABLE ── */
.prev-table{width:100%;border-collapse:collapse;font-size:.8rem;}
.prev-table thead th{background:var(--primario);color:#fff;padding:8px 12px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;white-space:nowrap;}
.prev-table tbody tr{border-bottom:1px solid #f0f0f0;}
.prev-table tbody tr:hover{background:#f8faff;}
.prev-table tbody td{padding:8px 12px;vertical-align:middle;}
.prev-table tbody tr:nth-child(even){background:#fafbfc;}

/* ── BADGE ── */
.badge-pill{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;}
.bp-green{background:#d1fae5;color:#065f46;}
.bp-yellow{background:#fef9c3;color:#854d0e;}
.bp-blue{background:#dbeafe;color:#1d4ed8;}
.bp-gray{background:#f1f5f9;color:#475569;}

/* ── LOG ── */
.log-row{display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid #f5f5f5;}
.log-row:last-child{border-bottom:none;}
.log-icon{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0;}
.log-icon.csv{background:#d1fae5;color:#16a34a;}
.log-icon.json{background:#dbeafe;color:#1d4ed8;}
.log-info{flex:1;}
.log-info strong{font-size:.82rem;color:#333;}
.log-info span{font-size:.75rem;color:#888;display:block;}
</style>';
?>
<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . BASE_URL . '/public/assets/js/modules/exportar.js"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">

  <!-- Encabezado -->
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="page-title">
      <i class="fas fa-file-export me-2" style="color:var(--primario);"></i>Exportar Datos
      <small>Descarga de información — <?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?></small>
    </div>
    <button class="btn-exp btn-todo" id="btnExportarTodo">
      <i class="fas fa-box-archive"></i> Exportar todo el sistema
    </button>
  </div>

  <!-- AVISO DE POLÍTICA -->
  <div style="background:#fffbeb;border:1.5px solid #fcd34d;border-radius:10px;padding:12px 16px;margin-bottom:18px;display:flex;align-items:flex-start;gap:10px;">
    <i class="fas fa-triangle-exclamation" style="color:#d97706;margin-top:2px;flex-shrink:0;"></i>
    <div style="font-size:.82rem;color:#78350f;">
      <strong>Política de exportación:</strong> Los archivos CSV incluyen codificación UTF-8 con BOM para compatibilidad con Microsoft Excel.
      Los datos exportados son de uso institucional.
    </div>
  </div>

  <!-- TARJETAS DE MÓDULOS -->
  <div class="row g-3 mb-3">

    <!-- Beneficiarios -->
    <div class="col-md-6 col-xl-3">
      <div class="export-mod-card">
        <div class="d-flex align-items-center gap-3 mb-2">
          <div class="mod-icon" style="background:#eef0f7;color:var(--primario);"><i class="fas fa-users"></i></div>
          <div>
            <div style="font-size:.92rem;font-weight:700;color:#1a1a1a;">Beneficiarios</div>
            <div class="mod-count"><?= number_format($conteos['beneficiarios'] ?? 0) ?></div>
            <div class="mod-sub">registros disponibles</div>
          </div>
        </div>
        <div class="prog-export"><div class="prog-export-fill" style="width:100%;"></div></div>
        <div class="d-flex flex-wrap gap-2 mt-2">
          <button class="btn-exp btn-csv" onclick="exportarCSV('beneficiarios')">
            <i class="fas fa-file-csv"></i> CSV
          </button>
        </div>
      </div>
    </div>

    <!-- Organizaciones -->
    <div class="col-md-6 col-xl-3">
      <div class="export-mod-card">
        <div class="d-flex align-items-center gap-3 mb-2">
          <div class="mod-icon" style="background:#fff8e6;color:#d97706;"><i class="fas fa-building"></i></div>
          <div>
            <div style="font-size:.92rem;font-weight:700;color:#1a1a1a;">Organizaciones</div>
            <div class="mod-count" style="color:#d97706;"><?= number_format($conteos['organizaciones'] ?? 0) ?></div>
            <div class="mod-sub">registros disponibles</div>
          </div>
        </div>
        <div class="prog-export"><div class="prog-export-fill" style="width:100%;background:#d97706;"></div></div>
        <div class="d-flex flex-wrap gap-2 mt-2">
          <button class="btn-exp btn-csv" onclick="exportarCSV('organizaciones')">
            <i class="fas fa-file-csv"></i> CSV
          </button>
        </div>
      </div>
    </div>

    <!-- Capacitaciones -->
    <div class="col-md-6 col-xl-3">
      <div class="export-mod-card">
        <div class="d-flex align-items-center gap-3 mb-2">
          <div class="mod-icon" style="background:#f0fdfa;color:#14b8a6;"><i class="fas fa-chalkboard-teacher"></i></div>
          <div>
            <div style="font-size:.92rem;font-weight:700;color:#1a1a1a;">Capacitaciones</div>
            <div class="mod-count" style="color:#14b8a6;"><?= number_format($conteos['capacitaciones'] ?? 0) ?></div>
            <div class="mod-sub"><?= number_format($conteos['participantes'] ?? 0) ?> participantes totales</div>
          </div>
        </div>
        <div class="prog-export"><div class="prog-export-fill" style="width:100%;background:#14b8a6;"></div></div>
        <div class="d-flex flex-wrap gap-2 mt-2">
          <button class="btn-exp btn-csv" onclick="exportarCSV('capacitaciones')">
            <i class="fas fa-file-csv"></i> CSV
          </button>
        </div>
      </div>
    </div>

    <!-- Asistencia Técnica -->
    <div class="col-md-6 col-xl-3">
      <div class="export-mod-card">
        <div class="d-flex align-items-center gap-3 mb-2">
          <div class="mod-icon" style="background:#f5f3ff;color:#8b5cf6;"><i class="fas fa-handshake"></i></div>
          <div>
            <div style="font-size:.92rem;font-weight:700;color:#1a1a1a;">Asistencia Técnica</div>
            <div class="mod-count" style="color:#8b5cf6;"><?= number_format($conteos['asistencias'] ?? 0) ?></div>
            <div class="mod-sub">visitas registradas</div>
          </div>
        </div>
        <div class="prog-export"><div class="prog-export-fill" style="width:100%;background:#8b5cf6;"></div></div>
        <div class="d-flex flex-wrap gap-2 mt-2">
          <button class="btn-exp btn-csv" onclick="exportarCSV('asistencias')">
            <i class="fas fa-file-csv"></i> CSV
          </button>
        </div>
      </div>
    </div>

  </div>

  <!-- EXPORTACIÓN CON FILTROS AVANZADOS -->
  <div class="card-box mb-3">
    <div class="card-box-header">
      <h6><i class="fas fa-sliders"></i> Exportación con filtros avanzados</h6>
      <div style="font-size:.78rem;color:#888;">Aplica filtros antes de exportar</div>
    </div>

    <div class="filtros-grid">
      <div>
        <div class="fl-label">Módulo *</div>
        <select class="fc" id="expModulo">
          <option value="">— Seleccionar módulo —</option>
          <option value="beneficiarios">Beneficiarios</option>
          <option value="organizaciones">Organizaciones</option>
          <option value="capacitaciones">Capacitaciones</option>
          <option value="asistencias">Asistencia Técnica</option>
        </select>
      </div>
      <div>
        <div class="fl-label">Departamento</div>
        <select class="fc" id="expDpto">
          <option value="">Todos</option>
          <?php foreach ($departamentos as $d): ?>
          <option value="<?= $d['id_departamento'] ?>"><?= htmlspecialchars($d['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <div class="fl-label">Estado</div>
        <select class="fc" id="expEstado">
          <option value="">Todos los estados</option>
          <option value="activo">Activo</option>
          <option value="inactivo">Inactivo</option>
          <option value="finalizado">Finalizado</option>
          <option value="borrador">Borrador</option>
        </select>
      </div>
      <div>
        <div class="fl-label">Año</div>
        <select class="fc" id="expAnio">
          <option value="">Todos los años</option>
          <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
          <option value="<?= $y ?>"><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
    </div>

    <div class="card-box-body">
      <div class="d-flex gap-2 mb-3 flex-wrap">
        <button class="btn-exp btn-csv" id="btnExportarFiltrado">
          <i class="fas fa-file-csv"></i> Exportar CSV filtrado
        </button>
        <button class="btn-exp btn-limpiar" id="btnLimpiarFiltros">
          <i class="fas fa-xmark"></i> Limpiar
        </button>
      </div>
    </div>
  </div>

  <!-- HISTORIAL DE EXPORTACIONES -->
  <div class="card-box">
    <div class="card-box-header">
      <h6><i class="fas fa-clock-rotate-left"></i> Historial de exportaciones</h6>
      <button class="btn-exp btn-limpiar" style="font-size:.76rem;padding:5px 12px;" id="btnLimpiarHistorial">
        <i class="fas fa-trash-can"></i> Limpiar
      </button>
    </div>
    <div class="card-box-body" style="padding:10px 18px;">
      <div id="historialLog">
        <div style="text-align:center;padding:20px;color:#aaa;font-size:.84rem;">
          <i class="fas fa-inbox" style="font-size:1.5rem;display:block;margin-bottom:8px;"></i>
          Aún no se han realizado exportaciones en esta sesión.
        </div>
      </div>
    </div>
  </div>

</div><!-- /content -->

<!-- Form oculto para descarga CSV -->
<form id="formExportar" method="post" action="<?= BASE_URL ?>/exportar/generar" style="display:none;">
  <input type="hidden" name="modulo"          id="fModulo"/>
  <input type="hidden" name="id_departamento" id="fDpto"/>
  <input type="hidden" name="estado"          id="fEstado"/>
  <input type="hidden" name="anio"            id="fAnio"/>
</form>

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>
