-- =====================================================
--  MIGRACION 018 — Censo Nacional + columna etnia
--  SAG Honduras Sin Hambre · Junio 2026
--
--  Crea:
--    1. sag_censo_nacional → estructura para alojar ~7M
--       personas del censo (carga via cli/cargar_censo.php, NO web).
--       Compartida entre programas (sin id_proyecto).
--    2. Agrega columna 'etnia' a sag_beneficiarios.
--    3. Lista cerrada de etnias reconocidas en Honduras como
--       lista permitida (ENUM-like en aplicación, no en BD para
--       flexibilidad futura — cualquier valor string es válido).
--
--  Indices criticos:
--    - sag_censo_nacional.dni: UNIQUE + INDEX para lookups <10ms
--    - sag_beneficiarios.etnia: INDEX para reportes por etnia
--
--  Idempotente:
--    CREATE TABLE IF NOT EXISTS + stored procedure para ADD COLUMN.
--
--  APLICACION:
--    USE mddesarr_sag;
--    SOURCE migracion_018_censo_nacional_y_etnia.sql;
--
--  ROLLBACK manual:
--    DROP TABLE IF EXISTS sag_censo_nacional;
--    ALTER TABLE sag_beneficiarios DROP COLUMN etnia;
-- =====================================================

SET NAMES utf8mb4;

-- -----------------------------------------------------
-- 1. Tabla sag_censo_nacional
--    Compartida (sin id_proyecto): el censo es nacional.
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS sag_censo_nacional (
    id_censo         BIGINT          NOT NULL AUTO_INCREMENT PRIMARY KEY,
    dni              VARCHAR(20)     NOT NULL,
    nombres          VARCHAR(150)    NOT NULL,
    apellidos        VARCHAR(150)    NOT NULL,
    fecha_nacimiento DATE                NULL,
    sexo             ENUM('M','F','I')   NULL,    -- I = indefinido / sin dato
    -- Vínculos opcionales al catálogo geográfico nacional:
    codigo_departamento VARCHAR(4)        NULL,    -- vincula a sag_departamentos.codigo
    codigo_municipio    VARCHAR(8)        NULL,    -- vincula a sag_municipios.codigo
    codigo_aldea        VARCHAR(10)       NULL,    -- vincula a sag_aldeas.codigo
    etnia            VARCHAR(60)         NULL,
    -- Auditoría
    fuente           VARCHAR(80)         NULL,    -- ej. 'INE 2025', 'RNP 2024'
    actualizado_en   DATE                NULL,
    cargado_en       TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_censo_dni (dni),
    INDEX idx_censo_nombre (apellidos, nombres),
    INDEX idx_censo_muni (codigo_municipio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COMMENT='Censo nacional ~7M personas. Carga via CLI, no web.';

-- -----------------------------------------------------
-- 2. Agregar columna etnia a sag_beneficiarios (idempotente)
-- -----------------------------------------------------
DROP PROCEDURE IF EXISTS sp_018_add_col;
DELIMITER //
CREATE PROCEDURE sp_018_add_col(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl TEXT, IN idx_def TEXT)
BEGIN
    DECLARE col_exists INT DEFAULT 0;
    SELECT COUNT(*) INTO col_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND COLUMN_NAME = col;
    IF col_exists = 0 THEN
        SET @s = CONCAT('ALTER TABLE ', tbl, ' ADD COLUMN ', col, ' ', ddl);
        PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        IF idx_def IS NOT NULL AND idx_def <> '' THEN
            SET @s = CONCAT('ALTER TABLE ', tbl, ' ADD INDEX ', idx_def);
            PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        END IF;
    END IF;
END //
DELIMITER ;

CALL sp_018_add_col(
    'sag_beneficiarios',
    'etnia',
    'VARCHAR(60) NULL AFTER sexo',
    'idx_etnia (etnia)'
);

DROP PROCEDURE IF EXISTS sp_018_add_col;

-- -----------------------------------------------------
-- VERIFICACION (manual)
-- -----------------------------------------------------
-- SHOW CREATE TABLE sag_censo_nacional;
-- SELECT COLUMN_NAME FROM information_schema.COLUMNS
--  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sag_beneficiarios' AND COLUMN_NAME = 'etnia';
-- -- Esperado: 1 fila con etnia.

-- =====================================================
-- FIN MIGRACION 018
-- =====================================================
