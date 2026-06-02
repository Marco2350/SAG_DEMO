@echo off
REM ============================================================
REM  Sync Trazaragro - Tarea programada Windows
REM ============================================================
REM
REM Programar en Windows:
REM   1. Abrir "Programador de tareas" (taskschd.msc)
REM   2. Crear tarea basica:
REM        Nombre:    SAG Sync Trazaragro
REM        Trigger:   Diariamente a las 06:00 (repetir cada 6 horas)
REM        Accion:    Iniciar un programa
REM        Programa:  C:\xampp\htdocs\sag_programas\cli\sync_trazaragro.bat
REM
REM   3. En "Configuracion" marcar:
REM      [x] Ejecutar tanto si el usuario inicio sesion como si no
REM      [x] Ejecutar con los privilegios mas altos
REM
REM Log: C:\xampp\htdocs\sag_programas\logs\sync_trazaragro.log
REM ============================================================

cd /d "C:\xampp\htdocs\sag_programas"
"C:\xampp\php\php.exe" cli\sync_trazaragro.php --dias=30

REM Salir con el codigo de salida del script
exit /b %ERRORLEVEL%
