-- =====================================================
--  MIGRACIÓN 007 — R-029/030 en participantes de capacitaciones
--  - es_externo: marca al productor que no pertenece a la org anfitriona
--  - id_beneficiario: vínculo con el padrón (cuando viene del censo)
-- =====================================================

ALTER TABLE sag_capacitaciones_participantes
  ADD COLUMN IF NOT EXISTS es_externo TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS id_beneficiario INT NULL;

CREATE INDEX IF NOT EXISTS idx_part_externo ON sag_capacitaciones_participantes (es_externo);
CREATE INDEX IF NOT EXISTS idx_part_ben     ON sag_capacitaciones_participantes (id_beneficiario);
