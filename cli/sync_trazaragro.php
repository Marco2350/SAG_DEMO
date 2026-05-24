<?php
/**
 * CLI: Sincroniza todos los programas con Trazaragro/OIRSA.
 *
 * Uso desde línea de comandos:
 *   php cli/sync_trazaragro.php                  # sincroniza los 3 programas
 *   php cli/sync_trazaragro.php pipa             # solo PIPA
 *   php cli/sync_trazaragro.php --dias=7         # últimos 7 días (default 30)
 *   php cli/sync_trazaragro.php pipc --dias=60   # PIPC últimos 60 días
 *
 * Para Windows Task Scheduler:
 *   Programa:   C:\xampp\php\php.exe
 *   Argumentos: C:\xampp\htdocs\sag_programas\cli\sync_trazaragro.php
 *   Iniciar en: C:\xampp\htdocs\sag_programas
 *   Trigger:    Diario a las 06:00, repetir cada 6 horas (recomendado)
 *
 * Log: logs/sync_trazaragro.log
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Este script solo puede correrse desde CLI.\n");
}

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/core/TrazaragroClient.php';

// ── Parse argumentos ────────────────────────────────────────────
$progArg = null;
$dias    = 30;
$top     = 1000;
foreach (array_slice($argv, 1) as $a) {
    if (str_starts_with($a, '--dias=')) $dias = max(1, (int)substr($a, 7));
    elseif (str_starts_with($a, '--top=')) $top = max(1, (int)substr($a, 6));
    elseif (!str_starts_with($a, '-')) $progArg = strtolower($a);
}
$desde = date('Y-m-d', strtotime("-{$dias} days"));
$hasta = date('Y-m-d');

// ── Logger ──────────────────────────────────────────────────────
$logFile = ROOT_PATH . '/logs/sync_trazaragro.log';
@mkdir(dirname($logFile), 0775, true);
function plog(string $m): void {
    global $logFile;
    $line = '[' . date('Y-m-d H:i:s') . "] $m\n";
    echo $line;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

plog("═══════════════════════════════════════════════════════════");
plog("  Inicio sync · rango={$desde} a {$hasta} · top={$top}" . ($progArg ? " · programa={$progArg}" : ""));
plog("═══════════════════════════════════════════════════════════");

// ── Cliente Trazaragro ──────────────────────────────────────────
$cli = new TrazaragroClient();
$ping = $cli->ping();
if (!$ping['ok']) {
    plog("✗ Auth OIRSA falló: " . $ping['msg']);
    exit(1);
}
plog("✓ Autenticado con OIRSA (modo: {$ping['mode']})");

// ── Por cada programa ───────────────────────────────────────────
$globalIns = 0; $globalUpd = 0; $globalErr = 0;

foreach (PROGRAMAS as $progId => $prog) {
    if ($progArg && $progId !== $progArg) continue;

    plog("─── Programa: {$prog['sigla']} · BD: {$prog['db']} ───");
    $rubro = $prog['trazaragro_rubro'] ?? null;

    // Conexión PDO directa (sin sesión)
    try {
        $dsn = "mysql:host=" . DB_MAIN['host'] . ";port=" . DB_MAIN['port']
             . ";dbname={$prog['db']};charset=" . DB_MAIN['charset'];
        $pdo = new PDO($dsn, DB_MAIN['username'], DB_MAIN['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    } catch (\Throwable $e) {
        plog("  ✗ Error conexión BD: " . $e->getMessage());
        $globalErr++;
        continue;
    }

    // Verificar que la tabla existe
    $tabla = $pdo->query("SHOW TABLES LIKE 'sag_trazaragro_movimientos'")->fetch();
    if (!$tabla) {
        plog("  ⚠ Tabla sag_trazaragro_movimientos no existe — corre migrar_004.php primero");
        $globalErr++;
        continue;
    }

    // Fetch desde OIRSA
    try {
        $movs = $cli->fetchEntregas([
            'top'   => $top,
            'desde' => $desde,
            'hasta' => $hasta,
            'rubro' => $rubro,
        ]);
        plog("  OIRSA devolvió " . count($movs) . " movimientos (rubro: " . ($rubro ?: 'todos') . ")");
    } catch (\Throwable $e) {
        plog("  ✗ Error consultando OIRSA: " . $e->getMessage());
        $globalErr++;
        continue;
    }

    if (empty($movs)) {
        plog("  (sin movimientos para este rango)");
        continue;
    }

    // UPSERT (mismo SQL que EntregasController::persistirMovimientos)
    $sql = "INSERT INTO sag_trazaragro_movimientos (
        movement_id, rubro, rubro_id, tipo_movimiento, tipo_movimiento_id, actividad_id,
        objeto_trazable, objeto_trazable_codigo, codigo_trazabilidad,
        guiasa_no, codigo_autorizacion,
        fecha_registro, fecha_autorizacion, fecha_expiracion,
        origen_persona, origen_establecimiento, origen_cue, origen_departamento, origen_municipio,
        destino_persona, destino_dni, destino_nombre, destino_establecimiento, destino_cue, destino_departamento, destino_municipio,
        cantidad, unidad, transportista, vehiculo, condicion, proposito,
        autorizado_por, creado_por, status_oirsa, status_id, event_stage, is_completed,
        estado_local, raw_json, synced_at
    ) VALUES (
        :movement_id, :rubro, :rubro_id, :tipo_movimiento, :tipo_movimiento_id, :actividad_id,
        :objeto_trazable, :objeto_trazable_codigo, :codigo_trazabilidad,
        :guiasa_no, :codigo_autorizacion,
        :fecha_registro, :fecha_autorizacion, :fecha_expiracion,
        :origen_persona, :origen_establecimiento, :origen_cue, :origen_departamento, :origen_municipio,
        :destino_persona, :destino_dni, :destino_nombre, :destino_establecimiento, :destino_cue, :destino_departamento, :destino_municipio,
        :cantidad, :unidad, :transportista, :vehiculo, :condicion, :proposito,
        :autorizado_por, :creado_por, :status_oirsa, :status_id, :event_stage, :is_completed,
        :estado_local, :raw_json, NOW()
    )
    ON DUPLICATE KEY UPDATE
        rubro=VALUES(rubro), tipo_movimiento=VALUES(tipo_movimiento), objeto_trazable=VALUES(objeto_trazable),
        codigo_trazabilidad=VALUES(codigo_trazabilidad), guiasa_no=VALUES(guiasa_no), codigo_autorizacion=VALUES(codigo_autorizacion),
        fecha_registro=VALUES(fecha_registro), fecha_autorizacion=VALUES(fecha_autorizacion), fecha_expiracion=VALUES(fecha_expiracion),
        origen_persona=VALUES(origen_persona), origen_establecimiento=VALUES(origen_establecimiento),
        origen_cue=VALUES(origen_cue), origen_departamento=VALUES(origen_departamento), origen_municipio=VALUES(origen_municipio),
        destino_persona=VALUES(destino_persona), destino_dni=VALUES(destino_dni), destino_nombre=VALUES(destino_nombre),
        destino_establecimiento=VALUES(destino_establecimiento), destino_cue=VALUES(destino_cue),
        destino_departamento=VALUES(destino_departamento), destino_municipio=VALUES(destino_municipio),
        cantidad=VALUES(cantidad), unidad=VALUES(unidad), transportista=VALUES(transportista), vehiculo=VALUES(vehiculo),
        condicion=VALUES(condicion), proposito=VALUES(proposito),
        autorizado_por=VALUES(autorizado_por), creado_por=VALUES(creado_por),
        status_oirsa=VALUES(status_oirsa), status_id=VALUES(status_id), event_stage=VALUES(event_stage),
        is_completed=VALUES(is_completed), estado_local=VALUES(estado_local), raw_json=VALUES(raw_json),
        synced_at=NOW()";

    $stmt = $pdo->prepare($sql);
    $checkStmt = $pdo->prepare("SELECT 1 FROM sag_trazaragro_movimientos WHERE movement_id=?");

    $ins = 0; $upd = 0; $err = 0;
    foreach ($movs as $m) {
        $movId = (int)($m['trazaragro_id'] ?? 0);
        if ($movId <= 0) { $err++; continue; }

        try {
            $checkStmt->execute([$movId]);
            $existe = (bool)$checkStmt->fetchColumn();

            $isCompleted = !empty($m['is_completed']) ? 1 : 0;
            $tieneCodigo = !empty($m['codigo_trazabilidad']);
            $estadoLocal = ($isCompleted || $tieneCodigo) ? 'entregado' : 'pendiente';

            $stmt->execute([
                ':movement_id'           => $movId,
                ':rubro'                 => $m['rubro'] ?: null,
                ':rubro_id'              => $m['rubro_id'] ?: null,
                ':tipo_movimiento'       => $m['tipo_movimiento'] ?: null,
                ':tipo_movimiento_id'    => $m['tipo_movimiento_id'] ?: null,
                ':actividad_id'          => $m['actividad_id'] ?: null,
                ':objeto_trazable'       => $m['objeto_trazable'] ?: null,
                ':objeto_trazable_codigo'=> $m['objeto_trazable_codigo'] ?: null,
                ':codigo_trazabilidad'   => $m['codigo_trazabilidad'] ?: null,
                ':guiasa_no'             => $m['registration_code'] ?: null,
                ':codigo_autorizacion'   => $m['authorization_code'] ?: null,
                ':fecha_registro'        => $m['fecha_registro'] ?: null,
                ':fecha_autorizacion'    => $m['fecha_autorizacion'] ?: null,
                ':fecha_expiracion'      => $m['fecha_expiracion'] ?: null,
                ':origen_persona'        => $m['origen_persona'] ?: null,
                ':origen_establecimiento'=> $m['origen_establecimiento'] ?: null,
                ':origen_cue'            => $m['origen_cue'] ?: null,
                ':origen_departamento'   => $m['origen_departamento'] ?: null,
                ':origen_municipio'      => $m['origen_municipio'] ?: null,
                ':destino_persona'       => $m['destino_persona'] ?: null,
                ':destino_dni'           => $m['destino_dni'] ?: null,
                ':destino_nombre'        => $m['destino_nombre'] ?: null,
                ':destino_establecimiento'=> $m['destino_establecimiento'] ?: null,
                ':destino_cue'           => $m['destino_cue'] ?: null,
                ':destino_departamento'  => $m['destino_departamento'] ?: null,
                ':destino_municipio'     => $m['destino_municipio'] ?: null,
                ':cantidad'              => (float)($m['cantidad'] ?? 0),
                ':unidad'                => $m['unidad'] ?: null,
                ':transportista'         => $m['transportista'] ?: null,
                ':vehiculo'              => $m['vehiculo'] ?: null,
                ':condicion'             => $m['condicion'] ?: null,
                ':proposito'             => $m['proposito'] ?: null,
                ':autorizado_por'        => $m['usuario_autoriza'] ?: null,
                ':creado_por'            => $m['usuario_crea'] ?: null,
                ':status_oirsa'          => $m['status'] ?: null,
                ':status_id'             => $m['status_id'] ?: null,
                ':event_stage'           => $m['event_stage'] ?: null,
                ':is_completed'          => $isCompleted,
                ':estado_local'          => $estadoLocal,
                ':raw_json'              => json_encode($m['raw'] ?? [], JSON_UNESCAPED_UNICODE),
            ]);
            if ($existe) $upd++; else $ins++;
        } catch (\Throwable $e) {
            $err++;
            plog("    ✗ MovementId {$movId}: " . substr($e->getMessage(), 0, 150));
        }
    }

    plog("  → {$ins} nuevos · {$upd} actualizados · {$err} errores");
    $globalIns += $ins; $globalUpd += $upd; $globalErr += $err;
}

plog("═══ Fin sync · total: {$globalIns} nuevos · {$globalUpd} actualizados · {$globalErr} errores ═══");
exit($globalErr > 0 ? 2 : 0);
