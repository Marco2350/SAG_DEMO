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

        // Postman lo hace como GET con body urlencoded — replicamos.
        $ch = curl_init($this->baseUrl . '/Services/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($status !== 200 || !$raw) {
            error_log("TrazaragroClient::getToken FAIL status={$status} err={$err}");
            return '';
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data['access_token'])) {
            error_log('TrazaragroClient::getToken sin access_token');
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
        return [
            'ok'       => $tok !== '',
            'mode'     => 'live',
            'msg'      => $tok ? 'Token obtenido correctamente' : 'No se pudo autenticar',
            'base'     => $this->baseUrl,
            'instance' => $this->instance,
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

        $res = $this->http('GET', '/Services/odata/QueryMovementProducts?' . $query);
        if ($res['status'] === 401) {
            // Token expirado o inválido -> refrescar y reintentar 1 vez
            $this->clearToken();
            $res = $this->http('GET', '/Services/odata/QueryMovementProducts?' . $query);
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
        $res = $this->http('GET', '/Services/odata/QueryMovementProducts?' . $query);
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

    // PARSER OData row -> entrega normalizada -----------------------------
    private function parseMovement(array $r): array
    {
        // DestinyEndpointCounterpart: "Nombre Apellidos; 0801196802642"
        [$nombreBen, $dniBen] = $this->splitNombreDni($r['DestinyEndpointCounterpart'] ?? '');

        // SourceEndpointDescription: "SAG DUMT (Bodega); 3400801153822"
        [$bodegaNombre, $bodegaCodigo] = $this->splitNombreDni($r['SourceEndpointDescription'] ?? '');

        $fechaAuth = $this->isoToDate($r['AuthorizationDate'] ?? null)
                  ?: $this->isoToDate($r['RegistrationDate']  ?? null);

        return [
            'trazaragro_id'           => (string)($r['MovementId']        ?? ''),
            'registration_code'       => (string)($r['RegistrationCode']  ?? ''),
            'authorization_code'      => (string)($r['AuthorizationCode'] ?? ''),
            'status'                  => (string)($r['Status']            ?? ''),
            'dni'                     => $dniBen,
            'nombre_beneficiario'     => $nombreBen,
            'destino_departamento'    => (string)($r['DestinyLocation1'] ?? ''),
            'destino_municipio'       => (string)($r['DestinyLocation2'] ?? ''),
            'destino_establecimiento' => (string)($r['DestinyEndpointDescription'] ?? ''),
            'bodega'                  => $bodegaNombre,
            'bodega_codigo'           => $bodegaCodigo,
            'origen_departamento'     => (string)($r['SourceLocation1'] ?? ''),
            'origen_municipio'        => (string)($r['SourceLocation2'] ?? ''),
            'producto'                => (string)($r['ProductTypeName']     ?? ''),
            'producto_actividad'      => (string)($r['ProductActivityName'] ?? ''),
            'cantidad'                => (float) ($r['ProductQuantity']     ?? 0),
            'unidad'                  => (string)($r['UnitName']            ?? ''),
            'transportista'           => (string)($r['Carrier']  ?? ''),
            'vehiculo'                => (string)($r['Vehicle']  ?? ''),
            'condicion'               => (string)($r['Condition']?? ''),
            'proposito'               => (string)($r['Purpose']  ?? ''),
            'fecha_entrega'           => $fechaAuth,
            'fecha_registro'          => $this->isoToDate($r['RegistrationDate'] ?? null),
            'fecha_expiracion'        => $this->isoToDate($r['ExpirationDate']   ?? null),
            'usuario_autoriza'        => (string)($r['AuthorizationUserName'] ?? ''),
            'is_completed'            => (bool)  ($r['IsCompleted']           ?? false),
            'raw'                     => $r,
        ];
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
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $raw    = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        $decoded = $raw ? json_decode($raw, true) : null;
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
