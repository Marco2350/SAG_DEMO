-- =====================================================================
--  SAG PROGRAMAS — Esquema UNIFICADO para hosting (base ya existente)
--  Base destino: mddesarr_sag  (NO ejecuta CREATE/DROP DATABASE)
--  Pegar en phpMyAdmin con la base mddesarr_sag seleccionada, o:
--     mysql -u USUARIO -p mddesarr_sag < sql/bd_mddesarr_sag.sql
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

USE mddesarr_sag;

-- Limpieza idempotente (permite re-ejecutar el script)
DROP TABLE IF EXISTS sag_entregas_lineas;
DROP TABLE IF EXISTS sag_entregas;
DROP TABLE IF EXISTS sag_inventario_movimientos;
DROP TABLE IF EXISTS sag_inventario_lineas;
DROP TABLE IF EXISTS sag_inventario_cronogramas;
DROP TABLE IF EXISTS sag_inventario_productos;
DROP TABLE IF EXISTS sag_bodegas;
DROP TABLE IF EXISTS sag_proveedores;
DROP TABLE IF EXISTS sag_documentos_programa;
DROP TABLE IF EXISTS sag_gastos_varios;
DROP TABLE IF EXISTS sag_solicitudes_viaticos;
DROP TABLE IF EXISTS sag_compras;
DROP TABLE IF EXISTS sag_modificaciones_presupuesto;
DROP TABLE IF EXISTS sag_lineas_presupuestarias;
DROP TABLE IF EXISTS sag_presupuestos;
DROP TABLE IF EXISTS sag_at_resultados;
DROP TABLE IF EXISTS sag_asistencias_tecnicas;
DROP TABLE IF EXISTS sag_cap_participantes;
DROP TABLE IF EXISTS sag_capacitaciones;
DROP TABLE IF EXISTS sag_beneficiarios;
DROP TABLE IF EXISTS sag_organizaciones;
DROP TABLE IF EXISTS sag_tecnicos;
DROP TABLE IF EXISTS sag_cultivos;
DROP TABLE IF EXISTS sag_tipo_at;
DROP TABLE IF EXISTS sag_subtemas;
DROP TABLE IF EXISTS sag_temas;
DROP TABLE IF EXISTS sag_municipios;
DROP TABLE IF EXISTS sag_departamentos;
DROP TABLE IF EXISTS sag_proyectos;
DROP TABLE IF EXISTS sag_logs;
DROP TABLE IF EXISTS sag_usuarios;
DROP TABLE IF EXISTS sag_roles;

-- =====================================================================
--  1) AUTENTICACIÓN GLOBAL
-- =====================================================================
CREATE TABLE sag_roles (
    id_rol   INT AUTO_INCREMENT PRIMARY KEY,
    nombre   VARCHAR(80) NOT NULL,
    slug     VARCHAR(40) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_rol) REFERENCES sag_roles(id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_logs (
    id_log     INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    programa   VARCHAR(20),
    accion     VARCHAR(100) NOT NULL,
    modulo     VARCHAR(80),
    detalle    TEXT,
    ip         VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario  (id_usuario),
    INDEX idx_programa (programa),
    FOREIGN KEY (id_usuario) REFERENCES sag_usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
--  2) CATÁLOGO DE PROYECTOS (discrimina cada programa)
-- =====================================================================
CREATE TABLE sag_proyectos (
    id_proyecto INT AUTO_INCREMENT PRIMARY KEY,
    codigo      VARCHAR(20)  NOT NULL UNIQUE,   -- 'pipc','pipg','pipa'
    nombre      VARCHAR(200) NOT NULL,
    sigla       VARCHAR(20)  NOT NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
--  3) CATÁLOGOS GEOGRÁFICOS
-- =====================================================================
CREATE TABLE sag_departamentos (
    id_departamento INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto     INT          NOT NULL,
    nombre          VARCHAR(100) NOT NULL,
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    INDEX idx_proyecto (id_proyecto),
    FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_municipios (
    id_municipio    INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto     INT          NOT NULL,
    id_departamento INT          NOT NULL,
    nombre          VARCHAR(100) NOT NULL,
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_dep (id_departamento),
    FOREIGN KEY (id_proyecto)     REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_departamento) REFERENCES sag_departamentos(id_departamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
--  4) CATÁLOGOS TÉCNICOS
-- =====================================================================
CREATE TABLE sag_temas (
    id_tema     INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto INT          NOT NULL,
    nombre      VARCHAR(200) NOT NULL,
    tipo        ENUM('capacitacion','at','ambos') NOT NULL DEFAULT 'ambos',
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    INDEX idx_proyecto (id_proyecto),
    FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_subtemas (
    id_subtema  INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto INT          NOT NULL,
    id_tema     INT          NOT NULL,
    nombre      VARCHAR(200) NOT NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_tema (id_tema),
    FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_tema)     REFERENCES sag_temas(id_tema) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_tipo_at (
    id_tipo_at  INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto INT          NOT NULL,
    nombre      VARCHAR(100) NOT NULL,
    icono       VARCHAR(50),
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    INDEX idx_proyecto (id_proyecto),
    FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_cultivos (
    id_cultivo  INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto INT          NOT NULL,
    nombre      VARCHAR(100) NOT NULL,
    tipo        ENUM('cultivo','ganaderia','otro') NOT NULL DEFAULT 'cultivo',
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    INDEX idx_proyecto (id_proyecto),
    FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
--  5) TÉCNICOS
-- =====================================================================
CREATE TABLE sag_tecnicos (
    id_tecnico      INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto     INT          NOT NULL,
    nombre_completo VARCHAR(200) NOT NULL,
    especialidad    VARCHAR(100),
    id_departamento INT,
    telefono        VARCHAR(20),
    email           VARCHAR(150),
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    FOREIGN KEY (id_proyecto)     REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_departamento) REFERENCES sag_departamentos(id_departamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
--  6) ORGANIZACIONES
-- =====================================================================
CREATE TABLE sag_organizaciones (
    id_organizacion INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto     INT          NOT NULL,
    nombre          VARCHAR(200) NOT NULL,
    tipo            ENUM('cooperativa','asociacion','grupo','empresa','otro') NOT NULL DEFAULT 'cooperativa',
    id_departamento INT          NOT NULL,
    id_municipio    INT          NOT NULL,
    aldea           VARCHAR(200),
    representante   VARCHAR(200),
    telefono        VARCHAR(20),
    email           VARCHAR(150),
    estado          ENUM('activa','pendiente','inactiva') NOT NULL DEFAULT 'pendiente',
    fecha_registro  DATE,
    coordenadas     VARCHAR(60),
    created_by      INT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_depto (id_departamento),
    FOREIGN KEY (id_proyecto)     REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_departamento) REFERENCES sag_departamentos(id_departamento),
    FOREIGN KEY (id_municipio)    REFERENCES sag_municipios(id_municipio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
--  7) BENEFICIARIOS
-- =====================================================================
CREATE TABLE sag_beneficiarios (
    id_beneficiario   INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto       INT          NOT NULL,
    id_organizacion   INT,
    id_departamento   INT          NOT NULL,
    id_municipio      INT          NOT NULL,
    aldea             VARCHAR(200),
    nombre            VARCHAR(100) NOT NULL,
    apellido          VARCHAR(100) NOT NULL,
    dni               VARCHAR(15),
    fecha_nacimiento  DATE,
    sexo              ENUM('M','F') NOT NULL,
    telefono          VARCHAR(20),
    email             VARCHAR(150),
    area_productiva   DECIMAL(10,2),
    cultivo_principal VARCHAR(100),
    coordenadas       VARCHAR(60),
    estado            ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    created_by        INT,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_depto (id_departamento),
    INDEX idx_org   (id_organizacion),
    UNIQUE KEY uq_proyecto_dni (id_proyecto, dni),
    FOREIGN KEY (id_proyecto)     REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_departamento) REFERENCES sag_departamentos(id_departamento),
    FOREIGN KEY (id_municipio)    REFERENCES sag_municipios(id_municipio),
    FOREIGN KEY (id_organizacion) REFERENCES sag_organizaciones(id_organizacion) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
--  8) CAPACITACIONES (incluye soft-delete: columna activo)
-- =====================================================================
CREATE TABLE sag_capacitaciones (
    id_capacitacion    INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto        INT          NOT NULL,
    id_departamento    INT          NOT NULL,
    id_municipio       INT          NOT NULL,
    aldea              VARCHAR(200),
    lugar_especifico   VARCHAR(300),
    id_tema            INT          NOT NULL,
    id_subtema         INT,
    descripcion        TEXT,
    fecha_capacitacion DATE         NOT NULL,
    duracion_horas     DECIMAL(4,1),
    id_tecnico         INT          NOT NULL,
    num_participantes  INT          NOT NULL DEFAULT 0,
    estado             ENUM('borrador','finalizado') NOT NULL DEFAULT 'borrador',
    activo             TINYINT(1)   NOT NULL DEFAULT 1,
    created_by         INT,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_fecha  (fecha_capacitacion),
    INDEX idx_depto  (id_departamento),
    INDEX idx_activo (activo),
    FOREIGN KEY (id_proyecto)     REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_departamento) REFERENCES sag_departamentos(id_departamento),
    FOREIGN KEY (id_municipio)    REFERENCES sag_municipios(id_municipio),
    FOREIGN KEY (id_tema)         REFERENCES sag_temas(id_tema),
    FOREIGN KEY (id_subtema)      REFERENCES sag_subtemas(id_subtema),
    FOREIGN KEY (id_tecnico)      REFERENCES sag_tecnicos(id_tecnico)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_cap_participantes (
    id_participante INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto     INT          NOT NULL,
    id_capacitacion INT          NOT NULL,
    nombre          VARCHAR(100) NOT NULL,
    apellido        VARCHAR(100),
    dni             VARCHAR(15),
    edad            TINYINT UNSIGNED,
    sexo            ENUM('M','F'),
    id_organizacion INT,
    telefono        VARCHAR(20),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_cap (id_capacitacion),
    FOREIGN KEY (id_proyecto)     REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_capacitacion) REFERENCES sag_capacitaciones(id_capacitacion) ON DELETE CASCADE,
    FOREIGN KEY (id_organizacion) REFERENCES sag_organizaciones(id_organizacion) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
--  9) ASISTENCIA TÉCNICA (incluye soft-delete)
-- =====================================================================
CREATE TABLE sag_asistencias_tecnicas (
    id_at              INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto        INT          NOT NULL,
    id_tipo_at         INT          NOT NULL,
    id_departamento    INT          NOT NULL,
    id_municipio       INT          NOT NULL,
    aldea              VARCHAR(200),
    id_tema            INT          NOT NULL,
    id_subtema         INT,
    id_cultivo         INT,
    descripcion        TEXT,
    fecha_visita       DATE         NOT NULL,
    hora_visita        TIME,
    duracion           VARCHAR(50),
    id_tecnico         INT          NOT NULL,
    productor_nombre   VARCHAR(200) NOT NULL,
    productor_apellido VARCHAR(200),
    productor_dni      VARCHAR(15),
    productor_edad     TINYINT UNSIGNED,
    productor_sexo     ENUM('M','F'),
    id_organizacion    INT,
    productor_telefono VARCHAR(20),
    area_productiva    DECIMAL(10,2),
    prox_visita        DATE,
    observaciones      TEXT,
    estado             ENUM('borrador','finalizado') NOT NULL DEFAULT 'borrador',
    activo             TINYINT(1)   NOT NULL DEFAULT 1,
    created_by         INT,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_fecha  (fecha_visita),
    INDEX idx_depto  (id_departamento),
    INDEX idx_activo (activo),
    FOREIGN KEY (id_proyecto)     REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_tipo_at)      REFERENCES sag_tipo_at(id_tipo_at),
    FOREIGN KEY (id_departamento) REFERENCES sag_departamentos(id_departamento),
    FOREIGN KEY (id_municipio)    REFERENCES sag_municipios(id_municipio),
    FOREIGN KEY (id_tema)         REFERENCES sag_temas(id_tema),
    FOREIGN KEY (id_subtema)      REFERENCES sag_subtemas(id_subtema),
    FOREIGN KEY (id_tecnico)      REFERENCES sag_tecnicos(id_tecnico),
    FOREIGN KEY (id_cultivo)      REFERENCES sag_cultivos(id_cultivo),
    FOREIGN KEY (id_organizacion) REFERENCES sag_organizaciones(id_organizacion) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_at_resultados (
    id_resultado INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto  INT          NOT NULL,
    id_at        INT          NOT NULL,
    resultado    VARCHAR(200) NOT NULL,
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_at (id_at),
    FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_at)       REFERENCES sag_asistencias_tecnicas(id_at) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
--  10) MÓDULO PRESUPUESTO
-- =====================================================================
CREATE TABLE sag_presupuestos (
    id_presupuesto      INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto         INT NOT NULL,
    nombre              VARCHAR(200) NOT NULL,
    descripcion         TEXT,
    anio                INT NOT NULL,
    moneda              ENUM('HNL','USD') DEFAULT 'HNL',
    tipo_cambio         DECIMAL(10,4) DEFAULT 1.0000,
    monto_total         DECIMAL(15,2) NOT NULL DEFAULT 0,
    estado              ENUM('borrador','activo','cerrado') DEFAULT 'borrador',
    documento_respaldo  VARCHAR(255),
    id_usuario_autoriza INT,
    fecha_autorizacion  DATETIME,
    observaciones       TEXT,
    created_by          INT,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_lineas_presupuestarias (
    id_linea       INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto    INT NOT NULL,
    id_presupuesto INT NOT NULL,
    codigo         VARCHAR(50),
    nombre         VARCHAR(200) NOT NULL,
    descripcion    TEXT,
    tipo           ENUM('compras','viaticos','gastos','personal','equipos','servicios','otro') DEFAULT 'otro',
    monto_aprobado DECIMAL(15,2) DEFAULT 0,
    activo         TINYINT(1) DEFAULT 1,
    orden          INT DEFAULT 0,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    FOREIGN KEY (id_proyecto)    REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_presupuesto) REFERENCES sag_presupuestos(id_presupuesto) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_modificaciones_presupuesto (
    id_modificacion    INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto        INT NOT NULL,
    id_linea           INT NOT NULL,
    tipo               ENUM('ajuste','ampliacion','reduccion') NOT NULL,
    monto_anterior     DECIMAL(15,2) NOT NULL,
    monto_nuevo        DECIMAL(15,2) NOT NULL,
    justificacion      TEXT NOT NULL,
    documento_respaldo VARCHAR(255),
    id_usuario         INT,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_linea)    REFERENCES sag_lineas_presupuestarias(id_linea) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_compras (
    id_compra           INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto         INT NOT NULL,
    numero_solicitud    VARCHAR(50),
    id_linea            INT,
    descripcion         TEXT NOT NULL,
    justificacion       TEXT,
    monto_estimado      DECIMAL(15,2) DEFAULT 0,
    monto_adjudicado    DECIMAL(15,2) DEFAULT 0,
    moneda              ENUM('HNL','USD') DEFAULT 'HNL',
    estado              ENUM('borrador','solicitada','cotizando','aprobada','ejecutada','anulada') DEFAULT 'borrador',
    proveedor           VARCHAR(200),
    numero_factura      VARCHAR(100),
    fecha_solicitud     DATE,
    fecha_aprobacion    DATE,
    fecha_ejecucion     DATE,
    archivo_solicitud   VARCHAR(255),
    archivo_cotizacion  VARCHAR(255),
    archivo_factura     VARCHAR(255),
    id_usuario_solicita INT NOT NULL,
    id_usuario_aprueba  INT,
    observaciones       TEXT,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_linea)    REFERENCES sag_lineas_presupuestarias(id_linea) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_solicitudes_viaticos (
    id_solicitud          INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto           INT NOT NULL,
    numero_solicitud      VARCHAR(50),
    id_linea              INT,
    id_solicitante        INT NOT NULL,
    destino               VARCHAR(200) NOT NULL,
    objetivo              TEXT NOT NULL,
    fecha_salida          DATE NOT NULL,
    fecha_retorno         DATE NOT NULL,
    dias                  INT DEFAULT 1,
    monto_solicitado      DECIMAL(15,2) NOT NULL,
    moneda                ENUM('HNL','USD') DEFAULT 'HNL',
    hospedaje             DECIMAL(15,2) DEFAULT 0,
    alimentacion          DECIMAL(15,2) DEFAULT 0,
    transporte            DECIMAL(15,2) DEFAULT 0,
    otros                 DECIMAL(15,2) DEFAULT 0,
    estado                ENUM('pendiente','visto_bueno','aprobada','rechazada','liquidada') DEFAULT 'pendiente',
    id_jefe               INT,
    fecha_visto_bueno     DATETIME,
    observacion_jefe      TEXT,
    id_autoridad          INT,
    fecha_aprobacion      DATETIME,
    observacion_autoridad TEXT,
    monto_ejecutado       DECIMAL(15,2),
    fecha_liquidacion     DATE,
    archivo_liquidacion   VARCHAR(255),
    archivo_solicitud     VARCHAR(255),
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_linea)    REFERENCES sag_lineas_presupuestarias(id_linea) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_gastos_varios (
    id_gasto         INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto      INT NOT NULL,
    id_linea         INT,
    descripcion      VARCHAR(300) NOT NULL,
    tipo             ENUM('operativo','administrativo','logistico','otro') DEFAULT 'otro',
    monto            DECIMAL(15,2) NOT NULL,
    moneda           ENUM('HNL','USD') DEFAULT 'HNL',
    fecha_gasto      DATE NOT NULL,
    beneficiario     VARCHAR(200),
    numero_documento VARCHAR(100),
    archivo          VARCHAR(255),
    estado           ENUM('registrado','aprobado','rechazado') DEFAULT 'registrado',
    id_usuario       INT NOT NULL,
    observaciones    TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_linea)    REFERENCES sag_lineas_presupuestarias(id_linea) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_documentos_programa (
    id_documento    INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto     INT NOT NULL,
    tipo            ENUM('carta_entendimiento','perfil_programa','acuerdo','convenio','resolucion','acta','otro') NOT NULL,
    nombre          VARCHAR(200) NOT NULL,
    descripcion     TEXT,
    archivo         VARCHAR(255) NOT NULL,
    tamano_bytes    INT,
    fecha_documento DATE,
    vigente         TINYINT(1) DEFAULT 1,
    activo          TINYINT(1) DEFAULT 1,
    created_by      INT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
--  11) MÓDULO INVENTARIOS + ENTREGAS
-- =====================================================================
CREATE TABLE sag_proveedores (
    id_proveedor INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto  INT          NOT NULL,
    nombre       VARCHAR(200) NOT NULL,
    rtn          VARCHAR(20),
    contacto     VARCHAR(150),
    telefono     VARCHAR(30),
    email        VARCHAR(150),
    direccion    VARCHAR(300),
    activo       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_activo (activo),
    INDEX idx_rtn    (rtn),
    FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_bodegas (
    id_bodega       INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto     INT          NOT NULL,
    codigo          VARCHAR(20)  NOT NULL,
    nombre          VARCHAR(200) NOT NULL,
    id_departamento INT,
    id_municipio    INT,
    direccion       VARCHAR(300),
    responsable     VARCHAR(200),
    telefono        VARCHAR(30),
    capacidad       DECIMAL(12,2),
    coordenadas     VARCHAR(60),
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_dep    (id_departamento),
    INDEX idx_activo (activo),
    UNIQUE KEY uq_proyecto_codigo (id_proyecto, codigo),
    FOREIGN KEY (id_proyecto)     REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_departamento) REFERENCES sag_departamentos(id_departamento),
    FOREIGN KEY (id_municipio)    REFERENCES sag_municipios(id_municipio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_inventario_productos (
    id_producto     INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto     INT          NOT NULL,
    codigo          VARCHAR(40)  NOT NULL,
    nombre          VARCHAR(200) NOT NULL,
    descripcion     TEXT,
    unidad          VARCHAR(30)  NOT NULL DEFAULT 'unidad',
    presentacion    VARCHAR(80),
    categoria       VARCHAR(80),
    precio_unitario DECIMAL(12,2),
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_activo (activo),
    INDEX idx_cat    (categoria),
    UNIQUE KEY uq_proyecto_codigo (id_proyecto, codigo),
    FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_inventario_cronogramas (
    id_cronograma    INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto      INT          NOT NULL,
    codigo           VARCHAR(40)  NOT NULL,
    id_proveedor     INT          NOT NULL,
    num_contrato     VARCHAR(80),
    num_orden_compra VARCHAR(80),
    descripcion      VARCHAR(300),
    monto_total      DECIMAL(14,2),
    fecha_firma      DATE,
    fecha_inicio     DATE,
    fecha_fin        DATE,
    archivo_contrato VARCHAR(255),
    estado           ENUM('borrador','vigente','finalizado','cancelado') NOT NULL DEFAULT 'borrador',
    observaciones    TEXT,
    created_by       INT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_prov   (id_proveedor),
    INDEX idx_estado (estado),
    INDEX idx_inicio (fecha_inicio),
    UNIQUE KEY uq_proyecto_codigo (id_proyecto, codigo),
    FOREIGN KEY (id_proyecto)  REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_proveedor) REFERENCES sag_proveedores(id_proveedor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_inventario_lineas (
    id_linea              INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto           INT          NOT NULL,
    id_cronograma         INT          NOT NULL,
    id_producto           INT          NOT NULL,
    id_bodega             INT          NOT NULL,
    cantidad_programada   DECIMAL(12,2) NOT NULL,
    cantidad_recibida     DECIMAL(12,2) NOT NULL DEFAULT 0,
    fecha_programada      DATE         NOT NULL,
    fecha_recibida        DATE,
    responsable_recepcion VARCHAR(200),
    estado                ENUM('pendiente','parcial','recibida','atrasada','cancelada') NOT NULL DEFAULT 'pendiente',
    observaciones         TEXT,
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_cron   (id_cronograma),
    INDEX idx_prod   (id_producto),
    INDEX idx_bodega (id_bodega),
    INDEX idx_estado (estado),
    INDEX idx_fecha  (fecha_programada),
    FOREIGN KEY (id_proyecto)   REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_cronograma) REFERENCES sag_inventario_cronogramas(id_cronograma) ON DELETE CASCADE,
    FOREIGN KEY (id_producto)   REFERENCES sag_inventario_productos(id_producto),
    FOREIGN KEY (id_bodega)     REFERENCES sag_bodegas(id_bodega)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_inventario_movimientos (
    id_movimiento    INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto      INT          NOT NULL,
    fecha            DATETIME     NOT NULL,
    tipo             ENUM('entrada','salida','ajuste') NOT NULL,
    id_producto      INT          NOT NULL,
    id_bodega        INT          NOT NULL,
    cantidad         DECIMAL(12,2) NOT NULL,
    saldo_resultante DECIMAL(12,2),
    origen           VARCHAR(50)  NOT NULL,
    id_origen        INT,
    descripcion      VARCHAR(300),
    created_by       INT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_fecha  (fecha),
    INDEX idx_prod   (id_producto),
    INDEX idx_bodega (id_bodega),
    INDEX idx_origen (origen, id_origen),
    FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_producto) REFERENCES sag_inventario_productos(id_producto),
    FOREIGN KEY (id_bodega)   REFERENCES sag_bodegas(id_bodega)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_entregas (
    id_entrega            INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto           INT          NOT NULL,
    codigo                VARCHAR(40)  NOT NULL,
    kobo_submission_id    VARCHAR(100),
    trazaragro_id         VARCHAR(100),
    fuente                ENUM('kobo','trazaragro','manual') NOT NULL DEFAULT 'manual',
    id_beneficiario       INT,
    dni                   VARCHAR(15),
    nombre_beneficiario   VARCHAR(200),
    sexo                  ENUM('M','F'),
    id_departamento       INT,
    id_municipio          INT,
    aldea                 VARCHAR(200),
    id_bodega             INT          NOT NULL,
    id_tecnico            INT,
    fecha_entrega         DATE         NOT NULL,
    hora_entrega          TIME,
    gps_lat               DECIMAL(10,6),
    gps_lon               DECIMAL(10,6),
    gps_precision         INT,
    foto_dni              VARCHAR(255),
    foto_entrega          VARCHAR(255),
    firma_beneficiario    VARCHAR(255),
    firma_tecnico         VARCHAR(255),
    telefono_actualizado  VARCHAR(30),
    direccion_actualizada VARCHAR(300),
    observaciones         TEXT,
    estado                ENUM('pendiente_revision','aprobada','con_alerta','rechazada','anulada')
                          NOT NULL DEFAULT 'pendiente_revision',
    alerta_motivo         VARCHAR(300),
    revisado_por          VARCHAR(200),
    fecha_revision        DATETIME,
    stock_descontado      TINYINT(1)   NOT NULL DEFAULT 0,
    synced_at             DATETIME,
    created_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_fecha  (fecha_entrega),
    INDEX idx_estado (estado),
    INDEX idx_bodega (id_bodega),
    INDEX idx_dni    (dni),
    INDEX idx_kobo   (kobo_submission_id),
    INDEX idx_traza  (trazaragro_id),
    UNIQUE KEY uq_proyecto_codigo (id_proyecto, codigo),
    FOREIGN KEY (id_proyecto)     REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_bodega)       REFERENCES sag_bodegas(id_bodega),
    FOREIGN KEY (id_tecnico)      REFERENCES sag_tecnicos(id_tecnico),
    FOREIGN KEY (id_beneficiario) REFERENCES sag_beneficiarios(id_beneficiario) ON DELETE SET NULL,
    FOREIGN KEY (id_departamento) REFERENCES sag_departamentos(id_departamento),
    FOREIGN KEY (id_municipio)    REFERENCES sag_municipios(id_municipio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_entregas_lineas (
    id_entrega_linea   INT AUTO_INCREMENT PRIMARY KEY,
    id_proyecto        INT          NOT NULL,
    id_entrega         INT          NOT NULL,
    id_producto        INT          NOT NULL,
    cantidad_asignada  DECIMAL(12,2) NOT NULL DEFAULT 0,
    cantidad_entregada DECIMAL(12,2) NOT NULL DEFAULT 0,
    razon_diferencia   VARCHAR(200),
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_proyecto (id_proyecto),
    INDEX idx_entrega  (id_entrega),
    INDEX idx_producto (id_producto),
    FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto),
    FOREIGN KEY (id_entrega)  REFERENCES sag_entregas(id_entrega) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES sag_inventario_productos(id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
--  12) SEED — proyectos base
-- =====================================================================
INSERT INTO sag_proyectos (id_proyecto, codigo, nombre, sigla) VALUES
    (1, 'pipc', 'Programa de Incentivos para la Producción de Café',     'PIPC'),
    (2, 'pipg', 'Programa de Incentivos para la Producción Ganadera',    'PIPG'),
    (3, 'pipa', 'Programa para la Producción Agrícola',                  'PIPA');

-- Rol y usuario administrador inicial.
--   Usuario: admin   ·   Contraseña: admin123   (cámbiala tras el primer ingreso)
INSERT INTO sag_roles (id_rol, nombre, slug) VALUES
    (1, 'Administrador', 'admin');

INSERT INTO sag_usuarios (id_rol, nombre, apellido, username, email, password_hash) VALUES
    (1, 'Admin', 'SAG', 'admin', 'admin@sag.hn',
     '$2y$10$e66w7CEVmx6WN8J47tKrBeQgnN6Cb0ycw4UahCC17HKorvm/NOo0K');

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  FIN — esquema unificado en la base mddesarr_sag.
--  Los 3 programas se distinguen por id_proyecto (sembrado arriba).
--  Login inicial → usuario: admin · contraseña: admin123
-- =====================================================================
