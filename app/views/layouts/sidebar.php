<?php
$prog      = $_SESSION['programa'] ?? [];
$progId    = $prog['id']    ?? '';
$progSigla = $prog['sigla'] ?? '';
$progColor = $prog['color'] ?? '#54668E';
$progIco   = $prog['icono'] ?? 'fa-seedling';

// Detectar página activa
$uri = $_SERVER['REQUEST_URI'] ?? '';
function navActive(string $segment): string {
    global $uri;
    return (strpos($uri, $segment) !== false) ? 'active' : '';
}
?>
<!-- ══ SIDEBAR ══ -->
<nav id="sidebar">
  <!-- Brand -->
  <a class="sidebar-brand" href="<?= BASE_URL ?>/dashboard">
    <div class="icon"><i class="fas fa-seedling"></i></div>
    <div class="brand-name">SAG Honduras<br>Sin Hambre</div>
  </a>

  <!-- Badge del programa activo -->
  <?php if ($progId): ?>
  <div class="sidebar-programa">
    <div class="sp-badge" style="border-color:<?= htmlspecialchars($progColor) ?>20;background:<?= htmlspecialchars($progColor) ?>15;">
      <?= progIconHtml($prog, 'font-size:.85rem;') ?>
      <span class="sp-text" style="color:<?= htmlspecialchars($progColor) ?>;"><?= htmlspecialchars($progSigla) ?></span>
      <a href="<?= BASE_URL ?>/programas/salir" class="sp-cambiar" title="Cambiar programa">
        <i class="fas fa-arrows-rotate" style="color:<?= htmlspecialchars($progColor) ?>;opacity:.7;"></i>
      </a>
    </div>
  </div>
  <?php endif; ?>

  <!-- Menú principal -->
  <div class="nav-section-label">Menú Principal</div>

  <a class="nav-item-s <?= navActive('/dashboard') ?>" href="<?= BASE_URL ?>/dashboard">
    <i class="fas fa-house"></i>
    <span class="nav-label">Dashboard</span>
  </a>
  <a class="nav-item-s <?= navActive('/organizaciones') ?>" href="<?= BASE_URL ?>/organizaciones">
    <i class="fas fa-building-wheat"></i>
    <span class="nav-label">Organizaciones</span>
  </a>
  <a class="nav-item-s <?= navActive('/beneficiarios') ?>" href="<?= BASE_URL ?>/beneficiarios">
    <i class="fas fa-users"></i>
    <span class="nav-label">Productores</span>
  </a>
  <a class="nav-item-s <?= navActive('/asistencia') ?>" href="<?= BASE_URL ?>/asistencia">
    <i class="fas fa-person-chalkboard"></i>
    <span class="nav-label">Asistencia Técnica</span>
  </a>
  <a class="nav-item-s <?= navActive('/capacitaciones') ?>" href="<?= BASE_URL ?>/capacitaciones">
    <i class="fas fa-graduation-cap"></i>
    <span class="nav-label">Capacitaciones</span>
  </a>

  <!-- Entregas e Inventarios -->
  <div class="nav-section-label">Entregas e Inventarios</div>
  <a class="nav-item-s <?= navActive('/entregas') ?>" href="<?= BASE_URL ?>/entregas">
    <i class="fas fa-truck-ramp-box"></i>
    <span class="nav-label">Entregas de Incentivos</span>
    <?php $alertCount = (int)($_SESSION['entregas_alertas_count'] ?? 0); ?>
    <?php if ($alertCount > 0): ?>
      <span class="nav-badge-alert" title="<?= $alertCount ?> alerta(s) requieren revisión"><?= $alertCount ?></span>
    <?php endif; ?>
  </a>
  <a class="nav-item-s <?= navActive('/inventarios') ?>" href="<?= BASE_URL ?>/inventarios">
    <i class="fas fa-warehouse"></i>
    <span class="nav-label">Inventarios de Incentivos</span>
  </a>

  <!-- Reportes -->
  <div class="nav-section-label">Reportes</div>
  <a class="nav-item-s <?= navActive('/estadisticas') ?>" href="<?= BASE_URL ?>/estadisticas">
    <i class="fas fa-chart-bar"></i>
    <span class="nav-label">Estadísticas</span>
  </a>
  <a class="nav-item-s <?= navActive('/exportar') ?>" href="<?= BASE_URL ?>/exportar">
    <i class="fas fa-file-export"></i>
    <span class="nav-label">Exportar Datos</span>
  </a>

  <!-- Administración Financiera -->
  <div class="nav-section-label">Administración</div>
  <a class="nav-item-s <?= navActive('/presupuesto') ?>" href="<?= BASE_URL ?>/presupuesto">
    <i class="fas fa-scale-balanced"></i>
    <span class="nav-label">Ejecución Presupuestaria</span>
  </a>

  <!-- Sistema -->
  <div class="nav-section-label">Sistema</div>
  <a class="nav-item-s <?= navActive('/mantenimiento') ?>" href="<?= BASE_URL ?>/mantenimiento">
    <i class="fas fa-gears"></i>
    <span class="nav-label">Mantenimiento</span>
  </a>

  <!-- Footer del sidebar -->
  <div class="sidebar-footer">
    <a href="<?= BASE_URL ?>/programas?cambiar=1" title="Cambiar programa">
      <i class="fas fa-layer-group"></i>
      <span>Cambiar Programa</span>
    </a>
    <a href="<?= BASE_URL ?>/auth/logout">
      <i class="fas fa-right-from-bracket"></i>
      <span>Cerrar Sesión</span>
    </a>
  </div>
</nav>
