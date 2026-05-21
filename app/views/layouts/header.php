<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="base-url" content="<?= BASE_URL ?>"/>
  <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>"/>
  <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>

  <!-- Bootstrap 5.3 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
  <!-- Font Awesome 6.4 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <!-- Tipografía moderna: Inter (UI) + Poppins (display) -->
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@500;600;700;800&display=swap"/>
  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css"/>
  <!-- Select2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"/>
  <!-- Leaflet (solo en páginas que la necesiten) -->
  <?php if (!empty($usaLeaflet)): ?>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <?php endif; ?>

  <!-- CSS principal -->
  <link rel="stylesheet" href="<?= asset('public/assets/css/main.css') ?>"/>

  <?php if (!empty($cssExtra)): ?>
  <?= $cssExtra ?>
  <?php endif; ?>
</head>
<?php
  $_themeId = strtolower($_SESSION['programa']['id'] ?? '');
  $_themeClass = in_array($_themeId, ['pipc','pipg','pipa'], true) ? "theme-{$_themeId}" : '';
?>
<body class="<?= htmlspecialchars($_themeClass) ?>">
