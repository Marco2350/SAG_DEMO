-- =====================================================
--  MIGRACION 016 — Seed inicial FPROG 2026 (perfil PPTX)
--  SAG Honduras Sin Hambre · Junio 2026
--
--  Carga los datos del "Perfil de Programa" presentado
--  por el Despacho del Secretario (PPTX Junio 2026):
--    - 7 componentes con presupuesto, meta y categoría
--    - 5 riesgos del análisis prob×impacto
--    - 8 roles del equipo técnico (Coord + 4 Regionales +
--      M&E + Admin + Auditor + Técnicos)
--    - 12 actividades del cronograma Jun–Dic
--
--  Idempotente: cada fila usa WHERE NOT EXISTS contra un
--  identificador estable (numero_romano para componentes,
--  texto distintivo para riesgos/equipo/cronograma).
--  Es seguro correr esta migración varias veces.
--
--  Prerequisitos:
--    - migracion_012_fprog_identidad.sql (id_proyecto=4 existe)
--    - migracion_013_fp_componentes.sql  (sag_fp_componentes)
--    - migracion_015_fp_riesgos_equipo_cronograma.sql (3 tablas)
--
--  APLICACION:
--    USE mddesarr_sag;
--    SOURCE migracion_016_seed_fprog_2026.sql;
--
--  ROLLBACK manual (sólo si se desea quitar el seed):
--    DELETE FROM sag_fp_cronograma WHERE id_proyecto = 4;
--    DELETE FROM sag_fp_equipo     WHERE id_proyecto = 4;
--    DELETE FROM sag_fp_riesgos    WHERE id_proyecto = 4;
--    DELETE FROM sag_fp_componentes WHERE id_proyecto = 4;
--    (Coordinar antes con auditoría si ya hay datos
--     producidos por usuarios encima del seed.)
-- =====================================================

SET NAMES utf8mb4;

-- ===========================================================
--  1. COMPONENTES — INSERT IGNORE aprovecha UNIQUE
--     (id_proyecto, numero_romano)
-- ===========================================================
INSERT IGNORE INTO sag_fp_componentes (
    id_proyecto, numero_romano, nombre, descripcion, categoria,
    presupuesto_asignado, presupuesto_ejecutado, moneda,
    meta_unidad, meta_valor, medio_verificacion,
    fecha_inicio, fecha_fin, estado
) VALUES
(4, 'I',
 'Multiplicación de Semilla Certificada',
 'Multiplicadores con 1–5 ha y acceso a agua; variedades de granos básicos certificadas y acondicionamiento de estructuras de almacenamiento.',
 'agricola',
 3063059.32, 0, 'HNL',
 'Ha vinculadas a producción de semilla', 312,
 'Registro técnico + fotografías',
 '2026-07-01', '2026-11-30', 'planificado'),

(4, 'II',
 'Rehabilitación de Reservorios con Geomembrana',
 'Revestimiento con geomembrana de reservorios existentes con fugas; no construye nuevos. Prioriza grupos organizados.',
 'infraestructura',
 15500100.00, 0, 'HNL',
 'm² de captación restaurados', 100000,
 'Informes + inspección',
 '2026-08-01', '2026-11-30', 'planificado'),

(4, 'III',
 'Parcelas Frutícolas Tecnificadas con Riego',
 'Dotación de plantas e insumos de frutales varios (aguacates, cítricos, pitaya, coco) a productores con ≥0.34 ha y acceso a riego (70+ plantas).',
 'agricola',
 9549200.00, 0, 'HNL',
 'productores con parcelas establecidas', 800,
 'Informes + fotografías',
 '2026-07-01', '2026-09-30', 'planificado'),

(4, 'IV',
 'Mejoramiento Genético del Hato (Embriones)',
 'Adquisición e implante de 420 embriones (140 Gyr Lechero + 140 Girolando + 140 Nelore) en vacas receptoras de productores individuales, con entrega en vaca preñada, protocolo veterinario y trazabilidad individual.',
 'pecuario',
 10500000.00, 0, 'HNL',
 'embriones implantados', 420,
 'Actas firmadas + trazabilidad individual',
 '2026-07-01', '2026-12-31', 'planificado'),

(4, 'V',
 'Módulos de Agua, Saleros y Sombra para Ganado',
 'Pilas prefabricadas para almacenamiento de agua, saleros para suplementación mineral y estructuras de sombra para unidades con déficit hídrico.',
 'pecuario',
 5100000.00, 0, 'HNL',
 'productores con módulos', 200,
 'Actas de entrega firmadas',
 '2026-08-01', '2026-10-31', 'planificado'),

(4, 'VI',
 'Fortalecimiento Nutricional Pecuario',
 'Paquete energético-mineral y asistencia técnica para formular recetas nutricionales por propósito productivo. Cobertura de 16 departamentos.',
 'pecuario',
 4527540.00, 0, 'HNL',
 'productores atendidos', 730,
 'Actas + fichas de capacitación',
 '2026-08-01', '2026-11-30', 'planificado'),

(4, 'VII',
 'Proyecto de Riego Productivo',
 '8,200 rollos de cinta de goteo para 2,700 ha y 100 kits de bomba solar (1,500 W) con riego por goteo para 1–5 ha.',
 'infraestructura',
 50720000.00, 0, 'HNL',
 'hectáreas con cinta + kits solares', 2700,
 'Actas + fichas + GPS + fotografías',
 '2026-07-01', '2026-10-31', 'planificado');

-- ===========================================================
--  2. RIESGOS — 5 riesgos del PPTX (slide 11)
--     Sin UNIQUE en la tabla → usamos WHERE NOT EXISTS por
--     descripción para evitar duplicados al re-ejecutar.
-- ===========================================================
INSERT INTO sag_fp_riesgos (
    id_proyecto, categoria, descripcion,
    probabilidad, impacto, medida_mitigacion,
    responsable, estado
)
SELECT 4, 'politico',
       'Retraso en la firma de la Carta de Entendimiento SAG–IICA',
       'alta', 'alto',
       'Paralelizar preparación de TdR y PAC con el proceso legal; firma antes del inicio de adquisiciones.',
       'Coordinador del Programa', 'identificado'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_riesgos
     WHERE id_proyecto = 4
       AND descripcion LIKE 'Retraso en la firma de la Carta%'
);

INSERT INTO sag_fp_riesgos (
    id_proyecto, categoria, descripcion,
    probabilidad, impacto, medida_mitigacion,
    responsable, estado
)
SELECT 4, 'financiero',
       'Incremento de precios de materiales durante la ejecución',
       'media', 'medio',
       'Licitación con especificaciones cerradas y validación de precios de mercado previa al PAC.',
       'Administrador/a', 'identificado'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_riesgos
     WHERE id_proyecto = 4
       AND descripcion LIKE 'Incremento de precios%'
);

INSERT INTO sag_fp_riesgos (
    id_proyecto, categoria, descripcion,
    probabilidad, impacto, medida_mitigacion,
    responsable, estado
)
SELECT 4, 'tecnico',
       'Variación en dimensiones o condiciones de los reservorios existentes',
       'media', 'alto',
       'Diagnóstico técnico previo, medición del área y priorización por número de beneficiarios.',
       'Coordinador del Componente II', 'identificado'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_riesgos
     WHERE id_proyecto = 4
       AND descripcion LIKE 'Variación en dimensiones%'
);

INSERT INTO sag_fp_riesgos (
    id_proyecto, categoria, descripcion,
    probabilidad, impacto, medida_mitigacion,
    responsable, estado
)
SELECT 4, 'operativo',
       'Disponibilidad limitada de plantas, cinta de goteo, bombas o geomembrana en el mercado',
       'media', 'alto',
       'Especificaciones oportunas, validación de proveedores y adquisición por lotes según ruta crítica.',
       'Administrador/a', 'identificado'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_riesgos
     WHERE id_proyecto = 4
       AND descripcion LIKE 'Disponibilidad limitada%'
);

INSERT INTO sag_fp_riesgos (
    id_proyecto, categoria, descripcion,
    probabilidad, impacto, medida_mitigacion,
    responsable, estado
)
SELECT 4, 'operativo',
       'Dificultad de coordinación operativa entre los siete componentes del programa',
       'media', 'medio',
       'Asignar recursos humanos por zonas y seguimiento mensual físico-financiero por componente.',
       'Coordinador del Programa', 'identificado'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_riesgos
     WHERE id_proyecto = 4
       AND descripcion LIKE 'Dificultad de coordinación%'
);

-- ===========================================================
--  3. EQUIPO TÉCNICO — 8 roles del PPTX (slide 9)
--     Total RH: L. 11,642,341.10 (10% del presupuesto)
--     Sin UNIQUE → WHERE NOT EXISTS por rol exacto.
-- ===========================================================
INSERT INTO sag_fp_equipo (
    id_proyecto, rol, cantidad, descripcion, ambito,
    porcentaje_presupuesto, estado
)
SELECT 4, 'Coordinador del Programa', 1,
       'Coordinación estratégica · enlace SAG–IICA · supervisión transversal de los 7 componentes',
       'Nacional', 2.0, 'vacante'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_equipo
     WHERE id_proyecto = 4 AND rol = 'Coordinador del Programa'
);

INSERT INTO sag_fp_equipo (
    id_proyecto, rol, cantidad, descripcion, ambito,
    porcentaje_presupuesto, estado
)
SELECT 4, 'Coordinador Regional', 4,
       'Supervisión técnica en campo, distribuidos en 4 zonas operativas',
       'Norte · Sur · Oriente · Occidente', 3.0, 'vacante'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_equipo
     WHERE id_proyecto = 4 AND rol = 'Coordinador Regional'
);

INSERT INTO sag_fp_equipo (
    id_proyecto, rol, cantidad, descripcion, ambito,
    porcentaje_presupuesto, estado
)
SELECT 4, 'Coordinación de Monitoreo y Evaluación', 1,
       'Indicadores físico-financieros y reportería al Despacho e IICA',
       'Nacional', 1.0, 'vacante'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_equipo
     WHERE id_proyecto = 4 AND rol = 'Coordinación de Monitoreo y Evaluación'
);

INSERT INTO sag_fp_equipo (
    id_proyecto, rol, cantidad, descripcion, ambito,
    porcentaje_presupuesto, estado
)
SELECT 4, 'Administrador/a', 1,
       'Gestión presupuestaria y de contrataciones bajo normativa IICA',
       'Nacional', 1.0, 'vacante'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_equipo
     WHERE id_proyecto = 4 AND rol = 'Administrador/a'
);

INSERT INTO sag_fp_equipo (
    id_proyecto, rol, cantidad, descripcion, ambito,
    porcentaje_presupuesto, estado
)
SELECT 4, 'Auditor/a Interno/a', 1,
       'Control de medios de verificación y cumplimiento ante el TSC',
       'Nacional', 1.0, 'vacante'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_equipo
     WHERE id_proyecto = 4 AND rol = 'Auditor/a Interno/a'
);

INSERT INTO sag_fp_equipo (
    id_proyecto, rol, cantidad, descripcion, ambito,
    porcentaje_presupuesto, estado
)
SELECT 4, 'Técnico de Campo — Componente I (Semilla)', 0,
       'Personal especializado en multiplicación de semilla certificada (cantidad a definir en PAC)',
       'Componente I', 0.5, 'vacante'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_equipo
     WHERE id_proyecto = 4 AND rol = 'Técnico de Campo — Componente I (Semilla)'
);

INSERT INTO sag_fp_equipo (
    id_proyecto, rol, cantidad, descripcion, ambito,
    porcentaje_presupuesto, estado
)
SELECT 4, 'Técnico de Campo — Componentes II y VII (Riego)', 0,
       'Personal especializado en reservorios, cinta de goteo y bombas solares (cantidad a definir en PAC)',
       'Componentes II y VII', 1.0, 'vacante'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_equipo
     WHERE id_proyecto = 4 AND rol = 'Técnico de Campo — Componentes II y VII (Riego)'
);

INSERT INTO sag_fp_equipo (
    id_proyecto, rol, cantidad, descripcion, ambito,
    porcentaje_presupuesto, estado
)
SELECT 4, 'Técnico de Campo — Componentes IV, V y VI (Pecuario)', 0,
       'Veterinarios y zootecnistas para mejoramiento genético, módulos ganaderos y nutrición pecuaria',
       'Componentes IV, V y VI', 0.5, 'vacante'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_equipo
     WHERE id_proyecto = 4 AND rol = 'Técnico de Campo — Componentes IV, V y VI (Pecuario)'
);

-- ===========================================================
--  4. CRONOGRAMA — 12 actividades del PPTX (slide 10)
--     Ruta de ejecución Jun–Dic 2026
--     Sin UNIQUE → WHERE NOT EXISTS por (numero_orden + actividad).
-- ===========================================================
INSERT INTO sag_fp_cronograma (
    id_proyecto, id_componente, numero_orden, actividad,
    mes_jun, mes_jul, mes_ago, mes_sep, mes_oct, mes_nov, mes_dic,
    responsable, estado
)
SELECT 4, NULL, 1, 'Firma CE SAG–IICA y conformación del equipo',
       1, 0, 0, 0, 0, 0, 0,
       'Coordinador del Programa', 'pendiente'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_cronograma
     WHERE id_proyecto = 4 AND numero_orden = 1
       AND actividad LIKE 'Firma CE SAG–IICA%'
);

INSERT INTO sag_fp_cronograma (
    id_proyecto, id_componente, numero_orden, actividad,
    mes_jun, mes_jul, mes_ago, mes_sep, mes_oct, mes_nov, mes_dic,
    responsable, estado
)
SELECT 4, NULL, 2, 'Licitaciones, contratos y adquisiciones',
       1, 1, 1, 0, 0, 0, 0,
       'Administrador/a', 'pendiente'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_cronograma
     WHERE id_proyecto = 4 AND numero_orden = 2
       AND actividad LIKE 'Licitaciones, contratos%'
);

INSERT INTO sag_fp_cronograma (
    id_proyecto, id_componente, numero_orden, actividad,
    mes_jun, mes_jul, mes_ago, mes_sep, mes_oct, mes_nov, mes_dic,
    responsable, estado
)
SELECT 4,
       (SELECT id_componente FROM sag_fp_componentes WHERE id_proyecto = 4 AND numero_romano = 'I' LIMIT 1),
       3, 'Multiplicación local de semilla',
       0, 1, 1, 1, 1, 1, 0,
       'Coordinador Regional', 'pendiente'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_cronograma
     WHERE id_proyecto = 4 AND numero_orden = 3
       AND actividad LIKE 'Multiplicación local de semilla%'
);

INSERT INTO sag_fp_cronograma (
    id_proyecto, id_componente, numero_orden, actividad,
    mes_jun, mes_jul, mes_ago, mes_sep, mes_oct, mes_nov, mes_dic,
    responsable, estado
)
SELECT 4,
       (SELECT id_componente FROM sag_fp_componentes WHERE id_proyecto = 4 AND numero_romano = 'II' LIMIT 1),
       4, 'Rehabilitación de reservorios con geomembrana',
       0, 0, 1, 1, 1, 1, 0,
       'Coordinador Regional', 'pendiente'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_cronograma
     WHERE id_proyecto = 4 AND numero_orden = 4
       AND actividad LIKE 'Rehabilitación de reservorios%'
);

INSERT INTO sag_fp_cronograma (
    id_proyecto, id_componente, numero_orden, actividad,
    mes_jun, mes_jul, mes_ago, mes_sep, mes_oct, mes_nov, mes_dic,
    responsable, estado
)
SELECT 4,
       (SELECT id_componente FROM sag_fp_componentes WHERE id_proyecto = 4 AND numero_romano = 'III' LIMIT 1),
       5, 'Parcelas frutícolas — entrega y establecimiento',
       0, 1, 1, 1, 0, 0, 0,
       'Coordinador Regional', 'pendiente'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_cronograma
     WHERE id_proyecto = 4 AND numero_orden = 5
       AND actividad LIKE 'Parcelas frutícolas%'
);

INSERT INTO sag_fp_cronograma (
    id_proyecto, id_componente, numero_orden, actividad,
    mes_jun, mes_jul, mes_ago, mes_sep, mes_oct, mes_nov, mes_dic,
    responsable, estado
)
SELECT 4,
       (SELECT id_componente FROM sag_fp_componentes WHERE id_proyecto = 4 AND numero_romano = 'I' LIMIT 1),
       6, 'Semilla certificada — distribución y capacitación',
       0, 1, 1, 1, 1, 0, 0,
       'Coordinador Regional', 'pendiente'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_cronograma
     WHERE id_proyecto = 4 AND numero_orden = 6
       AND actividad LIKE 'Semilla certificada — distribución%'
);

INSERT INTO sag_fp_cronograma (
    id_proyecto, id_componente, numero_orden, actividad,
    mes_jun, mes_jul, mes_ago, mes_sep, mes_oct, mes_nov, mes_dic,
    responsable, estado
)
SELECT 4,
       (SELECT id_componente FROM sag_fp_componentes WHERE id_proyecto = 4 AND numero_romano = 'V' LIMIT 1),
       7, 'Módulos ganaderos — pilas, saleros y sombra',
       0, 0, 1, 1, 1, 0, 0,
       'Coordinador Regional', 'pendiente'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_cronograma
     WHERE id_proyecto = 4 AND numero_orden = 7
       AND actividad LIKE 'Módulos ganaderos%'
);

INSERT INTO sag_fp_cronograma (
    id_proyecto, id_componente, numero_orden, actividad,
    mes_jun, mes_jul, mes_ago, mes_sep, mes_oct, mes_nov, mes_dic,
    responsable, estado
)
SELECT 4,
       (SELECT id_componente FROM sag_fp_componentes WHERE id_proyecto = 4 AND numero_romano = 'VI' LIMIT 1),
       8, 'Nutrición pecuaria — recursos locales y asistencia técnica',
       0, 0, 1, 1, 1, 1, 0,
       'Coordinador Regional', 'pendiente'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_cronograma
     WHERE id_proyecto = 4 AND numero_orden = 8
       AND actividad LIKE 'Nutrición pecuaria%'
);

INSERT INTO sag_fp_cronograma (
    id_proyecto, id_componente, numero_orden, actividad,
    mes_jun, mes_jul, mes_ago, mes_sep, mes_oct, mes_nov, mes_dic,
    responsable, estado
)
SELECT 4,
       (SELECT id_componente FROM sag_fp_componentes WHERE id_proyecto = 4 AND numero_romano = 'VII' LIMIT 1),
       9, 'Proyecto de riego — cinta, bombas e instalación',
       0, 1, 1, 1, 1, 0, 0,
       'Coordinador Regional', 'pendiente'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_cronograma
     WHERE id_proyecto = 4 AND numero_orden = 9
       AND actividad LIKE 'Proyecto de riego%'
);

INSERT INTO sag_fp_cronograma (
    id_proyecto, id_componente, numero_orden, actividad,
    mes_jun, mes_jul, mes_ago, mes_sep, mes_oct, mes_nov, mes_dic,
    responsable, estado
)
SELECT 4, NULL, 10, 'Monitoreo físico-financiero y reportería',
       1, 1, 1, 1, 1, 1, 1,
       'Coordinación de Monitoreo y Evaluación', 'pendiente'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_cronograma
     WHERE id_proyecto = 4 AND numero_orden = 10
       AND actividad LIKE 'Monitoreo físico-financiero%'
);

INSERT INTO sag_fp_cronograma (
    id_proyecto, id_componente, numero_orden, actividad,
    mes_jun, mes_jul, mes_ago, mes_sep, mes_oct, mes_nov, mes_dic,
    responsable, estado
)
SELECT 4, NULL, 11, 'Verificación de medios de verificación',
       0, 0, 0, 1, 1, 1, 1,
       'Coordinación de Monitoreo y Evaluación', 'pendiente'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_cronograma
     WHERE id_proyecto = 4 AND numero_orden = 11
       AND actividad LIKE 'Verificación de medios%'
);

INSERT INTO sag_fp_cronograma (
    id_proyecto, id_componente, numero_orden, actividad,
    mes_jun, mes_jul, mes_ago, mes_sep, mes_oct, mes_nov, mes_dic,
    responsable, estado
)
SELECT 4, NULL, 12, 'Informe final, auditoría y cierre',
       0, 0, 0, 0, 0, 1, 1,
       'Auditor/a Interno/a', 'pendiente'
WHERE NOT EXISTS (
    SELECT 1 FROM sag_fp_cronograma
     WHERE id_proyecto = 4 AND numero_orden = 12
       AND actividad LIKE 'Informe final%'
);

-- ===========================================================
--  VERIFICACIÓN (opcional, ejecutar a mano para auditar)
-- ===========================================================
-- SELECT 'Componentes', COUNT(*) FROM sag_fp_componentes WHERE id_proyecto = 4 AND activo = 1
-- UNION SELECT 'Riesgos',    COUNT(*) FROM sag_fp_riesgos    WHERE id_proyecto = 4 AND activo = 1
-- UNION SELECT 'Equipo',     COUNT(*) FROM sag_fp_equipo     WHERE id_proyecto = 4 AND activo = 1
-- UNION SELECT 'Cronograma', COUNT(*) FROM sag_fp_cronograma WHERE id_proyecto = 4 AND activo = 1;
--
-- Esperado: 7 / 5 / 8 / 12

-- =====================================================
--  FIN MIGRACION 016
-- =====================================================
