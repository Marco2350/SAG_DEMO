-- ============================================================
--  MÓDULO EJECUCIÓN PRESUPUESTARIA — SAG Programas
--  Ejecutar en cada base de datos de programa (sag_programa_*)
-- ============================================================

CREATE TABLE IF NOT EXISTS sag_presupuestos (
    id_presupuesto      INT AUTO_INCREMENT PRIMARY KEY,
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
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sag_lineas_presupuestarias (
    id_linea          INT AUTO_INCREMENT PRIMARY KEY,
    id_presupuesto    INT NOT NULL,
    codigo            VARCHAR(50),
    nombre            VARCHAR(200) NOT NULL,
    descripcion       TEXT,
    tipo              ENUM('compras','viaticos','gastos','personal','equipos','servicios','otro') DEFAULT 'otro',
    monto_aprobado    DECIMAL(15,2) DEFAULT 0,
    activo            TINYINT(1) DEFAULT 1,
    orden             INT DEFAULT 0,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_presupuesto) REFERENCES sag_presupuestos(id_presupuesto) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sag_modificaciones_presupuesto (
    id_modificacion   INT AUTO_INCREMENT PRIMARY KEY,
    id_linea          INT NOT NULL,
    tipo              ENUM('ajuste','ampliacion','reduccion') NOT NULL,
    monto_anterior    DECIMAL(15,2) NOT NULL,
    monto_nuevo       DECIMAL(15,2) NOT NULL,
    justificacion     TEXT NOT NULL,
    documento_respaldo VARCHAR(255),
    id_usuario        INT,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_linea) REFERENCES sag_lineas_presupuestarias(id_linea) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sag_compras (
    id_compra           INT AUTO_INCREMENT PRIMARY KEY,
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
    FOREIGN KEY (id_linea) REFERENCES sag_lineas_presupuestarias(id_linea) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sag_solicitudes_viaticos (
    id_solicitud        INT AUTO_INCREMENT PRIMARY KEY,
    numero_solicitud    VARCHAR(50),
    id_linea            INT,
    id_solicitante      INT NOT NULL,
    destino             VARCHAR(200) NOT NULL,
    objetivo            TEXT NOT NULL,
    fecha_salida        DATE NOT NULL,
    fecha_retorno       DATE NOT NULL,
    dias                INT DEFAULT 1,
    monto_solicitado    DECIMAL(15,2) NOT NULL,
    moneda              ENUM('HNL','USD') DEFAULT 'HNL',
    hospedaje           DECIMAL(15,2) DEFAULT 0,
    alimentacion        DECIMAL(15,2) DEFAULT 0,
    transporte          DECIMAL(15,2) DEFAULT 0,
    otros               DECIMAL(15,2) DEFAULT 0,
    estado              ENUM('pendiente','visto_bueno','aprobada','rechazada','liquidada') DEFAULT 'pendiente',
    id_jefe             INT,
    fecha_visto_bueno   DATETIME,
    observacion_jefe    TEXT,
    id_autoridad        INT,
    fecha_aprobacion    DATETIME,
    observacion_autoridad TEXT,
    monto_ejecutado     DECIMAL(15,2),
    fecha_liquidacion   DATE,
    archivo_liquidacion VARCHAR(255),
    archivo_solicitud   VARCHAR(255),
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_linea) REFERENCES sag_lineas_presupuestarias(id_linea) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sag_gastos_varios (
    id_gasto          INT AUTO_INCREMENT PRIMARY KEY,
    id_linea          INT,
    descripcion       VARCHAR(300) NOT NULL,
    tipo              ENUM('operativo','administrativo','logistico','otro') DEFAULT 'otro',
    monto             DECIMAL(15,2) NOT NULL,
    moneda            ENUM('HNL','USD') DEFAULT 'HNL',
    fecha_gasto       DATE NOT NULL,
    beneficiario      VARCHAR(200),
    numero_documento  VARCHAR(100),
    archivo           VARCHAR(255),
    estado            ENUM('registrado','aprobado','rechazado') DEFAULT 'registrado',
    id_usuario        INT NOT NULL,
    observaciones     TEXT,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_linea) REFERENCES sag_lineas_presupuestarias(id_linea) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sag_documentos_programa (
    id_documento      INT AUTO_INCREMENT PRIMARY KEY,
    tipo              ENUM('carta_entendimiento','perfil_programa','acuerdo','convenio','resolucion','acta','otro') NOT NULL,
    nombre            VARCHAR(200) NOT NULL,
    descripcion       TEXT,
    archivo           VARCHAR(255) NOT NULL,
    tamano_bytes      INT,
    fecha_documento   DATE,
    vigente           TINYINT(1) DEFAULT 1,
    activo            TINYINT(1) DEFAULT 1,
    created_by        INT,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
