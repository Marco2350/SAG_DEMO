-- =====================================================
--  MIGRACIÓN 009 — Tipo "Caja Rural" en sag_organizaciones (R-013)
--
--  El formulario de organizaciones ofrece "Caja Rural" pero el ENUM
--  no lo incluía: en MySQL 5.7 sin modo estricto se guardaba como ''.
--
--  NOTA: el servidor de producción es MySQL 5.7 — no soporta
--  ADD COLUMN IF NOT EXISTS (sintaxis MariaDB usada en migración 005).
--  Aplicada el 2026-06-10 junto con la 005 vía script idempotente.
-- =====================================================

ALTER TABLE sag_organizaciones
  MODIFY COLUMN tipo ENUM('cooperativa','asociacion','grupo','empresa','caja_rural','otro')
  NOT NULL DEFAULT 'cooperativa';
