-- Migración 021: cargas y control de combustible de Flota Vehicular.

CREATE TABLE IF NOT EXISTS sag_flota_combustible (
    id_carga        INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto     INT NOT NULL,
    id_vehiculo     INT NOT NULL,
    fecha           DATE NOT NULL,
    numero_vale     VARCHAR(40),
    conductor       VARCHAR(120),
    galones         DECIMAL(10,2) NOT NULL,
    precio_galon    DECIMAL(10,2) NOT NULL DEFAULT 0,
    monto           DECIMAL(12,2) NOT NULL DEFAULT 0,
    km_odometro     INT NOT NULL,
    estacion        VARCHAR(120),
    numero_factura  VARCHAR(60),
    observaciones   TEXT,
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_flota_combustible_proyecto_fecha (id_proyecto, fecha),
    INDEX idx_flota_combustible_vehiculo (id_vehiculo),
    CONSTRAINT fk_flota_combustible_proyecto
        FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos (id_proyecto)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_flota_combustible_vehiculo
        FOREIGN KEY (id_proyecto, id_vehiculo)
        REFERENCES sag_flota_vehiculos (id_proyecto, id_vehiculo)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cargas de combustible y lectura de odómetro por vehículo';
