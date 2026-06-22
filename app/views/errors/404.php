<?php
/**
 * Vista 404 — página no encontrada.
 * Standalone (no depende del layout principal). Usa BASE_URL para volver al inicio.
 */
$inicio = (defined('BASE_URL') ? BASE_URL : '') . '/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>404 — Página no encontrada</title>
  <style>
    :root { --verde-deep:#0B5D3D; --verde-mid:#16A34A; --crema:#F4F1EA; }
    * { box-sizing:border-box; margin:0; padding:0; }
    body {
      font-family:'Segoe UI', sans-serif; background:var(--crema); color:#2F3A4D;
      min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px;
    }
    .card {
      background:#fff; border-radius:16px; padding:48px 40px; max-width:480px; width:100%;
      text-align:center; box-shadow:0 12px 40px rgba(11,93,61,.12);
    }
    .code { font-size:84px; font-weight:800; line-height:1;
      background:linear-gradient(180deg,var(--verde-mid),var(--verde-deep));
      -webkit-background-clip:text; background-clip:text; color:transparent; }
    h1 { font-size:22px; margin:12px 0 8px; color:var(--verde-deep); }
    p  { color:#5b6675; margin-bottom:28px; line-height:1.5; }
    a.btn {
      display:inline-block; text-decoration:none; padding:12px 28px; border-radius:10px;
      background:var(--verde-deep); color:#fff; font-weight:600; transition:background .2s;
    }
    a.btn:hover { background:var(--verde-mid); }
  </style>
</head>
<body>
  <div class="card">
    <div class="code">404</div>
    <h1>Página no encontrada</h1>
    <p>La dirección que buscas no existe o fue movida. Verifica el enlace o vuelve al inicio.</p>
    <a class="btn" href="<?= htmlspecialchars($inicio, ENT_QUOTES) ?>">Volver al inicio</a>
  </div>
</body>
</html>
