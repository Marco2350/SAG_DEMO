-- =====================================================================
--  VERIFICACIÓN — Roles, privilegios y acceso multi-proyecto (migración 023)
--
--  Solo LECTURA. No modifica nada. Compatible con MySQL 5.7.
--  Correr sobre la base PRINCIPAL (sag_main).
--    phpMyAdmin → base sag_main → pestaña SQL → pegar TODO → Continuar.
--
--  Cada bloque devuelve una columna "estado" con OK / FALTA / REVISAR.
--  Si todos los chequeos dicen OK, la BD está lista.
-- =====================================================================

-- ─────────────────────────────────────────────────────────────────────
-- 1) Tablas nuevas existen
-- ─────────────────────────────────────────────────────────────────────
SELECT 'Tablas nuevas' AS chequeo, t.tabla,
       IF(EXISTS(SELECT 1 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = t.tabla),
          'OK', 'FALTA') AS estado
FROM (
  SELECT 'sag_modulos' AS tabla
  UNION SELECT 'sag_acciones'
  UNION SELECT 'sag_rol_privilegio'
  UNION SELECT 'sag_usuario_proyecto'
) t
ORDER BY t.tabla;

-- ─────────────────────────────────────────────────────────────────────
-- 2) Columnas nuevas existen (sag_roles + sag_usuarios)
-- ─────────────────────────────────────────────────────────────────────
SELECT 'Columnas nuevas' AS chequeo, c.tabla, c.columna,
       IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = c.tabla AND COLUMN_NAME = c.columna),
          'OK', 'FALTA') AS estado
FROM (
  SELECT 'sag_roles'    AS tabla, 'descripcion'     AS columna
  UNION SELECT 'sag_roles',    'es_admin'
  UNION SELECT 'sag_roles',    'activo'
  UNION SELECT 'sag_usuarios', 'todos_proyectos'
) c
ORDER BY c.tabla, c.columna;

-- ─────────────────────────────────────────────────────────────────────
-- 3) Conteo de catálogos sembrados (esperado: 16 módulos, 7 acciones)
-- ─────────────────────────────────────────────────────────────────────
SELECT 'Catálogo módulos'  AS chequeo, COUNT(*) AS total,
       IF(COUNT(*) >= 16, 'OK', 'REVISAR') AS estado FROM sag_modulos
UNION ALL
SELECT 'Catálogo acciones', COUNT(*),
       IF(COUNT(*) >= 7, 'OK', 'REVISAR') FROM sag_acciones;

-- ─────────────────────────────────────────────────────────────────────
-- 4) Roles administradores tienen la matriz COMPLETA
--    (16 módulos × 7 acciones = 112 privilegios cada uno)
-- ─────────────────────────────────────────────────────────────────────
SELECT 'Roles admin' AS chequeo, r.slug,
       COUNT(rp.id_accion) AS privilegios,
       (SELECT COUNT(*) FROM sag_modulos) * (SELECT COUNT(*) FROM sag_acciones) AS esperado,
       IF(COUNT(rp.id_accion) = (SELECT COUNT(*) FROM sag_modulos) * (SELECT COUNT(*) FROM sag_acciones),
          'OK', 'REVISAR') AS estado
FROM sag_roles r
LEFT JOIN sag_rol_privilegio rp ON rp.id_rol = r.id_rol
WHERE r.es_admin = 1
GROUP BY r.id_rol, r.slug
ORDER BY r.slug;

-- ─────────────────────────────────────────────────────────────────────
-- 5) Privilegios por rol (panorama general). 0 en un rol no-admin = sospechoso.
-- ─────────────────────────────────────────────────────────────────────
SELECT 'Privilegios por rol' AS chequeo, r.slug, r.es_admin, r.activo,
       COUNT(rp.id_accion) AS privilegios,
       IF(r.es_admin = 0 AND COUNT(rp.id_accion) = 0, 'REVISAR', 'OK') AS estado
FROM sag_roles r
LEFT JOIN sag_rol_privilegio rp ON rp.id_rol = r.id_rol
GROUP BY r.id_rol, r.slug, r.es_admin, r.activo
ORDER BY r.es_admin DESC, r.slug;

-- ─────────────────────────────────────────────────────────────────────
-- 6) Integridad referencial de la matriz (no debe haber huérfanos)
-- ─────────────────────────────────────────────────────────────────────
SELECT 'Huérfanos en matriz' AS chequeo,
       SUM(r.id_rol    IS NULL) AS rol_inexistente,
       SUM(m.id_modulo IS NULL) AS modulo_inexistente,
       SUM(a.id_accion IS NULL) AS accion_inexistente,
       IF(SUM(r.id_rol IS NULL) + SUM(m.id_modulo IS NULL) + SUM(a.id_accion IS NULL) = 0,
          'OK', 'REVISAR') AS estado
FROM sag_rol_privilegio rp
LEFT JOIN sag_roles    r ON r.id_rol    = rp.id_rol
LEFT JOIN sag_modulos  m ON m.id_modulo = rp.id_modulo
LEFT JOIN sag_acciones a ON a.id_accion = rp.id_accion;

-- ─────────────────────────────────────────────────────────────────────
-- 7) Alcance de proyectos por usuario
--    todos_proyectos=1  → acceso total
--    todos_proyectos=0  → debe tener al menos 1 fila en sag_usuario_proyecto
-- ─────────────────────────────────────────────────────────────────────
SELECT 'Alcance usuarios' AS chequeo,
       u.id_usuario, u.username, r.slug AS rol,
       u.todos_proyectos,
       COUNT(up.id_proyecto) AS proyectos_asignados,
       GROUP_CONCAT(p.codigo ORDER BY p.codigo) AS proyectos,
       CASE
         WHEN r.es_admin = 1                         THEN 'OK (admin)'
         WHEN u.todos_proyectos = 1                  THEN 'OK (todos)'
         WHEN COUNT(up.id_proyecto) > 0              THEN 'OK'
         ELSE 'REVISAR (sin acceso)'
       END AS estado
FROM sag_usuarios u
JOIN sag_roles r ON r.id_rol = u.id_rol
LEFT JOIN sag_usuario_proyecto up ON up.id_usuario = u.id_usuario
LEFT JOIN sag_proyectos p ON p.id_proyecto = up.id_proyecto
WHERE u.activo = 1
GROUP BY u.id_usuario, u.username, r.slug, r.es_admin, u.todos_proyectos
ORDER BY u.username;

-- ─────────────────────────────────────────────────────────────────────
-- 8) Usuarios que apuntan a un rol inexistente o inactivo (no debería haber)
-- ─────────────────────────────────────────────────────────────────────
SELECT 'Usuarios sin rol válido' AS chequeo,
       COUNT(*) AS usuarios,
       IF(COUNT(*) = 0, 'OK', 'REVISAR') AS estado
FROM sag_usuarios u
LEFT JOIN sag_roles r ON r.id_rol = u.id_rol
WHERE u.activo = 1 AND (r.id_rol IS NULL OR r.activo = 0);

-- ─────────────────────────────────────────────────────────────────────
-- 9) Debe existir al menos un administrador activo (no quedar sin acceso)
-- ─────────────────────────────────────────────────────────────────────
SELECT 'Admin activo existe' AS chequeo,
       COUNT(*) AS admins_activos,
       IF(COUNT(*) >= 1, 'OK', 'REVISAR') AS estado
FROM sag_usuarios u
JOIN sag_roles r ON r.id_rol = u.id_rol
WHERE u.activo = 1 AND r.es_admin = 1;
