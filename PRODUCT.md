# Product

## Register

product

## Users

Personal técnico y administrativo de la Secretaría de Agricultura y Ganadería de Honduras (SAG): técnicos de campo que registran visitas y capacitaciones, coordinadores de programa que validan y aprueban, y administradores que gestionan catálogos, usuarios y presupuesto. Trabajan en oficina con equipos Windows de gama media y conexiones variables; varios capturan datos en lote (decenas de registros por sesión). Hablan español hondureño; no son usuarios técnicos.

## Product Purpose

SAG Programas administra los tres Programas de Incentivos (PIPC café, PIPG ganadero, PIPA agrícola) del plan Honduras Sin Hambre: beneficiarios/productores, organizaciones, capacitaciones, asistencia técnica, entregas de incentivos (Trazaragro/OIRSA), inventarios y ejecución presupuestaria. Éxito = captura rápida y sin errores, trazabilidad completa de cada acción (es dinero público) y datos confiables para reportes nacionales.

## Brand Personality

Institucional, confiable, eficiente. Tono sobrio de herramienta gubernamental seria — no juguetón, no startup. La identidad visual cambia por programa (café/azul/verde vía `body.theme-*`) pero la voz es una sola: instrucciones directas, mensajes de error que dicen qué hacer, sin jerga técnica hacia el usuario.

## Anti-references

- Dashboards SaaS genéricos con gradientes, glassmorphism y métricas-héroe decorativas.
- Sistemas de gobierno legados: tablas grises sin jerarquía, alerts de navegador (`alert()`/`confirm()`), errores con stack traces o SQL visibles.
- Sobre-animación: el usuario está capturando datos en serie; nada debe hacerlo esperar.

## Design Principles

1. **La herramienta desaparece en la tarea.** Captura en serie sin fricción: modales que conservan contexto, tablas que mantienen la página, foco donde sigue el trabajo.
2. **Cada error dice qué hacer.** Mensajes en español claro con la acción correctiva; el detalle técnico va al log del servidor, nunca a la pantalla.
3. **Consistencia entre módulos.** Un solo vocabulario: mismo patrón tabla+modal, mismos badges, mismos botones. Lo que se aprende en Organizaciones aplica en todos.
4. **Trazabilidad visible.** Toda acción queda registrada (sag_logs) y es consultable; borrar = desactivar, los datos históricos se conservan.
5. **Familiaridad antes que sorpresa.** Bootstrap 5 + DataTables bien afinados; nada de affordances inventadas.

## Accessibility & Inclusion

Contraste AA (≥4.5:1) en texto de cuerpo; estados de foco visibles en formularios; toasts con `aria-live`; `prefers-reduced-motion` respetado en animaciones. Usuarios con monitores antiguos y zoom del sistema: tipografía base ≥ .84rem en datos, sin depender solo del color para estados (badge + texto).
