<?php
/**
 * Diagnóstico + migración segura de sag_trazaragro_movimientos
 * respetando el patrón single-DB SAG_DEMO (id_proyecto por fila).
 *
 * Modos:
 *   - Sin querystring → SOLO diagnóstico (no modifica nada).
 *   - ?aplicar=1      → Aplica las correcciones de schema necesarias.
 *   - ?reset=1        → (con aplicar=1) TRUNCATE la tabla antes de migrar.
 *
 * URL: http://localhost/SAG_DEMO/_diag_persistencia.php
 * Sólo accesible en localhost + APP_ENV=development.
 */
require_once __DIR__ . '/_test_guard.php';
header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/core/Database.php';

$db      = Database::main();
$aplicar = ($_GET['aplicar'] ?? '') === '1';
$reset   = ($_GET['reset']   ?? '') === '1';

echo "═══ DIAGNÓSTICO sag_trazaragro_movimientos ═══\n";
echo "Modo: " . ($aplicar ? "APLICAR CAMBIOS" : "SOLO DIAGNÓSTICO (sin tocar BD)") . "\n";
echo "DB:   " . (DB_MAIN['database'] ?? DB_MAIN['name'] ?? '?') . " @ " . DB_MAIN['host'] . "\n\n";

// ─── 1. ¿Existe la tabla? ───────────────────────────────────
$existe = !empty($db->fetchAll("SHOW TABLES LIKE 'sag_trazaragro_movimientos'"));
echo "1) Tabla existente: " . ($existe ? 'SÍ' : 'NO') . "\n";

// ─── 2. Columnas actuales ───────────────────────────────────
$colsActuales  = [];
$colsRequeridas = [
    'id_proyecto', 'movement_id', 'rubro', 'rubro_id',
    'tipo_movimiento', 'tipo_movimiento_id', 'actividad_id',
    'objeto_trazable', 'objeto_trazable_codigo', 'codigo_trazabilidad',
    'guiasa_no', 'codigo_autorizacion',
    'fecha_registro', 'fecha_autorizacion', 'fecha_expiracion',
    'origen_persona', 'origen_establecimiento', 'origen_cue', 'origen_departamento', 'origen_municipio',
    'destino_persona', 'destino_dni', 'destino_nombre', 'destino_establecimiento', 'destino_cue', 'destino_departamento', 'destino_municipio',
    'cantidad', 'unidad', 'transportista', 'vehiculo', 'condicion', 'proposito',
    'autorizado_por', 'creado_por', 'status_oirsa', 'status_id', 'event_stage', 'is_completed',
    'estado_local', 'raw_json', 'synced_at',
];

if ($existe) {
    $cols = $db->fetchAll("SHOW COLUMNS FROM sag_trazaragro_movimientos");
    $colsActuales = array_column($cols, 'Field');
    echo "2) Columnas presentes: " . count($colsActuales) . "\n";

    $faltantes = array_diff($colsRequeridas, $colsActuales);
    $sobrantes = array_diff($colsActuales, $colsRequeridas, ['id', 'observaciones_local', 'revisado_por_local', 'fecha_revision_local', 'created_at', 'updated_at']);
    echo "   Faltantes (" . count($faltantes) . "): " . (empty($faltantes) ? '—' : implode(', ', $faltantes)) . "\n";
    echo "   Extras    (" . count($sobrantes) . "): " . (empty($sobrantes) ? '—' : implode(', ', $sobrantes)) . "\n";
} else {
    echo "2) (tabla no existe — todas las columnas hay que crearlas)\n";
}

// ─── 3. PK / UNIQUE actuales ────────────────────────────────
if ($existe) {
    $pk = $db->fetchAll("SHOW INDEXES FROM sag_trazaragro_movimientos WHERE Key_name = 'PRIMARY'");
    $pkCols = array_column($pk, 'Column_name');
    echo "3) PRIMARY KEY: (" . implode(',', $pkCols) . ")\n";

    $uks = $db->fetchAll("SHOW INDEXES FROM sag_trazaragro_movimientos WHERE Non_unique = 0 AND Key_name <> 'PRIMARY'");
    $uksByName = [];
    foreach ($uks as $u) $uksByName[$u['Key_name']][] = $u['Column_name'];
    echo "   UNIQUE keys: " . (empty($uksByName) ? '—' : '') . "\n";
    foreach ($uksByName as $name => $c) {
        echo "      $name (" . implode(',', $c) . ")\n";
    }
}

// ─── 4. Conteo actual ───────────────────────────────────────
$total = $existe ? (int)($db->fetchOne("SELECT COUNT(*) AS n FROM sag_trazaragro_movimientos")['n'] ?? 0) : 0;
echo "4) Filas actuales: $total\n\n";

// ─── 5. Probar INSERT (para ver el error PDO exacto) ────────
if ($existe && !$aplicar) {
    echo "═══ TEST INSERT (sin commit) ═══\n";
    try {
        $db->beginTransaction();
        $cols2 = implode(', ', array_intersect($colsRequeridas, $colsActuales));
        // Construir SQL solo con columnas que existen
        $colsParaInsert = array_intersect($colsRequeridas, $colsActuales);
        $placeholders   = array_map(fn($c) => ':' . $c, $colsParaInsert);
        $sql = "INSERT INTO sag_trazaragro_movimientos (" . implode(',', $colsParaInsert) . ")
                VALUES (" . implode(',', $placeholders) . ")";

        $params = [];
        foreach ($colsParaInsert as $c) {
            $params[':' . $c] = match($c) {
                'movement_id'        => 99999999,
                'id_proyecto'        => 3,
                'rubro_id'           => 2375,
                'tipo_movimiento_id' => 111,
                'actividad_id'       => 329,
                'cantidad'           => 1.0,
                'is_completed'       => 0,
                'status_id'          => 0,
                'estado_local'       => 'pendiente',
                'synced_at'          => date('Y-m-d H:i:s'),
                'fecha_registro', 'fecha_autorizacion', 'fecha_expiracion' => null,
                'raw_json'           => '{}',
                default              => 'test',
            };
        }
        $db->execute($sql, $params);
        $db->rollback();
        echo "✓ INSERT de prueba ejecutó correctamente.\n";
        echo "  El problema NO está en el schema sino en algún dato puntual de OIRSA.\n";
    } catch (\Throwable $e) {
        $db->rollback();
        echo "✗ INSERT falló con error PDO:\n";
        echo "   " . $e->getMessage() . "\n\n";
        echo "  → Para corregir el schema: agregar ?aplicar=1 a esta URL.\n";
    }
    echo "\n";
}

// ─── 6. APLICAR CAMBIOS (solo con ?aplicar=1) ───────────────
if (!$aplicar) {
    echo "═══ Para aplicar correcciones de schema: ?aplicar=1 ═══\n";
    echo "    Para vaciar y re-migrar:        ?aplicar=1&reset=1\n";
    exit;
}

echo "═══ APLICANDO MIGRACIÓN ═══\n";

if (!$existe) {
    echo "Creando tabla desde cero (patrón SAG_DEMO con id_proyecto)...\n";
    $db->execute("
    CREATE TABLE sag_trazaragro_movimientos (
        id                    BIGINT       NOT NULL AUTO_INCREMENT PRIMARY KEY,
        id_proyecto           INT          NULL,
        movement_id           BIGINT       NOT NULL,
        rubro                 VARCHAR(120),
        rubro_id              INT,
        tipo_movimiento       VARCHAR(120),
        tipo_movimiento_id    INT,
        actividad_id          INT,
        objeto_trazable       VARCHAR(255),
        objeto_trazable_codigo VARCHAR(80),
        codigo_trazabilidad   VARCHAR(80),
        guiasa_no             VARCHAR(60),
        codigo_autorizacion   VARCHAR(60),
        fecha_registro        DATETIME,
        fecha_autorizacion    DATETIME,
        fecha_expiracion      DATETIME,
        origen_persona        VARCHAR(255),
        origen_establecimiento VARCHAR(255),
        origen_cue            VARCHAR(60),
        origen_departamento   VARCHAR(120),
        origen_municipio      VARCHAR(120),
        destino_persona       VARCHAR(255),
        destino_dni           VARCHAR(20),
        destino_nombre        VARCHAR(255),
        destino_establecimiento VARCHAR(255),
        destino_cue           VARCHAR(60),
        destino_departamento  VARCHAR(120),
        destino_municipio     VARCHAR(120),
        cantidad              DECIMAL(14,4) NOT NULL DEFAULT 0,
        unidad                VARCHAR(40),
        transportista         VARCHAR(200),
        vehiculo              VARCHAR(80),
        condicion             VARCHAR(120),
        proposito             VARCHAR(200),
        autorizado_por        VARCHAR(200),
        creado_por            VARCHAR(200),
        status_oirsa          VARCHAR(60),
        status_id             INT,
        event_stage           VARCHAR(80),
        is_completed          TINYINT(1)   NOT NULL DEFAULT 0,
        estado_local          ENUM('entregado','pendiente','observado','anulado') NOT NULL DEFAULT 'pendiente',
        observaciones_local   TEXT,
        revisado_por_local    VARCHAR(200),
        fecha_revision_local  DATETIME,
        raw_json              MEDIUMTEXT,
        synced_at             DATETIME     NOT NULL,
        created_at            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        updated_at            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_proy_mov_item (id_proyecto, movement_id, codigo_trazabilidad),
        INDEX idx_proyecto     (id_proyecto),
        INDEX idx_guiasa       (guiasa_no),
        INDEX idx_destino_dni  (destino_dni),
        INDEX idx_fecha        (fecha_autorizacion),
        INDEX idx_rubro        (rubro),
        INDEX idx_estado_local (estado_local)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ Tabla creada.\n";
} else {
    if ($reset) {
        echo "⚠ Reset solicitado. Vaciando tabla...\n";
        $db->execute("TRUNCATE TABLE sag_trazaragro_movimientos");
        echo "  ✓ Tabla vacía.\n";
    }

    // 1. Agregar id_proyecto si falta
    if (!in_array('id_proyecto', $colsActuales, true)) {
        echo "+ Agregando id_proyecto (patrón SAG_DEMO)...\n";
        $db->execute("ALTER TABLE sag_trazaragro_movimientos ADD COLUMN id_proyecto INT NULL AFTER movement_id");
        $db->execute("ALTER TABLE sag_trazaragro_movimientos ADD INDEX idx_proyecto (id_proyecto)");
        echo "  ✓ id_proyecto agregada.\n";
    }

    // 2. Si PK simple en movement_id → migrar a auto-inc + unique compuesta
    $pk = $db->fetchAll("SHOW INDEXES FROM sag_trazaragro_movimientos WHERE Key_name = 'PRIMARY'");
    $pkCols = array_column($pk, 'Column_name');
    if (count($pkCols) === 1 && $pkCols[0] === 'movement_id') {
        echo "+ Migrando PK simple → auto-increment + UNIQUE compuesta...\n";
        try {
            $db->execute("ALTER TABLE sag_trazaragro_movimientos DROP PRIMARY KEY");
            $db->execute("ALTER TABLE sag_trazaragro_movimientos ADD COLUMN id BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST");
            $db->execute("ALTER TABLE sag_trazaragro_movimientos ADD UNIQUE KEY uk_proy_mov_item (id_proyecto, movement_id, codigo_trazabilidad)");
            echo "  ✓ PK migrada.\n";
        } catch (\Throwable $e) {
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            echo "  → Probá con &reset=1 si la tabla tiene datos viejos.\n";
        }
    }
}

echo "\n═══ Estado final ═══\n";
$total = (int)($db->fetchOne("SELECT COUNT(*) AS n FROM sag_trazaragro_movimientos")['n'] ?? 0);
echo "Filas: $total\n";
echo "Ahora reintentá sincronizar desde /entregas con PIPA activo.\n";
