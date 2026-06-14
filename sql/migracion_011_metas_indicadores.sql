-- =====================================================
--  MIGRACION 011 — Metas e Indicadores por programa
--  SAG Honduras Sin Hambre · Junio 2026
--
--  Crea dos tablas independientes (sag_metas, sag_indicadores)
--  con aislamiento por id_proyecto. El mismo "concepto" puede
--  registrarse en cada programa SAG sin colisionar.
--
--  Patrón SAG_DEMO:
--    - Single-DB (mddesarr_sag), separación por id_proyecto.
--    - Soft-delete con columna 'activo'.
--    - Trazabilidad: created_by / updated_at.
--    - FK a sag_proyectos.
--    - Indices en columnas frecuentes de filtro.
--
--  APLICACION:
--     USE mddesarr_sag;
--     SOURCE migracion_005_metas_indicadores.sql;
--
--  ROLLBACK:
--     -- (No destructivo) Para revertir manualmente:
--     -- DROP TABLE IF EXISTS sag_indicadores;
--     -- DROP TABLE IF EXISTS sag_metas;
--
--  Idempotente: usa CREATE TABLE IF NOT EXISTS.
-- =====================================================

-- ───────────────────────────────────────────────────────
--  Tabla: sag_metas
-- ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_metas (
    id_meta            INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_proyecto        INT          NOT NULL,                           -- aislamiento
    codigo             VARCHAR(40)  NULL,                               -- p.ej. M-2026-001
    nombre             VARCHAR(200) NOT NULL,
    descripcion        TEXT         NULL,
    unidad_medida      VARCHAR(80)  NULL,                               -- 'productores', 'hectáreas', etc.
    valor_objetivo     DECIMAL(14,4) NOT NULL DEFAULT 0,
    valor_actual       DECIMAL(14,4) NOT NULL DEFAULT 0,
    periodo            VARCHAR(20)  NOT NULL DEFAULT 'anual',           -- anual, semestral, trimestral, mensual
    fecha_inicio       DATE         NULL,
    fecha_fin          DATE         NULL,
    responsable        VARCHAR(200) NULL,                               -- nombre responsable (libre)
    fuente_verificacion VARCHAR(255) NULL,
    estado             ENUM('planificada','en_progreso','cumplida','no_cumplida','reformulada')
                       NOT NULL DEFAULT 'planificada',
    observaciones      TEXT         NULL,
    -- Auditoría y soft-delete
    activo             TINYINT(1)   NOT NULL DEFAULT 1,
    created_by         INT          NULL,
    created_at         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Aislamiento + busqueda rapida
    UNIQUE KEY uk_meta_codigo (id_proyecto, codigo),
    INDEX idx_meta_proyecto  (id_proyecto),
    INDEX idx_meta_estado    (estado),
    INDEX idx_meta_periodo   (periodo),
    INDEX idx_meta_activo    (activo),
    CONSTRAINT fk_meta_proyecto
        FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ───────────────────────────────────────────────────────
--  Tabla: sag_indicadores
--  Un indicador puede o no estar vinculado a una meta.
-- ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_indicadores (
    id_indicador       INT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_proyecto        INT          NOT NULL,                           -- aislamiento
    id_meta            INT          NULL,                               -- vinculación opcional
    codigo             VARCHAR(40)  NULL,                               -- p.ej. IND-2026-001
    nombre             VARCHAR(200) NOT NULL,
    descripcion        TEXT         NULL,
    tipo               ENUM('producto','resultado','impacto','proceso','otro')
                       NOT NULL DEFAULT 'producto',
    unidad_medida      VARCHAR(80)  NULL,
    formula            TEXT         NULL,                               -- formula de calculo (libre)
    linea_base         DECIMAL(14,4) NULL,                              -- valor inicial de referencia
    valor_objetivo     DECIMAL(14,4) NOT NULL DEFAULT 0,
    valor_actual       DECIMAL(14,4) NOT NULL DEFAULT 0,
    frecuencia_medicion VARCHAR(30) NOT NULL DEFAULT 'mensual',         -- mensual, trimestral, etc.
    ultima_medicion    DATE         NULL,
    fuente_datos       VARCHAR(255) NULL,
    responsable        VARCHAR(200) NULL,
    estado             ENUM('activo','suspendido','reformulado','retirado')
                       NOT NULL DEFAULT 'activo',
    observaciones      TEXT         NULL,
    -- Auditoría y soft-delete
    activo             TINYINT(1)   NOT NULL DEFAULT 1,
    created_by         INT          NULL,
    created_at         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Aislamiento + busqueda rapida
    UNIQUE KEY uk_indicador_codigo (id_proyecto, codigo),
    INDEX idx_indicador_proyecto (id_proyecto),
    INDEX idx_indicador_meta     (id_meta),
    INDEX idx_indicador_tipo     (tipo),
    INDEX idx_indicador_estado   (estado),
    INDEX idx_indicador_activo   (activo),
    CONSTRAINT fk_indicador_proyecto
        FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_indicador_meta
        FOREIGN KEY (id_meta) REFERENCES sag_metas(id_meta)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
--  FIN MIGRACION 005
-- =====================================================
