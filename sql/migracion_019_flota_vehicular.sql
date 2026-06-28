-- ═══════════════════════════════════════════════════════════════════
-- Migración 019: Módulo Flota Vehicular
-- Fecha: 2026-06-27
-- Descripción: 3 tablas para gestión de vehículos, viajes y mantenimientos.
--              Multitenancy por id_proyecto. Sin catálogos extra (van como
--              constantes en config/app.php para simplicidad).
-- ═══════════════════════════════════════════════════════════════════

-- ── Tabla 1: VEHÍCULOS ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_flota_vehiculos (
    id_vehiculo         INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto         INT NOT NULL,
    placa               VARCHAR(20) NOT NULL,
    marca               VARCHAR(60),
    modelo              VARCHAR(60),
    anio                SMALLINT,
    color               VARCHAR(30),
    vin                 VARCHAR(40),
    tipo_combustible    VARCHAR(20) NOT NULL DEFAULT 'gasolina',
        -- ENUM lógico: gasolina, diesel, glp, electrico, hibrido (validado en PHP)
    km_actual           INT NOT NULL DEFAULT 0,
    estado              VARCHAR(20) NOT NULL DEFAULT 'activo',
        -- ENUM lógico: activo, taller, baja
    vence_seguro        DATE NULL,
    observaciones       TEXT,
    activo              TINYINT(1) NOT NULL DEFAULT 1,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_placa_proyecto (id_proyecto, placa),
    UNIQUE KEY uq_flota_vehiculo_proyecto (id_proyecto, id_vehiculo),
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_estado   (estado),
    CONSTRAINT fk_flota_vehiculo_proyecto
        FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos (id_proyecto)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Vehículos de la flota SAG por programa';

-- ── Tabla 2: VIAJES (asignaciones) ───────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_flota_viajes (
    id_viaje            INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto         INT NOT NULL,
    id_vehiculo         INT NOT NULL,
    fecha               DATE NOT NULL,
    conductor           VARCHAR(120) NOT NULL,
        -- Texto libre por simplicidad (no catálogo). Si después se necesita
        -- normalizar, se migra a sag_flota_conductores sin romper data.
    km_inicial          INT NOT NULL,
    km_final            INT NULL,
        -- NULL mientras el viaje esté en curso
    km_recorridos       INT GENERATED ALWAYS AS (km_final - km_inicial) STORED,
    destino             VARCHAR(160),
    proposito           VARCHAR(200),
    estado              VARCHAR(20) NOT NULL DEFAULT 'en_curso',
        -- ENUM lógico: en_curso, finalizado, cancelado
    observaciones       TEXT,
    activo              TINYINT(1) NOT NULL DEFAULT 1,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proyecto  (id_proyecto),
    INDEX idx_vehiculo  (id_vehiculo),
    INDEX idx_estado    (estado),
    INDEX idx_fecha     (fecha),
    CONSTRAINT fk_flota_viaje_proyecto
        FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos (id_proyecto)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_flota_viaje_vehiculo
        FOREIGN KEY (id_proyecto, id_vehiculo)
        REFERENCES sag_flota_vehiculos (id_proyecto, id_vehiculo)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Asignaciones / viajes con km inicial y final';

-- ── Tabla 3: MANTENIMIENTOS ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_flota_mantenimientos (
    id_mantenimiento    INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto         INT NOT NULL,
    id_vehiculo         INT NOT NULL,
    fecha               DATE NOT NULL,
    tipo                VARCHAR(40) NOT NULL,
        -- Clave del catálogo FLOTA_TIPOS_MANTENIMIENTO en config
        -- Ej: 'aceite', 'frenos', 'llantas', 'general'
    km_al_realizar      INT NOT NULL,
        -- Snapshot del km_actual al momento del servicio. Clave para
        -- predecir el próximo: si aceite es cada 5000 km y este se hizo
        -- a los 25000 → próximo a los 30000.
    costo               DECIMAL(12,2) DEFAULT 0,
    taller              VARCHAR(120),
    descripcion         TEXT,
    activo              TINYINT(1) NOT NULL DEFAULT 1,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_proyecto  (id_proyecto),
    INDEX idx_vehiculo  (id_vehiculo),
    INDEX idx_tipo      (tipo),
    INDEX idx_fecha     (fecha),
    CONSTRAINT fk_flota_manto_proyecto
        FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos (id_proyecto)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_flota_manto_vehiculo
        FOREIGN KEY (id_proyecto, id_vehiculo)
        REFERENCES sag_flota_vehiculos (id_proyecto, id_vehiculo)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historial de mantenimientos por vehículo';

-- ═══════════════════════════════════════════════════════════════════
-- FIN migración 019
-- ═══════════════════════════════════════════════════════════════════
