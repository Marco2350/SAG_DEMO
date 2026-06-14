-- =====================================================
--  MIGRACION 012 — Identidad del programa FPROG 2026
--  SAG Honduras Sin Hambre · Junio 2026
--
--  Reemplaza la identidad del FPROG genérico (id_proyecto = 4) por
--  el programa "Fortalecimiento de Programas y Proyectos SAG 2026"
--  según el perfil del PPTX presentado por el Despacho del Secretario.
--
--  La tabla seed bd_mddesarr_sag.sql sólo creaba PIPC/PIPG/PIPA;
--  esta migración asegura que el id_proyecto = 4 EXISTA antes de
--  que se le asocien componentes, riesgos, equipo y cronograma.
--
--  Identidad institucional:
--    - Ejecutor: SAG
--    - Administrador de fondos: IICA (RCI 5%)
--    - Marco político: PESAH 2023–2043
--    - Marco legal: Decreto Legislativo No. 04-2025
--    - Presupuesto: L. 116,423,410.97
--    - Beneficiarios: 5,500
--    - Período: Junio – Diciembre 2026
--
--  APLICACION (idempotente — se puede correr varias veces):
--    USE mddesarr_sag;
--    SOURCE migracion_006_fprog_identidad.sql;
--
--  ROLLBACK manual (sólo si se desea revertir la identidad nueva):
--    UPDATE sag_proyectos
--       SET codigo = 'fprog',
--           nombre = 'Fortalecimiento de Programas SAG',
--           sigla  = 'FPROG'
--     WHERE id_proyecto = 4;
--    -- (NO se borra el id_proyecto = 4: si tiene datos asociados
--    --  el rollback debe planificarse aparte.)
-- =====================================================

-- ───────────────────────────────────────────────────────
--  1. Asegurar que id_proyecto = 4 exista en sag_proyectos.
--     INSERT IGNORE para no fallar si ya existe.
-- ───────────────────────────────────────────────────────
INSERT IGNORE INTO sag_proyectos (id_proyecto, codigo, nombre, sigla)
VALUES (4, 'fprog',
        'Fortalecimiento de Programas y Proyectos SAG 2026',
        'FPROG');

-- ───────────────────────────────────────────────────────
--  2. Actualizar la identidad (siempre): nombre nuevo del perfil 2026.
--     Si la fila ya existía con un nombre distinto, se reemplaza.
-- ───────────────────────────────────────────────────────
UPDATE sag_proyectos
   SET codigo = 'fprog',
       nombre = 'Fortalecimiento de Programas y Proyectos SAG 2026',
       sigla  = 'FPROG'
 WHERE id_proyecto = 4;

-- =====================================================
--  FIN MIGRACION 006
-- =====================================================
