  <!-- ══ PAGE FOOTER ══ -->
  <div class="page-footer">
    <span>
      <i class="fas fa-wheat-awn" style="color:var(--dorado2);margin-right:4px;"></i>
      Sistema SAG Honduras Sin Hambre &nbsp;·&nbsp; Secretaría de Agricultura y Ganadería
    </span>
    <span>
      © 2026 &nbsp;·&nbsp;
      <?php if (!empty($_SESSION['programa']['sigla'])): ?>
        Programa: <strong><?= htmlspecialchars($_SESSION['programa']['sigla']) ?></strong> &nbsp;·&nbsp;
      <?php endif; ?>
      <?= htmlspecialchars(($_SESSION['user']['nombre'] ?? '') . ' ' . ($_SESSION['user']['apellido'] ?? '')) ?>
    </span>
  </div>
</div><!-- /main -->

<!-- ══ JS ══ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<?php if (!empty($usaLeaflet)): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<?php endif; ?>
<?php if (!empty($usaChartJs)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php endif; ?>

<!-- Main JS -->
<script src="<?= BASE_URL ?>/public/assets/js/main.js"></script>

<?php if (!empty($jsExtra)): ?>
<?= $jsExtra ?>
<?php endif; ?>

<script>
// ── SIDEBAR TOGGLE ──
const sidebar   = document.getElementById('sidebar');
const mainWrap  = document.getElementById('main');
const toggleBtn = document.getElementById('sidebarToggle');

// Restaurar estado guardado
if (localStorage.getItem('sag_sidebar') === 'collapsed') {
  sidebar.classList.add('collapsed');
}

toggleBtn.addEventListener('click', () => {
  sidebar.classList.toggle('collapsed');
  localStorage.setItem('sag_sidebar', sidebar.classList.contains('collapsed') ? 'collapsed' : 'open');
});

// ── USER DROPDOWN ──
const userChip     = document.getElementById('userChip');
const userDropdown = document.getElementById('userDropdown');
if (userChip && userDropdown) {
  userChip.addEventListener('click', (e) => {
    e.stopPropagation();
    userDropdown.classList.toggle('show');
  });
  document.addEventListener('click', () => {
    userDropdown?.classList.remove('show');
  });
}

// ── BORRADORES AUTO ─────────────────────────────────────────
// Cualquier <form data-sag-autosave="clave"> activa autosave y restauración.
// Se ejecuta cuando el modal contenedor (si lo hay) se abre.
$(function () {
  function attachForm($form) {
    const key = $form.data('sag-autosave');
    if (!key) return;
    if ($form.data('sag-autosave-attached')) return;
    $form.data('sag-autosave-attached', true);

    SAG.autosaveAttach($form, key);

    // Restaurar al abrir (solo si el form está visible / modal abierto)
    const restaurado = SAG.autosaveRestore($form, key, function (antigMs) {
      // Sólo restaurar si tiene menos de 24h
      return antigMs < (24 * 60 * 60 * 1000);
    });
    if (restaurado) {
      const $banner = $('<div class="sag-draft-banner" style="background:#fef3c7;color:#854d0e;border-left:4px solid #ca8a04;padding:6px 10px;margin-bottom:10px;font-size:.78rem;border-radius:6px;"><i class="fas fa-clock-rotate-left me-1"></i>Borrador anterior restaurado. <a href="#" class="sag-clear-draft" style="font-weight:700;color:#7c2d12;">Descartar</a></div>');
      $form.prepend($banner);
      $banner.on('click', '.sag-clear-draft', function (e) {
        e.preventDefault();
        SAG.autosaveClear(key);
        $form[0].reset();
        $banner.remove();
      });
    }

    // Limpiar al hacer submit exitoso (cuando se envía el form vía SAG.ajax)
    $form.on('submit', function () { SAG.autosaveClear(key); });
  }

  // Forms ya visibles
  $('form[data-sag-autosave]').each(function () { attachForm($(this)); });

  // Forms dentro de modales (se inician cuando el modal abre)
  $(document).on('shown.bs.modal', '.modal', function () {
    $(this).find('form[data-sag-autosave]').each(function () { attachForm($(this)); });
  });
});

// ── SELECT2 GLOBAL ─────────────────────────────────────────
// Activa búsqueda en selects largos. Patrón SAG:
//   - <select class="form-select sag-search"> → con search
//   - <select class="form-select"> dentro de modal → con search si >=10 options
// Para forzar exclusión, agregar class="form-select sag-no-search".
$(function () {
  if (typeof $.fn.select2 === 'undefined') return;

  function aplicarSelect2($sel) {
    if ($sel.hasClass('sag-no-search')) return;
    if ($sel.data('select2')) return; // ya activado
    $sel.select2({
      theme:        'bootstrap-5',
      width:        '100%',
      dropdownParent: $sel.closest('.modal').length ? $sel.closest('.modal') : $(document.body),
      placeholder:  $sel.attr('placeholder') || $sel.find('option:first').text() || 'Buscar...',
      allowClear:   false,
      minimumResultsForSearch: 10,
      language: {
        noResults:        () => 'Sin resultados',
        searching:        () => 'Buscando…',
        inputTooShort:    () => '',
      }
    });
  }

  // Aplicar a selects marcados explícitamente
  $('select.form-select.sag-search').each(function () { aplicarSelect2($(this)); });

  // Aplicar al abrir un modal (selects dentro de cualquier modal)
  $(document).on('shown.bs.modal', '.modal', function () {
    $(this).find('select.form-select').each(function () { aplicarSelect2($(this)); });
  });
});
</script>

</body>
</html>
