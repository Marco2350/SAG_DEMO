<?php
$cssExtra = '<style>
/* ── ENTREGAS MODULE STYLES ─────────────────────────────────── */
/* KPI Cards */
.kpi-ent{background:#fff;border-radius:12px;border:1.5px solid var(--borde);padding:16px 18px;display:flex;align-items:center;gap:12px;transition:transform .15s;}
.kpi-ent:hover{transform:translateY(-2px);box-shadow:0 6px 18px rgba(84,102,142,.08);}
.kpi-ent .kpi-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
.kpi-ent .kpi-val{font-size:1.4rem;font-weight:800;line-height:1;}
.kpi-ent .kpi-lbl{font-size:.72rem;color:#888;margin-top:3px;text-transform:uppercase;letter-spacing:.3px;}

/* Mock banner */
.mock-banner{background:linear-gradient(90deg,#fef3c7,#fef9c3);border:1.5px solid #f59e0b;border-radius:10px;padding:10px 16px;margin-bottom:14px;display:flex;align-items:center;gap:10px;font-size:.82rem;color:#78350f;}
.mock-banner i{font-size:1.1rem;}

/* Sync button */
.btn-sync{background:#0d9488;color:#fff;border:none;padding:10px 18px;border-radius:9px;font-size:.84rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:all .15s;}
.btn-sync:hover{background:#0f766e;}
.btn-sync.is-loading{opacity:.7;cursor:wait;}
.btn-sync .fa-rotate{transition:transform .6s;}
.btn-sync.is-loading .fa-rotate{animation:spin 1s linear infinite;}
@keyframes spin{to{transform:rotate(360deg);}}

/* Filtros */
.ent-filtros{background:#f8fafc;border:1.5px solid var(--borde);border-radius:10px;padding:12px 14px;margin-bottom:14px;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;}
.ent-filtros .fl{font-size:.7rem;font-weight:700;color:#555;margin-bottom:3px;text-transform:uppercase;letter-spacing:.3px;}
.ent-filtros .fc{padding:6px 9px;border:1.5px solid var(--borde);border-radius:6px;font-size:.8rem;width:100%;background:#fff;}
.ent-filtros .fc:focus{outline:none;border-color:var(--primario);}

/* Tabla */
.tbl-ent{width:100%;border-collapse:collapse;font-size:.79rem;background:#fff;}
.tbl-ent thead th{background:var(--primario);color:#fff;padding:10px 10px;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;text-align:left;white-space:nowrap;}
.tbl-ent tbody tr{border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .15s;}
.tbl-ent tbody tr:hover{background:#f8faff;}
.tbl-ent tbody td{padding:9px 10px;vertical-align:middle;}
.tbl-ent tbody tr:nth-child(even){background:#fafbfc;}
.tbl-ent tbody tr:nth-child(even):hover{background:#f0f4fa;}

/* Estados (badges) */
.ent-badge{display:inline-block;padding:3px 9px;border-radius:20px;font-size:.7rem;font-weight:700;white-space:nowrap;}
.eb-aprobada{background:#d1fae5;color:#065f46;}
.eb-pendiente_revision{background:#fef3c7;color:#92400e;}
.eb-con_alerta{background:#fed7aa;color:#9a3412;}
.eb-rechazada{background:#fee2e2;color:#991b1b;}

.alerta-icon{color:#f59e0b;font-size:.85rem;margin-right:4px;}

/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1050;align-items:center;justify-content:center;padding:16px;}
.modal-overlay.show{display:flex;}
.modal-box{background:#fff;border-radius:14px;width:100%;max-width:920px;max-height:92vh;display:flex;flex-direction:column;box-shadow:0 25px 60px rgba(0,0,0,.25);}
.modal-head{padding:14px 20px;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between;}
.modal-head h5{font-size:1rem;font-weight:700;color:#1a1a1a;margin:0;}
.modal-body-inner{padding:18px 20px;overflow-y:auto;flex:1;}
.modal-foot{padding:12px 20px;border-top:1px solid #eee;display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;}
.btn-close-x{background:none;border:none;font-size:1.3rem;color:#999;cursor:pointer;line-height:1;padding:0;}
.btn-close-x:hover{color:#333;}

/* Detalle del modal */
.det-section{margin-bottom:18px;}
.det-section-title{font-size:.74rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:var(--primario);border-bottom:1.5px solid var(--primario);padding-bottom:4px;margin-bottom:10px;}
.det-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:8px 14px;font-size:.83rem;}
.det-item{padding:6px 0;}
.det-item .k{font-size:.7rem;color:#888;text-transform:uppercase;letter-spacing:.3px;font-weight:700;}
.det-item .v{font-size:.85rem;color:#222;font-weight:500;margin-top:2px;}
.det-item .v strong{color:#0f766e;}

/* Imágenes y firmas */
.det-imgs{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;}
.det-img{border:1px solid #e2e8f0;border-radius:8px;padding:8px;text-align:center;}
.det-img img{max-width:100%;height:auto;border-radius:5px;}
.det-img .label{font-size:.7rem;color:#666;margin-top:5px;font-weight:600;}

/* Alerta dentro del modal */
.det-alerta{background:#fef3c7;border:1.5px solid #f59e0b;border-left:4px solid #f59e0b;border-radius:8px;padding:10px 14px;margin-bottom:14px;color:#78350f;font-size:.85rem;}
.det-rechazo{background:#fee2e2;border:1.5px solid #dc2626;border-left:4px solid #dc2626;border-radius:8px;padding:10px 14px;margin-bottom:14px;color:#991b1b;font-size:.85rem;}

/* GPS link */
.gps-link{color:var(--primario);text-decoration:none;font-size:.78rem;font-weight:600;}
.gps-link:hover{text-decoration:underline;}

/* Botones de acción */
.btn-aprobar{background:#16a34a;color:#fff;border:none;padding:8px 16px;border-radius:7px;font-size:.82rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;}
.btn-aprobar:hover{background:#15803d;}
.btn-rechazar{background:#dc2626;color:#fff;border:none;padding:8px 16px;border-radius:7px;font-size:.82rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:6px;}
.btn-rechazar:hover{background:#b91c1c;}
.btn-cerrar{background:#f1f5f9;color:#475569;border:1.5px solid #dde1e9;padding:8px 16px;border-radius:7px;font-size:.82rem;font-weight:600;cursor:pointer;}

/* Empty state */
.tbl-empty{text-align:center;padding:40px 20px;color:#94a3b8;font-size:.85rem;}
.tbl-empty i{font-size:2rem;display:block;margin-bottom:10px;opacity:.5;}
</style>';
?>
<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php $jsExtra = '<script src="' . asset('public/assets/js/modules/entregas.js') . '"></script>'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<?php
$pData = json_encode($entregas, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_APOS);
?>
<script>
const SAG_ENT = {
    entregas: <?= $pData ?>,
    esAdmin: <?= $esAdmin ? 'true' : 'false' ?>,
    rolUsuario: <?= json_encode($_SESSION['user']['rol_slug'] ?? '') ?>
};
</script>

<div class="content">

  <!-- Encabezado -->
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="page-title">
      <i class="fas fa-truck-ramp-box me-2" style="color:var(--primario);"></i>Entregas de Incentivos
      <small><?= htmlspecialchars($_SESSION['programa']['sigla'] ?? '') ?> &mdash; Sincronización con KoBoToolbox y Trazaragro</small>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <button class="btn-sync" id="btnSincronizar">
        <i class="fas fa-rotate"></i> Sincronizar con KoBo
      </button>
      <button class="btn-sync" id="btnSincronizarTrazaragro" style="background:#1e3a8a;">
        <i class="fas fa-link"></i> Sincronizar con Trazaragro
      </button>
    </div>
  </div>

  <!-- Banner mock -->
  <div class="mock-banner">
    <i class="fas fa-flask"></i>
    <div>
      <strong>Vista de prueba (mock)</strong> &mdash; Esta pantalla muestra datos ficticios para validar el diseño antes de conectar con KoBoToolbox y Trazaragro.
      Los botones funcionan pero no afectan ninguna base de datos real. Al aprobar una entrega, el sistema descontará automáticamente el stock del módulo de Inventarios.
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-2 mb-3">
    <div class="col-6 col-md-2">
      <div class="kpi-ent">
        <div class="kpi-icon" style="background:#eef0f7;color:var(--primario);"><i class="fas fa-list-check"></i></div>
        <div>
          <div class="kpi-val"><?= $kpis['total'] ?></div>
          <div class="kpi-lbl">Total entregas</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="kpi-ent">
        <div class="kpi-icon" style="background:#d1fae5;color:#16a34a;"><i class="fas fa-circle-check"></i></div>
        <div>
          <div class="kpi-val" style="color:#16a34a;"><?= $kpis['aprobadas'] ?? 0 ?></div>
          <div class="kpi-lbl">Aprobadas</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="kpi-ent">
        <div class="kpi-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-clock"></i></div>
        <div>
          <div class="kpi-val" style="color:#d97706;"><?= $kpis['pendiente_revision'] ?? 0 ?></div>
          <div class="kpi-lbl">Pendientes</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="kpi-ent">
        <div class="kpi-icon" style="background:#fed7aa;color:#9a3412;"><i class="fas fa-triangle-exclamation"></i></div>
        <div>
          <div class="kpi-val" style="color:#9a3412;"><?= $kpis['con_alerta'] ?? 0 ?></div>
          <div class="kpi-lbl">Con alerta</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="kpi-ent">
        <div class="kpi-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-circle-xmark"></i></div>
        <div>
          <div class="kpi-val" style="color:#dc2626;"><?= $kpis['rechazadas'] ?? 0 ?></div>
          <div class="kpi-lbl">Rechazadas</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="kpi-ent">
        <div class="kpi-icon" style="background:#dbeafe;color:#1e40af;"><i class="fas fa-boxes-stacked"></i></div>
        <div>
          <div class="kpi-val" style="color:#1e40af;"><?= number_format($kpis['sacos_entregados']) ?></div>
          <div class="kpi-lbl">Sacos entregados</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filtros -->
  <div class="ent-filtros">
    <div>
      <div class="fl">Estado</div>
      <select class="fc" id="fEstado">
        <option value="">Todos</option>
        <option value="aprobada">Aprobadas</option>
        <option value="pendiente_revision">Pendientes de revisión</option>
        <option value="con_alerta">Con alerta</option>
        <option value="rechazada">Rechazadas</option>
      </select>
    </div>
    <div>
      <div class="fl">Departamento</div>
      <select class="fc" id="fDepartamento">
        <option value="">Todos</option>
        <!-- llenado dinámicamente por JS desde los datos -->
      </select>
    </div>
    <div>
      <div class="fl">Municipio</div>
      <select class="fc" id="fMunicipio">
        <option value="">Todos</option>
        <!-- llenado dinámicamente según departamento elegido -->
      </select>
    </div>
    <div>
      <div class="fl">Bodega</div>
      <select class="fc" id="fBodega">
        <option value="">Todas</option>
        <?php foreach ($bodegas as $b): ?>
        <option value="<?= htmlspecialchars($b['codigo']) ?>"><?= htmlspecialchars($b['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <div class="fl">Técnico</div>
      <select class="fc" id="fTecnico">
        <option value="">Todos</option>
        <?php foreach ($tecnicos as $t): ?>
        <option value="<?= htmlspecialchars($t['codigo']) ?>"><?= htmlspecialchars($t['nombre']) ?></option>
        <?php endforeach; ?>
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
      <div class="fl">Buscar (DNI o nombre)</div>
      <input type="text" class="fc" id="fBuscar" placeholder="Ej. 0801... o María"/>
    </div>
    <div style="display:flex;align-items:end;">
      <button class="btn-cerrar" id="btnLimpiarFiltros" style="width:100%;font-size:.78rem;padding:7px;">
        <i class="fas fa-eraser"></i> Limpiar filtros
      </button>
    </div>
  </div>

  <!-- Tabla -->
  <div class="card-box">
    <div class="card-box-body p-0">
      <div style="overflow-x:auto;">
        <table class="tbl-ent">
          <thead>
            <tr>
              <th>#</th>
              <th>Fecha / Hora</th>
              <th>DNI</th>
              <th>Beneficiario</th>
              <th>Bodega</th>
              <th>Técnico</th>
              <th>Asign / Entreg</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody id="tblEntregas">
            <!-- llenado por JS -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div><!-- /content -->


<!-- ══════════════ MODAL DETALLE DE ENTREGA ══════════════ -->
<div class="modal-overlay" id="modalDetalle">
  <div class="modal-box">
    <div class="modal-head">
      <h5 id="dTitulo">Detalle de la entrega</h5>
      <button class="btn-close-x" onclick="cerrarModal('modalDetalle')">×</button>
    </div>
    <div class="modal-body-inner" id="dBody">
      <!-- llenado por JS -->
    </div>
    <div class="modal-foot" id="dFoot">
      <!-- botones de acción dinámicos -->
    </div>
  </div>
</div>

<!-- ══════════════ MODAL OBSERVACIÓN (aprobar/rechazar) ══════════════ -->
<div class="modal-overlay" id="modalObs">
  <div class="modal-box" style="max-width:480px;">
    <div class="modal-head">
      <h5 id="oTitulo">Observación</h5>
      <button class="btn-close-x" onclick="cerrarModal('modalObs')">×</button>
    </div>
    <div class="modal-body-inner">
      <input type="hidden" id="oId"/>
      <input type="hidden" id="oAccion"/>
      <div id="oResumen" style="font-size:.83rem;background:#f8fafc;padding:10px 12px;border-radius:8px;margin-bottom:12px;"></div>
      <div style="font-size:.74rem;font-weight:700;color:#555;margin-bottom:5px;text-transform:uppercase;">Observación / Justificación</div>
      <textarea id="oTexto" class="fc" rows="4" placeholder="Escriba la justificación de la decisión..." style="width:100%;padding:8px;border:1.5px solid var(--borde);border-radius:6px;font-size:.85rem;resize:vertical;"></textarea>
    </div>
    <div class="modal-foot">
      <button class="btn-cerrar" onclick="cerrarModal('modalObs')">Cancelar</button>
      <button class="btn-aprobar" id="oBtnConfirmar"><i class="fas fa-check"></i> Confirmar</button>
    </div>
  </div>
</div>

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>
