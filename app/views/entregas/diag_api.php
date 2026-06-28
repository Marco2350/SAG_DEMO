<?php
$pageTitle = 'Diagnóstico API OIRSA · SAG_DEMO';
require ROOT_PATH . '/app/views/layouts/header.php';
require ROOT_PATH . '/app/views/layouts/sidebar.php';
require ROOT_PATH . '/app/views/layouts/topbar.php';

$progSigla = $_SESSION['programa']['sigla'] ?? '';
?>

<div class="content">

  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <div class="page-title">
        <i class="fas fa-stethoscope" style="color:#0d9488;"></i>
        Diagnóstico de conexión OIRSA
        <small><?= htmlspecialchars($progSigla) ?> &mdash; Verifica si la API responde correctamente</small>
      </div>
    </div>
    <div>
      <button class="btn-sync" id="btnEjecutarDiag" style="background:#0d9488;">
        <i class="fas fa-play"></i> Ejecutar diagnóstico
      </button>
      <a class="btn-sync" href="<?= BASE_URL ?>/entregas" style="background:#64748b;">
        <i class="fas fa-arrow-left"></i> Volver
      </a>
    </div>
  </div>

  <div class="info-banner" style="background:linear-gradient(90deg,#dbeafe,#eff6ff);border-color:#3b82f6;color:#1e40af;">
    <i class="fas fa-info-circle"></i>
    <div>
      Esta página prueba la conexión con OIRSA Trazaragro en 4 pasos: configuración, alcanzabilidad del servidor, autenticación OAuth2 y consulta OData de prueba. Útil cuando un sync no responde — te dice exactamente dónde está el problema.
    </div>
  </div>

  <!-- Resultados -->
  <div id="diagResultados" style="margin-top:18px;"></div>

  <!-- Loading inicial -->
  <div id="diagLoading" style="display:none; padding:60px 20px; text-align:center;">
    <div style="font-size:2.5rem; color:#0d9488; margin-bottom:14px;"><i class="fas fa-spinner fa-spin"></i></div>
    <div style="font-size:1rem; color:#475569;">Probando conexión con OIRSA…</div>
    <div style="font-size:.82rem; color:#94a3b8; margin-top:8px;">Esto tarda 5-15 segundos.</div>
  </div>

  <!-- Detalles técnicos colapsables -->
  <div id="diagDetalles" style="display:none; margin-top:18px;">
    <details style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px 16px;">
      <summary style="cursor:pointer; font-weight:700; font-size:.9rem; color:#1e293b;">
        <i class="fas fa-cog me-2"></i> Configuración actual (lectura desde .env)
      </summary>
      <pre id="diagConfig" style="font-size:.78rem; margin-top:10px; background:#fff; padding:10px; border-radius:6px; overflow-x:auto;"></pre>
    </details>
  </div>

</div>

<style>
  .paso-card {
    background:#fff; border:1.5px solid #e2e8f0; border-radius:12px;
    padding:14px 18px; margin-bottom:10px;
    display:grid; grid-template-columns:auto 1fr auto; gap:16px; align-items:center;
    transition: all .15s;
  }
  .paso-card.ok { border-left:4px solid #16a34a; background:#f0fdf4; }
  .paso-card.fail { border-left:4px solid #dc2626; background:#fef2f2; }
  .paso-card.pending { border-left:4px solid #cbd5e1; }
  .paso-icon { font-size:1.5rem; width:42px; text-align:center; }
  .paso-icon.ok { color:#16a34a; }
  .paso-icon.fail { color:#dc2626; }
  .paso-icon.pending { color:#94a3b8; }
  .paso-titulo { font-weight:700; font-size:.95rem; color:#1a1a1a; }
  .paso-detalle { font-size:.82rem; color:#475569; margin-top:3px; word-break:break-word; }
  .paso-tiempo { font-size:.78rem; color:#64748b; white-space:nowrap; }
  .paso-tiempo strong { color:#0d9488; font-size:1.05rem; }

  .summary-banner {
    padding:16px 20px; border-radius:12px; margin-bottom:14px;
    display:flex; align-items:center; gap:14px; font-size:.95rem;
  }
  .summary-banner.ok { background:#dcfce7; color:#166534; border:2px solid #16a34a; }
  .summary-banner.fail { background:#fee2e2; color:#991b1b; border:2px solid #dc2626; }
  .summary-banner i { font-size:1.8rem; }
</style>

<script>
$(function () {

  function renderResultado(data) {
    const $r = $('#diagResultados').empty();
    const $c = $('#diagConfig');
    const $d = $('#diagDetalles');

    // Banner resumen
    const todoOk = !!data.todo_ok;
    const banner = todoOk
      ? `<div class="summary-banner ok">
           <i class="fas fa-circle-check"></i>
           <div>
             <strong>Conexión correcta.</strong> OIRSA responde bien. Podés sincronizar normalmente.
           </div>
         </div>`
      : `<div class="summary-banner fail">
           <i class="fas fa-circle-exclamation"></i>
           <div>
             <strong>Hay problemas de conexión.</strong> Revisá el paso que falló — el detalle te dice qué arreglar.
           </div>
         </div>`;
    $r.append(banner);

    // Pasos
    (data.pasos || []).forEach(p => {
      const cls = p.ok ? 'ok' : 'fail';
      const icon = p.ok ? 'fa-circle-check' : 'fa-circle-xmark';
      $r.append(`
        <div class="paso-card ${cls}">
          <div class="paso-icon ${cls}"><i class="fas ${icon}"></i></div>
          <div>
            <div class="paso-titulo">${SAG.esc(p.paso)}</div>
            <div class="paso-detalle">${SAG.esc(p.detalle || '')}</div>
          </div>
          <div class="paso-tiempo"><strong>${p.tiempo}</strong> ms</div>
        </div>
      `);
    });

    // Config técnica
    $c.text(JSON.stringify(data.config || {}, null, 2));
    $d.show();
  }

  // Helper de escape si SAG.esc no existe
  if (!window.SAG) window.SAG = {};
  if (!window.SAG.esc) {
    window.SAG.esc = function(s) {
      if (s === null || s === undefined) return '';
      return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    };
  }

  function ejecutar() {
    $('#diagResultados').empty();
    $('#diagDetalles').hide();
    $('#diagLoading').show();
    $('#btnEjecutarDiag').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Probando...');
    SAG.ajax({
      url: '/entregas/probarApi',
      type: 'GET',
      success: r => {
        $('#diagLoading').hide();
        $('#btnEjecutarDiag').prop('disabled', false).html('<i class="fas fa-rotate"></i> Volver a probar');
        if (!r.success || !r.data) {
          SAG.toast(r.message || 'Error inesperado', 'error');
          return;
        }
        renderResultado(r.data);
      },
      error: () => {
        $('#diagLoading').hide();
        $('#btnEjecutarDiag').prop('disabled', false).html('<i class="fas fa-rotate"></i> Volver a probar');
        SAG.toast('No se pudo ejecutar el diagnóstico (¿servidor caído?)', 'error');
      }
    });
  }

  $('#btnEjecutarDiag').on('click', ejecutar);

  // Auto-ejecutar al cargar la página
  ejecutar();
});
</script>

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>
