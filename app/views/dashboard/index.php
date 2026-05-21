<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php
$prog      = $_SESSION['programa'] ?? [];
$progSigla = $prog['sigla']  ?? '';
$progColor = $prog['color']  ?? '#54668E';
$progIco   = $prog['icono']  ?? 'fa-seedling';
$progNombre = $prog['nombre'] ?? '';
?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<!-- ══ CONTENT ══ -->
<div class="content">

  <!-- Page Header -->
  <div class="page-header">
    <div>
      <div class="page-title">
        Dashboard
        <small>
          <?= progIconHtml($prog, 'margin-right:4px;') ?>
          <?= htmlspecialchars($progSigla) ?> &mdash; <?= htmlspecialchars($progNombre) ?>
        </small>
      </div>
    </div>
    <div class="breadcrumb-bar">
      <i class="fas fa-house" style="font-size:.7rem;"></i>
      &nbsp;/ <span>Dashboard</span>
    </div>
  </div>

  <!-- ── KPI Cards ── -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
      <div class="kpi-card">
        <div class="kpi-icon"><i class="fas fa-building-wheat"></i></div>
        <div>
          <div class="kpi-val" id="kpi-orgs">0</div>
          <div class="kpi-lbl">Organizaciones activas</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="kpi-card gold">
        <div class="kpi-icon"><i class="fas fa-users"></i></div>
        <div>
          <div class="kpi-val" id="kpi-benes">0</div>
          <div class="kpi-lbl">Beneficiarios activos</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="kpi-card blue">
        <div class="kpi-icon"><i class="fas fa-graduation-cap"></i></div>
        <div>
          <div class="kpi-val" id="kpi-caps">0</div>
          <div class="kpi-lbl">Capacitaciones</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-xl-3">
      <div class="kpi-card purple">
        <div class="kpi-icon"><i class="fas fa-handshake"></i></div>
        <div>
          <div class="kpi-val" id="kpi-at">0</div>
          <div class="kpi-lbl">Asistencias Técnicas</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Mapa + Acciones rápidas ── -->
  <div class="row g-3 mb-3">

    <!-- Mapa Leaflet -->
    <div class="col-12 col-xl-8">
      <div class="card-box">
        <div class="card-box-header">
          <h6><i class="fas fa-map"></i> Mapa de Organizaciones Registradas</h6>
          <div style="display:flex;gap:12px;font-size:.75rem;">
            <span style="display:flex;align-items:center;gap:4px;color:#555;">
              <span style="width:10px;height:10px;border-radius:50%;background:#1a5c2a;display:inline-block;"></span> Activa
            </span>
            <span style="display:flex;align-items:center;gap:4px;color:#555;">
              <span style="width:10px;height:10px;border-radius:50%;background:#f5a623;display:inline-block;"></span> En revisión
            </span>
            <span style="display:flex;align-items:center;gap:4px;color:#555;">
              <span style="width:10px;height:10px;border-radius:50%;background:#6c757d;display:inline-block;"></span> Pendiente
            </span>
          </div>
        </div>
        <div id="map" style="height:380px;width:100%;"></div>
      </div>
    </div>

    <!-- Acciones rápidas -->
    <div class="col-12 col-xl-4">
      <div class="card-box h-100">
        <div class="card-box-header">
          <h6><i class="fas fa-bolt"></i> Acciones Rápidas</h6>
        </div>
        <div style="padding:14px;display:flex;flex-direction:column;gap:8px;">

          <a href="<?= BASE_URL ?>/organizaciones" class="quick-link">
            <div class="ql-icon" style="background:#e8f5ea;">
              <i class="fas fa-building-wheat" style="color:#2d8a3e;"></i>
            </div>
            <div>
              <div style="font-weight:600;font-size:.84rem;">Nueva Organización</div>
              <div style="font-size:.73rem;color:#888;">Registrar organización o cooperativa</div>
            </div>
          </a>

          <a href="<?= BASE_URL ?>/beneficiarios" class="quick-link">
            <div class="ql-icon" style="background:#fff8e6;">
              <i class="fas fa-user-plus" style="color:#d4891a;"></i>
            </div>
            <div>
              <div style="font-weight:600;font-size:.84rem;">Registrar Beneficiario</div>
              <div style="font-size:.73rem;color:#888;">Carga individual o masiva</div>
            </div>
          </a>

          <a href="<?= BASE_URL ?>/asistencia" class="quick-link">
            <div class="ql-icon" style="background:#eff6ff;">
              <i class="fas fa-person-chalkboard" style="color:#3b82f6;"></i>
            </div>
            <div>
              <div style="font-weight:600;font-size:.84rem;">Asistencia Técnica</div>
              <div style="font-size:.73rem;color:#888;">Registrar visita técnica (AT)</div>
            </div>
          </a>

          <a href="<?= BASE_URL ?>/capacitaciones" class="quick-link">
            <div class="ql-icon" style="background:#f5f3ff;">
              <i class="fas fa-graduation-cap" style="color:#8b5cf6;"></i>
            </div>
            <div>
              <div style="font-weight:600;font-size:.84rem;">Nueva Capacitación</div>
              <div style="font-size:.73rem;color:#888;">Registrar evento de capacitación</div>
            </div>
          </a>

          <a href="<?= BASE_URL ?>/exportar" class="quick-link">
            <div class="ql-icon" style="background:#fef2f2;">
              <i class="fas fa-file-export" style="color:#ef4444;"></i>
            </div>
            <div>
              <div style="font-weight:600;font-size:.84rem;">Exportar Reporte</div>
              <div style="font-size:.73rem;color:#888;">Generar reporte en Excel / PDF</div>
            </div>
          </a>

        </div>
      </div>
    </div>
  </div>

  <!-- ── Últimas organizaciones ── -->
  <div class="card-box mb-3">
    <div class="card-box-header">
      <h6><i class="fas fa-table-list"></i> Últimas Organizaciones Registradas</h6>
      <a href="<?= BASE_URL ?>/organizaciones" style="font-size:.78rem;color:var(--primario);text-decoration:none;font-weight:600;">
        Ver todas <i class="fas fa-arrow-right"></i>
      </a>
    </div>
    <div style="overflow-x:auto;">
      <table class="mini-table" id="tablaUltimas">
        <thead>
          <tr>
            <th>Organización</th>
            <th>Representante</th>
            <th>Departamento</th>
            <th>Beneficiarios</th>
            <th>Estado</th>
            <th>Fecha Registro</th>
          </tr>
        </thead>
        <tbody id="tbodyUltimas">
          <tr>
            <td colspan="6" style="text-align:center;color:#aaa;padding:24px;">
              <span class="spinner-border spinner-border-sm" style="margin-right:6px;"></span>
              Cargando datos...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /content -->

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>

<!-- Dashboard JS -->
<script>
const BASE_URL = '<?= BASE_URL ?>';
const PROG_COLOR = '<?= $progColor ?>';

// ──────────────────────────────────────
//  MAPA LEAFLET
// ──────────────────────────────────────
const map = L.map('map', { zoomControl: true }).setView([14.7, -86.8], 7);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors',
  maxZoom: 18
}).addTo(map);

const markerColors = { activa: '#1a5c2a', revision: '#f5a623', inactiva: '#6c757d', pendiente: '#6c757d' };
const statusLabel  = { activa: 'Activa', revision: 'En Revisión', inactiva: 'Inactiva', pendiente: 'Pendiente' };

function pintarMarcador(org) {
  if (!org.latitud || !org.longitud) return;
  const color = markerColors[org.estado] || '#6c757d';
  const icon = L.divIcon({
    className: '',
    html: `<div style="
      width:28px;height:28px;border-radius:50%;
      background:${color};border:2.5px solid #fff;
      box-shadow:0 2px 8px rgba(0,0,0,.35);
      display:flex;align-items:center;justify-content:center;cursor:pointer;">
      <i class="fas fa-building-wheat" style="color:#fff;font-size:.65rem;"></i>
    </div>`,
    iconSize: [28, 28], iconAnchor: [14, 14]
  });

  const marker = L.marker([parseFloat(org.latitud), parseFloat(org.longitud)], { icon }).addTo(map);
  const est = org.estado || 'pendiente';
  const estColor = est === 'activa' ? '#d4edda' : est === 'revision' ? '#fff3cd' : '#e2e3e5';
  const estText  = est === 'activa' ? '#155724' : est === 'revision' ? '#856404' : '#495057';

  marker.bindPopup(`
    <div style="font-family:'Segoe UI',sans-serif;min-width:180px;">
      <div style="font-weight:700;font-size:.9rem;color:#1a1a1a;margin-bottom:4px;">${org.nombre}</div>
      <div style="font-size:.78rem;color:#555;margin-bottom:8px;">${org.departamento || ''}</div>
      <div style="font-size:.78rem;margin-bottom:3px;"><b>Representante:</b> ${org.representante || '—'}</div>
      <div style="font-size:.78rem;margin-bottom:6px;"><b>Beneficiarios:</b>
        <span style="color:#2d8a3e;font-weight:700;">${(org.num_beneficiarios || 0).toLocaleString()}</span>
      </div>
      <span style="display:inline-block;padding:2px 8px;border-radius:10px;font-size:.68rem;font-weight:700;
        background:${estColor};color:${estText};">
        ${statusLabel[est] || est}
      </span>
    </div>`, { maxWidth: 220 });
}

// Cargar puntos desde API
fetch(BASE_URL + '/dashboard/mapa', {
  headers: { 'X-Requested-With': 'XMLHttpRequest' }
})
.then(r => r.json())
.then(res => {
  if (res.success && res.data && res.data.length > 0) {
    res.data.forEach(pintarMarcador);
  } else {
    // Fallback — marcadores de ejemplo para Honduras si no hay datos
    const ejemplos = [
      { lat:14.0818, lng:-87.2068, nombre:'Cooperativa Las Flores',   rep:'María Rodríguez',  dpto:'Francisco Morazán', benes:142, estado:'activa' },
      { lat:14.9203, lng:-88.2368, nombre:'Asociación Cafetalera HN', rep:'Carlos Mejía',     dpto:'Santa Bárbara',     benes:89,  estado:'revision' },
      { lat:14.6489, lng:-86.2196, nombre:'Cooperativa El Pinal',     rep:'José Hernández',   dpto:'Olancho',           benes:207, estado:'activa' },
      { lat:14.7718, lng:-88.7762, nombre:'Grupo Agrícola Copán',     rep:'Ana Contreras',    dpto:'Copán',             benes:55,  estado:'pendiente' },
      { lat:15.1200, lng:-87.0500, nombre:'Asociación Yoreña',        rep:'Pedro Amador',     dpto:'Yoro',              benes:178, estado:'activa' },
    ];
    ejemplos.forEach(o => pintarMarcador({
      latitud: o.lat, longitud: o.lng, nombre: o.nombre,
      representante: o.rep, departamento: o.dpto,
      num_beneficiarios: o.benes, estado: o.estado
    }));
  }
})
.catch(() => {});

// ──────────────────────────────────────
//  KPIs + tabla últimas orgs
// ──────────────────────────────────────
const badgeHtml = {
  activa:    '<span class="status-pill sp-activo">Activa</span>',
  revision:  '<span class="status-pill sp-revision">En Revisión</span>',
  inactiva:  '<span class="status-pill sp-pendiente">Inactiva</span>',
  pendiente: '<span class="status-pill sp-pendiente">Pendiente</span>',
};

fetch(BASE_URL + '/dashboard/stats', {
  headers: { 'X-Requested-With': 'XMLHttpRequest' }
})
.then(r => r.json())
.then(res => {
  if (!res.success) return;
  const k = res.data.kpis;

  // KPI cards
  document.getElementById('kpi-orgs').textContent  = (k.orgs  || 0).toLocaleString();
  document.getElementById('kpi-benes').textContent = (k.benes || 0).toLocaleString();
  document.getElementById('kpi-caps').textContent  = (k.caps  || 0).toLocaleString();
  document.getElementById('kpi-at').textContent    = (k.at    || 0).toLocaleString();

  // Tabla últimas organizaciones
  const tbody = document.getElementById('tbodyUltimas');
  if (!res.data.ultimas || res.data.ultimas.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#aaa;padding:24px;">Sin organizaciones registradas aún.</td></tr>';
    return;
  }
  tbody.innerHTML = res.data.ultimas.map(o => `
    <tr>
      <td>
        <strong>${o.nombre || '—'}</strong>
        ${o.email ? '<br><small style="color:#aaa;">' + o.email + '</small>' : ''}
      </td>
      <td>${o.representante || '—'}</td>
      <td>${o.departamento || '—'}</td>
      <td><span style="font-weight:700;color:var(--primario);">${(parseInt(o.num_beneficiarios) || 0).toLocaleString()}</span></td>
      <td>${badgeHtml[o.estado] || '<span class="status-pill sp-pendiente">' + (o.estado || '—') + '</span>'}</td>
      <td style="color:#888;font-size:.78rem;">${o.fecha_registro || '—'}</td>
    </tr>
  `).join('');
})
.catch(() => {
  document.getElementById('tbodyUltimas').innerHTML =
    '<tr><td colspan="6" style="text-align:center;color:#aaa;padding:24px;">Error al cargar datos.</td></tr>';
});
</script>
