-- =====================================================
--  MIGRACION 015 — Riesgos / Equipo / Cronograma FPROG 2026
--  SAG Honduras Sin Hambre · Junio 2026
--
--  Tres tablas accesorias del programa "Fortalecimiento de
--  Programas y Proyectos SAG 2026" (id_proyecto = 4):
--
--    1. sag_fp_riesgos     → Matriz de riesgos prob×impacto + mitigación
--    2. sag_fp_equipo      → Estructura del equipo técnico (roles, %)
--    3. sag_fp_cronograma  → Actividades por mes (Jun–Dic 2026)
--
--  Patrón SAG_DEMO:
--    - Aislamiento por id_proyecto (FK a sag_proyectos)
--    - Soft-delete vía columna activo
--    - Auditoría con created_by/created_at/updated_at
--    - Cronograma vincula opcionalmente a componente (FK SET NULL)
--
--  APLICACION (idempotente — usa CREATE TABLE IF NOT EXISTS):
--    USE mddesarr_sag;
--    SOURCE migracion_015_fp_riesgos_equipo_cronograma.sql;
--
--  ROLLBACK manual (sólo si no hay datos cargados):
--    DROP TABLE IF EXISTS sag_fp_cronograma;
--    DROP TABLE IF EXISTS sag_fp_equipo;
--    DROP TABLE IF EXISTS sag_fp_riesgos;
-- =====================================================

-- ───────────────────────────────────────────────────────
--  1. Matriz de riesgos (prob × impacto + mitigación)
-- ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_fp_riesgos (
    id_riesgo            INT             NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_proyecto          INT             NOT NULL,
    categoria            ENUM('operativo','financiero','tecnico','politico','legal','ambiental','otro')
                         NOT NULL DEFAULT 'operativo',
    descripcion          TEXT            NOT NULL,
    probabilidad         ENUM('baja','media','alta')
                         NOT NULL DEFAULT 'media',
    impacto              ENUM('bajo','medio','alto')
                         NOT NULL DEFAULT 'medio',
    medida_mitigacion    TEXT                NULL,
    responsable          VARCHAR(200)        NULL,
    estado               ENUM('identificado','mitigacion','materializado','superado','cerrado')
                         NOT NULL DEFAULT 'identificado',
    observaciones        TEXT                NULL,
    activo               TINYINT(1)      NOT NULL DEFAULT 1,
    created_by           INT                 NULL,
    created_at           TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at           TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_riesgo_proyecto    (id_proyecto),
    INDEX idx_riesgo_estado      (estado),
    INDEX idx_riesgo_categoria   (categoria),
    INDEX idx_riesgo_activo      (activo),
    INDEX idx_riesgo_filtro      (id_proyecto, activo, estado),
    CONSTRAINT fk_riesgo_proyecto
        FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_riesgo_creador
        FOREIGN KEY (created_by) REFERENCES sag_usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ───────────────────────────────────────────────────────
--  2. Estructura del equipo técnico
-- ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_fp_equipo (
    id_equipo             INT             NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_proyecto           INT             NOT NULL,
    rol                   VARCHAR(200)    NOT NULL,
    cantidad              INT             NOT NULL DEFAULT 1,
    descripcion           TEXT                NULL,
    ambito                VARCHAR(120)        NULL,   -- zona, departamento o componente
    porcentaje_presupuesto DECIMAL(5,2)       NULL,   -- % del presupuesto total
    presupuesto_asignado  DECIMAL(14,2)       NULL,
    responsable           VARCHAR(200)        NULL,
    estado                ENUM('vacante','en_proceso','contratado','baja')
                          NOT NULL DEFAULT 'vacante',
    observaciones         TEXT                NULL,
    activo                TINYINT(1)      NOT NULL DEFAULT 1,
    created_by            INT                 NULL,
    created_at            TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_equipo_proyecto  (id_proyecto),
    INDEX idx_equipo_estado    (estado),
    INDEX idx_equipo_activo    (activo),
    INDEX idx_equipo_filtro    (id_proyecto, activo, estado),
    CONSTRAINT fk_equipo_proyecto
        FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_equipo_creador
        FOREIGN KEY (created_by) REFERENCES sag_usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ───────────────────────────────────────────────────────
--  3. Cronograma de actividades (Jun–Dic 2026)
--     Cada actividad puede estar vinculada a un componente.
-- ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_fp_cronograma (
    id_actividad          INT             NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_proyecto           INT             NOT NULL,
    id_componente         INT                 NULL,    -- vinculación opcional
    numero_orden          INT             NOT NULL DEFAULT 0,
    actividad             VARCHAR(300)    NOT NULL,
    descripcion           TEXT                NULL,
    -- Marcas booleanas por mes (Jun = 6, ..., Dic = 12)
    mes_jun               TINYINT(1)      NOT NULL DEFAULT 0,
    mes_jul               TINYINT(1)      NOT NULL DEFAULT 0,
    mes_ago               TINYINT(1)      NOT NULL DEFAULT 0,
    mes_sep               TINYINT(1)      NOT NULL DEFAULT 0,
    mes_oct               TINYINT(1)      NOT NULL DEFAULT 0,
    mes_nov               TINYINT(1)      NOT NULL DEFAULT 0,
    mes_dic               TINYINT(1)      NOT NULL DEFAULT 0,
    responsable           VARCHAR(200)        NULL,
    estado                ENUM('pendiente','en_curso','completada','retrasada','cancelada')
                          NOT NULL DEFAULT 'pendiente',
    observaciones         TEXT                NULL,
    activo                TINYINT(1)      NOT NULL DEFAULT 1,
    created_by            INT                 NULL,
    created_at            TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cron_proyecto    (id_proyecto),
    INDEX idx_cron_componente  (id_componente),
    INDEX idx_cron_estado      (estado),
    INDEX idx_cron_orden       (numero_orden),
    INDEX idx_cron_activo      (activo),
    INDEX idx_cron_filtro      (id_proyecto, activo, estado),
    CONSTRAINT fk_cron_proyecto
        FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_cron_componente
        FOREIGN KEY (id_componente) REFERENCES sag_fp_componentes(id_componente)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_cron_creador
        FOREIGN KEY (created_by) REFERENCES sag_usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
--  FIN MIGRACION 015
-- =====================================================
