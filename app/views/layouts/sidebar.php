<?php
$prog      = $_SESSION['programa'] ?? [];
$progId    = $prog['id']    ?? '';
$progSigla = $prog['sigla'] ?? '';
$progColor = $prog['color'] ?? '#54668E';
$progIco   = $prog['icono'] ?? 'fa-seedling';
Permisos::init();
$puedeModulo = static fn(string $modulo): bool => Permisos::puedeEn($modulo, ACC_VER);

// Detectar página activa
$uri = (string)($_SERVER['REQUEST_URI'] ?? '');
function navActive(string $segment): string {
    global $uri;
    return (strpos((string)$uri, $segment) !== false) ? 'active' : '';
}
function navGroupOpen(array $segments): string {
    global $uri;
    foreach ($segments as $segment) {
        if (strpos((string)$uri, $segment) !== false) {
            return 'open';
        }
    }
    return '';
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

  <!-- Navegación (zona scrolleable; marca, badge y footer quedan fijos) -->
  <div class="sidebar-nav">

  <!-- Menú principal — varía según programa activo -->
  <div class="nav-section-label">Menú Principal</div>

  <?php if ($puedeModulo('dashboard')): ?>
  <a class="nav-item-s <?= navActive('/dashboard') ?>" href="<?= BASE_URL ?>/dashboard">
    <i class="fas fa-house"></i>
    <span class="nav-label">Dashboard</span>
  </a>
  <?php endif; ?>

  <?php if ($progId === 'fprog'): ?>
  <?php if ($puedeModulo('fprog')): ?>
  <!-- ── FPROG 2026: Fortalecimiento de Programas y Proyectos SAG ── -->
  <a class="nav-item-s <?= navActive('/componentes_fp') ?>" href="<?= BASE_URL ?>/componentes_fp">
    <i class="fas fa-layer-group"></i>
    <span class="nav-label">Componentes</span>
  </a>
  <a class="nav-item-s <?= navActive('/metas') ?>" href="<?= BASE_URL ?>/metas">
    <i class="fas fa-bullseye"></i>
    <span class="nav-label">Metas</span>
  </a>
  <a class="nav-item-s <?= navActive('/indicadores') ?>" href="<?= BASE_URL ?>/indicadores">
    <i class="fas fa-gauge-high"></i>
    <span class="nav-label">Indicadores</span>
  </a>
  <a class="nav-item-s <?= navActive('/cronograma_fp') ?>" href="<?= BASE_URL ?>/cronograma_fp">
    <i class="fas fa-calendar-days"></i>
    <span class="nav-label">Cronograma</span>
  </a>
  <a class="nav-item-s <?= navActive('/equipo_fp') ?>" href="<?= BASE_URL ?>/equipo_fp">
    <i class="fas fa-users-gear"></i>
    <span class="nav-label">Equipo Técnico</span>
  </a>
  <a class="nav-item-s <?= navActive('/fortalecimiento') ?>" href="<?= BASE_URL ?>/fortalecimiento">
    <i class="fas fa-chart-line"></i>
    <span class="nav-label">Acciones de Fortalecimiento</span>
  </a>
  <?php endif; ?>

  <?php else: ?>
  <!-- ── PIPs: Producción (PIPC / PIPG / PIPA) ── -->
  <?php if ($puedeModulo('organizaciones')): ?>
  <a class="nav-item-s <?= navActive('/organizaciones') ?>" href="<?= BASE_URL ?>/organizaciones">
    <i class="fas fa-building-wheat"></i>
    <span class="nav-label">Organizaciones</span>
  </a>
  <?php endif; ?>
  <?php if ($puedeModulo('beneficiarios')): ?>
  <a class="nav-item-s <?= navActive('/beneficiarios') ?>" href="<?= BASE_URL ?>/beneficiarios">
    <i class="fas fa-users"></i>
    <span class="nav-label">Productores</span>
  </a>
  <?php endif; ?>
  <?php if ($puedeModulo('asistencia')): ?>
  <a class="nav-item-s <?= navActive('/asistencia') ?>" href="<?= BASE_URL ?>/asistencia">
    <i class="fas fa-person-chalkboard"></i>
    <span class="nav-label">Asistencia Técnica</span>
  </a>
  <?php endif; ?>
  <?php if ($puedeModulo('capacitaciones')): ?>
  <a class="nav-item-s <?= navActive('/capacitaciones') ?>" href="<?= BASE_URL ?>/capacitaciones">
    <i class="fas fa-graduation-cap"></i>
    <span class="nav-label">Capacitaciones</span>
  </a>
  <?php endif; ?>

  <!-- Entregas e Inventarios (solo PIPs) -->
  <?php if ($puedeModulo('entregas') || $puedeModulo('inventarios') || $puedeModulo('movilizaciones')): ?>
  <?php $eiRutas = ['/entregas', '/inventarios', '/movilizaciones']; ?>
  <div class="nav-group <?= navGroupOpen($eiRutas) ?>" data-nav-group="entregas-inventarios">
    <button class="nav-group-toggle" type="button" aria-expanded="<?= navGroupOpen($eiRutas) ? 'true' : 'false' ?>">
      <span>Entregas e Inventarios</span>
      <i class="fas fa-chevron-down" aria-hidden="true"></i>
    </button>
    <div class="nav-group-items">
      <?php if ($puedeModulo('entregas')): ?>
      <a class="nav-item-s <?= navActive('/entregas') ?>" href="<?= BASE_URL ?>/entregas">
        <i class="fas fa-truck-ramp-box"></i>
        <span class="nav-label">Entregas de Incentivos</span>
        <?php $alertCount = (int)($_SESSION['entregas_alertas_count'] ?? 0); ?>
        <?php if ($alertCount > 0): ?>
          <span class="nav-badge-alert" title="<?= $alertCount ?> alerta(s) requieren revisión"><?= $alertCount ?></span>
        <?php endif; ?>
      </a>
      <?php endif; ?>
      <?php if ($puedeModulo('inventarios')): ?>
      <a class="nav-item-s <?= navActive('/inventarios') ?>" href="<?= BASE_URL ?>/inventarios">
        <i class="fas fa-warehouse"></i>
        <span class="nav-label">Inventarios de Incentivos</span>
      </a>
      <?php endif; ?>
      <?php if ($puedeModulo('movilizaciones')): ?>
      <a class="nav-item-s <?= navActive('/movilizaciones') ?>" href="<?= BASE_URL ?>/movilizaciones">
        <i class="fas fa-arrows-turn-to-dots"></i>
        <span class="nav-label">Movilizaciones OIRSA</span>
      </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <!-- Reportes -->
  <?php if ($puedeModulo('estadisticas') || $puedeModulo('exportar')): ?>
  <div class="nav-group <?= navGroupOpen(['/estadisticas', '/exportar']) ?>" data-nav-group="reportes">
    <button class="nav-group-toggle" type="button" aria-expanded="<?= navGroupOpen(['/estadisticas', '/exportar']) ? 'true' : 'false' ?>">
      <span>Reportes</span>
      <i class="fas fa-chevron-down" aria-hidden="true"></i>
    </button>
    <div class="nav-group-items">
      <?php if ($puedeModulo('estadisticas')): ?>
      <a class="nav-item-s <?= navActive('/estadisticas') ?>" href="<?= BASE_URL ?>/estadisticas">
        <i class="fas fa-chart-bar"></i>
        <span class="nav-label">Estadísticas</span>
      </a>
      <?php endif; ?>
      <?php if ($puedeModulo('exportar')): ?>
      <a class="nav-item-s <?= navActive('/exportar') ?>" href="<?= BASE_URL ?>/exportar">
        <i class="fas fa-file-export"></i>
        <span class="nav-label">Exportar Datos</span>
      </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Administración Financiera -->
  <?php endif; ?>
  <?php if ($puedeModulo('presupuesto') || $puedeModulo('flota')): ?>
  <div class="nav-group <?= navGroupOpen(['/presupuesto', '/flota']) ?>" data-nav-group="administracion">
    <button class="nav-group-toggle" type="button" aria-expanded="<?= navGroupOpen(['/presupuesto', '/flota']) ? 'true' : 'false' ?>">
      <span>Administración</span>
      <i class="fas fa-chevron-down" aria-hidden="true"></i>
    </button>
    <div class="nav-group-items">
      <?php if ($puedeModulo('presupuesto')): ?>
      <a class="nav-item-s <?= navActive('/presupuesto') ?>" href="<?= BASE_URL ?>/presupuesto">
        <i class="fas fa-scale-balanced"></i>
        <span class="nav-label">Ejecución Presupuestaria</span>
      </a>
      <?php endif; ?>
      <?php if ($puedeModulo('flota')): ?>
      <a class="nav-item-s <?= navActive('/flota') ?>" href="<?= BASE_URL ?>/flota">
        <i class="fas fa-car"></i>
        <span class="nav-label">Flota Vehicular</span>
      </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Parametrización y Sistema (solo roles con acceso a catálogos — ver MantenimientoController::ROLES_CATALOGOS) -->
  <?php endif; ?>
  <?php $rolSlug = $_SESSION['user']['rol_slug'] ?? '';
        if ($puedeModulo('catalogos') || $puedeModulo('mantenimiento') || $puedeModulo('auditoria')): ?>
  <?php if ($puedeModulo('catalogos')): ?>
  <?php $catRutas = ['/catalogos/tecnicos', '/catalogos/temas', '/catalogos/cultivos', '/catalogos/tiposat',
                     '/catalogos/proveedores', '/catalogos/productos', '/catalogos/bodegas']; ?>
  <div class="nav-group <?= navGroupOpen($catRutas) ?>" data-nav-group="parametrizacion">
    <button class="nav-group-toggle" type="button" aria-expanded="<?= navGroupOpen($catRutas) ? 'true' : 'false' ?>">
      <span>Parametrización</span>
      <i class="fas fa-chevron-down" aria-hidden="true"></i>
    </button>
    <div class="nav-group-items">
      <a class="nav-item-s <?= navActive('/catalogos/tecnicos') ?>" href="<?= BASE_URL ?>/catalogos/tecnicos">
        <i class="fas fa-user-tie"></i>
        <span class="nav-label">Técnicos</span>
      </a>
      <a class="nav-item-s <?= navActive('/catalogos/temas') ?>" href="<?= BASE_URL ?>/catalogos/temas">
        <i class="fas fa-tags"></i>
        <span class="nav-label">Temas y Subtemas</span>
      </a>
      <a class="nav-item-s <?= navActive('/catalogos/cultivos') ?>" href="<?= BASE_URL ?>/catalogos/cultivos">
        <i class="fas fa-seedling"></i>
        <span class="nav-label">Cultivos y Rubros</span>
      </a>
      <a class="nav-item-s <?= navActive('/catalogos/tiposat') ?>" href="<?= BASE_URL ?>/catalogos/tiposat">
        <i class="fas fa-list-check"></i>
        <span class="nav-label">Tipos de Asistencia</span>
      </a>
      <!-- Catálogos de inventario (proveedores, productos, bodegas) -->
      <a class="nav-item-s <?= navActive('/catalogos/proveedores') ?>" href="<?= BASE_URL ?>/catalogos/proveedores">
        <i class="fas fa-truck"></i>
        <span class="nav-label">Proveedores</span>
      </a>
      <a class="nav-item-s <?= navActive('/catalogos/productos') ?>" href="<?= BASE_URL ?>/catalogos/productos">
        <i class="fas fa-boxes-stacked"></i>
        <span class="nav-label">Productos</span>
      </a>
      <a class="nav-item-s <?= navActive('/catalogos/bodegas') ?>" href="<?= BASE_URL ?>/catalogos/bodegas">
        <i class="fas fa-warehouse"></i>
        <span class="nav-label">Bodegas</span>
      </a>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($puedeModulo('mantenimiento') || $puedeModulo('auditoria')): ?>
  <div class="nav-group <?= navGroupOpen(['/mantenimiento', '/auditoria']) ?>" data-nav-group="sistema">
    <button class="nav-group-toggle" type="button" aria-expanded="<?= navGroupOpen(['/mantenimiento', '/auditoria']) ? 'true' : 'false' ?>">
      <span>Sistema</span>
      <i class="fas fa-chevron-down" aria-hidden="true"></i>
    </button>
    <div class="nav-group-items">
      <?php if ($puedeModulo('mantenimiento')): ?>
      <a class="nav-item-s <?= navActive('/mantenimiento') ?>" href="<?= BASE_URL ?>/mantenimiento">
        <i class="fas fa-gears"></i>
        <span class="nav-label">Mantenimiento</span>
      </a>
      <?php endif; ?>
      <?php if ($puedeModulo('auditoria')): ?>
      <a class="nav-item-s <?= navActive('/auditoria') ?>" href="<?= BASE_URL ?>/auditoria">
        <i class="fas fa-clipboard-list"></i>
        <span class="nav-label">Auditoría</span>
      </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  </div><!-- /sidebar-nav -->

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
