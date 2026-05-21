-- =====================================================
--  SAG PROGRAMAS — Base de datos principal
--  sag_main: Usuarios, Roles, Sesiones
--  Ejecutar en phpMyAdmin PRIMERO
-- =====================================================

CREATE DATABASE IF NOT EXISTS sag_main
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sag_main;

-- ─────────────────────────────────────────────────
--  ROLES
-- ─────────────────────────────────────────────────
CREATE TABLE sag_roles (
    id_rol   INT AUTO_INCREMENT PRIMARY KEY,
    nombre   VARCHAR(80)  NOT NULL,
    slug     VARCHAR(40)  NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO sag_roles (nombre, slug) VALUES
('Administrador',       'admin'),
('Supervisor',          'supervisor'),
('Técnico de Campo',    'tecnico'),
('Digitador',           'digitador');

-- ─────────────────────────────────────────────────
--  USUARIOS
-- ─────────────────────────────────────────────────
CREATE TABLE sag_usuarios (
    id_usuario    INT AUTO_INCREMENT PRIMARY KEY,
    id_rol        INT          NOT NULL DEFAULT 1,
    nombre        VARCHAR(100) NOT NULL,
    apellido      VARCHAR(100) NOT NULL,
    username      VARCHAR(60)  NOT NULL UNIQUE,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    activo        TINYINT(1)   NOT NULL DEFAULT 1,
    ultimo_acceso DATETIME,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rol) REFERENCES sag_roles(id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin por defecto — contraseña: Admin123!
INSERT INTO sag_usuarios (id_rol, nombre, apellido, username, email, password_hash) VALUES
(1, 'Administrador', 'Sistema', 'admin', 'admin@sag.gob.hn',
 '$2y$12$Tr9Yxy0C5hv8np9UXSbS5uAkLoC5W8/unX/X60bD5MLrmNpdAmpDi');

-- ─────────────────────────────────────────────────
--  LOG DE ACTIVIDAD (global)
-- ─────────────────────────────────────────────────
CREATE TABLE sag_logs (
    id_log     INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    programa   VARCHAR(20),          -- pipc | pipg | pipa | NULL
    accion     VARCHAR(100) NOT NULL,
    modulo     VARCHAR(80),
    detalle    TEXT,
    ip         VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (id_usuario),
    INDEX idx_programa (programa),
    FOREIGN KEY (id_usuario) REFERENCES sag_usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
