-- =====================================================
--  SAG PROGRAMAS — Template de base de datos por programa
--
--  Ejecutar 3 veces cambiando el nombre:
--    1) sag_pipc  — Programa de Incentivos para la Producción de Café
--    2) sag_pipg  — Programa de Incentivos para la Producción Ganadera
--    3) sag_pipa  — Programa para la Producción Agrícola
--
--  Antes de ejecutar, reemplaza "sag_pipc" con el nombre correcto.
-- =====================================================

-- *** CAMBIAR ESTE NOMBRE SEGÚN EL PROGRAMA ***
CREATE DATABASE IF NOT EXISTS sag_pipc
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sag_pipc;

-- ─────────────────────────────────────────────────
--  CATÁLOGOS GEOGRÁFICOS
-- ─────────────────────────────────────────────────
CREATE TABLE sag_departamentos (
    id_departamento INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100) NOT NULL,
    activo          TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_municipios (
    id_municipio    INT AUTO_INCREMENT PRIMARY KEY,
    id_departamento INT          NOT NULL,
    nombre          VARCHAR(100) NOT NULL,
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    INDEX idx_dep (id_departamento),
    FOREIGN KEY (id_departamento) REFERENCES sag_departamentos(id_departamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 18 Departamentos de Honduras
INSERT INTO sag_departamentos (nombre) VALUES
('Atlántida'),('Choluteca'),('Colón'),('Comayagua'),('Copán'),
('Cortés'),('El Paraíso'),('Francisco Morazán'),('Gracias a Dios'),
('Intibucá'),('Islas de la Bahía'),('La Paz'),('Lempira'),
('Ocotepeque'),('Olancho'),('Santa Bárbara'),('Valle'),('Yoro');

-- Municipios por departamento
INSERT INTO sag_municipios (id_departamento, nombre) VALUES
(1,'La Ceiba'),(1,'El Porvenir'),(1,'Esparta'),(1,'Jutiapa'),(1,'La Masica'),
(1,'San Francisco'),(1,'Tela'),(1,'Arizona'),
(2,'Choluteca'),(2,'Apacilagua'),(2,'Concepción de María'),(2,'Duyure'),
(2,'El Corpus'),(2,'El Triunfo'),(2,'Marcovia'),(2,'Morolica'),
(2,'Namasigüe'),(2,'Orocuina'),(2,'Pespire'),(2,'San Antonio de Flores'),
(2,'San Isidro'),(2,'San José'),(2,'San Marcos de Colón'),(2,'Santa Ana'),
(3,'Trujillo'),(3,'Balfate'),(3,'Iriona'),(3,'Limón'),(3,'Sabá'),
(3,'Santa Fe'),(3,'Santa Rosa de Aguán'),(3,'Sonaguera'),(3,'Tocoa'),(3,'Bonito Oriental'),
(4,'Comayagua'),(4,'Ajuterique'),(4,'El Rosario'),(4,'Esquías'),(4,'Humuya'),
(4,'La Libertad'),(4,'Lamaní'),(4,'La Trinidad'),(4,'Lejamaní'),(4,'Meámbar'),
(4,'Minas de Oro'),(4,'Ojos de Agua'),(4,'San Jerónimo'),(4,'San José de Comayagua'),
(4,'San José del Potrero'),(4,'San Luis'),(4,'San Sebastián'),(4,'Siguatepeque'),
(4,'Trinidad de Copán'),(4,'Taulabé'),
(5,'Santa Rosa de Copán'),(5,'Cabañas'),(5,'Concepción'),(5,'Copán Ruinas'),
(5,'Corquín'),(5,'Cucuyagua'),(5,'Dolores'),(5,'Dulce Nombre'),
(5,'El Paraíso'),(5,'Florida'),(5,'La Jigua'),(5,'La Unión'),
(5,'Nueva Arcadia'),(5,'San Agustín'),(5,'San Antonio'),(5,'San Jerónimo'),
(5,'San José'),(5,'San Juan de Opoa'),(5,'San Nicolás'),(5,'San Pedro'),
(5,'Santa Rita'),(5,'Trinidad de Copán'),(5,'Veracruz'),
(6,'San Pedro Sula'),(6,'Choloma'),(6,'La Lima'),(6,'Naco'),(6,'Omoa'),
(6,'Pimienta'),(6,'Potrerillos'),(6,'Puerto Cortés'),(6,'San Antonio de Cortés'),
(6,'San Francisco de Yojoa'),(6,'San Manuel'),(6,'Santa Cruz de Yojoa'),
(6,'Villanueva'),(6,'Arizona'),
(7,'Yuscarán'),(7,'Alauca'),(7,'Danlí'),(7,'El Paraíso'),(7,'Güinope'),
(7,'Jacaleapa'),(7,'Liure'),(7,'Morocelí'),(7,'Oropolí'),(7,'Potrerillos'),
(7,'San Antonio de Flores'),(7,'San Lucas'),(7,'San Matías'),(7,'Soledad'),
(7,'Teupasenti'),(7,'Texiguat'),(7,'Vado Ancho'),(7,'Yauyupe'),(7,'Trojes'),
(8,'Tegucigalpa'),(8,'Alubarén'),(8,'Cedros'),(8,'Curarén'),(8,'El Porvenir'),
(8,'Guaimaca'),(8,'La Libertad'),(8,'La Venta'),(8,'Lepaterique'),(8,'Maraita'),
(8,'Marale'),(8,'Nueva Armenia'),(8,'Ojojona'),(8,'Orica'),(8,'Reitoca'),
(8,'Sabanagrande'),(8,'San Antonio de Oriente'),(8,'San Buenaventura'),
(8,'San Ignacio'),(8,'San Juan de Flores'),(8,'San Miguelito'),(8,'Santa Ana'),
(8,'Santa Lucía'),(8,'Talanga'),(8,'Tatumbla'),(8,'Valle de Ángeles'),
(8,'Villa de San Francisco'),(8,'Vallecillo'),
(9,'Puerto Lempira'),(9,'Brus Laguna'),(9,'Ahuas'),(9,'Juan Francisco Bulnes'),
(9,'Villeda Morales'),(9,'Wampusirpe'),
(10,'La Esperanza'),(10,'Camasca'),(10,'Colomoncagua'),(10,'Concepción'),
(10,'Dolores'),(10,'Intibucá'),(10,'Jesús de Otoro'),(10,'Magdalena'),
(10,'Masaguara'),(10,'San Antonio'),(10,'San Isidro'),(10,'San Juan'),
(10,'San Marcos de la Sierra'),(10,'San Miguelito'),(10,'Santa Lucía'),
(10,'Yamaranguila'),(10,'San Francisco de Opalaca'),
(11,'Roatán'),(11,'Guanaja'),(11,'José Santos Guardiola'),(11,'Utila'),
(12,'La Paz'),(12,'Aguanqueterique'),(12,'Cabañas'),(12,'Cane'),
(12,'Chinacla'),(12,'Guajiquiro'),(12,'Lauterique'),(12,'Marcala'),
(12,'Mercedes de Oriente'),(12,'Opatoro'),(12,'San Antonio del Norte'),
(12,'San José'),(12,'San Juan'),(12,'San Pedro de Tutule'),(12,'Santa Ana'),
(12,'Santa Elena'),(12,'Santa María'),(12,'Santiago de Puringla'),(12,'Yarula'),
(13,'Gracias'),(13,'Belén'),(13,'Candelaria'),(13,'Cololaca'),(13,'Erandique'),
(13,'Gualcince'),(13,'Guarita'),(13,'La Campa'),(13,'La Iguala'),
(13,'Las Flores'),(13,'La Unión'),(13,'La Virtud'),(13,'Lepaera'),
(13,'Mapulaca'),(13,'Piraera'),(13,'San Andrés'),(13,'San Francisco'),
(13,'San Juan Guarita'),(13,'San Manuel Colohete'),(13,'San Rafael'),
(13,'San Sebastián'),(13,'Santa Cruz'),(13,'Talgua'),(13,'Tambla'),
(13,'Tomalá'),(13,'Valladolid'),(13,'Virginia'),(13,'San Marcos de Caiquín'),
(14,'Ocotepeque'),(14,'Belén Gualcho'),(14,'Concepción'),(14,'Dolores Merendón'),
(14,'Fraternidad'),(14,'La Encarnación'),(14,'La Labor'),(14,'Lucerna'),
(14,'Mercedes'),(14,'San Fernando'),(14,'San Francisco del Valle'),
(14,'San Jorge'),(14,'San Marcos'),(14,'Santa Fe'),(14,'Sensenti'),
(14,'Sinuapa'),
(15,'Juticalpa'),(15,'Campamento'),(15,'Catacamas'),(15,'Concordia'),
(15,'Dulce Nombre de Culmí'),(15,'El Rosario'),(15,'Esquipulas del Norte'),
(15,'Gualaco'),(15,'Guarizama'),(15,'Guata'),(15,'Guayape'),(15,'Jano'),
(15,'La Unión'),(15,'Lepaguare'),(15,'Manto'),(15,'Salama'),(15,'San Esteban'),
(15,'San Francisco de Becerra'),(15,'San Francisco de la Paz'),(15,'Santa María del Real'),
(15,'Silca'),(15,'Yocón'),(15,'Patuca'),
(16,'Santa Bárbara'),(16,'Arada'),(16,'Atima'),(16,'Azacualpa'),(16,'Ceguaca'),
(16,'Concepción del Norte'),(16,'Concepción del Sur'),(16,'Chinda'),(16,'El Níspero'),
(16,'Gualala'),(16,'Ilama'),(16,'Las Vegas'),(16,'Macuelizo'),(16,'Naranjito'),
(16,'Nuevo Celilac'),(16,'Petoa'),(16,'Protección'),(16,'Quimistán'),(16,'San Francisco de Ojuera'),
(16,'San José de Colinas'),(16,'San Luis'),(16,'San Marcos'),(16,'San Nicolás'),
(16,'San Pedro Zacapa'),(16,'Santa Rita'),(16,'Trinidad'),(16,'San Vicente Centenario'),
(17,'Nacaome'),(17,'Alianza'),(17,'Amapala'),(17,'Aramecina'),(17,'Caridad'),
(17,'Goascorán'),(17,'Langue'),(17,'San Francisco de Coray'),(17,'San Lorenzo'),
(18,'Yoro'),(18,'Arenal'),(18,'El Negrito'),(18,'El Progreso'),(18,'Jocón'),
(18,'Morazán'),(18,'Olanchito'),(18,'Santa Rita'),(18,'Sulaco'),(18,'Victoria'),(18,'Yorito');

-- ─────────────────────────────────────────────────
--  CATÁLOGOS TÉCNICOS
-- ─────────────────────────────────────────────────
CREATE TABLE sag_temas (
    id_tema INT AUTO_INCREMENT PRIMARY KEY,
    nombre  VARCHAR(200) NOT NULL,
    tipo    ENUM('capacitacion','at','ambos') NOT NULL DEFAULT 'ambos',
    activo  TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_subtemas (
    id_subtema INT AUTO_INCREMENT PRIMARY KEY,
    id_tema    INT          NOT NULL,
    nombre     VARCHAR(200) NOT NULL,
    activo     TINYINT(1)   NOT NULL DEFAULT 1,
    INDEX idx_tema (id_tema),
    FOREIGN KEY (id_tema) REFERENCES sag_temas(id_tema) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_tipo_at (
    id_tipo_at INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL,
    icono      VARCHAR(50),
    activo     TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_cultivos (
    id_cultivo INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL,
    tipo       ENUM('cultivo','ganaderia','otro') NOT NULL DEFAULT 'cultivo',
    activo     TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed temas
INSERT INTO sag_temas (nombre, tipo) VALUES
('Manejo Agronómico',              'ambos'),
('Fertilización y Nutrición',      'ambos'),
('Control de Plagas y Enfermedades','ambos'),
('Manejo Post-Cosecha',            'ambos'),
('Buenas Prácticas Agrícolas',     'ambos'),
('Gestión Empresarial',            'capacitacion'),
('Acceso a Mercados',              'capacitacion'),
('Crédito y Financiamiento',       'capacitacion'),
('Riego y Agua',                   'ambos'),
('Suelo y Conservación',           'ambos'),
('Ganadería y Sanidad Animal',     'ambos'),
('Cambio Climático y Adaptación',  'capacitacion');

INSERT INTO sag_subtemas (id_tema, nombre) VALUES
(1,'Podas y mantenimiento'),(1,'Densidades de siembra'),(1,'Injertos y variedades'),
(2,'Análisis de suelos'),(2,'Fertilización foliar'),(2,'Abonos orgánicos'),
(3,'Manejo integrado de plagas'),(3,'Fungicidas y bactericidas'),(3,'Control biológico'),
(4,'Secado y almacenamiento'),(4,'Clasificación y calidad'),(4,'Procesamiento húmedo'),
(5,'BPA en campo'),(5,'Trazabilidad'),(5,'Certificaciones'),
(6,'Contabilidad básica'),(6,'Planes de negocio'),(6,'Cooperativismo'),
(9,'Riego por goteo'),(9,'Sistemas de aspersión'),(9,'Captación de agua'),
(10,'Terrazas y barreras vivas'),(10,'Compostaje'),(10,'Labranza mínima'),
(11,'Vacunación bovina'),(11,'Manejo de pastos'),(11,'Reproducción animal');

INSERT INTO sag_tipo_at (nombre, icono) VALUES
('Visita de seguimiento',    'fa-eye'),
('Diagnóstico inicial',      'fa-stethoscope'),
('Solución de problema',     'fa-wrench'),
('Demostración de práctica', 'fa-flask'),
('Entrega de insumos',       'fa-box-open');

INSERT INTO sag_cultivos (nombre, tipo) VALUES
('Maíz','cultivo'),('Frijol','cultivo'),('Café','cultivo'),('Hortalizas','cultivo'),
('Caña de Azúcar','cultivo'),('Palma Africana','cultivo'),('Musáceas (plátano/banano)','cultivo'),
('Granos básicos en general','cultivo'),('Ganadería bovina','ganaderia'),
('Ganadería porcina','ganaderia'),('Aves de corral','ganaderia'),
('Acuicultura','otro'),('Apicultura','otro'),('Otro','otro');

-- ─────────────────────────────────────────────────
--  TÉCNICOS
-- ─────────────────────────────────────────────────
CREATE TABLE sag_tecnicos (
    id_tecnico      INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(200) NOT NULL,
    especialidad    VARCHAR(100),
    id_departamento INT,
    telefono        VARCHAR(20),
    email           VARCHAR(150),
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_departamento) REFERENCES sag_departamentos(id_departamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO sag_tecnicos (nombre_completo, especialidad, id_departamento) VALUES
('Carlos Mejía López',     'Agronomía',          8),
('María Rodríguez Paz',    'Extensión Rural',     6),
('José Hernández Cruz',    'Sanidad Animal',      15),
('Ana Contreras Soto',     'Post-cosecha',        5),
('Pedro Amador Reyes',     'Riego y Drenaje',     18),
('Luisa Padilla Vásquez',  'Nutrición Vegetal',   7),
('Roberto Zelaya Ortiz',   'Mercadeo Agrícola',   6),
('Carmen Flores Mejía',    'Buenas Prácticas',    12);

-- ─────────────────────────────────────────────────
--  ORGANIZACIONES
-- ─────────────────────────────────────────────────
CREATE TABLE sag_organizaciones (
    id_organizacion INT AUTO_INCREMENT PRIMARY KEY,
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
    INDEX idx_depto (id_departamento),
    FOREIGN KEY (id_departamento) REFERENCES sag_departamentos(id_departamento),
    FOREIGN KEY (id_municipio)    REFERENCES sag_municipios(id_municipio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────
--  BENEFICIARIOS
-- ─────────────────────────────────────────────────
CREATE TABLE sag_beneficiarios (
    id_beneficiario INT AUTO_INCREMENT PRIMARY KEY,
    id_organizacion INT,
    id_departamento INT          NOT NULL,
    id_municipio    INT          NOT NULL,
    aldea           VARCHAR(200),
    nombre          VARCHAR(100) NOT NULL,
    apellido        VARCHAR(100) NOT NULL,
    dni             VARCHAR(15)  UNIQUE,
    fecha_nacimiento DATE,
    sexo            ENUM('M','F') NOT NULL,
    telefono        VARCHAR(20),
    email           VARCHAR(150),
    area_productiva DECIMAL(10,2),
    cultivo_principal VARCHAR(100),
    coordenadas     VARCHAR(60),
    estado          ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    created_by      INT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_depto (id_departamento),
    INDEX idx_org   (id_organizacion),
    FOREIGN KEY (id_departamento) REFERENCES sag_departamentos(id_departamento),
    FOREIGN KEY (id_municipio)    REFERENCES sag_municipios(id_municipio),
    FOREIGN KEY (id_organizacion) REFERENCES sag_organizaciones(id_organizacion) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────
--  CAPACITACIONES
-- ─────────────────────────────────────────────────
CREATE TABLE sag_capacitaciones (
    id_capacitacion   INT AUTO_INCREMENT PRIMARY KEY,
    id_departamento   INT          NOT NULL,
    id_municipio      INT          NOT NULL,
    aldea             VARCHAR(200),
    lugar_especifico  VARCHAR(300),
    id_tema           INT          NOT NULL,
    id_subtema        INT,
    descripcion       TEXT,
    fecha_capacitacion DATE        NOT NULL,
    duracion_horas    DECIMAL(4,1),
    id_tecnico        INT          NOT NULL,
    num_participantes INT          NOT NULL DEFAULT 0,
    estado            ENUM('borrador','finalizado') NOT NULL DEFAULT 'borrador',
    created_by        INT,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_fecha  (fecha_capacitacion),
    INDEX idx_depto  (id_departamento),
    FOREIGN KEY (id_departamento) REFERENCES sag_departamentos(id_departamento),
    FOREIGN KEY (id_municipio)    REFERENCES sag_municipios(id_municipio),
    FOREIGN KEY (id_tema)         REFERENCES sag_temas(id_tema),
    FOREIGN KEY (id_subtema)      REFERENCES sag_subtemas(id_subtema),
    FOREIGN KEY (id_tecnico)      REFERENCES sag_tecnicos(id_tecnico)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sag_cap_participantes (
    id_participante INT AUTO_INCREMENT PRIMARY KEY,
    id_capacitacion INT          NOT NULL,
    nombre          VARCHAR(100) NOT NULL,
    apellido        VARCHAR(100),
    dni             VARCHAR(15),
    edad            TINYINT UNSIGNED,
    sexo            ENUM('M','F'),
    id_organizacion INT,
    telefono        VARCHAR(20),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cap (id_capacitacion),
    FOREIGN KEY (id_capacitacion) REFERENCES sag_capacitaciones(id_capacitacion) ON DELETE CASCADE,
    FOREIGN KEY (id_organizacion) REFERENCES sag_organizaciones(id_organizacion) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────
--  ASISTENCIA TÉCNICA
-- ─────────────────────────────────────────────────
CREATE TABLE sag_asistencias_tecnicas (
    id_at              INT AUTO_INCREMENT PRIMARY KEY,
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
    created_by         INT,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_fecha   (fecha_visita),
    INDEX idx_depto   (id_departamento),
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
    id_at        INT          NOT NULL,
    resultado    VARCHAR(200) NOT NULL,
    INDEX idx_at (id_at),
    FOREIGN KEY (id_at) REFERENCES sag_asistencias_tecnicas(id_at) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
