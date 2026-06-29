-- =====================================================================
--  MIGRACIÓN 023 — Roles, privilegios en BD y acceso multi-proyecto
--
--  Aplica en sag_main (DB principal). Idempotente y COMPATIBLE CON MySQL 5.7.
--
--  Importante: MySQL 5.7 NO soporta «ALTER TABLE ... ADD COLUMN IF NOT EXISTS»
--  (eso es sintaxis de MariaDB). Por eso las columnas se agregan de forma
--  condicional usando information_schema + PREPARE, que funciona en 5.7 y 8.x.
--
--  Cómo correrlo:
--    - phpMyAdmin → base sag_main → pestaña SQL → pegar TODO → Continuar.
--    - O CLI:  mysql -u USUARIO -p sag_main < sql/migracion_023_roles_privilegios.sql
--    - O el runner:  http://localhost/SAG_DEMO/migrar_009.php?modo=ejecutar
-- =====================================================================

-- ─────────────────────────────────────────────────────────────────────
-- 1) Enriquecer sag_roles (idempotente, 5.7-safe)
-- ─────────────────────────────────────────────────────────────────────
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sag_roles' AND COLUMN_NAME='descripcion');
SET @s := IF(@c=0, 'ALTER TABLE sag_roles ADD COLUMN descripcion VARCHAR(255) NULL AFTER nombre', 'DO 0');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sag_roles' AND COLUMN_NAME='es_admin');
SET @s := IF(@c=0, 'ALTER TABLE sag_roles ADD COLUMN es_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER descripcion', 'DO 0');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sag_roles' AND COLUMN_NAME='activo');
SET @s := IF(@c=0, 'ALTER TABLE sag_roles ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER es_admin', 'DO 0');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Roles canónicos + heredados (idempotente por slug UNIQUE)
INSERT INTO sag_roles (slug, nombre, descripcion, es_admin, activo) VALUES
  ('super_admin',       'Super Administrador',     'Acceso total al sistema, todos los proyectos y módulos.', 1, 1),
  ('admin',             'Administrador',           'Rol administrador heredado con acceso total.',            1, 1),
  ('administrador',     'Administrador',           'Alias heredado del rol administrador.',                   1, 1),
  ('coord_nacional',    'Coordinador Nacional',    'Operación nacional de los tres programas.',               0, 1),
  ('coordinador',       'Coordinador',             'Rol coordinador heredado.',                               0, 1),
  ('coord_pip',         'Coordinador de PIP',      'Gestión integral del programa asignado.',                 0, 1),
  ('tecnico_campo',     'Técnico de Campo',        'Captura operativa de asistencia y capacitaciones.',       0, 1),
  ('tecnico',           'Técnico',                 'Rol técnico heredado.',                                   0, 1),
  ('admin_bodega',      'Administrador de Bodega', 'Gestión de inventario y recepción de insumos.',           0, 1),
  ('admin_presupuesto', 'Administrador de Programa','Gestión presupuestaria del programa asignado.',          0, 1),
  ('jefe',              'Jefatura',                'Revisión y visto bueno presupuestario.',                  0, 1)
ON DUPLICATE KEY UPDATE
  nombre      = VALUES(nombre),
  descripcion = VALUES(descripcion),
  es_admin    = VALUES(es_admin),
  activo      = 1;

-- ─────────────────────────────────────────────────────────────────────
-- 2) Catálogo de módulos
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_modulos (
  id_modulo INT AUTO_INCREMENT PRIMARY KEY,
  slug      VARCHAR(40) NOT NULL UNIQUE,
  nombre    VARCHAR(80) NOT NULL,
  orden     INT NOT NULL DEFAULT 0,
  activo    TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO sag_modulos (slug, nombre, orden) VALUES
  ('dashboard',      'Dashboard',                 1),
  ('organizaciones', 'Organizaciones',            2),
  ('beneficiarios',  'Beneficiarios',             3),
  ('asistencia',     'Asistencia Técnica',        4),
  ('capacitaciones', 'Capacitaciones',            5),
  ('entregas',       'Entregas',                  6),
  ('inventarios',    'Inventarios',               7),
  ('movilizaciones', 'Movilizaciones OIRSA',      8),
  ('fprog',          'Fortalecimiento de Programa',9),
  ('estadisticas',   'Estadísticas',             10),
  ('exportar',       'Exportar',                 11),
  ('presupuesto',    'Presupuesto',              12),
  ('flota',          'Flota Vehicular',          13),
  ('catalogos',      'Catálogos',                14),
  ('mantenimiento',  'Mantenimiento',            15),
  ('auditoria',      'Auditoría',                16)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), orden = VALUES(orden);

-- ─────────────────────────────────────────────────────────────────────
-- 3) Catálogo de acciones
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_acciones (
  id_accion INT AUTO_INCREMENT PRIMARY KEY,
  slug      VARCHAR(20) NOT NULL UNIQUE,
  nombre    VARCHAR(40) NOT NULL,
  orden     INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO sag_acciones (slug, nombre, orden) VALUES
  ('ver',      'Ver',              1),
  ('crear',    'Crear',            2),
  ('editar',   'Editar',           3),
  ('eliminar', 'Eliminar',         4),
  ('aprobar',  'Aprobar',          5),
  ('exportar', 'Exportar',         6),
  ('cargar',   'Cargar evidencia', 7)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), orden = VALUES(orden);

-- ─────────────────────────────────────────────────────────────────────
-- 4) Matriz de privilegios rol × módulo × acción
--    La presencia de la fila = permiso concedido.
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sag_rol_privilegio (
  id_rol    INT NOT NULL,
  id_modulo INT NOT NULL,
  id_accion INT NOT NULL,
  PRIMARY KEY (id_rol, id_modulo, id_accion),
  FOREIGN KEY (id_rol)    REFERENCES sag_roles(id_rol)      ON DELETE CASCADE,
  FOREIGN KEY (id_modulo) REFERENCES sag_modulos(id_modulo)  ON DELETE CASCADE,
  FOREIGN KEY (id_accion) REFERENCES sag_acciones(id_accion) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────────────
-- 5) Asignación usuario × proyecto (N:M) + flag de acceso total
-- ─────────────────────────────────────────────────────────────────────
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sag_usuarios' AND COLUMN_NAME='todos_proyectos');
SET @s := IF(@c=0, 'ALTER TABLE sag_usuarios ADD COLUMN todos_proyectos TINYINT(1) NOT NULL DEFAULT 0 AFTER id_rol', 'DO 0');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

CREATE TABLE IF NOT EXISTS sag_usuario_proyecto (
  id_usuario  INT NOT NULL,
  id_proyecto INT NOT NULL,
  PRIMARY KEY (id_usuario, id_proyecto),
  FOREIGN KEY (id_usuario)  REFERENCES sag_usuarios(id_usuario)   ON DELETE CASCADE,
  FOREIGN KEY (id_proyecto) REFERENCES sag_proyectos(id_proyecto) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
--  SIEMBRA DE PRIVILEGIOS  (reproduce config/permisos.php)
-- =====================================================================

-- 5a) Admins: acceso total = todos los módulos × todas las acciones
INSERT IGNORE INTO sag_rol_privilegio (id_rol, id_modulo, id_accion)
SELECT r.id_rol, m.id_modulo, a.id_accion
FROM sag_roles   r
JOIN sag_modulos m
JOIN sag_acciones a
WHERE r.slug IN ('super_admin', 'admin', 'administrador');

-- 5b) Resto de roles: definidos por grupos de acción
--     full=todo · lectura=ver · lect_export=ver,exportar
--     captura=ver,crear,editar · captura_carga=+cargar
--     entregas_bodega=ver,cargar · presup_aprobar=ver,crear,editar,aprobar
DROP TEMPORARY TABLE IF EXISTS tmp_grupo_accion;
CREATE TEMPORARY TABLE tmp_grupo_accion (grupo VARCHAR(20), accion VARCHAR(20)) ENGINE=MEMORY;
INSERT INTO tmp_grupo_accion VALUES
  ('full','ver'),('full','crear'),('full','editar'),('full','eliminar'),('full','aprobar'),('full','exportar'),('full','cargar'),
  ('lectura','ver'),
  ('lect_export','ver'),('lect_export','exportar'),
  ('captura','ver'),('captura','crear'),('captura','editar'),
  ('captura_carga','ver'),('captura_carga','crear'),('captura_carga','editar'),('captura_carga','cargar'),
  ('entregas_bodega','ver'),('entregas_bodega','cargar'),
  ('presup_aprobar','ver'),('presup_aprobar','crear'),('presup_aprobar','editar'),('presup_aprobar','aprobar');

DROP TEMPORARY TABLE IF EXISTS tmp_rol_mod_grupo;
CREATE TEMPORARY TABLE tmp_rol_mod_grupo (rol VARCHAR(40), modulo VARCHAR(40), grupo VARCHAR(20)) ENGINE=MEMORY;
INSERT INTO tmp_rol_mod_grupo VALUES
  -- coord_nacional
  ('coord_nacional','dashboard','lectura'),
  ('coord_nacional','organizaciones','full'),
  ('coord_nacional','beneficiarios','full'),
  ('coord_nacional','asistencia','full'),
  ('coord_nacional','capacitaciones','full'),
  ('coord_nacional','entregas','full'),
  ('coord_nacional','inventarios','full'),
  ('coord_nacional','movilizaciones','lect_export'),
  ('coord_nacional','fprog','full'),
  ('coord_nacional','estadisticas','lectura'),
  ('coord_nacional','exportar','lect_export'),
  ('coord_nacional','presupuesto','full'),
  ('coord_nacional','flota','full'),
  ('coord_nacional','auditoria','lectura'),
  -- coordinador (heredado)
  ('coordinador','dashboard','lectura'),
  ('coordinador','organizaciones','full'),
  ('coordinador','beneficiarios','full'),
  ('coordinador','asistencia','full'),
  ('coordinador','capacitaciones','full'),
  ('coordinador','entregas','full'),
  ('coordinador','inventarios','full'),
  ('coordinador','movilizaciones','lect_export'),
  ('coordinador','fprog','full'),
  ('coordinador','estadisticas','lectura'),
  ('coordinador','exportar','lect_export'),
  ('coordinador','presupuesto','full'),
  ('coordinador','flota','full'),
  ('coordinador','catalogos','full'),
  ('coordinador','mantenimiento','full'),
  -- coord_pip
  ('coord_pip','dashboard','lectura'),
  ('coord_pip','organizaciones','full'),
  ('coord_pip','beneficiarios','full'),
  ('coord_pip','asistencia','full'),
  ('coord_pip','capacitaciones','full'),
  ('coord_pip','entregas','full'),
  ('coord_pip','inventarios','full'),
  ('coord_pip','movilizaciones','lect_export'),
  ('coord_pip','fprog','full'),
  ('coord_pip','estadisticas','lectura'),
  ('coord_pip','exportar','lect_export'),
  ('coord_pip','presupuesto','full'),
  ('coord_pip','flota','full'),
  ('coord_pip','catalogos','full'),
  ('coord_pip','mantenimiento','full'),
  -- tecnico_campo
  ('tecnico_campo','dashboard','lectura'),
  ('tecnico_campo','organizaciones','lectura'),
  ('tecnico_campo','beneficiarios','captura'),
  ('tecnico_campo','asistencia','captura_carga'),
  ('tecnico_campo','capacitaciones','captura_carga'),
  ('tecnico_campo','entregas','lectura'),
  ('tecnico_campo','inventarios','lectura'),
  ('tecnico_campo','movilizaciones','lectura'),
  ('tecnico_campo','estadisticas','lectura'),
  ('tecnico_campo','exportar','lect_export'),
  ('tecnico_campo','presupuesto','captura'),
  ('tecnico_campo','flota','lectura'),
  -- tecnico (heredado)
  ('tecnico','dashboard','lectura'),
  ('tecnico','organizaciones','lectura'),
  ('tecnico','beneficiarios','captura'),
  ('tecnico','asistencia','captura_carga'),
  ('tecnico','capacitaciones','captura_carga'),
  ('tecnico','entregas','lectura'),
  ('tecnico','inventarios','lectura'),
  ('tecnico','movilizaciones','lectura'),
  ('tecnico','estadisticas','lectura'),
  ('tecnico','exportar','lect_export'),
  ('tecnico','presupuesto','captura'),
  ('tecnico','flota','lectura'),
  -- admin_bodega
  ('admin_bodega','dashboard','lectura'),
  ('admin_bodega','organizaciones','lectura'),
  ('admin_bodega','beneficiarios','lectura'),
  ('admin_bodega','entregas','entregas_bodega'),
  ('admin_bodega','inventarios','full'),
  ('admin_bodega','movilizaciones','lect_export'),
  ('admin_bodega','estadisticas','lectura'),
  ('admin_bodega','exportar','lect_export'),
  ('admin_bodega','presupuesto','captura'),
  ('admin_bodega','flota','lectura'),
  -- admin_presupuesto
  ('admin_presupuesto','dashboard','lectura'),
  ('admin_presupuesto','estadisticas','lectura'),
  ('admin_presupuesto','exportar','lect_export'),
  ('admin_presupuesto','presupuesto','full'),
  ('admin_presupuesto','flota','lectura'),
  -- jefe (heredado)
  ('jefe','dashboard','lectura'),
  ('jefe','organizaciones','lectura'),
  ('jefe','beneficiarios','lectura'),
  ('jefe','asistencia','lectura'),
  ('jefe','capacitaciones','lectura'),
  ('jefe','estadisticas','lectura'),
  ('jefe','exportar','lect_export'),
  ('jefe','presupuesto','presup_aprobar'),
  ('jefe','flota','lectura');

INSERT IGNORE INTO sag_rol_privilegio (id_rol, id_modulo, id_accion)
SELECT r.id_rol, m.id_modulo, a.id_accion
FROM tmp_rol_mod_grupo x
JOIN tmp_grupo_accion  g ON g.grupo  = x.grupo
JOIN sag_roles    r ON r.slug   = x.rol
JOIN sag_modulos  m ON m.slug   = x.modulo
JOIN sag_acciones a ON a.slug   = g.accion;

DROP TEMPORARY TABLE IF EXISTS tmp_grupo_accion;
DROP TEMPORARY TABLE IF EXISTS tmp_rol_mod_grupo;

-- =====================================================================
-- 6) Acceso por proyecto de los usuarios existentes
--    Si existe la columna heredada programa_asignado (instalaciones MariaDB),
--    se migra: NULL/'' → todos los proyectos; con PIP → fila en la puente.
--    Si NO existe (este MySQL 5.7), los usuarios actuales reciben acceso
--    total para no quedar bloqueados; luego se ajusta desde la UI.
-- =====================================================================
SET @hascol := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='sag_usuarios' AND COLUMN_NAME='programa_asignado');

SET @s := IF(@hascol>0,
  'UPDATE sag_usuarios SET todos_proyectos=1 WHERE (programa_asignado IS NULL OR TRIM(programa_asignado)='''') AND todos_proyectos=0',
  'UPDATE sag_usuarios SET todos_proyectos=1 WHERE todos_proyectos=0');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := IF(@hascol>0,
  'INSERT IGNORE INTO sag_usuario_proyecto (id_usuario,id_proyecto) SELECT u.id_usuario,p.id_proyecto FROM sag_usuarios u JOIN sag_proyectos p ON p.codigo=LOWER(TRIM(u.programa_asignado)) WHERE u.programa_asignado IS NOT NULL AND TRIM(u.programa_asignado)<>''''',
  'DO 0');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- =====================================================================
-- 7) Verificación (descomentar para inspeccionar)
-- =====================================================================
-- SELECT r.slug, COUNT(rp.id_accion) AS privilegios
--   FROM sag_roles r LEFT JOIN sag_rol_privilegio rp ON rp.id_rol=r.id_rol
--   GROUP BY r.id_rol ORDER BY r.slug;
-- SELECT u.username, u.todos_proyectos, GROUP_CONCAT(p.codigo) AS proyectos
--   FROM sag_usuarios u
--   LEFT JOIN sag_usuario_proyecto up ON up.id_usuario=u.id_usuario
--   LEFT JOIN sag_proyectos p ON p.id_proyecto=up.id_proyecto
--   GROUP BY u.id_usuario ORDER BY u.username;
