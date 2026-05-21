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
</script>

</body>
</html>
