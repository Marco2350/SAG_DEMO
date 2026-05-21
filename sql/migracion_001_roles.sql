-- =====================================================
--  MIGRACIÓN 001 — Agregar roles Coordinador y Jefe
--  Ejecutar en sag_main UNA SOLA VEZ
-- =====================================================

USE sag_main;

-- Insertar solo si no existen (idempotente)
INSERT INTO sag_roles (nombre, slug)
SELECT * FROM (
    SELECT 'Coordinador del Programa' AS nombre, 'coordinador' AS slug
    UNION SELECT 'Jefe Inmediato', 'jefe'
) AS nuevos
WHERE NOT EXISTS (
    SELECT 1 FROM sag_roles WHERE slug IN ('coordinador','jefe')
);

-- Verificar resultado
SELECT id_rol, nombre, slug FROM sag_roles ORDER BY id_rol;
