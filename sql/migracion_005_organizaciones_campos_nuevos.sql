-- =====================================================
--  MIGRACIÓN 005 — Campos nuevos en sag_organizaciones
--  Cubre R-016 (DNI representante) y R-017 (lat/lon separados)
-- =====================================================

ALTER TABLE sag_organizaciones
  ADD COLUMN IF NOT EXISTS representante_dni VARCHAR(15) NULL AFTER representante;

ALTER TABLE sag_organizaciones
  ADD COLUMN IF NOT EXISTS latitud DECIMAL(10,7) NULL AFTER coordenadas;

ALTER TABLE sag_organizaciones
  ADD COLUMN IF NOT EXISTS longitud DECIMAL(10,7) NULL AFTER latitud;

-- Backfill: si hay registros con `coordenadas` "lat,lon", parsearlos a lat/lon
UPDATE sag_organizaciones
  SET latitud = CAST(SUBSTRING_INDEX(coordenadas, ',', 1) AS DECIMAL(10,7)),
      longitud = CAST(SUBSTRING_INDEX(coordenadas, ',', -1) AS DECIMAL(10,7))
WHERE coordenadas REGEXP '^-?[0-9.]+,-?[0-9.]+$'
  AND latitud IS NULL;

-- Índices para búsquedas geográficas
CREATE INDEX IF NOT EXISTS idx_lat_lon ON sag_organizaciones (latitud, longitud);
CREATE INDEX IF NOT EXISTS idx_rep_dni ON sag_organizaciones (representante_dni);
