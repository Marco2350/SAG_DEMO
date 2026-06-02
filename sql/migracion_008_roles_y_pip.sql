-- =====================================================
--  MIGRACIÓN 008 — Sistema de Roles unificado (R-032 / R-033)
--
--  Esta migración se aplica en sag_main (DB principal, NO en los PIP).
--
--  1. Sembrar los 6 roles definidos en config/permisos.php en sag_roles
--  2. Agregar columna programa_asignado a sag_usuarios (alcance PIP)
-- =====================================================

-- ── Columna programa_asignado en sag_usuarios ──
ALTER TABLE sag_usuarios
  ADD COLUMN IF NOT EXISTS programa_asignado VARCHAR(10) NULL
        COMMENT 'pipc | pipg | pipa | NULL (sin restricción de PIP)' AFTER id_rol;

CREATE INDEX IF NOT EXISTS idx_usu_prog ON sag_usuarios (programa_asignado);

-- ── Roles del sistema (idempotente: usa INSERT IGNORE / ON DUPLICATE) ──
INSERT INTO sag_roles (slug, nombre, descripcion, activo) VALUES
  ('super_admin',       'Super Administrador',       'Acceso total a los 3 PIP, todos los módulos y acciones, incluyendo mantenimiento.', 1),
  ('coord_nacional',    'Coordinador Nacional',      'Acceso consolidado a los 3 PIP. Gestiona operación nacional sin administración de catálogos.', 1),
  ('coord_pip',         'Coordinador de PIP',        'Acceso completo al PIP asignado: operación, validación, reportes.', 1),
  ('tecnico_campo',     'Técnico de Campo',          'Registra visitas, capacitaciones y participantes. No aprueba ni elimina.', 1),
  ('admin_bodega',      'Administrador de Bodega',   'Gestiona inventarios, cronogramas y recepción de insumos del PIP asignado.', 1),
  ('admin_presupuesto', 'Administrador de Programa', 'Administra ejecución presupuestaria del PIP asignado.', 1)
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre),
  descripcion = VALUES(descripcion),
  activo = 1;
