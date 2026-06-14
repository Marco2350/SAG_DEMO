-- =====================================================
--  MIGRACION 013 — Componentes del programa FPROG 2026
--  SAG Honduras Sin Hambre · Junio 2026
--
--  Crea la tabla sag_fp_componentes — la unidad estructural
--  central del programa "Fortalecimiento de Programas y
--  Proyectos SAG 2026" (id_proyecto = 4 en config/app.php).
--
--  El programa se compone de 7 componentes con:
--    - Presupuesto asignado y ejecutado
--    - Meta cuantitativa con unidad de medida
--    - Avance acumulado
--    - Responsable, fechas, estado, medio de verificación
--
--  Patrón SAG_DEMO:
--    - Aislamiento por id_proyecto (FK a sag_proyectos)
--    - Soft-delete vía columna activo
--    - Auditoría con created_by, created_at, updated_at
--    - Índices en columnas de filtro frecuente
--    - UNIQUE compuesto (id_proyecto, numero_romano) para
--      evitar duplicar la posición del componente dentro del PIP
--
--  APLICACION (idempotente — usa CREATE TABLE IF NOT EXISTS):
--    USE mddesarr_sag;
--    SOURCE migracion_013_fp_componentes.sql;
--
--  ROLLBACK manual (sólo si no hay datos cargados):
--    DROP TABLE IF EXISTS sag_fp_componentes;
--    -- NO destructiva si la tabla tiene datos en producción;
--    -- coordinar con auditoría antes de DROP.
-- =====================================================

CREATE TABLE IF NOT EXISTS sag_fp_componentes (
    id_componente         INT             NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_proyecto           INT             NOT NULL,
    -- Identificación del componente
    numero_romano         VARCHAR(8)          NULL,     -- 'I', 'II', ..., 'VII'
    codigo                VARCHAR(40)         NULL,     -- código interno opcional
    nombre                VARCHAR(200)    NOT NULL,
    descripcion           TEXT                NULL,
    categoria             ENUM('agricola','pecuario','transversal','infraestructura','otro')
                          NOT NULL DEFAULT 'otro',
    -- Financiero
    presupuesto_asignado  DECIMAL(14,2)   NOT NULL DEFAULT 0,
    presupuesto_ejecutado DECIMAL(14,2)   NOT NULL DEFAULT 0,
    moneda                VARCHAR(8)      NOT NULL DEFAULT 'HNL',
    -- Meta física
    meta_unidad           VARCHAR(80)         NULL,     -- 'Ha', 'productores', 'm²', etc.
    meta_valor            DECIMAL(14,4)   NOT NULL DEFAULT 0,
    avance_valor          DECIMAL(14,4)   NOT NULL DEFAULT 0,
    medio_verificacion    VARCHAR(255)        NULL,
    -- Operativo
    fecha_inicio          DATE                NULL,
    fecha_fin             DATE                NULL,
    responsable           VARCHAR(200)        NULL,
    estado                ENUM('planificado','en_ejecucion','completado','suspendido','cancelado')
                          NOT NULL DEFAULT 'planificado',
    observaciones         TEXT                NULL,
    -- Soft-delete + auditoría
    activo                TINYINT(1)      NOT NULL DEFAULT 1,
    created_by            INT                 NULL,
    created_at            TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    -- Aislamiento: un mismo numero_romano no se repite dentro del proyecto
    UNIQUE KEY uk_comp_proy_num   (id_proyecto, numero_romano),
    INDEX idx_comp_proyecto       (id_proyecto),
    INDEX idx_comp_estado         (estado),
    INDEX idx_comp_categoria      (categoria),
    INDEX idx_comp_activo         (activo),
    INDEX idx_comp_filtro         (id_proyecto, activo, estado),
    CONSTRAINT fk_comp_proyecto
        FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_comp_creador
        FOREIGN KEY (created_by) REFERENCES sag_usuarios(id_usuario)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
--  FIN MIGRACION 013
-- =====================================================
