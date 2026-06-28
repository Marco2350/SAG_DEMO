<?php
require_once __DIR__ . '/_test_guard.php';
// Archivo deshabilitado a pedido del usuario (revertido el 2026-06-12).
// La migración v2 (PK compuesta + id_proyecto) NO se aplicó.
// El controlador volvió al INSERT original.
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
echo "Script descartado. Eliminar manualmente.\n";
