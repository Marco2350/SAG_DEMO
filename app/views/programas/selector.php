<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <?php $_csrf_value = function_exists('csrf_token') ? csrf_token() : ''; ?>
  <meta name="csrf-token" content="<?= htmlspecialchars($_csrf_value, ENT_QUOTES) ?>"/>
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    /* Variables compartidas con el resto del sistema SAG */
    :root {
      --sag-green:        #1f6e3a;
      --sag-green-dark:   #154a26;
      --sag-green-soft:   #e8f3ec;
      --sag-bg:           #f3faf5;
      --sag-text:         #1f2937;
      --sag-text-soft:    #6b7280;
      --sag-border:       #e5e7eb;
    }
    * { box-sizing:border-box; margin:0; padding:0; }
    html, body { height:100%; }
    body {
      font-family:'Segoe UI', system-ui, -apple-system, sans-serif;
      color: var(--sag-text);
      background:
        radial-gradient(circle at 8% 70%, rgba(31,110,58,.06) 0 1px, transparent 1.5px) 0 0/22px 22px,
        radial-gradient(circle at 92% 30%, rgba(31,110,58,.06) 0 1px, transparent 1.5px) 0 0/22px 22px,
        linear-gradient(180deg, #ffffff 0%, var(--sag-bg) 60%, #eaf6ee 100%);
      min-height:100vh;
      display:flex;
      flex-direction:column;
      position:relative;
      overflow-x:hidden;
    }

    /* Decorative subtle wave at bottom */
    body::before {
      content:'';
      position:absolute; left:0; right:0; bottom:0; height:140px;
      background:
        radial-gradient(ellipse at 20% 100%, rgba(31,110,58,.05), transparent 60%),
        radial-gradient(ellipse at 80% 100%, rgba(31,110,58,.05), transparent 60%);
      pointer-events:none; z-index:0;
    }

    /* ── Topbar ── */
    .sel-topbar {
      background: linear-gradient(135deg, var(--sag-green) 0%, var(--sag-green-dark) 100%);
      padding: 14px 30px;
      display:flex; align-items:center; justify-content:space-between;
      box-shadow: 0 2px 12px rgba(0,0,0,.08);
      position:relative; z-index:2;
    }
    .sel-brand { display:flex; align-items:center; gap:12px; text-decoration:none; }
    .sel-brand .icon {
      width:42px; height:42px; border-radius:10px;
      background:#fff;
      display:flex; align-items:center; justify-content:center;
      box-shadow: 0 2px 6px rgba(0,0,0,.1);
    }
    .sel-brand .icon i { color: var(--sag-green); font-size:1.15rem; }
    .sel-brand span { color:#fff; font-size:1.05rem; font-weight:700; letter-spacing:.2px; }

    .sel-user {
      display:flex; align-items:center; gap:14px;
      color: rgba(255,255,255,.95);
    }
    .sel-avatar {
      width:38px; height:38px; border-radius:50%;
      background: rgba(255,255,255,.15);
      border:1.5px solid rgba(255,255,255,.4);
      display:flex; align-items:center; justify-content:center;
      font-size:.85rem; font-weight:700; color:#fff;
    }
    .sel-userinfo { display:flex; align-items:center; gap:6px; font-size:.88rem; font-weight:600; }
    .sel-userinfo i { font-size:.65rem; opacity:.7; }
    .sel-logout {
      color:#fff; text-decoration:none;
      font-size:.82rem; font-weight:600;
      padding:7px 16px; border-radius:22px;
      border:1.5px solid rgba(255,255,255,.5);
      transition: all .2s;
      display:flex; align-items:center; gap:6px;
    }
    .sel-logout:hover { background:#fff; color: var(--sag-green); border-color:#fff; }

    /* ════════════════════════════════════════════════════════════ */
    /*  Selector de programas (rediseño de visualización)            */
    /* ════════════════════════════════════════════════════════════ */
    .program-selector-wrapper {
        max-width: 1500px;
        margin: 0 auto;
        padding: 22px 28px 16px;
        position: relative;
        z-index: 2;
        flex: 1;
        width: 100%;
    }
    .selector-title {
        text-align: center;
        margin-bottom: 20px;
    }
    .selector-title h1 {
        font-size: 26px;
        font-weight: 800;
        color: #101828;
        margin-bottom: 4px;
    }
    .selector-title p {
        font-size: 14px;
        color: #667085;
    }
    .program-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(240px, 1fr));
        gap: 18px;
        margin-bottom: 18px;
    }
    .program-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 8px 22px rgba(16, 24, 40, 0.06);
        transition: all 0.25s ease;
        cursor: pointer;
        display: flex;
        flex-direction: column;
    }
    .program-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 30px rgba(16, 24, 40, 0.11);
        border-color: rgba(15, 81, 50, 0.25);
    }
    .program-card.selected {
        border-color: var(--sag-green);
        box-shadow: 0 14px 30px rgba(31, 110, 58, 0.16);
    }
    .program-card-header {
        padding: 18px 22px 16px;
        text-align: center;
    }
    .program-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 700;
        margin-bottom: 12px;
        letter-spacing: .2px;
    }
    .program-icon {
        width: 66px;
        height: 66px;
        border-radius: 50%;
        margin: 0 auto 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
    }
    .program-code {
        font-size: 24px;
        font-weight: 900;
        letter-spacing: .8px;
        margin-bottom: 6px;
        line-height: 1;
    }
    .program-description {
        font-size: 12.5px;
        color: #475467;
        line-height: 1.4;
        min-height: 36px;
    }
    .program-stats {
        border-top: 1px solid #eaecf0;
        padding: 14px 22px 12px;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px 10px;
    }
    .stat-item {
        text-align: center;
    }
    .stat-value {
        font-size: 20px;
        font-weight: 900;
        line-height: 1;
    }
    .stat-label {
        font-size: 11.5px;
        color: #667085;
        margin-top: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    .stat-label i { font-size: .7rem; }
    .program-button {
        display: block;
        width: calc(100% - 24px);
        margin: 6px 12px 12px;
        padding: 11px 14px;
        border-radius: 10px;
        border: none;
        color: #ffffff;
        font-size: 14px;
        font-weight: 800;
        text-align: center;
        text-decoration: none;
        transition: all 0.2s ease;
        cursor: pointer;
        margin-top: auto;
    }
    .program-button:hover {
        filter: brightness(0.95);
        transform: translateY(-1px);
    }
    .summary-panel {
        background: #ffffff;
        border-radius: 18px;
        border: 1px solid #e5e7eb;
        padding: 16px 22px;
        box-shadow: 0 8px 22px rgba(16, 24, 40, 0.05);
    }
    .summary-title {
        font-size: 14.5px;
        font-weight: 700;
        color: #101828;
        margin-bottom: 12px;
        padding-left: 10px;
        border-left: 4px solid var(--sag-green);
        line-height: 1.2;
    }
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
    }
    .summary-item {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .summary-icon {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #e9f7ef;
        color: #0f6b3d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .summary-value {
        font-size: 22px;
        font-weight: 900;
        color: #101828;
        line-height: 1;
    }
    .summary-label {
        font-size: 12.5px;
        color: #667085;
        margin-top: 3px;
    }
    @media (max-width: 1200px) {
        .program-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .summary-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 700px) {
        .program-grid,
        .summary-grid {
            grid-template-columns: 1fr;
        }
        .program-selector-wrapper {
            padding: 28px 16px;
        }
    }

    /* ── Page footer ── */
    .sel-pagefooter {
      text-align:center;
      padding: 18px 16px 22px;
      font-size:.76rem;
      color: var(--sag-text-soft);
      position:relative; z-index:2;
      display:flex; justify-content:space-between;
      max-width:1180px; margin:0 auto; width:100%;
      flex-wrap:wrap; gap:8px;
    }
    .sel-pagefooter .left i { color: var(--sag-green); margin-right:4px; }

    /* ── Toast ── */
    .toast-msg {
      position:fixed; bottom:24px; right:24px; z-index:999;
      background:#1a1a1a; color:#fff; border-radius:10px;
      padding:14px 20px; font-size:.85rem; font-weight:500;
      box-shadow:0 8px 32px rgba(0,0,0,.3);
      display:none; align-items:center; gap:10px;
      border-left:4px solid #ef4444;
    }
  </style>
</head>
<body>

<!-- Topbar -->
<div class="sel-topbar">
  <a class="sel-brand" href="#">
    <div class="icon"><i class="fas fa-seedling"></i></div>
    <span>SAG Honduras Sin Hambre</span>
  </a>
  <div class="sel-user">
    <div class="sel-avatar"><?= htmlspecialchars($_SESSION['user']['initials'] ?? '??') ?></div>
    <div class="sel-userinfo">
      <span><?= htmlspecialchars(($_SESSION['user']['nombre'] ?? '') . ' ' . ($_SESSION['user']['apellido'] ?? '')) ?></span>
      <i class="fas fa-chevron-down"></i>
    </div>
    <a href="<?= BASE_URL ?>/auth/logout" class="sel-logout">
      <i class="fas fa-right-from-bracket"></i> Salir
    </a>
  </div>
</div>

<!-- Contenido -->
<div class="program-selector-wrapper">

  <div class="selector-title">
    <h1><i class="fas fa-layer-group" style="color:var(--sag-green);"></i> Seleccione el Programa</h1>
    <p>Elija el programa con el que desea trabajar en esta sesión</p>
  </div>

  <!-- Grid de programas -->
  <?php
  // Etiqueta corta descriptiva por programa (badge superior de la card)
  $badges = [
      'pipc'  => 'Incentivos Café',
      'pipg'  => 'Incentivos ganaderos',
      'pipa'  => 'Incentivos agrícola',
      'fprog' => 'Fortalecimiento 2026',
  ];
  ?>
  <div class="program-grid">
    <?php foreach ($programas as $key => $prog):
      $s = $stats[$key] ?? ['orgs'=>0,'benes'=>0,'caps'=>0,'at'=>0];
      $badgeText = $badges[$key] ?? '';
    ?>
    <div class="program-card" onclick="seleccionar('<?= $key ?>')">
      <div class="program-card-header" style="background: linear-gradient(160deg, <?= $prog['color_light'] ?> 0%, #ffffff 90%);">
        <?php if ($badgeText !== ''): ?>
        <div class="program-badge" style="background: <?= $prog['color_light'] ?>; color:<?= $prog['color'] ?>;">
          <?= htmlspecialchars($badgeText) ?>
        </div>
        <?php endif; ?>
        <div class="program-icon" style="background: <?= $prog['color_light'] ?>;">
          <?= progIconHtml($prog) ?>
        </div>
        <div class="program-code" style="color:<?= $prog['color'] ?>;"><?= $prog['sigla'] ?></div>
        <div class="program-description"><?= htmlspecialchars($prog['nombre']) ?></div>
      </div>

      <div class="program-stats">
        <?php if (($s['tipo'] ?? 'pip') === 'fprog'): ?>
        <div class="stat-item">
          <div class="stat-value" style="color:<?= $prog['color'] ?>;"><?= number_format($s['total']) ?></div>
          <div class="stat-label"><i class="fas fa-list-check"></i>Acciones</div>
        </div>
        <div class="stat-item">
          <div class="stat-value" style="color:<?= $prog['color'] ?>;"><?= number_format($s['planificado']) ?></div>
          <div class="stat-label"><i class="fas fa-clock"></i>Planificadas</div>
        </div>
        <div class="stat-item">
          <div class="stat-value" style="color:<?= $prog['color'] ?>;"><?= number_format($s['ejecucion']) ?></div>
          <div class="stat-label"><i class="fas fa-spinner"></i>En Ejecución</div>
        </div>
        <div class="stat-item">
          <div class="stat-value" style="color:<?= $prog['color'] ?>;"><?= number_format($s['completado']) ?></div>
          <div class="stat-label"><i class="fas fa-circle-check"></i>Completadas</div>
        </div>
        <?php else: ?>
        <div class="stat-item">
          <div class="stat-value" style="color:<?= $prog['color'] ?>;"><?= number_format($s['orgs']) ?></div>
          <div class="stat-label"><i class="fas fa-building-wheat"></i>Organizaciones</div>
        </div>
        <div class="stat-item">
          <div class="stat-value" style="color:<?= $prog['color'] ?>;"><?= number_format($s['benes']) ?></div>
          <div class="stat-label"><i class="fas fa-users"></i>Productores</div>
        </div>
        <div class="stat-item">
          <div class="stat-value" style="color:<?= $prog['color'] ?>;"><?= number_format($s['caps']) ?></div>
          <div class="stat-label"><i class="fas fa-graduation-cap"></i>Capacitaciones</div>
        </div>
        <div class="stat-item">
          <div class="stat-value" style="color:<?= $prog['color'] ?>;"><?= number_format($s['at']) ?></div>
          <div class="stat-label"><i class="fas fa-handshake"></i>Asistencias</div>
        </div>
        <?php endif; ?>
      </div>

      <button class="program-button" style="background:<?= $prog['color'] ?>;">
        <i class="fas fa-arrow-right-to-bracket"></i>
        Ingresar a <?= $prog['sigla'] ?>
      </button>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Totales globales -->
  <?php
  $totOrgs  = array_sum(array_map(fn($s) => $s['orgs']  ?? 0, $stats));
  $totBenes = array_sum(array_map(fn($s) => $s['benes'] ?? 0, $stats));
  $totCaps  = array_sum(array_map(fn($s) => $s['caps']  ?? 0, $stats));
  $totAT    = array_sum(array_map(fn($s) => $s['at']    ?? 0, $stats));
  ?>
  <div class="summary-panel">
    <div class="summary-title">Resumen general de registros</div>
    <div class="summary-grid">
      <div class="summary-item">
        <div class="summary-icon"><i class="fas fa-building-wheat"></i></div>
        <div>
          <div class="summary-value"><?= number_format($totOrgs) ?></div>
          <div class="summary-label">Organizaciones totales</div>
        </div>
      </div>
      <div class="summary-item">
        <div class="summary-icon"><i class="fas fa-users"></i></div>
        <div>
          <div class="summary-value"><?= number_format($totBenes) ?></div>
          <div class="summary-label">Productores totales</div>
        </div>
      </div>
      <div class="summary-item">
        <div class="summary-icon"><i class="fas fa-graduation-cap"></i></div>
        <div>
          <div class="summary-value"><?= number_format($totCaps) ?></div>
          <div class="summary-label">Capacitaciones totales</div>
        </div>
      </div>
      <div class="summary-item">
        <div class="summary-icon"><i class="fas fa-handshake"></i></div>
        <div>
          <div class="summary-value"><?= number_format($totAT) ?></div>
          <div class="summary-label">Visitas / Asistencias totales</div>
        </div>
      </div>
    </div>
  </div>

</div><!-- /program-selector-wrapper -->

<!-- Page footer -->
<div class="sel-pagefooter">
  <span class="left">
    <i class="fas fa-seedling"></i>
    Sistema SAG Honduras Sin Hambre &nbsp;·&nbsp; Secretaría de Agricultura y Ganadería
  </span>
  <span>© 2026 &nbsp;·&nbsp; Todos los derechos reservados</span>
</div>

<div class="toast-msg" id="toastErr">
  <i class="fas fa-circle-xmark"></i>
  <span id="toastMsg">Error</span>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE_URL = '<?= BASE_URL ?>';
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').content;

function seleccionar(id) {
  document.querySelectorAll('.program-card').forEach(c => c.classList.remove('selected'));
  event.currentTarget.classList.add('selected');

  fetch(BASE_URL + '/programas/seleccionar', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-Token': CSRF_TOKEN
    },
    body: 'programa=' + encodeURIComponent(id) + '&_csrf=' + encodeURIComponent(CSRF_TOKEN)
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      window.location.href = res.data.redirect;
    } else {
      mostrarError(res.message);
    }
  })
  .catch(() => mostrarError('Error de conexión.'));
}

function mostrarError(msg) {
  const t = document.getElementById('toastErr');
  document.getElementById('toastMsg').textContent = msg;
  t.style.display = 'flex';
  setTimeout(() => { t.style.display = 'none'; }, 4000);
}
</script>
</body>
</html>
