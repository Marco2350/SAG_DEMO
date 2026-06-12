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
    :root {
      --sag-green:        #1f6e3a;
      --sag-green-dark:   #154a26;
      --sag-green-soft:   #e8f3ec;
      --sag-bg:           #f3faf5;
      --sag-text:         #1f2937;
      --sag-text-soft:    #6b7280;
      --sag-border:       #e5e7eb;

      /* Programas */
      --pipc:        #6F4E37;
      --pipc-light:  #f7efe7;
      --pipg:        #2563eb;
      --pipg-light:  #eaf1ff;
      --pipa:        #16a34a;
      --pipa-light:  #e8f7ee;
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

    /* ── Contenido principal ── */
    .sel-content {
      flex:1; display:flex; flex-direction:column;
      align-items:center; justify-content:center;
      padding: 50px 20px 30px;
      position:relative; z-index:2;
    }

    .sel-title {
      text-align:center; margin-bottom:38px;
    }
    .sel-title h1 {
      color: var(--sag-text);
      font-size:1.9rem; font-weight:700; margin-bottom:8px;
      display:inline-flex; align-items:center; gap:12px;
    }
    .sel-title h1 .ico {
      color: var(--sag-green); font-size:1.6rem;
    }
    .sel-title p {
      color: var(--sag-text-soft);
      font-size:.95rem;
    }

    /* ── Grid de programas ── */
    .programas-grid {
      display:grid; grid-template-columns: repeat(4, 1fr);
      gap:22px; max-width:1440px; width:100%;
    }
    @media(max-width:1200px) { .programas-grid { grid-template-columns:1fr 1fr; max-width:720px; } }
    @media(max-width:620px)  { .programas-grid { grid-template-columns:1fr; max-width:420px; } }

    .prog-card {
      background:#fff;
      border-radius:18px;
      overflow:hidden;
      box-shadow: 0 6px 24px rgba(31,41,55,.08);
      transition: transform .2s ease, box-shadow .2s ease;
      cursor:pointer;
      border:2px solid transparent;
      display:flex; flex-direction:column;
    }
    .prog-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 14px 36px rgba(31,41,55,.14);
    }
    .prog-card.selected { border-color: var(--sag-green); }

    .prog-header {
      padding: 30px 22px 26px; text-align:center;
      position:relative;
    }
    .prog-icon-wrap {
      width:78px; height:78px; border-radius:50%;
      display:flex; align-items:center; justify-content:center;
      margin:0 auto 16px;
      font-size:2rem;
    }
    .prog-sigla {
      font-size:1.55rem; font-weight:800;
      letter-spacing:.5px; line-height:1;
      margin-bottom:8px;
    }
    .prog-nombre {
      font-size:.82rem;
      color: var(--sag-text-soft);
      line-height:1.45;
      padding:0 6px;
    }

    .prog-stats {
      padding:18px 22px 20px;
      border-top:1px solid #f0f0f0;
      display:grid; grid-template-columns:1fr 1fr;
      gap:14px 16px;
    }
    .prog-stat { text-align:center; }
    .prog-stat .val { font-size:1.5rem; font-weight:800; line-height:1; }
    .prog-stat .lbl {
      font-size:.72rem; color: var(--sag-text-soft);
      margin-top:4px; display:flex; align-items:center;
      justify-content:center; gap:4px;
    }
    .prog-stat .lbl i { font-size:.7rem; }

    .prog-btn {
      display:flex; align-items:center; justify-content:center; gap:8px;
      width:100%; padding:14px;
      font-size:.95rem; font-weight:700;
      color:#fff; border:none; cursor:pointer;
      transition: filter .2s;
      letter-spacing:.3px;
      margin-top:auto;
    }
    .prog-btn:hover { filter:brightness(.92); }
    .prog-btn i { font-size:.9rem; }

    /* ── Footer totales ── */
    .sel-footer {
      max-width:1440px; width:100%; margin-top:32px;
      background:#fff; border-radius:16px;
      padding:22px 30px;
      display:grid; grid-template-columns:repeat(4,1fr);
      gap:12px; text-align:left;
      box-shadow: 0 4px 18px rgba(31,41,55,.06);
    }
    @media(max-width:760px) { .sel-footer { grid-template-columns:1fr 1fr; } }

    .sf-item {
      display:flex; align-items:center; gap:14px;
    }
    .sf-icon {
      width:48px; height:48px; border-radius:50%;
      background: var(--sag-green-soft);
      color: var(--sag-green);
      display:flex; align-items:center; justify-content:center;
      font-size:1.2rem; flex-shrink:0;
    }
    .sf-val { font-size:1.5rem; font-weight:800; color:var(--sag-text); line-height:1; }
    .sf-lbl { font-size:.78rem; color:var(--sag-text-soft); margin-top:3px; }

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
<div class="sel-content">

  <div class="sel-title">
    <h1><i class="fas fa-layer-group ico"></i> Seleccione el Programa</h1>
    <p>Elija el programa con el que desea trabajar en esta sesión</p>
  </div>

  <!-- Grid de programas -->
  <div class="programas-grid">
    <?php foreach ($programas as $key => $prog):
      $s = $stats[$key] ?? ['orgs'=>0,'benes'=>0,'caps'=>0,'at'=>0];
    ?>
    <div class="prog-card" onclick="seleccionar('<?= $key ?>')">
      <div class="prog-header" style="background: linear-gradient(160deg, <?= $prog['color_light'] ?> 0%, #ffffff 90%);">
        <div class="prog-icon-wrap" style="background: <?= $prog['color'] ?>22;">
          <?= progIconHtml($prog) ?>
        </div>
        <div class="prog-sigla" style="color:<?= $prog['color'] ?>;"><?= $prog['sigla'] ?></div>
        <div class="prog-nombre"><?= htmlspecialchars($prog['nombre']) ?></div>
      </div>

      <div class="prog-stats">
        <?php if (($s['tipo'] ?? 'pip') === 'fprog'): ?>
        <div class="prog-stat">
          <div class="val" style="color:<?= $prog['color'] ?>;"><?= number_format($s['total']) ?></div>
          <div class="lbl"><i class="fas fa-list-check"></i>Acciones</div>
        </div>
        <div class="prog-stat">
          <div class="val" style="color:<?= $prog['color'] ?>;"><?= number_format($s['planificado']) ?></div>
          <div class="lbl"><i class="fas fa-clock"></i>Planificadas</div>
        </div>
        <div class="prog-stat">
          <div class="val" style="color:<?= $prog['color'] ?>;"><?= number_format($s['ejecucion']) ?></div>
          <div class="lbl"><i class="fas fa-spinner"></i>En Ejecución</div>
        </div>
        <div class="prog-stat">
          <div class="val" style="color:<?= $prog['color'] ?>;"><?= number_format($s['completado']) ?></div>
          <div class="lbl"><i class="fas fa-circle-check"></i>Completadas</div>
        </div>
        <?php else: ?>
        <div class="prog-stat">
          <div class="val" style="color:<?= $prog['color'] ?>;"><?= number_format($s['orgs']) ?></div>
          <div class="lbl"><i class="fas fa-building-wheat"></i>Organizaciones</div>
        </div>
        <div class="prog-stat">
          <div class="val" style="color:<?= $prog['color'] ?>;"><?= number_format($s['benes']) ?></div>
          <div class="lbl"><i class="fas fa-users"></i>Productores</div>
        </div>
        <div class="prog-stat">
          <div class="val" style="color:<?= $prog['color'] ?>;"><?= number_format($s['caps']) ?></div>
          <div class="lbl"><i class="fas fa-graduation-cap"></i>Capacitaciones</div>
        </div>
        <div class="prog-stat">
          <div class="val" style="color:<?= $prog['color'] ?>;"><?= number_format($s['at']) ?></div>
          <div class="lbl"><i class="fas fa-handshake"></i>Asistencias</div>
        </div>
        <?php endif; ?>
      </div>

      <button class="prog-btn" style="background:<?= $prog['color'] ?>;">
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
  <div class="sel-footer">
    <div class="sf-item">
      <div class="sf-icon"><i class="fas fa-building-wheat"></i></div>
      <div>
        <div class="sf-val"><?= number_format($totOrgs) ?></div>
        <div class="sf-lbl">Organizaciones totales</div>
      </div>
    </div>
    <div class="sf-item">
      <div class="sf-icon"><i class="fas fa-users"></i></div>
      <div>
        <div class="sf-val"><?= number_format($totBenes) ?></div>
        <div class="sf-lbl">Productores totales</div>
      </div>
    </div>
    <div class="sf-item">
      <div class="sf-icon"><i class="fas fa-graduation-cap"></i></div>
      <div>
        <div class="sf-val"><?= number_format($totCaps) ?></div>
        <div class="sf-lbl">Capacitaciones totales</div>
      </div>
    </div>
    <div class="sf-item">
      <div class="sf-icon"><i class="fas fa-handshake"></i></div>
      <div>
        <div class="sf-val"><?= number_format($totAT) ?></div>
        <div class="sf-lbl">Visitas AT totales</div>
      </div>
    </div>
  </div>

</div><!-- /sel-content -->

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
  document.querySelectorAll('.prog-card').forEach(c => c.classList.remove('selected'));
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
