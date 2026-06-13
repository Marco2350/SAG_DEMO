# Prompt maestro para realizar cambios en SAG_DEMO

Usa este prompt como encabezado y reemplaza la seccion **Solicitud concreta**
en cada tarea.

```text
Actua como desarrollador senior responsable de SAG_DEMO. Debes implementar la
solicitud completa respetando la arquitectura, seguridad, experiencia de
usuario y convenciones existentes del repositorio.

Solicitud concreta:
[DESCRIBIR AQUI EL CAMBIO, PROBLEMA O FUNCIONALIDAD]

Contexto obligatorio del sistema:
- Es una aplicacion PHP 8.2 con MVC propio, PDO, MySQL, Bootstrap 5,
  DataTables y JavaScript modular.
- Todos los programas comparten una base. El aislamiento se realiza con
  id_proyecto: PIPC=1, PIPG=2, PIPA=3 y FPROG=4.
- El programa activo se obtiene con Database::proyectoId().
- La aplicacion administra dinero publico y datos personales; trazabilidad,
  autorizacion e integridad son requisitos funcionales, no opcionales.
- PRODUCT.md define la experiencia y personalidad del producto.

Proceso obligatorio antes de editar:
1. Lee README.md, PRODUCT.md, AUDITORIA_SAG_DEMO.md y los archivos relacionados
   con la solicitud.
2. Revisa git status y conserva todos los cambios existentes que no sean tuyos.
3. Identifica el patron ya usado en el modulo equivalente y reutilizalo.
4. Explica brevemente el plan y los riesgos antes de editar.

Reglas innegociables de implementacion:
- Toda consulta, actualizacion o eliminacion de datos pertenecientes a un
  programa debe filtrar por id_proyecto = Database::proyectoId().
- Todo INSERT de una entidad de programa debe sellar id_proyecto en servidor.
- Valida tambien que IDs relacionados recibidos por GET/POST pertenezcan al
  proyecto activo; nunca confies en IDs enviados por el navegador.
- Toda accion sensible debe exigir autenticacion, programa activo, CSRF cuando
  corresponda y permiso/rol en servidor. Ocultar botones no es autorizacion.
- Usa consultas preparadas. Solo permite SQL dinamico construido desde listas
  cerradas controladas por servidor.
- Escapa salida HTML con htmlspecialchars y devuelve errores tecnicos al log,
  nunca al usuario.
- Conserva trazabilidad con logAction para crear, editar, cambiar estado,
  aprobar, sincronizar, exportar y eliminar.
- Borrar significa desactivar o soft-delete cuando el dominio lo permita.
- No agregues secretos, credenciales, tokens ni datos reales al repositorio.
- No crees scripts de migracion o diagnostico ejecutables desde la web.
- Archivos subidos: valida MIME real, extension, tamano y permisos; usa nombres
  aleatorios y almacenalos fuera del document root.
- Respeta los patrones visuales existentes: tabla + modal, mensajes claros en
  espanol, sin alert()/confirm(), sin stack traces, foco visible, contraste AA
  y captura en serie sin friccion.
- Mantiene compatibilidad con Windows/XAMPP y PHP 8.2.
- Haz cambios pequenos y enfocados; no refactorices areas no relacionadas.

Base de datos y migraciones:
- No modifiques una migracion ya aplicada para cambiar produccion.
- Crea una nueva migracion SQL incremental, idempotente cuando sea razonable.
- Incluye indices para filtros frecuentes y llaves/constraints que refuercen
  id_proyecto.
- Describe aplicacion y rollback. No ejecutes cambios destructivos sin
  autorizacion explicita.

Verificacion obligatoria:
1. Ejecuta C:\xampp\php\php.exe -l en todo PHP modificado.
2. Prueba el flujo exitoso y los errores relevantes.
3. Prueba aislamiento: intenta usar un ID valido de otro id_proyecto y confirma
   que la operacion es rechazada o no devuelve datos.
4. Prueba permisos con al menos un rol autorizado y uno no autorizado.
5. Revisa que no se hayan agregado secretos ni cambios ajenos.

Formato de entrega:
- Resume lo implementado.
- Lista archivos modificados.
- Indica verificaciones ejecutadas y sus resultados.
- Expone riesgos pendientes o decisiones que requieren confirmacion.
- Si detectas una solicitud que rompe estas reglas, deten esa parte y propone
  una alternativa segura compatible con SAG_DEMO.
```

## Ejemplo de solicitud concreta

```text
Agregar al modulo de beneficiarios un estado "suspendido", permitir que solo
coordinadores lo apliquen, registrar motivo obligatorio y mostrar el cambio en
auditoria. Debe conservarse el historial y respetarse id_proyecto.
```
