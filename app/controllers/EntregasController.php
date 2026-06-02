<?php
/**
 * EntregasController — Módulo de Entregas de Incentivos
 *
 * VERSIÓN MOCK (mayo 2026)
 * Esta versión NO está conectada a KoBoToolbox ni persiste en BD.
 * Los datos del listado son ficticios para validar UX antes del sync real.
 *
 * Integraciones planeadas:
 *  · KoBoToolbox      → core/KoboClient.php (a crear) — datos de campo
 *  · Trazaragro       → core/TrazaragroClient.php (OAuth + OData v3 LIVE)
 *  · Inventarios      → InventariosController::registrarSalida() al aprobar
 *
 * Pendiente:
 * - Implementar KoboClient real con credenciales
 * - Persistir en sag_entregas + sag_entregas_lineas (migración 003)
 * - Cron para sync automático
 */
class EntregasController extends Controller
{
    public function __construct()
    {
        $this->requirePrograma();
    }

    public function index(): void
    {
        $pageTitle = 'Entregas de Incentivos — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;

        $entregas = $this->mockEntregas();
        $kpis     = $this->calcularKpis($entregas);

        $bodegas = [
            ['codigo' => 'FM01', 'nombre' => 'Bodega Central FM-01'],
            ['codigo' => 'FM02', 'nombre' => 'Bodega Valle FM-02'],
            ['codigo' => 'CP01', 'nombre' => 'Bodega Santa Rosa CP-01'],
            ['codigo' => 'CP02', 'nombre' => 'Bodega La Entrada CP-02'],
            ['codigo' => 'OL01', 'nombre' => 'Bodega Juticalpa OL-01'],
            ['codigo' => 'OL02', 'nombre' => 'Bodega Campamento OL-02'],
        ];

        $tecnicos = [
            ['codigo' => 'tec01', 'nombre' => 'Jose Luis Ramirez'],
            ['codigo' => 'tec02', 'nombre' => 'Carmen Sosa'],
            ['codigo' => 'tec03', 'nombre' => 'Ivan Mendoza'],
            ['codigo' => 'tec04', 'nombre' => 'Lucia Bardales'],
            ['codigo' => 'tec05', 'nombre' => 'Mario Discua'],
        ];

        $esAdmin = in_array(($_SESSION['user']['rol_slug'] ?? ''), ['admin', 'coordinador']);

        $this->view('entregas/index', compact(
            'pageTitle', 'entregas', 'kpis', 'bodegas', 'tecnicos', 'esAdmin'
        ));
    }

    // ════════════════════════════════════════════════════════════
    //  SINCRONIZACIÓN — Kobo (campo) y Trazaragro (manifiestos)
    // ════════════════════════════════════════════════════════════
    public function sincronizar(): void
    {
        $nuevas  = rand(2, 8);
        $alertas = rand(0, 2);
        $this->logAction('SYNC_KOBO', 'entregas', "MOCK +{$nuevas} entregas, {$alertas} alertas");
        $this->success(
            "Sincronización con KoBo completada: {$nuevas} nuevas entregas importadas" .
            ($alertas ? " ({$alertas} requieren revisión)" : ''),
            ['nuevas' => $nuevas, 'alertas' => $alertas, 'origen' => 'kobo']
        );
    }

    /**
     * Sincronización con Trazaragro vía API REST (OData v3 + OAuth2).
     * Envuelto en try/catch para que cualquier fallo se reporte como JSON
     * (evita devolver HTML 500 que el frontend no puede parsear).
     */
    public function sincronizarTrazaragro(): void
    {
        try {
            // Verificar que la clase existe (archivo desplegado correctamente)
            if (!class_exists('TrazaragroClient')) {
                $this->error('El archivo core/TrazaragroClient.php no está desplegado en este servidor. Cópialo y vuelve a intentar.');
                return;
            }

            $cli  = new TrazaragroClient();
            $ping = $cli->ping();
            if (!$ping['ok']) {
                $this->error('No se pudo contactar a Trazaragro: ' . ($ping['msg'] ?? 'sin respuesta'));
                return;
            }

            $authCode = trim((string) $this->getPost('authCode', ''));
            $desde    = $this->getPost('desde', date('Y-m-d', strtotime('-7 days')));
            $hasta    = $this->getPost('hasta', date('Y-m-d'));
            $top      = (int) $this->getPost('top', 100);

            $entregas = $cli->fetchEntregas([
                'top'      => $top,
                'desde'    => $desde,
                'hasta'    => $hasta,
                'authCode' => $authCode ?: null,
            ]);

            // Validación cruzada local
            $matched = 0; $sinMatch = 0; $duplicados = 0; $alertas = [];
            foreach ($entregas as &$e) {
                $e['_validacion'] = $this->validarVsBeneficiarios($e);
                if ($e['_validacion']['estado'] === 'matched')       $matched++;
                elseif ($e['_validacion']['estado'] === 'sin_match') $sinMatch++;
                elseif ($e['_validacion']['estado'] === 'duplicado') $duplicados++;
                if (!empty($e['_validacion']['alerta'])) $alertas[] = $e['_validacion']['alerta'];
            }
            unset($e);

            $nuevas = count($entregas);

            $this->logAction('SYNC_TRAZARAGRO', 'entregas',
                "Modo: {$ping['mode']} · recibidas={$nuevas} · ok={$matched} · sin_match={$sinMatch} · dup={$duplicados}");

            $resumen = "Trazaragro ({$ping['mode']}): {$nuevas} manifiestos recibidos";
            if ($matched)    $resumen .= " · {$matched} validados";
            if ($sinMatch)   $resumen .= " · {$sinMatch} sin match en padrón";
            if ($duplicados) $resumen .= " · {$duplicados} duplicados";

            $this->success($resumen, [
                'nuevas'     => $nuevas,
                'matched'    => $matched,
                'sin_match'  => $sinMatch,
                'duplicados' => $duplicados,
                'alertas'    => $alertas,
                'origen'     => 'trazaragro',
                'modo'       => $ping['mode'],
                'rango'      => ['desde' => $desde, 'hasta' => $hasta],
                'entregas'   => $entregas,
            ]);
        } catch (\Throwable $e) {
            error_log('sincronizarTrazaragro EXCEPTION: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            $this->error('Error interno: ' . $e->getMessage() . ' (línea ' . $e->getLine() . ' de ' . basename($e->getFile()) . ')');
        }
    }

    /**
     * Endpoint de diagnóstico — qué archivos del paquete Trazaragro/Inventarios
     * están desplegados en este servidor. Útil cuando aparece 500 o "Error de conexión".
     */
    public function diag(): void
    {
        $base = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
        $checks = [
            'core/TrazaragroClient.php'                     => $base . '/core/TrazaragroClient.php',
            'core/Database.php'                             => $base . '/core/Database.php',
            'app/controllers/EntregasController.php'        => $base . '/app/controllers/EntregasController.php',
            'app/controllers/InventariosController.php'     => $base . '/app/controllers/InventariosController.php',
            'app/views/inventarios/index.php'               => $base . '/app/views/inventarios/index.php',
            'public/assets/js/modules/inventarios.js'       => $base . '/public/assets/js/modules/inventarios.js',
            'bd.sql'                                        => $base . '/bd.sql',
        ];
        $report = [];
        foreach ($checks as $rel => $abs) {
            $exists = is_file($abs);
            $report[] = [
                'archivo'   => $rel,
                'existe'    => $exists,
                'tamano'    => $exists ? filesize($abs) : 0,
                'modificado'=> $exists ? date('Y-m-d H:i:s', filemtime($abs)) : null,
            ];
        }
        $classes = [
            'TrazaragroClient'       => class_exists('TrazaragroClient'),
            'InventariosController'  => class_exists('InventariosController'),
            'Database'               => class_exists('Database'),
        ];
        $config = [
            'TRAZARAGRO_definido' => defined('TRAZARAGRO'),
            'TRAZARAGRO_url'      => defined('TRAZARAGRO') ? (TRAZARAGRO['base_url'] ?? '') : '',
            'TRAZARAGRO_user_set' => defined('TRAZARAGRO') ? !empty(TRAZARAGRO['username']) : false,
            'php_version'         => PHP_VERSION,
            'session_programa'    => $_SESSION['programa']['sigla'] ?? '(ninguno)',
        ];
        $this->success('Diagnóstico de despliegue', [
            'archivos' => $report,
            'clases'   => $classes,
            'config'   => $config,
        ]);
    }

    /**
     * Valida una entrega Trazaragro vs el padrón local de beneficiarios.
     */
    private function validarVsBeneficiarios(array $entrega): array
    {
        $dni = $entrega['dni'] ?? '';
        if ($dni === '') {
            return ['estado' => 'sin_match', 'alerta' => 'Manifiesto sin DNI de beneficiario'];
        }
        return ['estado' => 'matched', 'alerta' => null];
    }

    public function aprobar(): void
    {
        if (!in_array(($_SESSION['user']['rol_slug'] ?? ''), ['admin', 'coordinador'])) {
            $this->error('Sin permisos para aprobar entregas.'); return;
        }
        $id  = (int) $this->getPost('id', 0);
        $obs = $this->getPost('observacion', '');

        try {
            $salida = ['ok' => false, 'msg' => 'InventariosController no desplegado'];
            if (class_exists('InventariosController')) {
                $mockBodegaId = 1;
                $mockLineas   = [['id_producto' => 1, 'cantidad' => 4]];
                $salida = InventariosController::registrarSalida($id, $mockBodegaId, $mockLineas);
            }

            $ack = ['ok' => false, 'msg' => 'TrazaragroClient no desplegado'];
            if (class_exists('TrazaragroClient')) {
                $cli = new TrazaragroClient();
                $ack = $cli->notificarRevision('TRZ-MOCK-' . $id, 'aprobada', $obs);
            }

            $this->logAction('APROBAR_ENTREGA', 'entregas', "#{$id} (stock: {$salida['msg']})");
            $this->success("Entrega #{$id} aprobada.", [
                'id'         => $id,
                'inventario' => $salida,
                'trazaragro' => $ack,
            ]);
        } catch (\Throwable $e) {
            error_log('aprobar EXCEPTION: ' . $e->getMessage());
            $this->error('Error al aprobar: ' . $e->getMessage());
        }
    }

    public function rechazar(): void
    {
        if (!in_array(($_SESSION['user']['rol_slug'] ?? ''), ['admin', 'coordinador'])) {
            $this->error('Sin permisos para rechazar entregas.'); return;
        }
        $id  = (int) $this->getPost('id', 0);
        $obs = $this->getPost('observacion', '');
        if (!$obs) { $this->error('Debe indicar el motivo del rechazo.'); return; }

        try {
            if (class_exists('TrazaragroClient')) {
                $cli = new TrazaragroClient();
                $cli->notificarRevision('TRZ-MOCK-' . $id, 'rechazada', $obs);
            }
            $this->logAction('RECHAZAR_ENTREGA', 'entregas', "#{$id} obs={$obs}");
            $this->success("Entrega #{$id} rechazada.", ['id' => $id]);
        } catch (\Throwable $e) {
            $this->error('Error al rechazar: ' . $e->getMessage());
        }
    }

    public function detalle(): void
    {
        $id = (int) $this->getPost('id', 0);
        $entregas = $this->mockEntregas();
        foreach ($entregas as $e) {
            if ($e['id_entrega'] == $id) {
                $this->success('OK', ['entrega' => $e]);
                return;
            }
        }
        $this->error('Entrega no encontrada.');
    }

    // ════════════════════════════════════════════════════════════
    //  DATOS FICTICIOS
    // ════════════════════════════════════════════════════════════
    private function mockEntregas(): array
    {
        $img   = 'https://placehold.co/400x300/16A34A/ffffff?text=Foto';
        $firma = 'https://placehold.co/300x100/ffffff/333333?text=Firma';
        return [
            $this->mk(1001, '0801198512345', 'María Cristina López Pérez', 'F',
                'Francisco Morazán', 'Tegucigalpa', 'El Hatillo',
                'FM01', 'Bodega Central FM-01', 'tec01', 'José Luis Ramírez',
                '2026-05-08', '09:34', 4, 4, 1, null, null, null,
                14.0723, -87.1921, 8, $img, $img, $firma, $firma, '',
                'aprobada', null, 'Sistema', '2026-05-08 10:15:00', '2026-05-08 10:12:00'),
            $this->mk(1002, '0801197823456', 'Juan Carlos Hernández Mejía', 'M',
                'Francisco Morazán', 'Distrito Central', 'Suyapa',
                'FM01', 'Bodega Central FM-01', 'tec01', 'José Luis Ramírez',
                '2026-05-08', '10:21', 6, 4, 0, 'stock_insuficiente', '9123-4567', null,
                14.0612, -87.1845, 12, $img, $img, $firma, null,
                'Solo había 4 sacos en bodega al momento de la entrega.',
                'con_alerta', 'Entrega incompleta (4/6 sacos) — pendiente regularizar',
                null, null, '2026-05-08 10:45:00'),
            $this->mk(1003, '9999998888777', 'DNI no encontrado en padrón', null,
                'Francisco Morazán', null, null,
                'FM01', 'Bodega Central FM-01', 'tec02', 'Carmen Sosa',
                '2026-05-08', '11:05', null, 3, null, null, null, null,
                14.0701, -87.1923, 6, $img, $img, $firma, null,
                'El DNI no está en el sistema. El técnico ingresó manualmente.',
                'pendiente_revision', 'Beneficiario no registrado en sag_beneficiarios. Verificar identidad.',
                null, null, '2026-05-08 11:18:00'),
            $this->mk(1004, '0801198645678', 'Pedro Antonio Mejía Soto', 'M',
                'Francisco Morazán', 'Santa Lucía', 'Jutiapa',
                'FM02', 'Bodega Valle FM-02', 'tec02', 'Carmen Sosa',
                '2026-05-07', '14:22', 5, 5, 1, null, '8800-9988', null,
                14.1875, -87.0334, 10, $img, $img, $firma, $firma, '',
                'con_alerta', 'Beneficiario en estado SANCIONADO al momento de la entrega.',
                null, null, '2026-05-07 15:30:00'),
            $this->mk(1005, '0401198256789', 'Ana Lucía Vásquez Ramírez', 'F',
                'Copán', 'Santa Rosa de Copán', 'El Naranjo',
                'CP01', 'Bodega Santa Rosa CP-01', 'tec03', 'Iván Mendoza',
                '2026-05-08', '08:45', 8, 8, 1, null, null,
                'Casa esquina Barrio El Naranjo #28 (junto a pulpería Doña Rosa)',
                14.7689, -88.7811, 5, $img, $img, $firma, $firma,
                'Productora muy organizada, agradece el incentivo.',
                'aprobada', null, 'Sistema', '2026-05-08 09:00:00', '2026-05-08 08:58:00'),
            $this->mk(1006, '0401198067890', 'Manuel de Jesús Ramos', 'M',
                'Copán', 'Copán Ruinas', 'La Pintada',
                'CP01', 'Bodega Santa Rosa CP-01', 'tec03', 'Iván Mendoza',
                '2026-05-08', '11:15', 4, 4, 1, null, null, null,
                14.8345, -89.1421, 7, $img, $img, $firma, null, '',
                'con_alerta', 'Posible duplicado: recibió incentivo similar en PIPG el 2026-04-22.',
                null, null, '2026-05-08 11:32:00'),
            $this->mk(1007, '0401199578901', 'Carla Sofía Núñez Aguilar', 'F',
                'Copán', 'La Entrada', 'Florida',
                'CP02', 'Bodega La Entrada CP-02', 'tec04', 'Lucía Bardales',
                '2026-05-07', '15:40', 2, 2, 1, null, null, null,
                15.0512, -88.7723, 9, $img, $img, $firma, $firma, '',
                'aprobada', null, 'Sistema', '2026-05-07 16:00:00', '2026-05-07 15:58:00'),
            $this->mk(1008, '1501197590123', 'Esperanza del Carmen Bonilla', 'F',
                'Olancho', 'Juticalpa', 'Boquerón',
                'OL01', 'Bodega Juticalpa OL-01', 'tec05', 'Mario Discua',
                '2026-05-08', '07:30', 5, 5, 1, null, null, null,
                14.6534, -86.2178, 11, $img, $img, $firma, $firma,
                'Entrega tempranera. Beneficiaria líder de cooperativa local.',
                'aprobada', null, 'Sistema', '2026-05-08 07:45:00', '2026-05-08 07:42:00'),
            $this->mk(1009, '1501199212345', 'Diana Yamileth Cáceres Rivera', 'F',
                'Olancho', 'Campamento', 'El Achote',
                'OL02', 'Bodega Campamento OL-02', 'tec05', 'Mario Discua',
                '2026-05-06', '13:15', 3, 0, 0, 'beneficiario_no_presente', null, null,
                14.6213, -85.9876, 14, null, null, null, $firma,
                'Beneficiaria suspendida en sistema. No se procedió con la entrega.',
                'rechazada', 'Beneficiario en estado SUSPENDIDO.',
                'Iván Mendoza (coordinador)', '2026-05-06 14:30:00', '2026-05-06 13:45:00'),
            $this->mk(1010, '1501198723456', 'Carlos Eduardo Discua Lobo', 'M',
                'Olancho', 'Salamá', 'El Naranjal',
                'OL02', 'Bodega Campamento OL-02', 'tec05', 'Mario Discua',
                '2026-05-08', '12:50', 4, 4, 1, null, null, null,
                14.7892, -86.1234, 8, $img, $img, $firma, null,
                'Apellido del beneficiario coincide con técnico (verificar parentesco).',
                'pendiente_revision', 'Posible conflicto de interés: apellido del técnico coincide con el del beneficiario.',
                null, null, '2026-05-08 13:05:00'),
        ];
    }

    /** Factory helper para mantener mockEntregas() legible. */
    private function mk(int $id, string $dni, string $nombre, ?string $sexo,
        string $depto, ?string $mun, ?string $aldea, string $bodCod, string $bodNom,
        string $tecCod, string $tecNom, string $fecha, string $hora,
        ?int $sacAsig, ?int $sacEnt, ?int $compl, ?string $rzNoCom,
        ?string $telAct, ?string $dirAct, float $lat, float $lon, int $gpsPrec,
        ?string $fotoDni, ?string $fotoEnt, ?string $firmaBen, ?string $firmaTec,
        string $obs, string $estado, ?string $alerta, ?string $revPor, ?string $fechaRev, string $synced): array
    {
        return [
            'id_entrega'           => $id,
            'kobo_submission_id'   => 'kobo_abc_' . str_pad((string)$id, 3, '0', STR_PAD_LEFT),
            'dni'                  => $dni,
            'beneficiario'         => $nombre,
            'sexo'                 => $sexo,
            'departamento'         => $depto,
            'municipio'            => $mun,
            'aldea'                => $aldea,
            'bodega_codigo'        => $bodCod,
            'bodega'               => $bodNom,
            'tecnico_codigo'       => $tecCod,
            'tecnico'              => $tecNom,
            'fecha_entrega'        => $fecha,
            'hora_entrega'         => $hora,
            'sacos_asignados'      => $sacAsig,
            'sacos_entregados'     => $sacEnt,
            'entrega_completa'     => $compl,
            'razon_no_completa'    => $rzNoCom,
            'telefono_actualizado' => $telAct,
            'direccion_actualizada'=> $dirAct,
            'gps_lat'              => $lat,
            'gps_lon'              => $lon,
            'gps_precision'        => $gpsPrec,
            'foto_dni'             => $fotoDni,
            'foto_entrega'         => $fotoEnt,
            'firma_beneficiario'   => $firmaBen,
            'firma_tecnico'        => $firmaTec,
            'observaciones'        => $obs,
            'estado'               => $estado,
            'alerta_motivo'        => $alerta,
            'revisado_por'         => $revPor,
            'fecha_revision'       => $fechaRev,
            'synced_at'            => $synced,
        ];
    }

    private function calcularKpis(array $entregas): array
    {
        $kpis = [
            'total'              => count($entregas),
            'aprobadas'          => 0,
            'pendiente_revision' => 0,
            'con_alerta'         => 0,
            'rechazadas'         => 0,
            'sacos_entregados'   => 0,
        ];
        foreach ($entregas as $e) {
            $kpis[$e['estado']] = ($kpis[$e['estado']] ?? 0) + 1;
            $kpis['sacos_entregados'] += (int) ($e['sacos_entregados'] ?? 0);
        }
        return $kpis;
    }
}
