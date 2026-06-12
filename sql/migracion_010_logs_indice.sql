-- =====================================================
--  MIGRACIÓN 010 — Índice por fecha en sag_logs
--
--  El visor de Auditoría ordena y filtra por created_at;
--  sin índice cada página recorre la tabla completa.
--
--  NOTA MySQL 5.7: CREATE INDEX IF NOT EXISTS no existe.
--  Verificar antes en INFORMATION_SCHEMA.STATISTICS o
--  aplicar vía script idempotente (patrón migrar_006.php).
-- =====================================================

CREATE INDEX idx_logs_fecha ON sag_logs (created_at);
