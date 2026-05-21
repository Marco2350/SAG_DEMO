-- =====================================================
--  MIGRACIÓN 002 — Soft delete para Capacitaciones y AT
--  Ejecutar en CADA BD de programa: sag_pipc, sag_pipg, sag_pipa
-- =====================================================

-- ── CAPACITACIONES ───────────────────────────────────
ALTER TABLE sag_capacitaciones
    ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER estado,
    ADD INDEX idx_activo (activo);

-- ── ASISTENCIAS TÉCNICAS ─────────────────────────────
ALTER TABLE sag_asistencias_tecnicas
    ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER estado,
    ADD INDEX idx_activo (activo);

-- Verificación
SELECT 'sag_capacitaciones' AS tabla, COUNT(*) AS total, SUM(activo) AS activos FROM sag_capacitaciones
UNION
SELECT 'sag_asistencias_tecnicas', COUNT(*), SUM(activo) FROM sag_asistencias_tecnicas;
