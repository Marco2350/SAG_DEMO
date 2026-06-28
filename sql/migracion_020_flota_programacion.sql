-- Migración 020: programación de mantenimiento por vehículo.
-- Se ejecuta sobre la misma base unificada configurada en DB_NAME.

CREATE TABLE IF NOT EXISTS sag_flota_programacion_mantenimiento (
    id_programacion INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto     INT NOT NULL,
    id_vehiculo     INT NOT NULL,
    tipo            VARCHAR(40) NOT NULL,
    intervalo_km    INT NOT NULL,
    proximo_km      INT NOT NULL,
    ultimo_km       INT NULL,
    activo          TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_flota_programacion (id_proyecto, id_vehiculo, tipo),
    INDEX idx_flota_programacion_proximo (id_proyecto, proximo_km),
    CONSTRAINT fk_flota_programacion_proyecto
        FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos (id_proyecto)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_flota_programacion_vehiculo
        FOREIGN KEY (id_proyecto, id_vehiculo)
        REFERENCES sag_flota_vehiculos (id_proyecto, id_vehiculo)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Próximo kilometraje por tipo de mantenimiento y vehículo';
