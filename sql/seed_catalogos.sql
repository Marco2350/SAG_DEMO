-- =====================================================================
--  SAG PROGRAMAS - Semilla de CATALOGOS (base mddesarr_sag)
--  Departamentos, municipios, tipos de AT, cultivos, temas/subtemas
--  Para los 3 programas: id_proyecto = 1 (PIPC), 2 (PIPG), 3 (PIPA)
--  Ejecutar UNA sola vez, sobre catalogos vacios.
-- =====================================================================

SET NAMES utf8mb4;
USE mddesarr_sag;

INSERT INTO sag_departamentos (id_departamento, id_proyecto, nombre) VALUES
 (1,1,'Atlántida'),(2,1,'Choluteca'),(3,1,'Colón'),(4,1,'Comayagua'),
 (5,1,'Copán'),(6,1,'Cortés'),(7,1,'El Paraíso'),(8,1,'Francisco Morazán'),
 (9,1,'Gracias a Dios'),(10,1,'Intibucá'),(11,1,'Islas de la Bahía'),(12,1,'La Paz'),
 (13,1,'Lempira'),(14,1,'Ocotepeque'),(15,1,'Olancho'),(16,1,'Santa Bárbara'),
 (17,1,'Valle'),(18,1,'Yoro');

INSERT INTO sag_municipios (id_proyecto, id_departamento, nombre) VALUES
 (1,1,'La Ceiba'),(1,1,'El Porvenir'),(1,1,'Esparta'),(1,1,'Jutiapa'),
 (1,1,'La Masica'),(1,1,'San Francisco'),(1,1,'Tela'),(1,1,'Arizona'),
 (1,2,'Choluteca'),(1,2,'Apacilagua'),(1,2,'Concepción de María'),(1,2,'Duyure'),
 (1,2,'El Corpus'),(1,2,'El Triunfo'),(1,2,'Marcovia'),(1,2,'Morolica'),
 (1,2,'Namasigüe'),(1,2,'Orocuina'),(1,2,'Pespire'),(1,2,'San Antonio de Flores'),
 (1,2,'San Isidro'),(1,2,'San José'),(1,2,'San Marcos de Colón'),(1,2,'Santa Ana de Yusguare'),
 (1,3,'Trujillo'),(1,3,'Balfate'),(1,3,'Iriona'),(1,3,'Limón'),
 (1,3,'Sabá'),(1,3,'Santa Fe'),(1,3,'Santa Rosa de Aguán'),(1,3,'Sonaguera'),
 (1,3,'Tocoa'),(1,3,'Bonito Oriental'),
 (1,4,'Comayagua'),(1,4,'Ajuterique'),(1,4,'El Rosario'),(1,4,'Esquías'),
 (1,4,'Humuya'),(1,4,'La Libertad'),(1,4,'Lamaní'),(1,4,'La Trinidad'),
 (1,4,'Lejamaní'),(1,4,'Meámbar'),(1,4,'Minas de Oro'),(1,4,'Ojos de Agua'),
 (1,4,'San Jerónimo'),(1,4,'San José de Comayagua'),(1,4,'San José del Potrero'),(1,4,'San Luis'),
 (1,4,'San Sebastián'),(1,4,'Siguatepeque'),(1,4,'Villa de San Antonio'),(1,4,'Las Lajas'),
 (1,4,'Taulabé'),
 (1,5,'Santa Rosa de Copán'),(1,5,'Cabañas'),(1,5,'Concepción'),(1,5,'Copán Ruinas'),
 (1,5,'Corquín'),(1,5,'Cucuyagua'),(1,5,'Dolores'),(1,5,'Dulce Nombre'),
 (1,5,'El Paraíso'),(1,5,'Florida'),(1,5,'La Jigua'),(1,5,'La Unión'),
 (1,5,'Nueva Arcadia'),(1,5,'San Agustín'),(1,5,'San Antonio'),(1,5,'San Jerónimo'),
 (1,5,'San José'),(1,5,'San Juan de Opoa'),(1,5,'San Nicolás'),(1,5,'San Pedro'),
 (1,5,'Santa Rita'),(1,5,'Trinidad de Copán'),(1,5,'Veracruz'),
 (1,6,'San Pedro Sula'),(1,6,'Choloma'),(1,6,'Omoa'),(1,6,'Pimienta'),
 (1,6,'Potrerillos'),(1,6,'Puerto Cortés'),(1,6,'San Antonio de Cortés'),(1,6,'San Francisco de Yojoa'),
 (1,6,'San Manuel'),(1,6,'Santa Cruz de Yojoa'),(1,6,'Villanueva'),(1,6,'La Lima'),
 (1,7,'Yuscarán'),(1,7,'Alauca'),(1,7,'Danlí'),(1,7,'El Paraíso'),
 (1,7,'Güinope'),(1,7,'Jacaleapa'),(1,7,'Liure'),(1,7,'Morocelí'),
 (1,7,'Oropolí'),(1,7,'Potrerillos'),(1,7,'San Antonio de Flores'),(1,7,'San Lucas'),
 (1,7,'San Matías'),(1,7,'Soledad'),(1,7,'Teupasenti'),(1,7,'Texiguat'),
 (1,7,'Vado Ancho'),(1,7,'Yauyupe'),(1,7,'Trojes'),
 (1,8,'Distrito Central'),(1,8,'Alubarén'),(1,8,'Cedros'),(1,8,'Curarén'),
 (1,8,'El Porvenir'),(1,8,'Guaimaca'),(1,8,'La Libertad'),(1,8,'La Venta'),
 (1,8,'Lepaterique'),(1,8,'Maraita'),(1,8,'Marale'),(1,8,'Nueva Armenia'),
 (1,8,'Ojojona'),(1,8,'Orica'),(1,8,'Reitoca'),(1,8,'Sabanagrande'),
 (1,8,'San Antonio de Oriente'),(1,8,'San Buenaventura'),(1,8,'San Ignacio'),(1,8,'San Juan de Flores'),
 (1,8,'San Miguelito'),(1,8,'Santa Ana'),(1,8,'Santa Lucía'),(1,8,'Talanga'),
 (1,8,'Tatumbla'),(1,8,'Valle de Ángeles'),(1,8,'Vallecillo'),(1,8,'Villa de San Francisco'),
 (1,9,'Puerto Lempira'),(1,9,'Brus Laguna'),(1,9,'Ahuas'),(1,9,'Juan Francisco Bulnes'),
 (1,9,'Ramón Villeda Morales'),(1,9,'Wampusirpi'),
 (1,10,'La Esperanza'),(1,10,'Camasca'),(1,10,'Colomoncagua'),(1,10,'Concepción'),
 (1,10,'Dolores'),(1,10,'Intibucá'),(1,10,'Jesús de Otoro'),(1,10,'Magdalena'),
 (1,10,'Masaguara'),(1,10,'San Antonio'),(1,10,'San Isidro'),(1,10,'San Juan'),
 (1,10,'San Marcos de la Sierra'),(1,10,'San Miguel Guancapla'),(1,10,'Santa Lucía'),(1,10,'Yamaranguila'),
 (1,10,'San Francisco de Opalaca'),
 (1,11,'Roatán'),(1,11,'Guanaja'),(1,11,'José Santos Guardiola'),(1,11,'Utila'),
 (1,12,'La Paz'),(1,12,'Aguanqueterique'),(1,12,'Cabañas'),(1,12,'Cane'),
 (1,12,'Chinacla'),(1,12,'Guajiquiro'),(1,12,'Lauterique'),(1,12,'Marcala'),
 (1,12,'Mercedes de Oriente'),(1,12,'Opatoro'),(1,12,'San Antonio del Norte'),(1,12,'San José'),
 (1,12,'San Juan'),(1,12,'San Pedro de Tutule'),(1,12,'Santa Ana'),(1,12,'Santa Elena'),
 (1,12,'Santa María'),(1,12,'Santiago de Puringla'),(1,12,'Yarula'),
 (1,13,'Gracias'),(1,13,'Belén'),(1,13,'Candelaria'),(1,13,'Cololaca'),
 (1,13,'Erandique'),(1,13,'Gualcince'),(1,13,'Guarita'),(1,13,'La Campa'),
 (1,13,'La Iguala'),(1,13,'Las Flores'),(1,13,'La Unión'),(1,13,'La Virtud'),
 (1,13,'Lepaera'),(1,13,'Mapulaca'),(1,13,'Piraera'),(1,13,'San Andrés'),
 (1,13,'San Francisco'),(1,13,'San Juan Guarita'),(1,13,'San Manuel Colohete'),(1,13,'San Rafael'),
 (1,13,'San Sebastián'),(1,13,'Santa Cruz'),(1,13,'Talgua'),(1,13,'Tambla'),
 (1,13,'Tomalá'),(1,13,'Valladolid'),(1,13,'Virginia'),(1,13,'San Marcos de Caiquín'),
 (1,14,'Ocotepeque'),(1,14,'Belén Gualcho'),(1,14,'Concepción'),(1,14,'Dolores Merendón'),
 (1,14,'Fraternidad'),(1,14,'La Encarnación'),(1,14,'La Labor'),(1,14,'Lucerna'),
 (1,14,'Mercedes'),(1,14,'San Fernando'),(1,14,'San Francisco del Valle'),(1,14,'San Jorge'),
 (1,14,'San Marcos'),(1,14,'Santa Fe'),(1,14,'Sensenti'),(1,14,'Sinuapa'),
 (1,15,'Juticalpa'),(1,15,'Campamento'),(1,15,'Catacamas'),(1,15,'Concordia'),
 (1,15,'Dulce Nombre de Culmí'),(1,15,'El Rosario'),(1,15,'Esquipulas del Norte'),(1,15,'Gualaco'),
 (1,15,'Guarizama'),(1,15,'Guata'),(1,15,'Guayape'),(1,15,'Jano'),
 (1,15,'La Unión'),(1,15,'Mangulile'),(1,15,'Manto'),(1,15,'Salamá'),
 (1,15,'San Esteban'),(1,15,'San Francisco de Becerra'),(1,15,'San Francisco de la Paz'),(1,15,'Santa María del Real'),
 (1,15,'Silca'),(1,15,'Yocón'),(1,15,'Patuca'),
 (1,16,'Santa Bárbara'),(1,16,'Arada'),(1,16,'Atima'),(1,16,'Azacualpa'),
 (1,16,'Ceguaca'),(1,16,'Concepción del Norte'),(1,16,'Concepción del Sur'),(1,16,'Chinda'),
 (1,16,'El Níspero'),(1,16,'Gualala'),(1,16,'Ilama'),(1,16,'Las Vegas'),
 (1,16,'Macuelizo'),(1,16,'Naranjito'),(1,16,'Nuevo Celilac'),(1,16,'Nueva Frontera'),
 (1,16,'Petoa'),(1,16,'Protección'),(1,16,'Quimistán'),(1,16,'San Francisco de Ojuera'),
 (1,16,'San José de Colinas'),(1,16,'San Luis'),(1,16,'San Marcos'),(1,16,'San Nicolás'),
 (1,16,'San Pedro Zacapa'),(1,16,'San Vicente Centenario'),(1,16,'Santa Rita'),(1,16,'Trinidad'),
 (1,17,'Nacaome'),(1,17,'Alianza'),(1,17,'Amapala'),(1,17,'Aramecina'),
 (1,17,'Caridad'),(1,17,'Goascorán'),(1,17,'Langue'),(1,17,'San Francisco de Coray'),
 (1,17,'San Lorenzo'),
 (1,18,'Yoro'),(1,18,'Arenal'),(1,18,'El Negrito'),(1,18,'El Progreso'),
 (1,18,'Jocón'),(1,18,'Morazán'),(1,18,'Olanchito'),(1,18,'Santa Rita'),
 (1,18,'Sulaco'),(1,18,'Victoria'),(1,18,'Yorito');

INSERT INTO sag_departamentos (id_departamento, id_proyecto, nombre)
 SELECT id_departamento + 100, 2, nombre FROM sag_departamentos WHERE id_proyecto = 1;

INSERT INTO sag_departamentos (id_departamento, id_proyecto, nombre)
 SELECT id_departamento + 200, 3, nombre FROM sag_departamentos WHERE id_proyecto = 1;

INSERT INTO sag_municipios (id_proyecto, id_departamento, nombre)
 SELECT 2, id_departamento + 100, nombre FROM sag_municipios WHERE id_proyecto = 1;

INSERT INTO sag_municipios (id_proyecto, id_departamento, nombre)
 SELECT 3, id_departamento + 200, nombre FROM sag_municipios WHERE id_proyecto = 1;

INSERT INTO sag_tipo_at (id_proyecto, nombre, icono) VALUES
 (1,'Visita a finca','fa-tractor'),
 (1,'Diagnóstico','fa-stethoscope'),
 (1,'Seguimiento técnico','fa-clipboard-check'),
 (1,'Capacitación grupal','fa-users'),
 (1,'Demostración de método','fa-chalkboard'),
 (1,'Asesoría fitosanitaria','fa-bug');

INSERT INTO sag_tipo_at (id_proyecto, nombre, icono)
 SELECT 2, nombre, icono FROM sag_tipo_at WHERE id_proyecto = 1;

INSERT INTO sag_tipo_at (id_proyecto, nombre, icono)
 SELECT 3, nombre, icono FROM sag_tipo_at WHERE id_proyecto = 1;

INSERT INTO sag_cultivos (id_proyecto, nombre, tipo) VALUES
 (1,'Café arábica','cultivo'),(1,'Café robusta','cultivo'),(1,'Plátano (sombra)','cultivo'),
 (1,'Cítricos','cultivo'),(1,'Aguacate','cultivo'),
 (2,'Bovino doble propósito','ganaderia'),(2,'Bovino de leche','ganaderia'),(2,'Bovino de carne','ganaderia'),
 (2,'Pastos mejorados','cultivo'),(2,'Forrajes','cultivo'),(2,'Apicultura','otro'),
 (3,'Maíz','cultivo'),(3,'Frijol','cultivo'),(3,'Arroz','cultivo'),
 (3,'Sorgo (maicillo)','cultivo'),(3,'Hortalizas','cultivo'),(3,'Plátano','cultivo');

INSERT INTO sag_temas (id_tema, id_proyecto, nombre, tipo) VALUES
 (1,1,'Manejo agronómico del café','ambos'),
 (2,1,'Manejo integrado de la roya','ambos'),
 (3,1,'Beneficiado y calidad','ambos'),
 (4,1,'Buenas prácticas agrícolas','ambos'),
 (11,2,'Manejo sanitario del hato','ambos'),
 (12,2,'Pastos y forrajes','ambos'),
 (13,2,'Producción de leche','ambos'),
 (14,2,'Nutrición animal','ambos'),
 (21,3,'Manejo de granos básicos','ambos'),
 (22,3,'Manejo integrado de plagas','ambos'),
 (23,3,'Fertilización de suelos','ambos'),
 (24,3,'Cosecha y poscosecha','ambos');

INSERT INTO sag_subtemas (id_proyecto, id_tema, nombre) VALUES
 (1,1,'Poda y manejo de tejido'),(1,1,'Fertilización'),
 (1,2,'Monitoreo de roya'),(1,2,'Control químico y cultural'),
 (1,3,'Fermentado y secado'),(1,3,'Catación y calidad'),
 (1,4,'Conservación de suelos'),
 (2,11,'Vacunación y desparasitación'),(2,11,'Prevención de mastitis'),
 (2,12,'Establecimiento de pasturas'),(2,12,'Ensilaje'),
 (2,13,'Ordeño higiénico'),
 (2,14,'Suplementación mineral'),
 (3,21,'Selección de semilla'),(3,21,'Densidad de siembra'),
 (3,22,'Monitoreo de plagas'),(3,22,'Manejo de malezas'),
 (3,23,'Análisis de suelo'),
 (3,24,'Almacenamiento de grano');
