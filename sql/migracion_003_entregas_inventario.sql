-- =====================================================
--  MIGRACIÓN 003 — Entregas de Incentivos + Inventarios
--  SAG Honduras Sin Hambre · Mayo 2026
--
--  Ejecutar en cada BD de programa:
--     USE sag_pipc;   SOURCE migracion_003_entregas_inventario.sql;
--     USE sag_pipg;   SOURCE migracion_003_entregas_inventario.sql;
--     USE sag_pipa;   SOURCE migracion_003_entregas_inventario.sql;
--
--  Crea:
--    · Catálogos: proveedores, bodegas, productos
--    · Inventarios: cronogramas (cabecera) + líneas + kardex
--    · Entregas:   cabecera + líneas (que descuentan stock)
-- =====================================================

-- ─────────────────────────────────────────────────
--  PROVEEDORES
-- ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_proveedores (
    id_proveedor     INT AUTO_INCREMENT PRIMARY KEY,
    nombre           VARCHAR(200) NOT NULL,
    rtn              VARCHAR(20),
    contacto         VARCHAR(150),
    telefono         VARCHAR(30),
    email            VARCHAR(150),
    direccion        VARCHAR(300),
    activo           TINYINT(1)   NOT NULL DEFAULT 1,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activo (activo),
    INDEX idx_rtn    (rtn)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────
--  BODEGAS
-- ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_bodegas (
    id_bodega        INT AUTO_INCREMENT PRIMARY KEY,
    codigo           VARCHAR(20)  NOT NULL UNIQUE,
    nombre           VARCHAR(200) NOT NULL,
    id_departamento  INT,
    id_municipio     INT,
    direccion        VARCHAR(300),
    responsable      VARCHAR(200),
    telefono         VARCHAR(30),
    capacidad        DECIMAL(12,2),
    coordenadas      VARCHAR(60),
    activo           TINYINT(1)   NOT NULL DEFAULT 1,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dep    (id_departamento),
    INDEX idx_activo (activo),
    FOREIGN KEY (id_departamento) REFERENCES sag_departamentos(id_departamento),
    FOREIGN KEY (id_municipio)    REFERENCES sag_municipios(id_municipio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Semilla de bodegas (las que estaban hard-coded en el mock)
INSERT IGNORE INTO sag_bodegas (codigo, nombre, id_departamento, activo) VALUES
('FM01', 'Bodega Central FM-01',     8,  1),
('FM02', 'Bodega Valle FM-02',       8,  1),
('CP01', 'Bodega Santa Rosa CP-01',  5,  1),
('CP02', 'Bodega La Entrada CP-02',  5,  1),
('OL01', 'Bodega Juticalpa OL-01',  15, 1),
('OL02', 'Bodega Campamento OL-02', 15, 1);

-- ─────────────────────────────────────────────────
--  PRODUCTOS / INSUMOS
-- ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_inventario_productos (
    id_producto      INT AUTO_INCREMENT PRIMARY KEY,
    codigo           VARCHAR(40)  NOT NULL UNIQUE,
    nombre           VARCHAR(200) NOT NULL,
    descripcion      TEXT,
    unidad           VARCHAR(30)  NOT NULL DEFAULT 'unidad',     -- saco, kg, litro, unidad, etc
    presentacion     VARCHAR(80),                                -- 'saco 50 lb', 'galón 5 lt'
    categoria        VARCHAR(80),                                -- 'fertilizante', 'semilla', 'maquinaria'
    precio_unitario  DECIMAL(12,2),
    activo           TINYINT(1)   NOT NULL DEFAULT 1,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activo (activo),
    INDEX idx_cat    (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────
--  CRONOGRAMAS DE INVENTARIO (cabecera)
--  Un cronograma = un contrato/OC con un proveedor.
--  Sus líneas indican qué se debe recibir, cuándo y dónde.
-- ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_inventario_cronogramas (
    id_cronograma    INT AUTO_INCREMENT PRIMARY KEY,
    codigo           VARCHAR(40)  NOT NULL UNIQUE,                -- ej. CRON-PIPC-2026-001
    id_proveedor     INT          NOT NULL,
    num_contrato     VARCHAR(80),
    num_orden_compra VARCHAR(80),
    descripcion      VARCHAR(300),
    monto_total      DECIMAL(14,2),
    fecha_firma      DATE,
    fecha_inicio     DATE,
    fecha_fin        DATE,
    archivo_contrato VARCHAR(255),                                -- ruta al PDF
    estado           ENUM('borrador','vigente','finalizado','cancelado') NOT NULL DEFAULT 'borrador',
    observaciones    TEXT,
    created_by       INT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_prov   (id_proveedor),
    INDEX idx_estado (estado),
    INDEX idx_inicio (fecha_inicio),
    FOREIGN KEY (id_proveedor) REFERENCES sag_proveedores(id_proveedor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────
--  LÍNEAS DEL CRONOGRAMA
--  Cada línea = una entrega programada (qué, cuánto, cuándo, dónde)
-- ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_inventario_lineas (
    id_linea            INT AUTO_INCREMENT PRIMARY KEY,
    id_cronograma       INT          NOT NULL,
    id_producto         INT          NOT NULL,
    id_bodega           INT          NOT NULL,
    cantidad_programada DECIMAL(12,2) NOT NULL,
    cantidad_recibida   DECIMAL(12,2) NOT NULL DEFAULT 0,
    fecha_programada    DATE         NOT NULL,
    fecha_recibida      DATE,
    responsable_recepcion VARCHAR(200),
    estado              ENUM('pendiente','parcial','recibida','atrasada','cancelada') NOT NULL DEFAULT 'pendiente',
    observaciones       TEXT,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cron     (id_cronograma),
    INDEX idx_prod     (id_producto),
    INDEX idx_bodega   (id_bodega),
    INDEX idx_estado   (estado),
    INDEX idx_fecha    (fecha_programada),
    FOREIGN KEY (id_cronograma) REFERENCES sag_inventario_cronogramas(id_cronograma) ON DELETE CASCADE,
    FOREIGN KEY (id_producto)   REFERENCES sag_inventario_productos(id_producto),
    FOREIGN KEY (id_bodega)     REFERENCES sag_bodegas(id_bodega)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────
--  KARDEX / MOVIMIENTOS DE INVENTARIO
--  Toda variación de stock pasa por aquí:
--    · entrada: recepción de cronograma
--    · salida:  entrega validada a beneficiario
--    · ajuste:  conteo/merma manual
-- ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_inventario_movimientos (
    id_movimiento    INT AUTO_INCREMENT PRIMARY KEY,
    fecha            DATETIME     NOT NULL,
    tipo             ENUM('entrada','salida','ajuste') NOT NULL,
    id_producto      INT          NOT NULL,
    id_bodega        INT          NOT NULL,
    cantidad         DECIMAL(12,2) NOT NULL,
    saldo_resultante DECIMAL(12,2),
    origen           VARCHAR(50)  NOT NULL,                        -- 'cronograma','entrega','ajuste_manual'
    id_origen        INT,                                          -- id_linea o id_entrega
    descripcion      VARCHAR(300),
    created_by       INT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fecha  (fecha),
    INDEX idx_prod   (id_producto),
    INDEX idx_bodega (id_bodega),
    INDEX idx_origen (origen, id_origen),
    FOREIGN KEY (id_producto) REFERENCES sag_inventario_productos(id_producto),
    FOREIGN KEY (id_bodega)   REFERENCES sag_bodegas(id_bodega)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────
--  ENTREGAS DE INCENTIVOS (cabecera)
--  Sincronización: KoBo (registro de campo) + Trazaragro (manifiesto/control)
-- ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_entregas (
    id_entrega          INT AUTO_INCREMENT PRIMARY KEY,
    codigo              VARCHAR(40)  NOT NULL UNIQUE,             -- ej. ENT-PIPC-2026-000123
    kobo_submission_id  VARCHAR(100),                              -- referencia al envío de KoBo
    trazaragro_id       VARCHAR(100),                              -- referencia al manifiesto Trazaragro
    fuente              ENUM('kobo','trazaragro','manual') NOT NULL DEFAULT 'manual',

    -- Beneficiario (cache para facilitar consultas; FK opcional)
    id_beneficiario     INT,
    dni                 VARCHAR(15),
    nombre_beneficiario VARCHAR(200),
    sexo                ENUM('M','F'),
    id_departamento     INT,
    id_municipio        INT,
    aldea               VARCHAR(200),

    -- Logística
    id_bodega           INT          NOT NULL,
    id_tecnico          INT,
    fecha_entrega       DATE         NOT NULL,
    hora_entrega        TIME,

    -- Evidencias (URLs o rutas locales)
    gps_lat             DECIMAL(10,6),
    gps_lon             DECIMAL(10,6),
    gps_precision       INT,
    foto_dni            VARCHAR(255),
    foto_entrega        VARCHAR(255),
    firma_beneficiario  VARCHAR(255),
    firma_tecnico       VARCHAR(255),

    -- Actualizaciones del beneficiario en campo
    telefono_actualizado   VARCHAR(30),
    direccion_actualizada  VARCHAR(300),

    -- Cierre administrativo
    observaciones       TEXT,
    estado              ENUM('pendiente_revision','aprobada','con_alerta','rechazada','anulada')
                        NOT NULL DEFAULT 'pendiente_revision',
    alerta_motivo       VARCHAR(300),
    revisado_por        VARCHAR(200),
    fecha_revision      DATETIME,
    stock_descontado    TINYINT(1)   NOT NULL DEFAULT 0,           -- 1 cuando ya descontó del inventario
    synced_at           DATETIME,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_fecha    (fecha_entrega),
    INDEX idx_estado   (estado),
    INDEX idx_bodega   (id_bodega),
    INDEX idx_dni      (dni),
    INDEX idx_kobo     (kobo_submission_id),
    INDEX idx_traza    (trazaragro_id),
    FOREIGN KEY (id_bodega)       REFERENCES sag_bodegas(id_bodega),
    FOREIGN KEY (id_tecnico)      REFERENCES sag_tecnicos(id_tecnico),
    FOREIGN KEY (id_beneficiario) REFERENCES sag_beneficiarios(id_beneficiario) ON DELETE SET NULL,
    FOREIGN KEY (id_departamento) REFERENCES sag_departamentos(id_departamento),
    FOREIGN KEY (id_municipio)    REFERENCES sag_municipios(id_municipio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────
--  LÍNEAS DE LA ENTREGA
--  Productos entregados — cada línea descuenta del stock al aprobar.
-- ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_entregas_lineas (
    id_entrega_linea   INT AUTO_INCREMENT PRIMARY KEY,
    id_entrega         INT          NOT NULL,
    id_producto        INT          NOT NULL,
    cantidad_asignada  DECIMAL(12,2) NOT NULL DEFAULT 0,
    cantidad_entregada DECIMAL(12,2) NOT NULL DEFAULT 0,
    razon_diferencia   VARCHAR(200),                               -- 'stock_insuficiente','beneficiario_no_presente',etc
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entrega  (id_entrega),
    INDEX idx_producto (id_producto),
    FOREIGN KEY (id_entrega)  REFERENCES sag_entregas(id_entrega) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES sag_inventario_productos(id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
--  FIN DE LA MIGRACIÓN 003
-- =====================================================
