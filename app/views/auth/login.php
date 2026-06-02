<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    :root {
      --verde-deep:    #0B5D3D;
      --verde-darker:  #073a26;
      --verde-mid:     #16A34A;
      --verde-light:   #86efac;
      --crema:         #F4F1EA;
      --crema-soft:    #ECE7D8;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #F4F1EA;
      min-height: 100vh;
      display: flex; flex-direction: column;
      color: #2F3A4D;
      position: relative;
      overflow-x: hidden;
    }

    /* ── Top strip ── */
    .top-strip {
      background: var(--verde-deep);
      padding: 14px 28px;
      display: flex; align-items: center; gap: 18px;
      z-index: 10; position: relative;
    }
    .top-logo {
      width: 50px; height: 50px;
      background: #fff; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 2px 8px rgba(0,0,0,.18);
    }
    .top-logo i { font-size: 1.7rem; color: var(--verde-mid); }
    .top-strip h6 {
      color: #fff; font-size: .92rem;
      font-weight: 500; letter-spacing: .3px; margin: 0;
    }
    .top-strip h6 .sep { opacity: .6; margin: 0 6px; }

    /* ── Decoraciones de fondo ── */
    .bg-decor {
      position: absolute; inset: 0;
      pointer-events: none;
      overflow: hidden;
      z-index: 0;
    }
    .bg-illu-left {
      position: absolute; left: 0; right: 0; bottom: 0;
      width: 100%; height: auto;
      max-height: 70vh;
      opacity: .55;
      pointer-events: none;
    }
    .bg-dots-right {
      position: absolute; right: 6vw; bottom: 14vh;
      width: 180px; height: 180px;
      background-image: radial-gradient(circle, #b8b8b8 1.6px, transparent 1.6px);
      background-size: 18px 18px;
      opacity: .35;
    }
    /* Patrón sutil de íconos agrícolas en toda la página */
    body::before {
      content: '';
      position: fixed; inset: 0;
      background-image:
        radial-gradient(circle at 10% 20%, rgba(22,163,74,.04) 0, transparent 12%),
        radial-gradient(circle at 85% 15%, rgba(11,93,61,.04) 0, transparent 12%),
        radial-gradient(circle at 70% 80%, rgba(22,163,74,.05) 0, transparent 14%);
      pointer-events: none;
      z-index: 0;
    }

    /* ── Login card ── */
    .login-wrapper {
      flex: 1;
      display: flex; align-items: center; justify-content: center;
      padding: 40px 16px;
      z-index: 1; position: relative;
    }
    .login-card {
      width: 100%; max-width: 460px;
      background: #fff; border-radius: 18px;
      box-shadow: 0 18px 48px rgba(0,0,0,.12), 0 2px 8px rgba(0,0,0,.06);
      overflow: hidden;
    }

    /* ── Card header verde ── */
    .card-header-custom {
      background: var(--verde-deep);
      padding: 32px 28px 26px;
      text-align: center;
      position: relative;
    }
    .escudo-wrapper {
      width: 86px; height: 86px; border-radius: 50%;
      background: #fff;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 18px;
      box-shadow: 0 4px 16px rgba(0,0,0,.22);
    }
    .escudo-wrapper i {
      font-size: 2.4rem;
      color: var(--verde-mid);
    }
    .card-header-custom h1 {
      font-size: 1.55rem; font-weight: 700;
      color: #fff; margin-bottom: 6px;
      line-height: 1.2;
    }
    .card-header-custom .subt {
      font-size: .85rem; color: rgba(255,255,255,.85);
      margin: 0 0 14px;
    }
    .programa-badge {
      display: inline-flex; align-items: center; gap: 7px;
      background: var(--verde-mid); color: #fff;
      font-size: .76rem; font-weight: 600;
      padding: 7px 16px; border-radius: 30px;
      box-shadow: 0 2px 6px rgba(22,163,74,.35);
    }

    /* ── Card body ── */
    .card-body-custom { padding: 28px 32px 32px; }

    .alerta {
      border-radius: 10px; padding: 12px 14px;
      font-size: .82rem; margin-bottom: 12px;
      display: flex; align-items: flex-start; gap: 10px;
      line-height: 1.4;
    }
    .alerta i.icon-l {
      font-size: 1rem; margin-top: 1px; flex-shrink: 0;
    }
    .alerta-info  { background: #FFFBEA; border: 1px solid #F5E2A0; color: #7a5200; }
    .alerta-error { background: #FEF2F2; border: 1px solid #FECACA; color: #991b1b; }

    .form-label {
      font-size: .88rem; font-weight: 600;
      color: #333; margin-bottom: 8px;
      display: block;
    }

    .input-group-icon {
      position: relative;
      margin-bottom: 18px;
    }
    .form-control {
      background: #F1F1F0;
      border: 1.5px solid transparent;
      border-radius: 10px;
      padding: 13px 42px 13px 44px;
      font-size: .92rem;
      width: 100%;
      transition: background .2s, border-color .2s, box-shadow .2s;
      color: #333;
    }
    .form-control::placeholder { color: #999; }
    .form-control:focus {
      background: #fff;
      border-color: var(--verde-mid);
      box-shadow: 0 0 0 3px rgba(22,163,74,.18);
      outline: none;
    }
    .input-group-icon .icon-left {
      position: absolute; left: 15px;
      bottom: 14px;
      color: #888; font-size: .95rem; z-index: 1;
    }
    .input-group-icon .icon-right {
      position: absolute; right: 15px;
      bottom: 14px;
      color: #888; font-size: .95rem; z-index: 1;
      cursor: pointer;
    }
    .input-group-icon .icon-right:hover { color: var(--verde-deep); }

    .btn-login {
      width: 100%;
      background: var(--verde-deep);
      color: #fff;
      border: none; padding: 14px;
      border-radius: 10px;
      font-size: 1rem; font-weight: 600;
      cursor: pointer; transition: all .2s;
      margin-top: 6px;
      display: flex; align-items: center; justify-content: center; gap: 10px;
    }
    .btn-login:hover {
      background: var(--verde-darker);
      transform: translateY(-1px);
      box-shadow: 0 8px 20px rgba(11,93,61,.3);
    }
    .btn-login:active { transform: translateY(0); }
    .btn-login .spinner-border {
      width: 1.1rem; height: 1.1rem; border-width: 2px;
      display: none;
    }
    .btn-login.loading .spinner-border { display: inline-block; }
    .btn-login.loading .icon-login { display: none; }

    /* ── Footer ── */
    footer {
      background: var(--verde-deep);
      text-align: center; padding: 16px;
      z-index: 10; position: relative;
    }
    footer p {
      color: rgba(255,255,255,.88);
      font-size: .82rem; margin: 0;
    }
    footer p .pipe { color: rgba(255,255,255,.4); margin: 0 12px; }
    footer p .leaf { color: var(--verde-light); margin-right: 4px; }

    /* ── Responsive ── */
    @media (max-width: 640px) {
      .top-strip { padding: 12px 18px; gap: 12px; }
      .top-strip h6 { font-size: .78rem; }
      .top-logo { width: 42px; height: 42px; }
      .top-logo i { font-size: 1.4rem; }
      .bg-illu-left { width: 280px; opacity: .35; }
      .bg-dots-right { display: none; }
      .card-header-custom h1 { font-size: 1.3rem; }
      .card-body-custom { padding: 22px 22px 26px; }
    }
  </style>
</head>
<body>

<!-- ══ Top strip ══ -->
<div class="top-strip">
  <div class="top-logo"><i class="fas fa-seedling"></i></div>
  <h6>República de Honduras <span class="sep">•</span> Gobierno de la República</h6>
</div>

<!-- ══ Decoración de fondo ══ -->
<div class="bg-decor">
  <!-- Ilustración SVG: colinas, sol, árbol y plantas -->
  <svg class="bg-illu-left" viewBox="0 0 1600 720" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMax slice">
    <!-- Sol -->
    <circle cx="280" cy="220" r="90" fill="#F2E3CC" opacity=".55"/>

    <!-- Colinas que abarcan TODO el ancho (viewBox 1600x720) -->
    <path d="M-20 540 Q 220 440, 460 490 T 920 470 T 1380 480 L 1620 470 L 1620 720 L -20 720 Z"
          fill="#B8D4BD" opacity=".55"/>
    <path d="M-20 590 Q 200 530, 430 555 T 880 540 T 1320 550 L 1620 540 L 1620 720 L -20 720 Z"
          fill="#9DC3A5" opacity=".55"/>
    <path d="M-20 650 Q 260 610, 540 630 T 1100 625 L 1620 630 L 1620 720 L -20 720 Z"
          fill="#82B58D" opacity=".55"/>

    <!-- Árboles dispersos a lo largo del paisaje -->
    <g opacity=".75" fill="#3F5A42">
      <!-- Árbol 1 (izquierda) -->
      <rect x="298" y="500" width="3" height="35"/>
      <ellipse cx="300" cy="490" rx="24" ry="30"/>
      <ellipse cx="286" cy="500" rx="14" ry="18"/>
      <ellipse cx="314" cy="500" rx="14" ry="18"/>
      <!-- Árbol 2 (centro-izquierda) -->
      <rect x="678" y="515" width="3" height="32"/>
      <ellipse cx="680" cy="505" rx="20" ry="26"/>
      <ellipse cx="668" cy="513" rx="12" ry="15"/>
      <ellipse cx="692" cy="513" rx="12" ry="15"/>
      <!-- Árbol 3 (centro-derecha) -->
      <rect x="1098" y="510" width="3" height="32"/>
      <ellipse cx="1100" cy="500" rx="22" ry="28"/>
      <ellipse cx="1086" cy="510" rx="13" ry="17"/>
      <ellipse cx="1114" cy="510" rx="13" ry="17"/>
      <!-- Árbol 4 (derecha) -->
      <rect x="1428" y="495" width="3" height="35"/>
      <ellipse cx="1430" cy="485" rx="25" ry="32"/>
      <ellipse cx="1414" cy="498" rx="14" ry="19"/>
      <ellipse cx="1446" cy="498" rx="14" ry="19"/>
    </g>

    <!-- Plantas y frondas en ambos extremos inferiores -->
    <g opacity=".75" fill="#6FA478">
      <!-- Izquierda -->
      <path d="M5 720 Q 8 620, 30 580 Q 38 605, 28 660 Q 32 700, 12 720 Z"/>
      <path d="M40 720 Q 38 640, 65 600 Q 72 625, 62 680 Q 68 710, 45 720 Z"/>
      <path d="M75 720 Q 72 660, 95 625 Q 102 645, 92 690 Q 98 715, 80 720 Z"/>
      <path d="M28 580 Q 22 560, 14 555 Q 22 568, 28 580 Z"/>
      <path d="M30 580 Q 36 562, 44 558 Q 36 570, 30 580 Z"/>
      <path d="M65 600 Q 58 582, 50 578 Q 58 590, 65 600 Z"/>
      <path d="M65 600 Q 72 584, 80 580 Q 72 592, 65 600 Z"/>
      <!-- Derecha -->
      <path d="M1595 720 Q 1592 620, 1570 580 Q 1562 605, 1572 660 Q 1568 700, 1588 720 Z"/>
      <path d="M1560 720 Q 1562 640, 1535 600 Q 1528 625, 1538 680 Q 1532 710, 1555 720 Z"/>
      <path d="M1525 720 Q 1528 660, 1505 625 Q 1498 645, 1508 690 Q 1502 715, 1520 720 Z"/>
      <path d="M1572 580 Q 1578 560, 1586 555 Q 1578 568, 1572 580 Z"/>
      <path d="M1570 580 Q 1564 562, 1556 558 Q 1564 570, 1570 580 Z"/>
    </g>
  </svg>

  <!-- Patrón de puntos a la derecha -->
  <div class="bg-dots-right"></div>
</div>

<!-- ══ Login card ══ -->
<div class="login-wrapper">
  <div class="login-card">

    <div class="card-header-custom">
      <div class="escudo-wrapper"><i class="fas fa-seedling"></i></div>
      <h1>Sistema SAG<br>Honduras Sin Hambre</h1>
      <p class="subt">Secretaría de Agricultura y Ganadería</p>
      <div class="programa-badge">
        <i class="fas fa-shield-halved"></i> Sistema de Gestión de Programas Agrícolas
      </div>
    </div>

    <div class="card-body-custom">

      <?php if ($timeout): ?>
      <div class="alerta alerta-error">
        <i class="fas fa-shield-halved icon-l"></i>
        <span><strong>Su sesión ha expirado por inactividad.</strong> Inicie sesión nuevamente.</span>
      </div>
      <?php endif; ?>

      <div class="alerta alerta-info">
        <i class="fas fa-shield-halved icon-l"></i>
        <span><strong>Acceso restringido.</strong> Sistema de uso exclusivo para funcionarios autorizados de la SAG.</span>
      </div>

      <div id="errorLogin" style="display:none;" class="alerta alerta-error">
        <i class="fas fa-circle-xmark icon-l"></i>
        <span id="errorMsg"></span>
      </div>

      <form id="loginForm" novalidate>
        <div class="input-group-icon">
          <label class="form-label" for="usuario">Usuario o Correo</label>
          <i class="fas fa-user icon-left"></i>
          <input type="text" class="form-control" id="usuario" name="usuario"
                 placeholder="usuario o correo" autocomplete="username" required/>
        </div>
        <div class="input-group-icon">
          <label class="form-label" for="password">Contraseña</label>
          <i class="fas fa-unlock-keyhole icon-left"></i>
          <input type="password" class="form-control" id="password" name="password"
                 placeholder="Digitar contraseña" autocomplete="current-password" required/>
          <i class="fas fa-eye icon-right" id="togglePwd" title="Mostrar/ocultar"></i>
        </div>
        <button type="submit" class="btn-login" id="btnLogin">
          <span class="spinner-border spinner-border-sm" role="status"></span>
          <i class="fas fa-right-to-bracket icon-login"></i>
          Iniciar Sesión
        </button>
      </form>
    </div>
  </div>
</div>

<!-- ══ Footer ══ -->
<footer>
  <p>
    <i class="fas fa-leaf leaf"></i>
    Secretaría de Agricultura y Ganadería
    <span class="pipe">|</span> Honduras Sin Hambre
    <span class="pipe">|</span> &copy; 2026
  </p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const BASE_URL = '<?= BASE_URL ?>';

// Toggle contraseña
document.getElementById('togglePwd').addEventListener('click', function () {
  const pwd = document.getElementById('password');
  const visible = pwd.type === 'text';
  pwd.type = visible ? 'password' : 'text';
  this.className = visible ? 'fas fa-eye icon-right' : 'fas fa-eye-slash icon-right';
});

// Submit
document.getElementById('loginForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const btn = document.getElementById('btnLogin');
  const err = document.getElementById('errorLogin');
  err.style.display = 'none';
  btn.classList.add('loading'); btn.disabled = true;

  fetch(BASE_URL + '/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
    body: new URLSearchParams(new FormData(this))
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      window.location.href = res.data.redirect;
    } else {
      document.getElementById('errorMsg').textContent = res.message;
      err.style.display = 'flex';
      btn.classList.remove('loading'); btn.disabled = false;
    }
  })
  .catch(() => {
    document.getElementById('errorMsg').textContent = 'Error de conexión. Intente nuevamente.';
    err.style.display = 'flex';
    btn.classList.remove('loading'); btn.disabled = false;
  });
});
</script>
</body>
</html>
