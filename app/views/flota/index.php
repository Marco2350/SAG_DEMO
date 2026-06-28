<?php
$cssExtra = '<style>
/* ── Flota Vehicular — estilos específicos ─── */
.flota-kpi { background:#fff; border:1.5px solid var(--borde); border-radius:12px;
  padding:14px 16px; display:flex; align-items:center; gap:12px;
  transition: transform .15s; }
.flota-kpi:hover { transform: translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.05); }
.flota-kpi .ki { width:44px; height:44px; border-radius:10px;
  display:flex; align-items:center; justify-content:center;
  font-size:1.15rem; flex-shrink:0; }
.flota-kpi .kv { font-size:1.4rem; font-weight:800; line-height:1; }
.flota-kpi .kl { font-size:.7rem; color:#888; text-transform:uppercase; letter-spacing:.3px; margin-top:3px; }

.flota-tabs { display:flex; border-bottom:1.5px solid var(--borde); margin-bottom:16px; }
.flota-tab { padding:10px 18px; background:none; border:none; cursor:pointer;
  font-size:.85rem; font-weight:600; color:#666; border-bottom:2.5px solid transparent;
  transition: all .12s; }
.flota-tab.active { color:var(--primario); border-bottom-color:var(--primario); }
.flota-tab:not(.active):hover { color:#333; background:#f8fafc; }

.flota-panel { display:none; }
.flota-panel.active { display:block; }

/* Estados (chips de color) */
.chip { display:inline-block; padding:2px 10px; border-radius:99px;
  font-size:.72rem; font-weight:700; white-space:nowrap; }
.chip-ok       { background:#dcfce7; color:#166534; }
.chip-taller   { background:#fef3c7; color:#92400e; }
.chip-baja     { background:#fee2e2; color:#991b1b; }
.chip-curso    { background:#dbeafe; color:#1e40af; }
.chip-final    { background:#e0e7ff; color:#3730a3; }

/* Alertas mantenimiento */
.alert-ok      { background:#dcfce7; color:#166534; }
.alert-cerca   { background:#fef3c7; color:#92400e; }
.alert-vencido { background:#fee2e2; color:#991b1b; }
.fleet-scope{display:inline-flex;align-items:center;gap:6px;margin-top:7px;padding:4px 10px;border-radius:20px;background:#eef2ff;color:var(--primario);font-size:.7rem;font-weight:700}
.fleet-shell{background:#fff;border:1.5px solid var(--borde);border-radius:12px;overflow:hidden}.fleet-shell .flota-tabs{margin:0;padding:0 8px;overflow-x:auto}.fleet-shell .flota-panel{padding:16px}
.fleet-layout{display:grid;grid-template-columns:minmax(0,2fr) minmax(260px,1fr);gap:16px}.fleet-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:11px}
.fleet-card{border:1.5px solid var(--borde);border-radius:11px;padding:13px;transition:.15s}.fleet-card:hover{box-shadow:0 4px 14px rgba(50,65,95,.08);transform:translateY(-1px)}
.fleet-card-head{display:flex;justify-content:space-between;align-items:flex-start;gap:8px}.fleet-card-title{font-size:.82rem;font-weight:800;color:#202735}.fleet-card-meta{font-size:.66rem;color:#758095;margin-top:2px}
.fleet-card-stats{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:11px;padding-top:10px;border-top:1px solid #edf0f4;font-size:.7rem}.fleet-card-stats span{display:block;font-size:.61rem;color:#8a91a0}
.fleet-section-title{font-size:.83rem;font-weight:800;color:#202735;margin:0}.fleet-section-sub{font-size:.68rem;color:#758095;margin-top:2px}.fleet-alert{border-radius:9px;padding:9px 11px;margin-bottom:8px;font-size:.7rem}.fleet-alert.warn{background:#fffbeb;border:1px solid #fde68a;color:#92400e}.fleet-alert.danger{background:#fff1f2;border:1px solid #fecdd3;color:#9f1239}
.fleet-empty{padding:42px 18px;text-align:center;color:#758095;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:10px}
@media(max-width:1050px){.fleet-layout{grid-template-columns:1fr}}@media(max-width:700px){.fleet-grid{grid-template-columns:1fr}.flota-tab{padding:10px 12px}}
</style>';

$progSigla = $_SESSION['programa']['sigla'] ?? '';
$prog      = $_SESSION['programa'] ?? [];

require ROOT_PATH . '/app/views/layouts/header.php';
require ROOT_PATH . '/app/views/layouts/sidebar.php';
require ROOT_PATH . '/app/views/layouts/topbar.php';

$combs   = defined('FLOTA_COMBUSTIBLES')        ? FLOTA_COMBUSTIBLES        : [];
$tiposM  = defined('FLOTA_TIPOS_MANTENIMIENTO') ? FLOTA_TIPOS_MANTENIMIENTO : [];
$estados = defined('FLOTA_ESTADOS_VEHICULO')    ? FLOTA_ESTADOS_VEHICULO    : [];
?>

<div class="content">

  <!-- Page Header -->
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <div class="page-title">
        <i class="fas fa-car" style="color:#1e3a8a;"></i>
        Flota Vehicular
        <small><?= htmlspecialchars($progSigla) ?> &mdash; Control operativo, mantenimiento y uso de vehículos</small>
      </div>
      <span class="fleet-scope"><i class="fas fa-layer-group"></i> PIPC · PIPG · PIPA · FPROG</span>
    </div>
    <?php if ($esAdmin): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <button class="btn btn-outline-secondary" type="button" onclick="window.print()">
        <i class="fas fa-file-export me-1"></i> Exportar
      </button>
      <button class="btn btn-primary" id="btnNuevoVehiculo">
        <i class="fas fa-plus me-1"></i> Nuevo vehículo
      </button>
    </div>
    <?php endif; ?>
  </div>

  <!-- KPIs -->
  <div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
      <div class="flota-kpi">
        <div class="ki" style="background:#eef2ff;color:#4338ca;"><i class="fas fa-car"></i></div>
        <div>
          <div class="kv"><?= number_format($kpis['total']) ?></div>
          <div class="kl">Vehículos</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="flota-kpi">
        <div class="ki" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-circle-check"></i></div>
        <div>
          <div class="kv"><?= number_format($kpis['activos']) ?></div>
          <div class="kl">Activos</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="flota-kpi">
        <div class="ki" style="background:#fef3c7;color:#b45309;"><i class="fas fa-wrench"></i></div>
        <div>
          <div class="kv"><?= number_format($kpis['taller']) ?></div>
          <div class="kl">En taller</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="flota-kpi">
        <div class="ki" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-triangle-exclamation"></i></div>
        <div>
          <div class="kv"><?= number_format($kpis['con_alerta']) ?></div>
          <div class="kl">Con alerta</div>
        </div>
      </div>
    </div>
  </div>

  <div class="fleet-shell">
  <!-- Tabs -->
  <div class="flota-tabs">
    <button class="flota-tab active" data-tab="resumen">
      <i class="fas fa-gauge-high me-1"></i> Resumen
    </button>
    <button class="flota-tab" data-tab="vehiculos">
      <i class="fas fa-car me-1"></i> Vehículos <small style="opacity:.7;">(<?= count($vehiculos) ?>)</small>
    </button>
    <button class="flota-tab" data-tab="viajes">
      <i class="fas fa-route me-1"></i> Viajes <small style="opacity:.7;">(<?= count($viajes) ?>)</small>
    </button>
    <button class="flota-tab" data-tab="mantos">
      <i class="fas fa-wrench me-1"></i> Mantenimientos <small style="opacity:.7;">(<?= count($mantos) ?>)</small>
    </button>
    <button class="flota-tab" data-tab="combustible"><i class="fas fa-gas-pump me-1"></i> Combustible</button>
    <button class="flota-tab" data-tab="conductores"><i class="fas fa-id-card me-1"></i> Conductores</button>
  </div>

  <div class="flota-panel active" id="panel-resumen">
    <div class="fleet-layout">
      <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div><h3 class="fleet-section-title">Estado de la flota</h3><div class="fleet-section-sub">Unidades registradas en <?= htmlspecialchars($progSigla) ?></div></div>
        </div>
        <?php if (empty($vehiculos)): ?>
          <div class="fleet-empty"><i class="fas fa-car-side fa-2x mb-2"></i><br>No hay vehículos registrados todavía.</div>
        <?php else: ?>
        <div class="fleet-grid">
          <?php foreach (array_slice($vehiculos, 0, 6) as $v): ?>
          <article class="fleet-card">
            <div class="fleet-card-head">
              <div><div class="fleet-card-title"><i class="fas fa-truck-pickup me-1" style="color:var(--primario)"></i><?= htmlspecialchars(trim($v['marca'].' '.$v['modelo'])) ?></div><div class="fleet-card-meta">Placa <?= htmlspecialchars($v['placa']) ?> · <?= htmlspecialchars($progSigla) ?></div></div>
              <?php $ec=$v['estado']; $cc=['activo'=>'chip-ok','taller'=>'chip-taller','baja'=>'chip-baja'][$ec]??'chip-ok'; ?>
              <span class="chip <?= $cc ?>"><?= htmlspecialchars($estados[$ec]??$ec) ?></span>
            </div>
            <div class="fleet-card-stats">
              <div><span>Año</span><strong><?= htmlspecialchars($v['anio'] ?: '—') ?></strong></div>
              <div><span>Kilometraje</span><strong><?= number_format((int)$v['km_actual']) ?> km</strong></div>
              <div><span>Próximo servicio</span><strong><?php $pk=array_column($v['programacion']??[],'proximo_km'); echo $pk?number_format(min($pk)).' km':'Sin programar'; ?></strong></div>
              <div><span>Combustible</span><strong><?= htmlspecialchars($combs[$v['tipo_combustible']]??$v['tipo_combustible']) ?></strong></div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <div>
        <div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="fleet-section-title">Alertas operativas</h3><div class="fleet-section-sub">Requieren atención próxima</div></div><span class="chip <?= $kpis['con_alerta']?'alert-vencido':'alert-ok' ?>"><?= (int)$kpis['con_alerta'] ?> alertas</span></div>
        <?php $ha=false; foreach($vehiculos as $v): if(($v['alerta']['nivel']??'ok')==='ok')continue;$ha=true; ?>
          <div class="fleet-alert <?= $v['alerta']['nivel']==='vencido'?'danger':'warn' ?>"><strong><?= htmlspecialchars($v['placa']) ?></strong><br><?= htmlspecialchars($v['alerta']['msg']) ?></div>
        <?php endforeach; if(!$ha): ?><div class="fleet-alert warn"><i class="fas fa-circle-check me-1"></i>No hay mantenimientos próximos o vencidos.</div><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ══ TAB: VEHÍCULOS ══ -->
  <div class="flota-panel" id="panel-vehiculos">
    <div class="card-box">
      <div style="overflow-x:auto;">
        <table class="sag-table" id="tblVehiculos">
          <thead>
            <tr>
              <th>Placa</th>
              <th>Marca / Modelo</th>
              <th>Año</th>
              <th>Combustible</th>
              <th style="text-align:right;">Km actual</th>
              <th>Estado</th>
              <th>Alerta mantenimiento</th>
              <?php if ($esAdmin): ?><th>Acciones</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($vehiculos as $v): ?>
            <tr data-row='<?= htmlspecialchars(json_encode($v), ENT_QUOTES) ?>'>
              <td><strong><?= htmlspecialchars($v['placa']) ?></strong></td>
              <td><?= htmlspecialchars(trim($v['marca'] . ' ' . $v['modelo'])) ?></td>
              <td><?= htmlspecialchars($v['anio'] ?? '') ?></td>
              <td><?= htmlspecialchars($combs[$v['tipo_combustible']] ?? $v['tipo_combustible']) ?></td>
              <td style="text-align:right;font-weight:700;"><?= number_format((int)$v['km_actual']) ?></td>
              <td>
                <?php $e = $v['estado']; $clase = ['activo'=>'chip-ok','taller'=>'chip-taller','baja'=>'chip-baja'][$e] ?? 'chip-ok'; ?>
                <span class="chip <?= $clase ?>"><?= htmlspecialchars($estados[$e] ?? $e) ?></span>
              </td>
              <td>
                <?php $a = $v['alerta']; $alertClass = "alert-{$a['nivel']}"; ?>
                <span class="chip <?= $alertClass ?>"><?= htmlspecialchars($a['msg']) ?></span>
              </td>
              <?php if ($esAdmin): ?>
              <td>
                <button class="btn btn-sm btn-outline-primary btn-edit-veh" title="Editar"><i class="fas fa-pen"></i></button>
                <button class="btn btn-sm btn-outline-success btn-add-viaje" title="Nuevo viaje"><i class="fas fa-route"></i></button>
                <button class="btn btn-sm btn-outline-warning btn-add-manto" title="Mantenimiento"><i class="fas fa-wrench"></i></button>
                <button class="btn btn-sm btn-outline-danger btn-del-veh" title="Eliminar"><i class="fas fa-trash"></i></button>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ══ TAB: VIAJES ══ -->
  <div class="flota-panel" id="panel-viajes">
    <div class="card-box">
      <div style="overflow-x:auto;">
        <table class="sag-table" id="tblViajes">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Vehículo</th>
              <th>Conductor</th>
              <th>Destino</th>
              <th style="text-align:right;">Km inicial</th>
              <th style="text-align:right;">Km final</th>
              <th style="text-align:right;">Recorridos</th>
              <th>Estado</th>
              <?php if ($esAdmin): ?><th>Acciones</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($viajes as $vj): ?>
            <tr data-row='<?= htmlspecialchars(json_encode($vj), ENT_QUOTES) ?>'>
              <td><?= htmlspecialchars($vj['fecha']) ?></td>
              <td><?= htmlspecialchars($vj['placa'] . ' · ' . trim(($vj['marca']??'').' '.($vj['modelo']??''))) ?></td>
              <td><?= htmlspecialchars($vj['conductor']) ?></td>
              <td><?= htmlspecialchars($vj['destino']) ?></td>
              <td style="text-align:right;"><?= number_format((int)$vj['km_inicial']) ?></td>
              <td style="text-align:right;"><?= $vj['km_final'] !== null ? number_format((int)$vj['km_final']) : '—' ?></td>
              <td style="text-align:right;font-weight:700;color:#16a34a;">
                <?= $vj['km_final'] !== null ? number_format((int)$vj['km_recorridos']) : '—' ?>
              </td>
              <td>
                <?php $clase = $vj['estado'] === 'en_curso' ? 'chip-curso' : 'chip-final'; ?>
                <span class="chip <?= $clase ?>"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $vj['estado']))) ?></span>
              </td>
              <?php if ($esAdmin): ?>
              <td>
                <?php if ($vj['estado'] === 'en_curso'): ?>
                  <button class="btn btn-sm btn-outline-success btn-close-viaje" title="Cerrar viaje"><i class="fas fa-flag-checkered"></i> Cerrar</button>
                <?php endif; ?>
                <button class="btn btn-sm btn-outline-danger btn-del-viaje" title="Eliminar"><i class="fas fa-trash"></i></button>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ══ TAB: MANTENIMIENTOS ══ -->
  <div class="flota-panel" id="panel-mantos">
    <div class="card-box">
      <div style="overflow-x:auto;">
        <table class="sag-table" id="tblMantos">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Vehículo</th>
              <th>Tipo</th>
              <th style="text-align:right;">Km al realizar</th>
              <th style="text-align:right;">Costo (L.)</th>
              <th>Taller</th>
              <th>Descripción</th>
              <?php if ($esAdmin): ?><th>Acciones</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($mantos as $m): ?>
            <tr>
              <td><?= htmlspecialchars($m['fecha']) ?></td>
              <td><?= htmlspecialchars($m['placa'] . ' · ' . trim(($m['marca']??'').' '.($m['modelo']??''))) ?></td>
              <td><?= htmlspecialchars($tiposM[$m['tipo']]['nombre'] ?? $m['tipo']) ?></td>
              <td style="text-align:right;"><?= number_format((int)$m['km_al_realizar']) ?></td>
              <td style="text-align:right;font-weight:700;">L. <?= number_format((float)$m['costo'], 2) ?></td>
              <td><?= htmlspecialchars($m['taller']) ?></td>
              <td><?= htmlspecialchars($m['descripcion']) ?></td>
              <?php if ($esAdmin): ?>
              <td>
                <button class="btn btn-sm btn-outline-danger btn-del-manto" data-id="<?= $m['id_mantenimiento'] ?>" title="Eliminar"><i class="fas fa-trash"></i></button>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="flota-panel" id="panel-combustible">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div><h3 class="fleet-section-title">Control de combustible</h3><div class="fleet-section-sub">Vales, cargas, costos y rendimiento por vehículo</div></div>
      <?php if ($esAdmin): ?><button class="btn btn-primary" id="btnNuevaCarga"><i class="fas fa-gas-pump me-1"></i> Registrar carga</button><?php endif; ?>
    </div>
    <div class="row g-2 mb-3">
      <div class="col-md-6"><div class="flota-kpi"><div class="ki" style="background:#dbeafe;color:#1d4ed8"><i class="fas fa-droplet"></i></div><div><div class="kv"><?= number_format((float)$kpis['galones_mes'],2) ?></div><div class="kl">Galones este mes</div></div></div></div>
      <div class="col-md-6"><div class="flota-kpi"><div class="ki" style="background:#dcfce7;color:#15803d"><i class="fas fa-coins"></i></div><div><div class="kv">L. <?= number_format((float)$kpis['costo_mes'],2) ?></div><div class="kl">Costo este mes</div></div></div></div>
    </div>
    <div style="overflow-x:auto"><table class="sag-table" id="tblCombustible"><thead><tr>
      <th>Fecha</th><th>Vale / Factura</th><th>Vehículo</th><th>Conductor</th>
      <th style="text-align:right">Galones</th><th style="text-align:right">Precio/gal</th>
      <th style="text-align:right">Monto</th><th style="text-align:right">Odómetro</th><th>Estación</th>
      <?php if($esAdmin): ?><th>Acciones</th><?php endif; ?>
    </tr></thead><tbody>
      <?php foreach($combustibles as $c): ?><tr>
        <td><?= htmlspecialchars($c['fecha']) ?></td>
        <td><strong><?= htmlspecialchars($c['numero_vale'] ?: ($c['numero_factura'] ?: '—')) ?></strong></td>
        <td><?= htmlspecialchars($c['placa'].' · '.trim($c['marca'].' '.$c['modelo'])) ?></td>
        <td><?= htmlspecialchars($c['conductor'] ?: '—') ?></td>
        <td style="text-align:right"><?= number_format((float)$c['galones'],2) ?></td>
        <td style="text-align:right">L. <?= number_format((float)$c['precio_galon'],2) ?></td>
        <td style="text-align:right;font-weight:700">L. <?= number_format((float)$c['monto'],2) ?></td>
        <td style="text-align:right"><?= number_format((int)$c['km_odometro']) ?> km</td>
        <td><?= htmlspecialchars($c['estacion'] ?: '—') ?></td>
        <?php if($esAdmin): ?><td><button class="btn btn-sm btn-outline-danger btn-del-carga" data-id="<?= (int)$c['id_carga'] ?>"><i class="fas fa-trash"></i></button></td><?php endif; ?>
      </tr><?php endforeach; ?>
    </tbody></table></div>
  </div>

  <div class="flota-panel" id="panel-conductores">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div><h3 class="fleet-section-title">Conductores autorizados</h3><div class="fleet-section-sub">Licencias, disponibilidad e historial de asignaciones</div></div>
      <button class="btn btn-primary" disabled><i class="fas fa-user-plus me-1"></i> Nuevo conductor</button>
    </div>
    <div class="fleet-empty"><i class="fas fa-id-card fa-2x mb-2"></i><br>Área preparada para la siguiente fase funcional.</div>
  </div>

</div><!-- /fleet-shell -->
</div>

<!-- ═══ MODAL: Vehículo ═══ -->
<div class="modal fade" id="modalVehiculo" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="formVehiculo">
        <div class="modal-header" style="background:#1e3a8a;color:#fff;">
          <h5 class="modal-title"><i class="fas fa-car me-2"></i> <span id="vehTitulo">Nuevo vehículo</span></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="vehId" name="id_vehiculo" value="0">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label" style="font-weight:600;">Placa *</label>
              <input class="form-control" id="vehPlaca" name="placa" required>
            </div>
            <div class="col-md-4">
              <label class="form-label" style="font-weight:600;">Marca</label>
              <input class="form-control" id="vehMarca" name="marca" placeholder="Toyota">
            </div>
            <div class="col-md-4">
              <label class="form-label" style="font-weight:600;">Modelo</label>
              <input class="form-control" id="vehModelo" name="modelo" placeholder="Hilux">
            </div>
            <div class="col-md-3">
              <label class="form-label" style="font-weight:600;">Año</label>
              <input type="number" class="form-control" id="vehAnio" name="anio" min="1990" max="2030">
            </div>
            <div class="col-md-3">
              <label class="form-label" style="font-weight:600;">Color</label>
              <input class="form-control" id="vehColor" name="color">
            </div>
            <div class="col-md-3">
              <label class="form-label" style="font-weight:600;">VIN/Chasis</label>
              <input class="form-control" id="vehVin" name="vin">
            </div>
            <div class="col-md-3">
              <label class="form-label" style="font-weight:600;">Km actual</label>
              <input type="number" class="form-control" id="vehKm" name="km_actual" value="0" min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label" style="font-weight:600;">Combustible</label>
              <select class="form-control" id="vehCombustible" name="tipo_combustible">
                <?php foreach ($combs as $k => $n): ?>
                  <option value="<?= $k ?>"><?= htmlspecialchars($n) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label" style="font-weight:600;">Estado</label>
              <select class="form-control" id="vehEstado" name="estado">
                <?php foreach ($estados as $k => $n): ?>
                  <option value="<?= $k ?>"><?= htmlspecialchars($n) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label" style="font-weight:600;">Vence seguro</label>
              <input type="date" class="form-control" id="vehVenceSeguro" name="vence_seguro">
            </div>
            <div class="col-12 mt-3">
              <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:9px;padding:12px;">
                <div style="font-weight:700;color:#1e3a8a;margin-bottom:3px;">
                  <i class="fas fa-gauge-high me-1"></i> Programación inicial de mantenimiento
                </div>
                <small style="color:#64748b;">Indique en qué kilometraje corresponde realizar cada servicio.</small>
                <div class="row g-2 mt-1">
                  <?php foreach ($tiposM as $k => $tt): ?>
                  <div class="col-md-4">
                    <label class="form-label" style="font-size:.75rem;font-weight:600;">
                      <?= htmlspecialchars($tt['nombre']) ?>
                      <span style="color:#94a3b8;">· cada <?= number_format($tt['cada_km']) ?> km</span>
                    </label>
                    <input type="number"
                           class="form-control veh-proximo-manto"
                           id="vehProximo_<?= htmlspecialchars($k) ?>"
                           name="proximo_mantenimiento[<?= htmlspecialchars($k) ?>]"
                           data-intervalo="<?= (int)$tt['cada_km'] ?>"
                           min="0"
                           placeholder="Próximo km">
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label" style="font-weight:600;">Observaciones</label>
              <textarea class="form-control" id="vehObs" name="observaciones" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" style="background:#1e3a8a;border-color:#1e3a8a;">
            <i class="fas fa-save me-1"></i> Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══ MODAL: Viaje ═══ -->
<div class="modal fade" id="modalViaje" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formViaje">
        <div class="modal-header" style="background:#0d9488;color:#fff;">
          <h5 class="modal-title"><i class="fas fa-route me-2"></i> Nuevo viaje</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="viajeIdVehiculo" name="id_vehiculo" value="0">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
          <p style="font-size:.9rem;color:#475569;" id="viajeVehInfo"></p>
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label" style="font-weight:600;">Fecha *</label>
              <input type="date" class="form-control" name="fecha" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" style="font-weight:600;">Conductor *</label>
              <input class="form-control" name="conductor" required placeholder="Nombre del conductor">
            </div>
            <div class="col-md-6">
              <label class="form-label" style="font-weight:600;">Km inicial *</label>
              <input type="number" class="form-control" name="km_inicial" id="viajeKmInicial" min="0" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" style="font-weight:600;">Destino</label>
              <input class="form-control" name="destino" placeholder="Ej: Olancho">
            </div>
            <div class="col-12">
              <label class="form-label" style="font-weight:600;">Propósito</label>
              <input class="form-control" name="proposito" placeholder="Supervisión, entrega, etc.">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-flag me-1"></i> Iniciar viaje
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══ MODAL: Cerrar viaje ═══ -->
<div class="modal fade" id="modalCerrarViaje" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formCerrarViaje">
        <div class="modal-header" style="background:#16a34a;color:#fff;">
          <h5 class="modal-title"><i class="fas fa-flag-checkered me-2"></i> Cerrar viaje</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="cerrarIdViaje" name="id_viaje" value="0">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
          <p style="font-size:.9rem;color:#475569;" id="cerrarInfo"></p>
          <label class="form-label" style="font-weight:600;">Km final del viaje *</label>
          <input type="number" class="form-control" name="km_final" id="cerrarKmFinal" min="0" required>
          <small style="color:#64748b;">Esto actualiza el kilometraje del vehículo y finaliza el viaje.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check me-1"></i> Cerrar viaje
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══ MODAL: Mantenimiento ═══ -->
<div class="modal fade" id="modalManto" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formManto">
        <div class="modal-header" style="background:#b45309;color:#fff;">
          <h5 class="modal-title"><i class="fas fa-wrench me-2"></i> Nuevo mantenimiento</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="mantoIdVehiculo" name="id_vehiculo" value="0">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
          <p style="font-size:.9rem;color:#475569;" id="mantoVehInfo"></p>
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label" style="font-weight:600;">Tipo *</label>
              <select class="form-control" name="tipo" required>
                <option value="">— Seleccione —</option>
                <?php foreach ($tiposM as $k => $tt): ?>
                  <option value="<?= $k ?>"><?= htmlspecialchars($tt['nombre']) ?> (cada <?= number_format($tt['cada_km']) ?> km)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label" style="font-weight:600;">Fecha *</label>
              <input type="date" class="form-control" name="fecha" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" style="font-weight:600;">Km al realizar *</label>
              <input type="number" class="form-control" name="km_al_realizar" id="mantoKmRealizar" min="0" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" style="font-weight:600;">Costo (L.)</label>
              <input type="number" step="0.01" class="form-control" name="costo" value="0">
            </div>
            <div class="col-md-6">
              <label class="form-label" style="font-weight:600;">Taller</label>
              <input class="form-control" name="taller">
            </div>
            <div class="col-12">
              <label class="form-label" style="font-weight:600;">Descripción</label>
              <textarea class="form-control" name="descripcion" rows="2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" style="background:#b45309;border-color:#b45309;">
            <i class="fas fa-save me-1"></i> Guardar
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalCombustible" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
    <form id="formCombustible">
      <div class="modal-header" style="background:#1d4ed8;color:#fff">
        <h5 class="modal-title"><i class="fas fa-gas-pump me-2"></i>Registrar carga de combustible</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body"><div class="row g-2">
        <div class="col-md-6"><label class="form-label fw-semibold">Vehículo *</label><select class="form-control" name="id_vehiculo" id="cargaVehiculo" required><option value="">— Seleccione —</option><?php foreach($vehiculos as $v): ?><option value="<?= (int)$v['id_vehiculo'] ?>" data-km="<?= (int)$v['km_actual'] ?>"><?= htmlspecialchars($v['placa'].' · '.trim($v['marca'].' '.$v['modelo'])) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Fecha *</label><input type="date" class="form-control" name="fecha" value="<?= date('Y-m-d') ?>" required></div>
        <div class="col-md-3"><label class="form-label fw-semibold">No. vale</label><input class="form-control" name="numero_vale"></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Conductor</label><input class="form-control" name="conductor"></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Galones *</label><input type="number" step="0.01" min="0.01" class="form-control calc-carga" name="galones" required></div>
        <div class="col-md-3"><label class="form-label fw-semibold">Precio/galón</label><input type="number" step="0.01" min="0" class="form-control calc-carga" name="precio_galon"></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Monto total *</label><input type="number" step="0.01" min="0" class="form-control" name="monto" id="cargaMonto" required></div>
        <div class="col-md-4"><label class="form-label fw-semibold">Odómetro *</label><input type="number" min="0" class="form-control" name="km_odometro" id="cargaKm" required></div>
        <div class="col-md-4"><label class="form-label fw-semibold">No. factura</label><input class="form-control" name="numero_factura"></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Estación / proveedor</label><input class="form-control" name="estacion"></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Observaciones</label><input class="form-control" name="observaciones"></div>
      </div></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar carga</button></div>
    </form>
  </div></div>
</div>

<?php
$jsExtra = '<script src="' . asset('public/assets/js/modules/flota.js') . '"></script>';
require ROOT_PATH . '/app/views/layouts/footer.php';
?>
