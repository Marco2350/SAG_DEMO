# SAG_DEMO

Sistema web de la Secretaría de Agricultura y Ganadería de Honduras para
administrar PIPC, PIPG y PIPA. La aplicación usa PHP, MySQL, Bootstrap y
JavaScript sin un proceso de compilación obligatorio.

## Requisitos

- PHP 8.1 o superior con PDO MySQL, cURL, mbstring, fileinfo y ZipArchive.
- MySQL 5.7 compatible.
- Apache con `mod_rewrite` y lectura de archivos `.htaccess`.

## Configuración local

1. Copiar `.env.example` como `.env`.
2. Configurar la conexión a la base de datos.
3. Configurar las credenciales OIRSA únicamente en `.env`.
4. Abrir `http://localhost/SAG_DEMO`.

Los secretos y archivos cargados por usuarios no deben incluirse en Git.

## Verificación antes de publicar

Desde PowerShell, en la raíz del proyecto:

```powershell
C:\xampp\php\php.exe tests\smoke.php

$files = rg --files -g "*.php"
foreach ($file in $files) { C:\xampp\php\php.exe -l $file }

$files = rg --files -g "*.js"
foreach ($file in $files) { node --check $file }

git diff --check
```

La prueba `tests/smoke.php` no se conecta a la base de datos ni modifica
información. Valida la matriz de permisos, el aislamiento entre PIP y las
protecciones mínimas de configuración.

## Reglas de mantenimiento

- Toda consulta operativa debe filtrar por `id_proyecto`.
- Las eliminaciones con historial deben desactivar registros, no borrarlos.
- Las acciones que cambian datos requieren autenticación, permiso y CSRF.
- Los archivos deben validarse por tamaño, extensión y contenido MIME.
- No deben agregarse datos simulados o endpoints provisionales al código activo.
