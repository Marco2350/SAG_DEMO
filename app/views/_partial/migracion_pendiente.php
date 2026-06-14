<?php
/**
 * _partial/migracion_pendiente.php
 *
 * Vista compartida para mostrar al usuario que un módulo aún no fue
 * inicializado en la base de datos. Reemplaza el stack trace de PDO
 * por un mensaje claro con los pasos a seguir.
 *
 * Variables esperadas:
 *   $tituloModulo  string  — Ej. "Componentes"
 *   $tablasFalta   array   — Ej. ['sag_fp_componentes']
 *   $migraciones   array   — Ej. ['migracion_013_fp_componentes.sql']
 *   $pageTitle     string  (opcional)
 */
$tituloModulo = $tituloModulo ?? 'Módulo';
$tablasFalta  = $tablasFalta  ?? [];
$migraciones  = $migraciones  ?? [];
?>
<?php require ROOT_PATH . '/app/views/layouts/header.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/sidebar.php'; ?>
<?php require ROOT_PATH . '/app/views/layouts/topbar.php'; ?>

<div class="content">
  <div class="crumb-bar">
    <i class="fas fa-house"></i>
    <span>Inicio</span>
    <i class="fas fa-chevron-right crumb-sep"></i>
    <span class="crumb-pill">
      <i class="fas fa-database"></i>
      <?= htmlspecialchars($tituloModulo) ?> — pendiente de inicializar
    </span>
  </div>

  <div class="card-box" style="margin-top:24px;">
    <div class="card-box-header" style="background:#fef3c7;border-left:5px solid #f59e0b;">
      <h5 style="margin:0;color:#92400e;">
        <i class="fas fa-triangle-exclamation"></i>
        Este módulo aún no fue inicializado en la base de datos
      </h5>
    </div>
    <div class="card-box-body" style="padding:24px;">
      <p style="font-size:1rem;color:#374151;">
        El módulo <strong><?= htmlspecialchars($tituloModulo) ?></strong>
        requiere tablas que aún no existen en <code>mddesarr_sag</code>.
        Esto suele pasar cuando una migración SQL no fue aplicada.
      </p>

      <?php if (!empty($tablasFalta)): ?>
      <div style="margin:18px 0;">
        <div style="font-size:.85rem;color:#6b7280;text-transform:uppercase;font-weight:600;">
          Tabla(s) faltante(s):
        </div>
        <ul style="margin:8px 0 0 0;padding-left:20px;">
          <?php foreach ($tablasFalta as $t): ?>
            <li><code><?= htmlspecialchars($t) ?></code></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if (!empty($migraciones)): ?>
      <div style="background:#f3f4f6;border-radius:6px;padding:16px;margin-top:18px;">
        <div style="font-size:.85rem;color:#6b7280;text-transform:uppercase;font-weight:600;margin-bottom:8px;">
          Cómo solucionar
        </div>
        <ol style="margin:0;padding-left:20px;line-height:1.8;">
          <li>Abrir <strong>phpMyAdmin</strong> (<code>http://localhost/phpmyadmin</code>).</li>
          <li>Seleccionar la base <code>mddesarr_sag</code>.</li>
          <li>Ir a la pestaña <strong>SQL</strong>.</li>
          <li>Pegar y ejecutar el contenido de los siguientes archivos, en este orden:
            <ul style="margin:8px 0 0 0;">
              <?php foreach ($migraciones as $m): ?>
                <li><code>sql/<?= htmlspecialchars($m) ?></code></li>
              <?php endforeach; ?>
            </ul>
          </li>
          <li>Recargar esta página.</li>
        </ol>
      </div>
      <?php endif; ?>

      <div style="margin-top:18px;color:#6b7280;font-size:.9rem;">
        <i class="fas fa-circle-info"></i>
        Si las migraciones ya se aplicaron y este mensaje persiste, verifique
        que la base de datos activa sea efectivamente <code>mddesarr_sag</code>
        y avise al administrador del sistema.
      </div>
    </div>
  </div>
</div>

<?php require ROOT_PATH . '/app/views/layouts/footer.php'; ?>
