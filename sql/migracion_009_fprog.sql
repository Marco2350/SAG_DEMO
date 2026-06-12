-- =====================================================================
--  Migración 009 — Fortalecimiento de Programas SAG (FPROG)
--  Base destino : mddesarr_sag
--  Idempotente  : usa INSERT IGNORE / CREATE TABLE IF NOT EXISTS
--
--  phpMyAdmin   : seleccionar base mddesarr_sag → pestaña SQL → pegar
--  CLI          : mysql -u USUARIO -p mddesarr_sag < sql/migracion_009_fprog.sql
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────────────────────
--  1. Registrar el programa en el discriminador raíz
--     id_proyecto = 4 es explícito para mantener consistencia
--     con los valores hardcodeados en config/app.php
-- ─────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO sag_proyectos (id_proyecto, codigo, nombre, sigla, activo)
VALUES (4, 'fprog', 'Fortalecimiento de Programas SAG', 'FPROG', 1);

-- ─────────────────────────────────────────────────────────────────────
--  2. Catálogo geográfico — 18 departamentos de Honduras
--     Sin id_departamento explícito: AUTO_INCREMENT asigna IDs únicos
--     para id_proyecto=4, igual que los demás programas
-- ─────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO sag_departamentos (id_proyecto, nombre) VALUES
(4, 'Atlántida'),
(4, 'Choluteca'),
(4, 'Colón'),
(4, 'Comayagua'),
(4, 'Copán'),
(4, 'Cortés'),
(4, 'El Paraíso'),
(4, 'Francisco Morazán'),
(4, 'Gracias a Dios'),
(4, 'Intibucá'),
(4, 'Islas de la Bahía'),
(4, 'La Paz'),
(4, 'Lempira'),
(4, 'Ocotepeque'),
(4, 'Olancho'),
(4, 'Santa Bárbara'),
(4, 'Valle'),
(4, 'Yoro');

-- ─────────────────────────────────────────────────────────────────────
--  3. Tabla principal de acciones de fortalecimiento
--
--  Notas de diseño:
--   • id_proyecto     → discriminador, FK a sag_proyectos
--   • id_departamento → nullable: acciones de alcance 'nacional' no lo requieren
--   • id_responsable  → nullable FK a sag_tecnicos (se pobla via catálogos)
--   • activo          → soft-delete estándar del sistema
--   • estado          → máquina de estados: planificado → en_ejecucion → completado
--   • avance/meta     → permite calcular % de cumplimiento
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_acciones_fortalecimiento (
    id_accion             INT            NOT NULL AUTO_INCREMENT,
    id_proyecto           INT            NOT NULL,
    tipo_accion           ENUM(
                            'consultoria',
                            'taller',
                            'reunion',
                            'estudio',
                            'asistencia_tecnica',
                            'capacitacion',
                            'otro'
                          )              NOT NULL DEFAULT 'otro',
    titulo                VARCHAR(300)   NOT NULL,
    descripcion           TEXT,
    objetivo              TEXT,
    alcance               ENUM('nacional','departamental')
                                         NOT NULL DEFAULT 'nacional',
    id_departamento       INT                NULL,
    fecha_inicio          DATE           NOT NULL,
    fecha_fin             DATE               NULL,
    id_responsable        INT                NULL,
    institucion_ejecutora VARCHAR(200)        NULL,
    presupuesto_asignado  DECIMAL(12,2)      NULL,
    presupuesto_ejecutado DECIMAL(12,2)  NOT NULL DEFAULT 0.00,
    indicador             VARCHAR(300)       NULL,
    meta                  DECIMAL(10,2)      NULL,
    avance                DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    num_participantes     INT            NOT NULL DEFAULT 0,
    estado                ENUM(
                            'planificado',
                            'en_ejecucion',
                            'completado',
                            'cancelado'
                          )              NOT NULL DEFAULT 'planificado',
    observaciones         TEXT,
    activo                TINYINT(1)     NOT NULL DEFAULT 1,
    created_by            INT                NULL,
    created_at            TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                         ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id_accion),

    INDEX idx_proyecto  (id_proyecto),
    INDEX idx_estado    (estado),
    INDEX idx_activo    (activo),
    INDEX idx_tipo      (tipo_accion),
    INDEX idx_fecha     (fecha_inicio),
    INDEX idx_filtro    (id_proyecto, activo, estado),

    CONSTRAINT fk_af_proyecto
        FOREIGN KEY (id_proyecto)
        REFERENCES sag_proyectos(id_proyecto),

    CONSTRAINT fk_af_departamento
        FOREIGN KEY (id_departamento)
        REFERENCES sag_departamentos(id_departamento)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_af_responsable
        FOREIGN KEY (id_responsable)
        REFERENCES sag_tecnicos(id_tecnico)
        ON UPDATE CASCADE ON DELETE SET NULL,

    CONSTRAINT fk_af_creador
        FOREIGN KEY (created_by)
        REFERENCES sag_usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────
--  4. Participantes por acción
--
--  Notas de diseño:
--   • activo TINYINT(1) → soft-delete en sub-tabla (mejora sobre el
--     patrón de sag_cap_participantes que usa hard-delete en cascada)
--   • ON DELETE CASCADE en id_accion → si se elimina la acción padre
--     los participantes también desaparecen en BD
--   • cargo / institucion → campos institucionales propios de FPROG
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_fprog_participantes (
    id_participante INT           NOT NULL AUTO_INCREMENT,
    id_accion       INT           NOT NULL,
    id_proyecto     INT           NOT NULL,
    nombre          VARCHAR(100)  NOT NULL,
    apellido        VARCHAR(100)      NULL,
    cargo           VARCHAR(150)      NULL,
    institucion     VARCHAR(200)      NULL,
    sexo            ENUM('M','F')     NULL,
    activo          TINYINT(1)    NOT NULL DEFAULT 1,
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id_participante),

    INDEX idx_accion   (id_accion),
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_activo   (activo),

    CONSTRAINT fk_fp_accion
        FOREIGN KEY (id_accion)
        REFERENCES sag_acciones_fortalecimiento(id_accion)
        ON DELETE CASCADE,

    CONSTRAINT fk_fp_proyecto
        FOREIGN KEY (id_proyecto)
        REFERENCES sag_proyectos(id_proyecto)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  VERIFICACIÓN — ejecutar después para confirmar la migración
-- =====================================================================
-- SELECT * FROM sag_proyectos WHERE codigo = 'fprog';
-- SELECT COUNT(*) FROM sag_departamentos WHERE id_proyecto = 4;
-- SHOW TABLES LIKE 'sag_%fprog%';
-- SHOW TABLES LIKE 'sag_acciones%';
-- =====================================================================
