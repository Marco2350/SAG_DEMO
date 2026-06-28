-- =====================================================
-- MIGRACIÓN 022 — Integración idempotente OIRSA ↔ Inventario
-- Base unificada SAG_DEMO · Junio 2026
-- =====================================================

ALTER TABLE sag_bodegas
  ADD COLUMN oirsa_cue VARCHAR(60) NULL
    COMMENT 'Source/DestinyEndpointCode de OIRSA' AFTER codigo;

ALTER TABLE sag_inventario_productos
  ADD COLUMN oirsa_codigo VARCHAR(80) NULL
    COMMENT 'ProductTypeCode de OIRSA' AFTER codigo;

ALTER TABLE sag_inventario_movimientos
  MODIFY COLUMN id_origen BIGINT NULL,
  ADD COLUMN referencia_externa VARCHAR(190) NULL
    COMMENT 'Clave idempotente de la fuente externa' AFTER id_origen;

-- Completar únicamente correspondencias inequívocas ya presentes.
UPDATE sag_bodegas b
SET b.oirsa_cue = b.codigo
WHERE b.oirsa_cue IS NULL
  AND EXISTS (
      SELECT 1
      FROM sag_trazaragro_movimientos m
      WHERE m.id_proyecto = b.id_proyecto
        AND (m.origen_cue = b.codigo OR m.destino_cue = b.codigo)
  );

UPDATE sag_inventario_productos p
JOIN (
    SELECT id_proyecto, objeto_trazable,
           MIN(objeto_trazable_codigo) AS codigo
    FROM sag_trazaragro_movimientos
    WHERE objeto_trazable_codigo IS NOT NULL
    GROUP BY id_proyecto, objeto_trazable
    HAVING COUNT(DISTINCT objeto_trazable_codigo) = 1
) o ON o.id_proyecto = p.id_proyecto
   AND o.objeto_trazable = p.nombre
SET p.oirsa_codigo = o.codigo
WHERE p.oirsa_codigo IS NULL;

CREATE UNIQUE INDEX uq_bodega_oirsa_cue
  ON sag_bodegas (id_proyecto, oirsa_cue);

CREATE UNIQUE INDEX uq_producto_oirsa_codigo
  ON sag_inventario_productos (id_proyecto, oirsa_codigo);

CREATE UNIQUE INDEX uq_inv_origen_referencia
  ON sag_inventario_movimientos (id_proyecto, origen, referencia_externa);
