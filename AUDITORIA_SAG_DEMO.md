# Auditoria tecnica de SAG_DEMO

Fecha: 2026-06-13

## Resumen ejecutivo

SAG_DEMO tiene una base funcional clara: PHP 8.2 sin framework, MVC propio,
PDO con consultas preparadas, una base unificada y separacion logica por
`id_proyecto`. Tambien incorpora CSRF, sesiones, bitacora, escape HTML y
soft-delete en varias areas.

El riesgo principal es que el aislamiento entre programas y la autorizacion
dependen de que cada controlador recuerde aplicar sus filtros. Ya existen
operaciones que omiten `id_proyecto`, por lo que un usuario autenticado puede
leer o modificar registros de otro programa si conoce sus IDs.

Estado recomendado: **no publicar en produccion antes de corregir los puntos
criticos y altos**.

## Hallazgos

### Critico - Acceso cruzado entre programas en Presupuesto

Varias consultas y actualizaciones usan solamente el ID del registro y no
validan `id_proyecto`.

Ejemplos:

- `app/controllers/PresupuestoController.php:246`: obtiene un presupuesto sin
  filtrar por proyecto.
- `app/controllers/PresupuestoController.php:249`: lista lineas sin validar que
  el presupuesto pertenezca al proyecto activo.
- `app/controllers/PresupuestoController.php:282`: consulta una linea sin
  proyecto antes de registrar una modificacion.
- `app/controllers/PresupuestoController.php:301`: recalcula un presupuesto sin
  proyecto.
- `app/controllers/PresupuestoController.php:316`: elimina logicamente una
  linea sin proyecto.
- `app/controllers/PresupuestoController.php:459`: cambia una compra sin
  proyecto.
- `app/controllers/PresupuestoController.php:575`, `:592`, `:609`: procesa
  viaticos sin proyecto.
- `app/controllers/PresupuestoController.php:694`: cambia un gasto sin proyecto.
- `app/controllers/PresupuestoController.php:765`, `:773`: elimina o descarga
  documentos sin proyecto.

Impacto: exposicion y alteracion de informacion financiera de otro PIP.

Correccion: toda lectura, actualizacion y borrado de entidades de programa debe
incluir `AND id_proyecto = ?`. Las relaciones recibidas por POST tambien deben
validarse contra el proyecto activo antes de guardar.

### Critico - El alcance de programa asignado no se aplica

- `app/controllers/AuthController.php:72`: la sesion no guarda
  `programa_asignado`.
- `app/controllers/ProgramasController.php:12`: muestra estadisticas de todos
  los programas a cualquier usuario autenticado.
- `app/controllers/ProgramasController.php:69`: permite seleccionar cualquier
  entrada de `PROGRAMAS`.
- `core/Permisos.php:77`: si no existe programa asignado, permite operar.

Impacto: un usuario limitado a un PIP puede seleccionar otro PIP y acceder a
sus modulos.

Correccion: cargar el alcance desde base de datos durante login, filtrar el
selector y rechazar en servidor cualquier seleccion fuera del alcance.

### Alto - Autorizacion insuficiente en controladores operativos

Los controladores de Beneficiarios, Organizaciones, Capacitaciones, Asistencia
Tecnica y Fortalecimiento solo exigen un programa activo. Sus acciones de
crear, editar, finalizar y eliminar no verifican permiso o rol.

Impacto: cualquier usuario autenticado con programa activo puede ejecutar
acciones sensibles llamando directamente al endpoint, aunque la interfaz no
muestre el boton.

Correccion: centralizar permisos por modulo/accion y aplicarlos en servidor a
cada accion. La visibilidad de botones no sustituye autorizacion.

### Alto - Scripts de diagnostico y migracion expuestos por web

La raiz contiene scripts ejecutables como `migrar_003.php` a `migrar_008.php`,
`diag_permisos.php`, `test_oirsa_explore.php`, `test_entregas_db.php` y
`verificar_municipios.php`. Varios muestran errores y conectan directamente a
la base o a OIRSA sin el guard de desarrollo.

La regla `.htaccess` restringe solamente archivos cuyo nombre inicia con
`_test_`; no cubre esos scripts.

Impacto: ejecucion de migraciones, fuga de estructura, errores, datos o acceso
a integraciones.

Correccion: mover tareas operativas a `cli/`, bloquearlas por Apache y requerir
un guard explicito de CLI/desarrollo. No dejar migraciones ejecutables por GET.

### Alto - Secreto y credencial inicial conocidos

- `config/app.php:111` incluye un `client_secret` por defecto.
- `.env.example:21` publica ese mismo secreto.
- `sql/bd_mddesarr_sag.sql:740` documenta y crea `admin/admin123`.

Impacto: acceso no autorizado si se despliega sin reemplazar valores.

Correccion: rotar el secreto, eliminar defaults sensibles y exigir variables de
entorno. Crear el primer administrador mediante un proceso de instalacion con
contrasena unica.

### Alto - Evidencias sin aislamiento por proyecto

`core/EvidenciaService.php` consulta y actualiza por ID, sin `id_proyecto`, en
las lineas 83, 90, 134 y 156. Ademas almacena en `/uploads/evidencias`, fuera de
`public/uploads` y sin una regla explicita de denegacion.

Impacto: reemplazo, validacion o lectura de evidencia perteneciente a otro PIP;
posible acceso directo a archivos si el directorio es servido por Apache.

Correccion: agregar el filtro de proyecto en todas las operaciones, almacenar
fuera del document root y servir archivos solo mediante controlador autorizado.

### Medio - Enrutamiento dinamico amplia la superficie

`core/Router.php:37` permite despachar cualquier metodo publico existente,
aunque no este registrado como ruta. Esto vuelve accesibles metodos auxiliares
o diagnosticos como endpoints.

Correccion: usar exclusivamente rutas registradas o una lista permitida de
acciones; rechazar metodos no declarados.

### Medio - Sesion sin endurecimiento completo

Antes de `session_start()` no se configuran explicitamente cookies
`httponly`, `secure` y `samesite`. El logout tambien usa GET.

Correccion: configurar cookies de sesion seguras segun ambiente y cambiar
logout a POST con CSRF.

### Medio - Matriz de permisos incompleta o abandonada

`core/Permisos.php` requiere `config/permisos.php`, pero ese archivo no existe
en el repositorio y el helper casi no se utiliza. En paralelo, distintos
controladores contienen listas de roles hardcodeadas con slugs que no siempre
coinciden con `sql/migracion_008_roles_y_pip.sql`.

Correccion: definir una sola fuente de verdad para roles y permisos, agregarla
al repositorio y probarla.

### Medio - Falta de pruebas automatizadas y pipeline

No existen PHPUnit, Composer, CI ni pruebas repetibles de autorizacion,
aislamiento o migraciones. Los archivos `test_*.php` son diagnosticos manuales.

Correccion: comenzar con pruebas de integracion para:

1. Un usuario asignado no puede seleccionar otro PIP.
2. Ningun CRUD puede leer o modificar un ID de otro `id_proyecto`.
3. Cada rol es rechazado o autorizado correctamente por accion.
4. Cargas y descargas de archivos respetan proyecto, tipo y permisos.
5. Las migraciones se pueden aplicar desde cero y en orden.

### Medio - Documentacion y versionado de esquema insuficientes

`README.md` solo contiene el titulo. Hay scripts `migrar_*.php` y SQL con
numeraciones incompletas o duplicadas, sin runner ni tabla formal de versiones.

Correccion: documentar instalacion, configuracion, arquitectura, roles,
despliegue y rollback. Mantener migraciones inmutables y ejecutarlas por CLI.

## Fortalezas observadas

- PDO usa consultas preparadas y `ATTR_EMULATE_PREPARES=false`.
- El modelo base aplica `id_proyecto` por defecto.
- Existe proteccion CSRF global para POST.
- El login usa `password_verify`, regenera sesion y renueva CSRF.
- La salida HTML se escapa de forma consistente en muchas vistas.
- Hay bitacora y una intencion clara de trazabilidad.
- La interfaz y `PRODUCT.md` definen principios de diseno utiles y coherentes.
- Todos los archivos PHP pasan `C:\xampp\php\php.exe -l`.

## Plan recomendado

### Fase 1 - Antes de produccion

1. Corregir todos los accesos sin `id_proyecto`.
2. Aplicar alcance de PIP y permisos por accion en servidor.
3. retirar o bloquear scripts web operativos.
4. Rotar secretos y eliminar credenciales por defecto.
5. Proteger evidencias y documentos fuera del document root.

### Fase 2 - Estabilizacion

1. Crear pruebas de aislamiento y autorizacion.
2. Unificar roles y permisos.
3. Endurecer sesion, headers y manejo de archivos.
4. Introducir migraciones por CLI y documentar despliegue.

### Fase 3 - Calidad continua

1. Agregar CI con lint, pruebas y revision de secretos.
2. Medir cobertura de flujos financieros y trazabilidad.
3. Revisar accesibilidad, rendimiento de consultas e indices.

## Verificaciones realizadas

- Inventario completo del repositorio y revision de arquitectura.
- Revision estatica de controladores, modelos, rutas, configuracion y SQL.
- Revision de aislamiento `id_proyecto`, autenticacion, CSRF y archivos.
- `C:\xampp\php\php.exe -l` sobre todos los archivos PHP: correcto.

No se ejecutaron pruebas funcionales contra la base ni OIRSA para evitar
modificar datos o consumir integraciones durante la auditoria.
