-- =====================================================
--  MIGRACIÓN 004 — Movimientos de Trazaragro (vista nativa OIRSA)
--  SAG Honduras Sin Hambre · Mayo 2026
--
--  Ejecutar en cada BD de programa:
--     USE sag_pipc;   SOURCE migracion_004_trazaragro_movimientos.sql;
--     USE sag_pipg;   SOURCE migracion_004_trazaragro_movimientos.sql;
--     USE sag_pipa;   SOURCE migracion_004_trazaragro_movimientos.sql;
--
--  Crea: sag_trazaragro_movimientos — refleja 1:1 las columnas que OIRSA
--        muestra en su vista "Programas nacionales" (Registro de Movilización).
--        Cada fila = un objeto trazable (una línea OData QueryMovementProducts).
-- =====================================================

CREATE TABLE IF NOT EXISTS sag_trazaragro_movimientos (
    movement_id           BIGINT       NOT NULL PRIMARY KEY,
    -- Categoría / clasificación
    rubro                 VARCHAR(120),                  -- ProductActivityName
    rubro_id              INT,                           -- ProductActivityId
    tipo_movimiento       VARCHAR(120),                  -- MovementTypeName
    tipo_movimiento_id    INT,                           -- MovementTypeId
    actividad_id          INT,                           -- ActivityId (329 = Insumos)

    -- Identificadores OIRSA
    objeto_trazable       VARCHAR(255),                  -- ProductTypeName (NPK 12-24-12...)
    objeto_trazable_codigo VARCHAR(80),                  -- ProductTypeCode
    codigo_trazabilidad   VARCHAR(80),                   -- ItemCode/SealCodes — vacío si pendiente
    guiasa_no             VARCHAR(60),                   -- RegistrationCode (EH0000210)
    codigo_autorizacion   VARCHAR(60),                   -- AuthorizationCode (HE2068658A)

    -- Fechas
    fecha_registro        DATETIME,
    fecha_autorizacion    DATETIME,
    fecha_expiracion      DATETIME,

    -- Origen
    origen_persona        VARCHAR(255),                  -- SourceEndpointCounterpart "Nombre; DNI"
    origen_establecimiento VARCHAR(255),                 -- SourceEndpointDescription
    origen_cue            VARCHAR(60),                   -- SourceEndpointCode
    origen_departamento   VARCHAR(120),                  -- SourceLocation1
    origen_municipio      VARCHAR(120),                  -- SourceLocation2

    -- Destino
    destino_persona       VARCHAR(255),                  -- DestinyEndpointCounterpart "Nombre; DNI"
    destino_dni           VARCHAR(20),                   -- extraído del Counterpart
    destino_nombre        VARCHAR(255),                  -- extraído del Counterpart
    destino_establecimiento VARCHAR(255),                -- DestinyEndpointDescription
    destino_cue           VARCHAR(60),                   -- DestinyEndpointCode
    destino_departamento  VARCHAR(120),                  -- DestinyLocation1
    destino_municipio     VARCHAR(120),                  -- DestinyLocation2

    -- Cantidad / unidad
    cantidad              DECIMAL(14,4) NOT NULL DEFAULT 0,
    unidad                VARCHAR(40),

    -- Logística
    transportista         VARCHAR(200),                  -- Carrier
    vehiculo              VARCHAR(80),                   -- Vehicle
    condicion             VARCHAR(120),                  -- Condition
    proposito             VARCHAR(200),                  -- Purpose

    -- Autorización (¡el técnico real SAG/OIRSA!)
    autorizado_por        VARCHAR(200),                  -- AuthorizationUserName
    creado_por            VARCHAR(200),                  -- CreationUserName
    status_oirsa          VARCHAR(60),                   -- Status ("Autorizado")
    status_id             INT,                           -- StatusId
    event_stage           VARCHAR(80),                   -- EventStage
    is_completed          TINYINT(1)   NOT NULL DEFAULT 0,

    -- Auditoría local
    estado_local          ENUM('entregado','pendiente','observado','anulado')
                          NOT NULL DEFAULT 'pendiente',
    observaciones_local   TEXT,
    revisado_por_local    VARCHAR(200),
    fecha_revision_local  DATETIME,

    -- Backup completo del registro original (JSON crudo de OIRSA)
    raw_json              MEDIUMTEXT,

    -- Timestamps
    synced_at             DATETIME     NOT NULL,
    created_at            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_guiasa       (guiasa_no),
    INDEX idx_auth_code    (codigo_autorizacion),
    INDEX idx_destino_dni  (destino_dni),
    INDEX idx_fecha        (fecha_autorizacion),
    INDEX idx_rubro        (rubro),
    INDEX idx_objeto       (objeto_trazable),
    INDEX idx_estado_local (estado_local),
    INDEX idx_destino_dep  (destino_departamento),
    INDEX idx_is_compl     (is_completed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
--  FIN MIGRACIÓN 004
-- =====================================================
