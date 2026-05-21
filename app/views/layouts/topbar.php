<?php
$user      = $_SESSION['user']    ?? [];
$prog      = $_SESSION['programa'] ?? [];
?>
<div id="main">
<?php
$progSigla = $prog['sigla'] ?? '';
$progColor = $prog['color'] ?? '#54668E';
$progIco   = $prog['icono'] ?? 'fa-seedling';
$initials  = $user['initials'] ?? '??';
$nombre    = trim(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? ''));
$rolNombre = $user['rol_nombre'] ?? '';
?>
<!-- ══ TOPBAR ══ -->
<div class="topbar">
  <button class="btn-toggle" id="sidebarToggle" title="Menú">
    <i class="fas fa-bars"></i>
  </button>

  <!-- Chip del programa activo -->
  <?php if ($progSigla): ?>
  <a href="<?= BASE_URL ?>/programas?cambiar=1" class="prog-chip" style="border-color:<?= htmlspecialchars($progColor) ?>40;background:<?= htmlspecialchars($progColor) ?>10;" title="Cambiar programa">
    <?= progIconHtml($prog) ?>
    <span style="color:<?= htmlspecialchars($progColor) ?>;font-weight:700;"><?= htmlspecialchars($progSigla) ?></span>
    <i class="fas fa-chevron-down" style="color:<?= htmlspecialchars($progColor) ?>;opacity:.6;font-size:.6rem;"></i>
  </a>
  <?php endif; ?>

  <!-- Separador -->
  <div style="flex:1;"></div>

  <!-- Derecha -->
  <div class="topbar-right">
    <!-- Notificaciones (placeholder) -->
    <button class="topbar-btn" title="Notificaciones">
      <i class="fas fa-bell" style="font-size:.9rem;"></i>
    </button>

    <!-- Chip de usuario -->
    <div class="user-chip" id="userChip">
      <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
      <div class="user-chip-info">
        <span class="uc-nombre"><?= htmlspecialchars($nombre) ?></span>
        <span class="uc-rol"><?= htmlspecialchars($rolNombre) ?></span>
      </div>
      <i class="fas fa-chevron-down" style="font-size:.6rem;color:#aaa;margin-left:2px;"></i>
    </div>

    <!-- Dropdown usuario -->
    <div class="user-dropdown" id="userDropdown">
      <div class="ud-header">
        <div class="ud-avatar"><?= htmlspecialchars($initials) ?></div>
        <div>
          <div style="font-weight:700;font-size:.85rem;"><?= htmlspecialchars($nombre) ?></div>
          <div style="font-size:.75rem;color:#888;"><?= htmlspecialchars($user['email'] ?? '') ?></div>
          <div style="font-size:.72rem;color:var(--primario);font-weight:600;margin-top:2px;"><?= htmlspecialchars($rolNombre) ?></div>
        </div>
      </div>
      <div class="ud-divider"></div>
      <a href="<?= BASE_URL ?>/programas?cambiar=1" class="ud-item">
        <i class="fas fa-layer-group"></i> Cambiar Programa
      </a>
      <div class="ud-divider"></div>
      <a href="<?= BASE_URL ?>/auth/logout" class="ud-item ud-logout">
        <i class="fas fa-right-from-bracket"></i> Cerrar Sesión
      </a>
    </div>
  </div>
</div>
