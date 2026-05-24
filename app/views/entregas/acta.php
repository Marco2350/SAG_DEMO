<?php
/**
 * Acta de Recepción de Insumos — vista imprimible/PDF
 * Variables disponibles: $beneficiario, $porGuiasa, $movs, $programa, $usuario
 *
 * Para guardar como PDF: el usuario presiona Ctrl+P → "Guardar como PDF"
 */
$progSigla  = $programa['sigla'] ?? '';
$progNombre = $programa['nombre'] ?? '';
$progColor  = $programa['color']  ?? '#0B5D3D';
$fechaImpr  = date('d/m/Y H:i');
$totalItems = 0; $totalCantidad = 0;
foreach ($porGuiasa as $g) { $totalItems += $g['total_items']; $totalCantidad += $g['total_cantidad']; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Acta de Recepción — <?= htmlspecialchars($beneficiario['nombre']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    color: #1a1a1a; line-height: 1.45;
    background: #f5f5f0; padding: 20px 0;
  }
  .pagina {
    max-width: 800px; margin: 0 auto; background: #fff;
    box-shadow: 0 4px 18px rgba(0,0,0,.08);
    padding: 40px 50px; border-radius: 4px;
  }
  /* Encabezado */
  .head { display: flex; align-items: flex-start; justify-content: space-between;
          border-bottom: 3px solid <?= htmlspecialchars($progColor) ?>; padding-bottom: 14px; margin-bottom: 22px; }
  .head .left h1 { font-size: 1.2rem; color: <?= htmlspecialchars($progColor) ?>; margin: 0; }
  .head .left .sub { font-size: .82rem; color: #666; margin-top: 3px; }
  .head .left .sig { font-size: 1.5rem; font-weight: 800; color: <?= htmlspecialchars($progColor) ?>; }
  .head .right { text-align: right; font-size: .75rem; color: #555; }
  .head .right .doc { font-size: .68rem; color: #999; }
  /* Título del acta */
  h2.acta-titulo {
    font-size: 1.5rem; text-align: center; color: <?= htmlspecialchars($progColor) ?>;
    margin: 8px 0 4px;
    letter-spacing: 1px; text-transform: uppercase;
  }
  .acta-sub { text-align: center; font-size: .82rem; color: #666; margin-bottom: 24px; }
  /* Bloque beneficiario */
  .ben-box {
    background: #f8fafc; border-left: 4px solid <?= htmlspecialchars($progColor) ?>;
    padding: 14px 18px; margin-bottom: 22px; border-radius: 4px;
  }
  .ben-box .label { font-size: .65rem; color: #888; text-transform: uppercase; letter-spacing: .5px; font-weight: 700; }
  .ben-box .nombre { font-size: 1.1rem; font-weight: 700; color: #1a1a1a; margin-top: 2px; }
  .ben-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 18px; margin-top: 12px; font-size: .82rem; }
  .ben-grid .k { color: #666; font-weight: 600; }
  /* GUIASA card */
  .guiasa-card {
    border: 1.5px solid #e5e7eb; border-radius: 6px;
    margin-bottom: 18px; overflow: hidden;
    page-break-inside: avoid;
  }
  .guiasa-head {
    background: <?= htmlspecialchars($progColor) ?>; color: #fff;
    padding: 10px 16px; display: flex; justify-content: space-between; align-items: center;
    font-size: .8rem;
  }
  .guiasa-head .gtitle { font-weight: 700; font-size: .92rem; }
  .guiasa-head .gright { font-size: .72rem; opacity: .9; }
  .guiasa-meta {
    background: #fafbfc; padding: 8px 16px; font-size: .78rem; color: #555;
    border-bottom: 1.5px solid #e5e7eb;
    display: flex; flex-wrap: wrap; gap: 14px;
  }
  .guiasa-meta b { color: #1a1a1a; }
  /* Tabla de items */
  .items-tbl { width: 100%; border-collapse: collapse; font-size: .82rem; }
  .items-tbl th { background: #f1f5f9; color: #555; padding: 7px 10px; font-size: .7rem;
                  text-transform: uppercase; letter-spacing: .3px; text-align: left; border-bottom: 1.5px solid #cbd5e1; font-weight: 700; }
  .items-tbl td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
  .items-tbl tr:last-child td { border-bottom: none; }
  .items-tbl .num { text-align: right; font-weight: 600; }
  .items-tbl .center { text-align: center; }
  .traza-code { color: #0f766e; font-weight: 700; font-family: 'Consolas', monospace; font-size: .78rem; }
  .traza-pend { color: #bbb; font-style: italic; }
  /* Resumen final */
  .resumen-box {
    background: #f0fdf4; border: 1.5px solid #16a34a;
    border-radius: 6px; padding: 14px 18px; margin-top: 18px;
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 10px;
  }
  .resumen-box .item { text-align: center; }
  .resumen-box .v { font-size: 1.4rem; font-weight: 800; color: #15803d; line-height: 1; }
  .resumen-box .l { font-size: .7rem; color: #166534; text-transform: uppercase; letter-spacing: .3px; margin-top: 3px; }
  /* Firmas */
  .firmas { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 70px; page-break-inside: avoid; }
  .firma-box { text-align: center; }
  .firma-line { border-top: 1.5px solid #1a1a1a; margin-bottom: 6px; height: 50px; }
  .firma-name { font-weight: 700; font-size: .85rem; }
  .firma-rol  { font-size: .72rem; color: #666; margin-top: 2px; }
  /* Footer */
  .footer {
    text-align: center; font-size: .68rem; color: #999;
    margin-top: 40px; padding-top: 14px; border-top: 1px solid #e5e7eb;
  }
  /* Botones (solo en pantalla) */
  .toolbar {
    position: sticky; top: 0; background: #fff;
    padding: 10px 20px; border-bottom: 1.5px solid #e5e7eb;
    display: flex; gap: 8px; justify-content: flex-end;
    max-width: 800px; margin: -20px auto 14px; border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
  }
  .btn-print {
    background: <?= htmlspecialchars($progColor) ?>; color: #fff;
    border: none; padding: 9px 18px; border-radius: 6px;
    cursor: pointer; font-size: .85rem; font-weight: 600;
    display: inline-flex; align-items: center; gap: 6px;
  }
  .btn-print:hover { opacity: .9; }
  .btn-back { background: #6b7280; }
  /* Print: ocultar toolbar y fondos */
  @media print {
    body { background: #fff !important; padding: 0 !important; }
    .toolbar { display: none !important; }
    .pagina { box-shadow: none !important; padding: 20px !important; max-width: 100% !important; }
    .guiasa-card { page-break-inside: avoid; }
    .firmas { page-break-inside: avoid; }
    a { text-decoration: none; color: inherit; }
  }
  @page { size: letter; margin: 1.2cm; }
</style>
</head>
<body>

<div class="toolbar">
  <button class="btn-print btn-back" onclick="history.back()"><i class="fas fa-arrow-left"></i> Volver</button>
  <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Imprimir / Guardar PDF</button>
</div>

<div class="pagina">

  <!-- Encabezado -->
  <div class="head">
    <div class="left">
      <h1>Sistema SAG · Honduras Sin Hambre</h1>
      <div class="sub">Secretaría de Agricultura y Ganadería</div>
      <div class="sig"><?= htmlspecialchars($progSigla) ?></div>
      <div class="sub"><?= htmlspecialchars($progNombre) ?></div>
    </div>
    <div class="right">
      <div class="doc">DOCUMENTO No.</div>
      <div><strong>ACTA-<?= htmlspecialchars($progSigla) ?>-<?= htmlspecialchars($beneficiario['dni']) ?>-<?= date('Ymd') ?></strong></div>
      <div class="doc" style="margin-top:6px;">Emitido</div>
      <div><?= $fechaImpr ?></div>
    </div>
  </div>

  <!-- Título -->
  <h2 class="acta-titulo">Acta de Recepción de Insumos</h2>
  <div class="acta-sub">Datos sincronizados desde Trazaragro (OIRSA)</div>

  <!-- Beneficiario -->
  <div class="ben-box">
    <div class="label">Productor / Beneficiario</div>
    <div class="nombre"><?= htmlspecialchars($beneficiario['nombre']) ?></div>
    <div class="ben-grid">
      <div><span class="k">DNI:</span> <strong><?= htmlspecialchars($beneficiario['dni']) ?></strong></div>
      <div><span class="k">CUE:</span> <?= htmlspecialchars($beneficiario['cue'] ?: '—') ?></div>
      <div><span class="k">Departamento:</span> <?= htmlspecialchars($beneficiario['departamento'] ?: '—') ?></div>
      <div><span class="k">Municipio:</span> <?= htmlspecialchars($beneficiario['municipio'] ?: '—') ?></div>
      <div style="grid-column:1/-1;"><span class="k">Establecimiento:</span> <?= htmlspecialchars($beneficiario['establecimiento'] ?: '—') ?></div>
    </div>
  </div>

  <!-- GUIASAs y sus items -->
  <?php foreach ($porGuiasa as $g): ?>
  <div class="guiasa-card">
    <div class="guiasa-head">
      <div class="gtitle"><i class="fas fa-file-invoice"></i> GUIASA No. <?= htmlspecialchars($g['guiasa']) ?></div>
      <div class="gright">Cód. autorización: <strong><?= htmlspecialchars($g['codigo_autorizacion'] ?: '—') ?></strong></div>
    </div>
    <div class="guiasa-meta">
      <div><b>Fecha:</b> <?= htmlspecialchars(substr((string)$g['fecha'], 0, 10) ?: '—') ?></div>
      <div><b>Bodega de origen:</b> <?= htmlspecialchars($g['bodega_origen'] ?: '—') ?></div>
      <div><b>Autorizó:</b> <?= htmlspecialchars($g['autorizado_por'] ?: '—') ?></div>
      <div><b>Rubro:</b> <?= htmlspecialchars($g['rubro'] ?: '—') ?></div>
    </div>
    <table class="items-tbl">
      <thead>
        <tr>
          <th style="width:5%;">#</th>
          <th style="width:42%;">Objeto trazable</th>
          <th style="width:25%;">Código de trazabilidad</th>
          <th style="width:13%;" class="num">Cantidad</th>
          <th style="width:15%;" class="center">Estado</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($g['items'] as $i => $it):
          $entregado = !empty($it['is_completed']) || !empty($it['codigo_trazabilidad']);
        ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><strong><?= htmlspecialchars($it['objeto_trazable'] ?: '(sin nombre)') ?></strong></td>
          <td>
            <?php if ($it['codigo_trazabilidad']): ?>
              <span class="traza-code"><?= htmlspecialchars($it['codigo_trazabilidad']) ?></span>
            <?php else: ?>
              <span class="traza-pend">— pendiente —</span>
            <?php endif; ?>
          </td>
          <td class="num"><?= number_format((float)$it['cantidad'], 0) ?> <?= htmlspecialchars($it['unidad']) ?></td>
          <td class="center">
            <?php if ($entregado): ?>
              <span style="color:#15803d;font-weight:700;">✓ ENTREGADO</span>
            <?php else: ?>
              <span style="color:#a16207;font-weight:700;">PENDIENTE</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endforeach; ?>

  <!-- Resumen total -->
  <div class="resumen-box">
    <div class="item">
      <div class="v"><?= count($porGuiasa) ?></div>
      <div class="l">Manifiestos (GUIASA)</div>
    </div>
    <div class="item">
      <div class="v"><?= $totalItems ?></div>
      <div class="l">Objetos trazables</div>
    </div>
    <div class="item">
      <div class="v"><?= number_format($totalCantidad, 0) ?></div>
      <div class="l">Unidades totales</div>
    </div>
  </div>

  <!-- Firmas -->
  <div class="firmas">
    <div class="firma-box">
      <div class="firma-line"></div>
      <div class="firma-name"><?= htmlspecialchars($beneficiario['nombre']) ?></div>
      <div class="firma-rol">Productor / Beneficiario · DNI: <?= htmlspecialchars($beneficiario['dni']) ?></div>
    </div>
    <div class="firma-box">
      <div class="firma-line"></div>
      <div class="firma-name">Funcionario SAG</div>
      <div class="firma-rol">Verificó la entrega de los insumos detallados</div>
    </div>
  </div>

  <!-- Footer -->
  <div class="footer">
    Documento generado el <?= $fechaImpr ?> por <?= htmlspecialchars(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? '')) ?>
    · Programa <strong><?= htmlspecialchars($progSigla) ?></strong> · SAG Honduras Sin Hambre · Datos verificados con Trazaragro
  </div>

</div>

</body>
</html>
