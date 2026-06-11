<?php
/**
 * EvidenciaService — Gestión de archivos de evidencia (R-027 / R-028)
 *
 * Maneja:
 *  - Validación de archivos (tipo, tamaño)
 *  - Almacenamiento en uploads/evidencias/
 *  - Persistencia de metadatos en sag_capacitaciones / sag_asistencias_tecnicas
 *  - Cambio de estado (pendiente, cargada, validada, rechazada)
 *
 * Tipos aceptados: PDF, Excel (.xls/.xlsx), imágenes (.jpg/.png)
 * Tamaño máx: 10 MB
 */
class EvidenciaService
{
    const TAMANO_MAX = 10 * 1024 * 1024; // 10 MB
    const TIPOS_PERMITIDOS = [
        'application/pdf'                                                            => 'pdf',
        'application/vnd.ms-excel'                                                   => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'           => 'xlsx',
        'image/jpeg'                                                                  => 'jpg',
        'image/png'                                                                   => 'png',
    ];

    /**
     * Tablas permitidas — para evitar SQL injection en la actualización dinámica.
     */
    const TABLAS_PERMITIDAS = [
        'sag_capacitaciones'        => 'id_capacitacion',
        'sag_asistencias_tecnicas'  => 'id_at',
    ];

    /**
     * Procesa el archivo subido vía $_FILES y guarda su metadata en la tabla indicada.
     *
     * @return array{ok:bool, msg:string, archivo?:string, mime?:string, tamano?:int}
     */
    public static function guardarEvidencia(
        string $tabla, int $idRegistro, array $file,
        ?int $idUsuario, ?string $observaciones = null
    ): array {
        if (!isset(self::TABLAS_PERMITIDAS[$tabla])) {
            return ['ok' => false, 'msg' => 'Tabla no permitida'];
        }
        if (!is_array($file) || empty($file['tmp_name'])) {
            return ['ok' => false, 'msg' => 'No se recibió archivo'];
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'msg' => 'Error al subir el archivo (código ' . $file['error'] . ')'];
        }
        if ($file['size'] > self::TAMANO_MAX) {
            return ['ok' => false, 'msg' => 'Archivo demasiado grande (máx ' . (self::TAMANO_MAX/1024/1024) . ' MB)'];
        }

        $mime = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? 'application/octet-stream');
        if (!isset(self::TIPOS_PERMITIDOS[$mime])) {
            return ['ok' => false, 'msg' => 'Tipo de archivo no permitido: ' . $mime . '. Aceptados: PDF, Excel, JPG, PNG.'];
        }
        $ext = self::TIPOS_PERMITIDOS[$mime];

        // Directorio destino
        $base = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
        $dir  = $base . '/uploads/evidencias';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            return ['ok' => false, 'msg' => 'No se pudo crear el directorio de evidencias'];
        }

        // Nombre único: {tabla}_{id}_{timestamp}_{random}.{ext}
        $tablaSlug = preg_replace('/^sag_/', '', $tabla);
        $fileName  = $tablaSlug . '_' . $idRegistro . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath  = $dir . '/' . $fileName;

        if (!@move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['ok' => false, 'msg' => 'No se pudo mover el archivo a su destino'];
        }

        // Persistir en BD
        try {
            $db = Database::programa();
            $pk = self::TABLAS_PERMITIDAS[$tabla];

            // Borrar archivo viejo si existía
            $row = $db->fetchOne("SELECT evidencia_archivo FROM {$tabla} WHERE {$pk} = ?", [$idRegistro]);
            if (!empty($row['evidencia_archivo'])) {
                $oldFile = $dir . '/' . basename($row['evidencia_archivo']);
                if (is_file($oldFile)) @unlink($oldFile);
            }

            $db->execute(
                "UPDATE {$tabla} SET
                    evidencia_archivo = ?,
                    evidencia_nombre_original = ?,
                    evidencia_mime = ?,
                    evidencia_tamano = ?,
                    evidencia_subida_at = NOW(),
                    evidencia_subida_por = ?,
                    evidencia_estado = 'cargada',
                    evidencia_observaciones = ?
                 WHERE {$pk} = ?",
                [
                    $fileName, $file['name'], $mime, (int)$file['size'],
                    $idUsuario, $observaciones, $idRegistro,
                ]
            );
        } catch (\Throwable $e) {
            @unlink($destPath);
            error_log('EvidenciaService::guardarEvidencia — ' . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error al guardar la evidencia en la base de datos. Revise el log del servidor.'];
        }

        return [
            'ok'      => true,
            'msg'     => 'Evidencia cargada correctamente.',
            'archivo' => $fileName,
            'mime'    => $mime,
            'tamano'  => (int)$file['size'],
        ];
    }

    /**
     * Cambia el estado de la evidencia (validada / rechazada) con observaciones.
     */
    public static function cambiarEstado(
        string $tabla, int $idRegistro, string $nuevoEstado, string $observaciones = ''
    ): array {
        if (!isset(self::TABLAS_PERMITIDAS[$tabla])) return ['ok' => false, 'msg' => 'Tabla no permitida'];
        if (!in_array($nuevoEstado, ['validada', 'rechazada', 'pendiente'])) {
            return ['ok' => false, 'msg' => 'Estado no válido'];
        }
        try {
            $db = Database::programa();
            $pk = self::TABLAS_PERMITIDAS[$tabla];
            $db->execute(
                "UPDATE {$tabla} SET evidencia_estado = ?, evidencia_observaciones = ? WHERE {$pk} = ?",
                [$nuevoEstado, $observaciones, $idRegistro]
            );
            return ['ok' => true, 'msg' => "Evidencia marcada como {$nuevoEstado}."];
        } catch (\Throwable $e) {
            error_log('EvidenciaService::cambiarEstado — ' . $e->getMessage());
            return ['ok' => false, 'msg' => 'Error al cambiar el estado de la evidencia.'];
        }
    }

    /**
     * Sirve el archivo de evidencia (descarga/preview).
     * El controlador llama esto desde GET /capacitaciones/evidencia?id=N
     */
    public static function servirArchivo(string $tabla, int $idRegistro): void
    {
        if (!isset(self::TABLAS_PERMITIDAS[$tabla])) { http_response_code(403); exit; }
        try {
            $db = Database::programa();
            $pk = self::TABLAS_PERMITIDAS[$tabla];
            $row = $db->fetchOne(
                "SELECT evidencia_archivo, evidencia_nombre_original, evidencia_mime
                 FROM {$tabla} WHERE {$pk} = ?",
                [$idRegistro]
            );
        } catch (\Throwable $e) {
            http_response_code(500); exit;
        }
        if (!$row || empty($row['evidencia_archivo'])) { http_response_code(404); echo 'Sin evidencia.'; exit; }

        $base = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__);
        $path = $base . '/uploads/evidencias/' . basename($row['evidencia_archivo']);
        if (!is_file($path)) { http_response_code(404); echo 'Archivo no encontrado.'; exit; }

        header('Content-Type: ' . ($row['evidencia_mime'] ?: 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . ($row['evidencia_nombre_original'] ?: basename($path)) . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=300');
        readfile($path);
        exit;
    }
}
