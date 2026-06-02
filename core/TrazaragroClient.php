<?php
/**
 * TrazaragroClient — Cliente API de Trazaragro (OIRSA)
 *
 * Ambiente:  https://pruebas-trazaragro.oirsa.org   (pruebas)
 *            https://trazaragro.oirsa.org           (producción — pendiente)
 *
 * Autenticación:
 *   OAuth2 password grant en /Services/token
 *   client_id=TZWEB · client_secret en config
 *   Devuelve access_token tipo bearer con TTL ~12 horas (43199 s).
 *
 * API de datos:
 *   OData v3 en /Services/odata/QueryMovementProducts
 *   Header obligatorio: X-Local-Instance: HN
 *   Filtros OData ($top, $orderby, $filter, $inlinecount, $skip, $select).
 *
 * Configuración en config/app.php:
 *   define('TRAZARAGRO', [
 *       'base_url'      => 'https://pruebas-trazaragro.oirsa.org',
 *       'username'      => 'usuario_sag',
 *       'password'      => 'contrasena',
 *       'client_id'     => 'TZWEB',
 *       'client_secret' => '44007759-8c91-4557-9347-53708a1bb5c5',
 *       'instance'      => 'HN',
 *       'timeout'       => 30,
 *   ]);
 *
 * Modo MOCK:
 *   Si no hay TRAZARAGRO definido o le faltan username/password, el cliente
 *   responde con datos simulados para que la UI no se rompa en desarrollo.
 */
class TrazaragroClient
{
    private string $baseUrl     = '';
    private string $username    = '';
    private string $password    = '';
    private string $clientId    = 'TZWEB';
    private string $clientSecret= '';
    private string $instance    = 'HN';
    private int    $timeout     = 30;
    private bool   $isMock      = true;

    /** Ubicación del cache del token (archivo) */
    private string $tokenCacheFile;

    /** Última info de diagnóstico tras una llamada HTTP (status, cuerpo, error cURL) */
    private array $lastDiag = [];

    public function __construct()
    {
        $this->tokenCacheFile = sys_get_temp_dir() . '/sag_trazaragro_token.json';

        if (defined('TRAZARAGRO') && is_array(TRAZARAGRO)) {
            $cfg = TRAZARAGRO;
            $this->baseUrl      = rtrim($cfg['base_url']      ?? '', '/');
            $this->username     =        $cfg['username']     ?? '';
            $this->password     =        $cfg['password']     ?? '';
            $this->clientId     =        $cfg['client_id']    ?? 'TZWEB';
            $this->clientSecret =        $cfg['client_secret']?? '';
            $this->instance     =        $cfg['instance']     ?? 'HN';
            $this->timeout      = (int) ($cfg['timeout']      ?? 30);

            // Está en modo live solo si tiene URL + credenciales completas
            $this->isMock = empty($this->baseUrl)
                         || empty($this->username)
                         || empty($this->password)
                         || empty($this->clientSecret);
        }
    }

    // AUTENTICACIÓN -------------------------------------------------------

    /**
     * Obtiene un access_token válido. Usa cache mientras no expire.
     */
    public function getToken(): string
    {
        if ($this->isMock) return '';

        // 1) Intentar cache
        if (is_file($this->tokenCacheFile)) {
            $j = @json_decode((string) @file_get_contents($this->tokenCacheFile), true);
            if (is_array($j) && !empty($j['access_token']) && !empty($j['expires_at'])) {
                if (time() < (int)$j['expires_at'] - 60) {
                    $this->lastDiag = ['source' => 'cache', 'status' => 200];
                    return (string) $j['access_token'];
                }
            }
        }

        // 2) Pedir token nuevo
        $body = http_build_query([
            'username'      => $this->username,
            'password'      => $this->password,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'password',
        ]);

        $url = $this->baseUrl . '/Services/token';

        // Postman lo hace como GET con body urlencoded — replicamos.
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
        ];

        // SSL: si XAMPP en Windows no tiene CA bundle configurado, intentamos
        // localizar el cacert.pem; si no existe, desactivamos verificación
        // (solo aceptable en dev local — emite warning al error_log)
        $caBundle = $this->findCaBundle();
        if ($caBundle) {
            $opts[CURLOPT_SSL_VERIFYPEER] = true;
            $opts[CURLOPT_CAINFO]         = $caBundle;
        } else {
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
            $opts[CURLOPT_SSL_VERIFYHOST] = 0;
            error_log('TrazaragroClient: cacert.pem no encontrado, verificación SSL desactivada (dev only).');
        }

        curl_setopt_array($ch, $opts);
        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        $errNo  = curl_errno($ch);
        curl_close($ch);

        // Guardar diagnóstico para que ping()/sincronizar() puedan reportarlo
        $this->lastDiag = [
            'source'     => 'live',
            'url'        => $url,
            'status'     => $status,
            'curl_errno' => $errNo,
            'curl_error' => $err,
            'body_snip'  => $raw ? mb_substr((string)$raw, 0, 400) : null,
            'ca_bundle'  => $caBundle ?: '(ninguno — SSL verify off)',
        ];

        if ($status !== 200 || !$raw) {
            error_log("TrazaragroClient::getToken FAIL status={$status} errno={$errNo} err={$err}");
            return '';
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['access_token'])) {
            error_log('TrazaragroClient::getToken sin access_token: ' . mb_substr((string)$raw, 0, 200));
            $this->lastDiag['parse_error'] = 'No se encontró access_token en la respuesta';
            return '';
        }

        $token   = (string) $data['access_token'];
        $expires = (int) ($data['expires_in'] ?? 43199);
        @file_put_contents($this->tokenCacheFile, json_encode([
            'access_token' => $token,
            'expires_at'   => time() + $expires,
            'country'      => $data['country']  ?? null,
            'operator'     => $data['operator'] ?? null,
        ]));
        return $token;
    }

    /**
     * Busca cacert.pem en ubicaciones típicas de XAMPP/PHP.
     * Devuelve la ruta absoluta si existe, o null.
     */
    private function findCaBundle(): ?string
    {
        // 1) Si php.ini ya tiene curl.cainfo o openssl.cafile, usar esos
        $iniPaths = [
            ini_get('curl.cainfo'),
            ini_get('openssl.cafile'),
        ];
        foreach ($iniPaths as $p) {
            if ($p && is_file($p)) return $p;
        }
        // 2) Ubicaciones comunes en XAMPP / PHP
        $candidates = [
            'C:/xampp/apache/bin/curl-ca-bundle.crt',
            'C:/xampp/php/extras/ssl/cacert.pem',
            'C:/xampp/php/cacert.pem',
            '/etc/ssl/certs/ca-certificates.crt',
            '/etc/pki/tls/certs/ca-bundle.crt',
        ];
        foreach ($candidates as $p) {
            if (is_file($p)) return $p;
        }
        return null;
    }

    /** Diagnóstico de la última llamada HTTP (para ping() y debugging). */
    public function getLastDiag(): array
    {
        return $this->lastDiag;
    }

    /** Borra el cache del token (forzar re-auth) */
    public function clearToken(): void
    {
        if (is_file($this->tokenCacheFile)) @unlink($this->tokenCacheFile);
    }

    // PING ----------------------------------------------------------------
    public function ping(): array
    {
        if ($this->isMock) {
            return [
                'ok'       => true,
                'mode'     => 'mock',
                'msg'      => 'Trazaragro en modo MOCK — falta configurar credenciales.',
                'base'     => $this->baseUrl ?: '(no configurado)',
                'instance' => $this->instance,
                'ts'       => date('c'),
            ];
        }
        $tok = $this->getToken();
        $diag = $this->getLastDiag();

        // Construir un mensaje descriptivo basado en el diagnóstico
        $msg = $tok ? 'Token obtenido correctamente' : 'No se pudo autenticar';
        if (!$tok) {
            if (!empty($diag['curl_errno'])) {
                $msg .= ' — cURL error ' . $diag['curl_errno'] . ': ' . ($diag['curl_error'] ?? '');
            } elseif (!empty($diag['status']) && $diag['status'] !== 200) {
                $msg .= ' — HTTP ' . $diag['status'];
                if (!empty($diag['body_snip'])) {
                    $msg .= ' · ' . preg_replace('/\s+/', ' ', mb_substr($diag['body_snip'], 0, 200));
                }
            } elseif (!empty($diag['parse_error'])) {
                $msg .= ' — ' . $diag['parse_error'];
            }
        }

        return [
            'ok'       => $tok !== '',
            'mode'     => 'live',
            'msg'      => $msg,
            'base'     => $this->baseUrl,
            'instance' => $this->instance,
            'diag'     => $diag,
            'ts'       => date('c'),
        ];
    }

    // CONSULTA DE MOVIMIENTOS --------------------------------------------

    /**
     * Trae movimientos de productos (entregas de incentivos) desde Trazaragro.
     *
     * Filtros soportados:
     *   - top         int   default 100
     *   - skip        int   default 0
     *   - orderby     string default 'MovementId desc'
     *   - authCode    string filtra por AuthorizationCode (substringof)
     *   - desde       Y-m-d filtra AuthorizationDate >= desde
     *   - hasta       Y-m-d filtra AuthorizationDate <= hasta
     *   - actividadId int   default 329 (Insumos agropecuarios)
     *   - tipoMovId   int   default 141 (Movimiento de entrega de insumos)
     *   - extraFilter string filtro OData adicional (AND)
     */
    public function fetchEntregas(array $filtros = []): array
    {
        if ($this->isMock) return $this->mockEntregas();

        $top         = max(1, (int)($filtros['top']   ?? 100));
        $skip        = max(0, (int)($filtros['skip']  ?? 0));
        $orderby     = $filtros['orderby'] ?? 'MovementId desc';
        $actividadId = (int)($filtros['actividadId'] ?? 329);
        $tipoMovId   = (int)($filtros['tipoMovId']   ?? 141);

        // Construir filtro OData
        $parts = [];
        $parts[] = "ActivityId eq {$actividadId}";
        $parts[] = "MovementTypeId eq {$tipoMovId}";

        if (!empty($filtros['authCode'])) {
            $code = strtolower(addslashes($filtros['authCode']));
            $parts[] = "substringof('{$code}',tolower(AuthorizationCode))";
        }
        // Filtro por rubro (ProductActivityName) — clave para mostrar solo el rubro
        // del programa activo: agrícola para PIPA, café para PIPC, pecuario/pesquero para PIPG.
        // Usamos substringof para tolerar pequeñas variaciones de capitalización/acento.
        if (!empty($filtros['rubro'])) {
            $r = strtolower(addslashes($filtros['rubro']));
            // Extraer la palabra clave después del último espacio (agrícola/café/pecuario)
            $kw = trim((string)preg_replace('/.*\s/', '', $r));
            if ($kw === '') $kw = $r;
            $parts[] = "substringof('{$kw}',tolower(ProductActivityName))";
        }
        if (!empty($filtros['desde'])) {
            $d = date('Y-m-d', strtotime($filtros['desde']));
            $parts[] = "AuthorizationDate ge datetime'{$d}T00:00:00'";
        }
        if (!empty($filtros['hasta'])) {
            $d = date('Y-m-d', strtotime($filtros['hasta']));
            $parts[] = "AuthorizationDate le datetime'{$d}T23:59:59'";
        }
        if (!empty($filtros['extraFilter'])) {
            $parts[] = '(' . $filtros['extraFilter'] . ')';
        }

        $query = http_build_query([
            '$top'          => $top,
            '$skip'         => $skip,
            '$orderby'      => $orderby,
            '$inlinecount'  => 'allpages',
            '$filter'       => implode(' and ', $parts),
        ]);

        // Endpoint CORRECTO: QueryMovementNationalPrograms es el que usa la pestaña
        // "Programas nacionales" de OIRSA web. Trae el objeto trazable, código de
        // trazabilidad, cantidad real y nombre del autorizador.
        // (El antiguo QueryMovementProducts devuelve esos campos en NULL.)
        $endpoint = '/Services/odata/QueryMovementNationalPrograms?';

        $res = $this->http('GET', $endpoint . $query);
        if ($res['status'] === 401) {
            $this->clearToken();
            $res = $this->http('GET', $endpoint . $query);
        }

        $values = $res['body']['value'] ?? [];
        $normalized = [];
        foreach ($values as $row) {
            $normalized[] = $this->parseMovement($row);
        }
        return $normalized;
    }

    /** Conteo total (sin traer datos) */
    public function countEntregas(array $filtros = []): int
    {
        if ($this->isMock) return count($this->mockEntregas());

        $actividadId = (int)($filtros['actividadId'] ?? 329);
        $tipoMovId   = (int)($filtros['tipoMovId']   ?? 141);
        $parts = ["ActivityId eq {$actividadId}", "MovementTypeId eq {$tipoMovId}"];
        if (!empty($filtros['authCode'])) {
            $code = strtolower(addslashes($filtros['authCode']));
            $parts[] = "substringof('{$code}',tolower(AuthorizationCode))";
        }
        if (!empty($filtros['desde'])) {
            $parts[] = "AuthorizationDate ge datetime'" . date('Y-m-d', strtotime($filtros['desde'])) . "T00:00:00'";
        }
        if (!empty($filtros['hasta'])) {
            $parts[] = "AuthorizationDate le datetime'" . date('Y-m-d', strtotime($filtros['hasta'])) . "T23:59:59'";
        }
        $query = http_build_query([
            '$top'         => 1,
            '$inlinecount' => 'allpages',
            '$filter'      => implode(' and ', $parts),
        ]);
        $res = $this->http('GET', '/Services/odata/QueryMovementNationalPrograms?' . $query);
        return (int)($res['body']['odata.count'] ?? 0);
    }

    /**
     * Notifica a Trazaragro el resultado de la revisión local.
     * Trazaragro hoy es read-only; cuando OIRSA habilite endpoint POST se cablea.
     */
    public function notificarRevision(string $trazaragroId, string $estado, string $observacion = ''): array
    {
        return [
            'ok'   => true,
            'mode' => $this->isMock ? 'mock' : 'noop',
            'msg'  => "Revisión registrada localmente (id={$trazaragroId} estado={$estado}). "
                   . "Trazaragro hoy es read-only; pendiente endpoint de feedback de OIRSA.",
        ];
    }

    /**
     * Devuelve el primer campo presente y no-vacío del array $r entre las claves $keys.
     * Útil porque QueryMovementNationalPrograms devuelve algunos campos con nombre en
     * español y otros en inglés (a veces ambos coexisten).
     */
    private function pick(array $r, array $keys, $default = null) {
        foreach ($keys as $k) {
            if (array_key_exists($k, $r) && $r[$k] !== null && $r[$k] !== '') {
                return $r[$k];
            }
        }
        return $default;
    }

    // PARSER OData row -> entrega normalizada -----------------------------
    private function parseMovement(array $r): array
    {
        // Counterparts: "Nombre Apellidos; 0801196802642"
        $destinyCp = $this->pick($r, ['DestinyEndpointCounterpart']);
        [$nombreBen, $dniBen] = $this->splitNombreDni((string)$destinyCp);

        // Source establishment: "SAG DUMT (Bodega); 3400801153822"
        $sourceDesc = $this->pick($r, ['SourceEndpointDescription', 'Descripción del punto final de origen', 'FuenteEndpointDescripción']);
        [$bodegaNombre, $bodegaCodigo] = $this->splitNombreDni((string)$sourceDesc);

        $fechaAuthRaw = $this->pick($r, ['AuthorizationDate', 'Fecha de autorización']);
        $fechaRegRaw  = $this->pick($r, ['RegistrationDate', 'Fecha de registro']);
        $fechaExpRaw  = $this->pick($r, ['ExpirationDate', 'Fecha de caducidad']);
        $fechaAuth    = $this->isoToDate($fechaAuthRaw) ?: $this->isoToDate($fechaRegRaw);

        // Código de trazabilidad — el campo "Código del artículo" (ItemCode) es el HNPIPA...
        $codigoTraza = trim((string)$this->pick($r, [
            'ItemCode', 'Código del artículo', 'Código del Artículo',
            'SealCodes', 'Códigos de sello'
        ]));

        // Helpers para campos donde OIRSA usa nombre ES y/o EN
        $movTypeName  = (string)$this->pick($r, ['MovementTypeName', 'Nombre del tipo de movimiento']);
        $actName      = (string)$this->pick($r, ['ActivityName',    'Nombre de la actividad']);
        $rubro        = (string)$this->pick($r, ['ProductActivityName','NombreActividadProducto']);
        $rubroId      = (int)   $this->pick($r, ['ProductActivityId', 'IdActividadProducto'], 0);
        $objeto       = (string)$this->pick($r, ['ProductTypeName',  'Nombre del tipo de producto']);
        $objetoCod    = (string)$this->pick($r, ['ProductTypeCode',  'Código de tipo de producto']);
        $cantidad     = (float) $this->pick($r, ['ProductQuantity',  'Cantidad del producto'], 0);
        $unidad       = (string)$this->pick($r, ['UnitName',         'Nombre de la unidad']);
        $regCode      = (string)$this->pick($r, ['RegistrationCode', 'Código de registro']);
        $authCode     = (string)$this->pick($r, ['AuthorizationCode','Código de autorización']);
        $authUser     = (string)$this->pick($r, ['AuthorizationUserName', 'Nombre de usuario de autorización']);
        $createUser   = (string)$this->pick($r, ['CreationUserName',     'Nombre de usuario de creación', 'CreaciónUsuarioNombre']);
        $status       = (string)$this->pick($r, ['Status',           'Estado']);
        $statusId     = (int)   $this->pick($r, ['StatusId'], 0);
        $eventStage   = (string)$this->pick($r, ['EventStage',       'Escenario del evento']);
        $isCompleted  = (bool)  $this->pick($r, ['IsCompleted',      'Completado'], false);
        $carrier      = (string)$this->pick($r, ['Carrier',          'Portador', 'Transportista']);
        $condition    = (string)$this->pick($r, ['Condition',        'Condición']);
        $purpose      = (string)$this->pick($r, ['Purpose',          'Objeto', 'Objetivo', 'Propósito']);
        $vehicle      = (string)$this->pick($r, ['Vehicle',          'Vehículo']);
        // Destino
        $destDesc     = (string)$this->pick($r, ['DestinyEndpointDescription', 'Descripción del punto final del destino']);
        $destCode     = (string)$this->pick($r, ['DestinyEndpointCode',        'Código de punto final de destino', 'Código de DestinyEndpoint']);
        $destLoc1     = (string)$this->pick($r, ['DestinyLocation1', 'DestinoUbicación1']);
        $destLoc2     = (string)$this->pick($r, ['DestinyLocation2', 'DestinoUbicación2']);
        // Origen
        $srcCp        = (string)$this->pick($r, ['SourceEndpointCounterpart', 'FuenteEndpointContraparte']);
        $srcCode      = (string)$this->pick($r, ['SourceEndpointCode',        'Código de punto final de origen']);
        $srcLoc1      = (string)$this->pick($r, ['SourceLocation1', 'FuenteUbicación1', 'Ubicación de origen1', 'Ubicación de origen 1']);
        $srcLoc2      = (string)$this->pick($r, ['SourceLocation2', 'FuenteUbicación2', 'Ubicación de origen 2']);

        return [
            // Identidad y clasificación
            'trazaragro_id'           => (string)($r['MovementId']        ?? ''),
            'registration_code'       => $regCode,
            'authorization_code'      => $authCode,
            'status'                  => $status,
            'status_id'               => $statusId,
            'event_stage'             => $eventStage,
            'is_completed'            => $isCompleted,
            'actividad_id'            => (int)($r['ActivityId'] ?? 0),
            'tipo_movimiento'         => $movTypeName,
            'tipo_movimiento_id'      => (int)($r['MovementTypeId'] ?? 0),
            'rubro'                   => $rubro,
            'rubro_id'                => $rubroId,
            'objeto_trazable'         => $objeto,
            'objeto_trazable_codigo'  => $objetoCod,
            'codigo_trazabilidad'     => $codigoTraza,

            // Destino
            'destino_persona'         => (string)$destinyCp,
            'destino_nombre'          => $nombreBen,
            'destino_dni'             => $dniBen,
            'destino_establecimiento' => $destDesc,
            'destino_cue'             => $destCode,
            'destino_departamento'    => $destLoc1,
            'destino_municipio'       => $destLoc2,

            // Origen
            'origen_persona'          => $srcCp,
            'origen_establecimiento'  => (string)$sourceDesc,
            'origen_cue'              => $srcCode,
            'origen_departamento'     => $srcLoc1,
            'origen_municipio'        => $srcLoc2,

            // Cantidad / logística
            'cantidad'                => $cantidad,
            'unidad'                  => $unidad,
            'transportista'           => $carrier,
            'vehiculo'                => $vehicle,
            'condicion'               => $condition,
            'proposito'               => $purpose,

            // Fechas
            'fecha_registro'          => $this->isoToDateTime($fechaRegRaw),
            'fecha_autorizacion'      => $this->isoToDateTime($fechaAuthRaw),
            'fecha_expiracion'        => $this->isoToDateTime($fechaExpRaw),
            // Compat
            'fecha_entrega'           => $fechaAuth,
            'bodega'                  => $bodegaNombre,
            'bodega_codigo'           => $bodegaCodigo,
            'producto'                => $objeto,
            'producto_actividad'      => $rubro,
            'nombre_beneficiario'     => $nombreBen,
            'dni'                     => $dniBen,

            // Usuarios
            'usuario_autoriza'        => $authUser,
            'usuario_crea'            => $createUser,

            // Backup
            'raw'                     => $r,
        ];
    }

    private function isoToDateTime(?string $iso): ?string
    {
        if (!$iso) return null;
        $ts = strtotime($iso);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    private function splitNombreDni(string $s): array
    {
        if ($s === '') return ['', ''];
        $p = array_map('trim', explode(';', $s, 2));
        return [$p[0] ?? '', $p[1] ?? ''];
    }

    private function isoToDate(?string $iso): ?string
    {
        if (!$iso) return null;
        $ts = strtotime($iso);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    // HTTP transport ------------------------------------------------------
    private function http(string $method, string $path, ?array $body = null): array
    {
        $url = $this->baseUrl . $path;
        $token = $this->getToken();
        $headers = [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
            'X-Local-Instance: ' . $this->instance,
        ];

        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
        ];

        // Mismo manejo SSL que en getToken()
        $caBundle = $this->findCaBundle();
        if ($caBundle) {
            $opts[CURLOPT_SSL_VERIFYPEER] = true;
            $opts[CURLOPT_CAINFO]         = $caBundle;
        } else {
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
            $opts[CURLOPT_SSL_VERIFYHOST] = 0;
        }

        curl_setopt_array($ch, $opts);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        $errNo  = curl_errno($ch);
        curl_close($ch);

        $decoded = $raw ? json_decode($raw, true) : null;

        // Guardar diagnóstico para sincronizarTrazaragro()
        $this->lastDiag = [
            'source'     => 'odata',
            'url'        => $url,
            'status'     => $status,
            'curl_errno' => $errNo,
            'curl_error' => $err,
            'body_snip'  => $raw ? mb_substr((string)$raw, 0, 400) : null,
        ];

        return [
            'status' => $status,
            'body'   => is_array($decoded) ? $decoded : ['raw' => $raw, 'curl_error' => $err],
        ];
    }

    // MOCK ----------------------------------------------------------------
    private function mockEntregas(): array
    {
        $hoy = date('Y-m-d');
        return [
            [
                'trazaragro_id'           => '8775',
                'registration_code'       => 'EH0000078',
                'authorization_code'      => 'HD2432605B',
                'status'                  => 'Autorizado',
                'dni'                     => '0801196802642',
                'nombre_beneficiario'     => 'Jose Luis Osorio Medina',
                'destino_departamento'    => 'Santa Bárbara',
                'destino_municipio'       => 'Quimistán',
                'destino_establecimiento' => 'HACIENDA LOS LIMONES; 3401618000066',
                'bodega'                  => 'SAG DUMT (Bodega)',
                'bodega_codigo'           => '3400801153822',
                'origen_departamento'     => 'Francisco Morazán',
                'origen_municipio'        => 'Distrito Central',
                'producto'                => 'Pecuario | Vaquilla preñadas',
                'producto_actividad'      => 'Insumo/Incentivo pecuario',
                'cantidad'                => 1,
                'unidad'                  => 'unidades',
                'transportista'           => 'Maria Luisa Gomez Iden',
                'vehiculo'                => 'L164590',
                'condicion'               => 'Cambio de propietario',
                'proposito'               => 'Programa Incentivo a la Producción',
                'fecha_entrega'           => $hoy,
                'fecha_registro'          => $hoy,
                'fecha_expiracion'        => date('Y-m-d', strtotime('+3 days')),
                'usuario_autoriza'        => 'Mariana Chavarría Azofeifa',
                'is_completed'            => false,
                'raw'                     => ['_mock' => true],
            ],
        ];
    }
}
