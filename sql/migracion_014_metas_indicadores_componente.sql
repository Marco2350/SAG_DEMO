-- =====================================================
--  MIGRACION 014 — Vincular Metas e Indicadores a Componentes FP
--  SAG Honduras Sin Hambre · Junio 2026
--
--  Agrega la columna `id_componente` (nullable) a:
--    - sag_metas
--    - sag_indicadores
--
--  Esto permite que cada meta o indicador opcionalmente cuelgue de
--  un componente del programa "Fortalecimiento de Programas y
--  Proyectos SAG 2026" (id_proyecto = 4). Para los PIPs (PIPC,
--  PIPG, PIPA) el campo queda en NULL.
--
--  El vínculo es opcional (ON DELETE SET NULL), no rompe metas
--  existentes; se valida en el controlador que el componente
--  pertenezca al proyecto activo (no se confía en IDs del cliente).
--
--  APLICACION (idempotente, no falla si las columnas ya existen):
--    USE mddesarr_sag;
--    SOURCE migracion_014_metas_indicadores_componente.sql;
--
--  ROLLBACK manual:
--    ALTER TABLE sag_metas DROP FOREIGN KEY fk_meta_componente;
--    ALTER TABLE sag_metas DROP COLUMN id_componente;
--    ALTER TABLE sag_indicadores DROP FOREIGN KEY fk_indicador_componente;
--    ALTER TABLE sag_indicadores DROP COLUMN id_componente;
-- =====================================================

-- ───────────────────────────────────────────────────────
--  Procedure idempotente: agrega columna sólo si no existe
-- ───────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_add_id_componente_if_missing;

DELIMITER //
CREATE PROCEDURE sp_add_id_componente_if_missing(IN tbl VARCHAR(64))
BEGIN
    DECLARE col_exists INT DEFAULT 0;
    SELECT COUNT(*) INTO col_exists
      FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = tbl
       AND COLUMN_NAME  = 'id_componente';

    IF col_exists = 0 THEN
        SET @ddl = CONCAT(
            'ALTER TABLE ', tbl, ' ',
            'ADD COLUMN id_componente INT NULL AFTER id_proyecto, ',
            'ADD INDEX idx_', tbl, '_componente (id_componente)'
        );
        PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

CALL sp_add_id_componente_if_missing('sag_metas');
CALL sp_add_id_componente_if_missing('sag_indicadores');

DROP PROCEDURE IF EXISTS sp_add_id_componente_if_missing;

-- ───────────────────────────────────────────────────────
--  FKs (sólo se crean si no existen previamente)
-- ───────────────────────────────────────────────────────
DROP PROCEDURE IF EXISTS sp_add_fk_componente_if_missing;

DELIMITER //
CREATE PROCEDURE sp_add_fk_componente_if_missing(IN tbl VARCHAR(64), IN fk_name VARCHAR(64))
BEGIN
    DECLARE fk_exists INT DEFAULT 0;
    SELECT COUNT(*) INTO fk_exists
      FROM information_schema.TABLE_CONSTRAINTS
     WHERE TABLE_SCHEMA    = DATABASE()
       AND TABLE_NAME      = tbl
       AND CONSTRAINT_NAME = fk_name
       AND CONSTRAINT_TYPE = 'FOREIGN KEY';

    IF fk_exists = 0 THEN
        SET @ddl = CONCAT(
            'ALTER TABLE ', tbl, ' ',
            'ADD CONSTRAINT ', fk_name, ' ',
            'FOREIGN KEY (id_componente) REFERENCES sag_fp_componentes(id_componente) ',
            'ON UPDATE CASCADE ON DELETE SET NULL'
        );
        PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

CALL sp_add_fk_componente_if_missing('sag_metas',       'fk_meta_componente');
CALL sp_add_fk_componente_if_missing('sag_indicadores', 'fk_indicador_componente');

DROP PROCEDURE IF EXISTS sp_add_fk_componente_if_missing;

-- =====================================================
--  FIN MIGRACION 014
-- =====================================================
