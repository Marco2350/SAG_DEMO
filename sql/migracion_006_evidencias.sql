-- =====================================================
--  MIGRACIÓN 006 — Evidencias documentales
--  R-027 / R-028: Carga de listado/evidencia en
--  Asistencia Técnica y Capacitaciones (PDF, Excel, imágenes)
--  Estados: pendiente, cargada, validada, rechazada
-- =====================================================

ALTER TABLE sag_capacitaciones
  ADD COLUMN IF NOT EXISTS evidencia_archivo VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS evidencia_nombre_original VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS evidencia_mime VARCHAR(80) NULL,
  ADD COLUMN IF NOT EXISTS evidencia_tamano INT NULL,
  ADD COLUMN IF NOT EXISTS evidencia_subida_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS evidencia_subida_por INT NULL,
  ADD COLUMN IF NOT EXISTS evidencia_estado ENUM('pendiente','cargada','validada','rechazada')
        NOT NULL DEFAULT 'pendiente',
  ADD COLUMN IF NOT EXISTS evidencia_observaciones TEXT NULL;

ALTER TABLE sag_asistencias_tecnicas
  ADD COLUMN IF NOT EXISTS evidencia_archivo VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS evidencia_nombre_original VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS evidencia_mime VARCHAR(80) NULL,
  ADD COLUMN IF NOT EXISTS evidencia_tamano INT NULL,
  ADD COLUMN IF NOT EXISTS evidencia_subida_at DATETIME NULL,
  ADD COLUMN IF NOT EXISTS evidencia_subida_por INT NULL,
  ADD COLUMN IF NOT EXISTS evidencia_estado ENUM('pendiente','cargada','validada','rechazada')
        NOT NULL DEFAULT 'pendiente',
  ADD COLUMN IF NOT EXISTS evidencia_observaciones TEXT NULL;

CREATE INDEX IF NOT EXISTS idx_ev_estado_cap ON sag_capacitaciones (evidencia_estado);
CREATE INDEX IF NOT EXISTS idx_ev_estado_at  ON sag_asistencias_tecnicas (evidencia_estado);
