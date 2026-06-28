<?php
/**
 * FlotaController — Módulo Flota Vehicular SAG_DEMO
 *
 * Maneja 3 entidades simples:
 *   · vehiculos        → registro maestro de unidades
 *   · viajes           → asignaciones con km inicial/final
 *   · mantenimientos   → historial + cálculo de alertas
 *
 * Multitenancy: todo filtrado por Database::proyectoId().
 * Catálogos como constantes (FLOTA_*) en config/app.php, no en tablas.
 *
 * Alerta de mantenimiento por vehículo:
 *   Para cada tipo (FLOTA_TIPOS_MANTENIMIENTO), busca el último realizado
 *   en sag_flota_mantenimientos. Si no hay, asume km=0. Calcula:
 *     proximo_km = ultimo_km + cada_km
 *     km_restantes = proximo_km - km_actual
 *   Si km_restantes < 0     → 🔴 vencido
 *   Si km_restantes < 10%   → 🟡 cerca
 *   Sino                    → 🟢 ok
 */
class FlotaController extends Controller
{
    private const TABLAS_REQUERIDAS = [
        'sag_flota_vehiculos',
        'sag_flota_viajes',
        'sag_flota_mantenimientos',
        'sag_flota_programacion_mantenimiento',
        'sag_flota_combustible',
    ];

    private const ROLES_GESTION = [
        'admin', 'super_admin', 'administrador', 'coordinador',
        'coord_nacional', 'coord_pip',
    ];

    public function __construct()
    {
        $this->requirePrograma();
    }

    // ════════════════════════════════════════════════════════════
    //  VISTA PRINCIPAL
    // ════════════════════════════════════════════════════════════
    public function index(): void
    {
        $db = Database::main();
        $tablasFalta = array_values(array_filter(
            self::TABLAS_REQUERIDAS,
            static fn(string $tabla): bool => !$db->tablaExiste($tabla)
        ));

        if ($tablasFalta) {
            $tituloModulo = 'Flota Vehicular';
            $migraciones  = [
                'migracion_019_flota_vehicular.sql',
                'migracion_020_flota_programacion.sql',
                'migracion_021_flota_combustible.sql',
            ];
            $pageTitle    = 'Flota Vehicular — inicialización pendiente · ' . APP_NAME;
            $this->view(
                '_partial/migracion_pendiente',
                compact('tituloModulo', 'tablasFalta', 'migraciones', 'pageTitle')
            );
            return;
        }

        $pageTitle = 'Flota Vehicular — ' . ($_SESSION['programa']['sigla'] ?? '') . ' · ' . APP_NAME;

        $vehiculos = $this->cargarVehiculos();
        $viajes    = $this->cargarViajes();
        $mantos    = $this->cargarMantenimientos();
        $planes    = $this->cargarProgramacion();
        $combustibles = $this->cargarCombustible();

        // Cálculo de alertas por vehículo
        foreach ($vehiculos as &$v) {
            $v['programacion'] = $planes[(int)$v['id_vehiculo']] ?? [];
            $v['alerta'] = $this->calcularAlertaVehiculo($v, $mantos);
        }
        unset($v);

        // KPIs
        $kpis = [
            'total'      => count($vehiculos),
            'activos'    => 0,
            'taller'     => 0,
            'con_alerta' => 0,
            'galones_mes'=> 0,
            'costo_mes'  => 0,
        ];
        foreach ($vehiculos as $v) {
            if ($v['estado'] === 'activo') $kpis['activos']++;
            if ($v['estado'] === 'taller') $kpis['taller']++;
            if ($v['alerta']['nivel'] !== 'ok') $kpis['con_alerta']++;
        }
        $mesActual = date('Y-m');
        foreach ($combustibles as $carga) {
            if (str_starts_with((string)$carga['fecha'], $mesActual)) {
                $kpis['galones_mes'] += (float)$carga['galones'];
                $kpis['costo_mes'] += (float)$carga['monto'];
            }
        }

        $esAdmin = in_array(
            ($_SESSION['user']['rol_slug'] ?? ''),
            self::ROLES_GESTION,
            true
        );

        $this->view('flota/index', compact(
            'pageTitle', 'vehiculos', 'viajes', 'mantos', 'combustibles', 'kpis', 'esAdmin'
        ));
    }

    /**
     * Las mutaciones nunca deben llegar a MySQL si el módulo no fue inicializado.
     */
    private function requireSchema(): void
    {
        $db = Database::main();
        foreach (self::TABLAS_REQUERIDAS as $tabla) {
            if (!$db->tablaExiste($tabla)) {
                $this->error(
                    'Flota Vehicular aún no está inicializada. Aplique la migración 019.',
                    503
                );
            }
        }
    }

    private function vehiculoDelPrograma(int $idVehiculo): array|false
    {
        if ($idVehiculo <= 0) {
            return false;
        }

        return Database::programa()->fetchOne(
            "SELECT *
               FROM sag_flota_vehiculos
              WHERE id_vehiculo = ?
                AND id_proyecto = ?
                AND activo = 1",
            [$idVehiculo, Database::proyectoId()]
        );
    }

    // ════════════════════════════════════════════════════════════
    //  CARGAS DESDE BD
    // ════════════════════════════════════════════════════════════
    private function cargarVehiculos(): array
    {
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();
            if (!$db->tablaExiste('sag_flota_vehiculos')) return [];
            return $db->fetchAll(
                "SELECT * FROM sag_flota_vehiculos
                  WHERE id_proyecto = ? AND activo = 1
               ORDER BY placa ASC",
                [$pid]
            );
        } catch (\Throwable $e) {
            error_log('FlotaController::cargarVehiculos — ' . $e->getMessage());
            return [];
        }
    }

    private function cargarViajes(): array
    {
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();
            if (!$db->tablaExiste('sag_flota_viajes')) return [];
            return $db->fetchAll(
                "SELECT v.*, h.placa, h.marca, h.modelo
                   FROM sag_flota_viajes v
                   LEFT JOIN sag_flota_vehiculos h
                     ON h.id_vehiculo = v.id_vehiculo
                    AND h.id_proyecto = v.id_proyecto
                  WHERE v.id_proyecto = ? AND v.activo = 1
               ORDER BY v.fecha DESC, v.id_viaje DESC",
                [$pid]
            );
        } catch (\Throwable $e) {
            error_log('FlotaController::cargarViajes — ' . $e->getMessage());
            return [];
        }
    }

    private function cargarMantenimientos(): array
    {
        try {
            $db  = Database::programa();
            $pid = Database::proyectoId();
            if (!$db->tablaExiste('sag_flota_mantenimientos')) return [];
            return $db->fetchAll(
                "SELECT m.*, h.placa, h.marca, h.modelo
                   FROM sag_flota_mantenimientos m
                   LEFT JOIN sag_flota_vehiculos h
                     ON h.id_vehiculo = m.id_vehiculo
                    AND h.id_proyecto = m.id_proyecto
                  WHERE m.id_proyecto = ? AND m.activo = 1
               ORDER BY m.fecha DESC, m.id_mantenimiento DESC",
                [$pid]
            );
        } catch (\Throwable $e) {
            error_log('FlotaController::cargarMantenimientos — ' . $e->getMessage());
            return [];
        }
    }

    private function cargarProgramacion(): array
    {
        $rows = Database::programa()->fetchAll(
            "SELECT id_vehiculo, tipo, intervalo_km, proximo_km, ultimo_km
               FROM sag_flota_programacion_mantenimiento
              WHERE id_proyecto = ? AND activo = 1
              ORDER BY proximo_km",
            [Database::proyectoId()]
        );
        $resultado = [];
        foreach ($rows as $row) {
            $resultado[(int)$row['id_vehiculo']][$row['tipo']] = $row;
        }
        return $resultado;
    }

    private function cargarCombustible(): array
    {
        return Database::programa()->fetchAll(
            "SELECT c.*, v.placa, v.marca, v.modelo
               FROM sag_flota_combustible c
               JOIN sag_flota_vehiculos v
                 ON v.id_vehiculo = c.id_vehiculo
                AND v.id_proyecto = c.id_proyecto
              WHERE c.id_proyecto = ? AND c.activo = 1
              ORDER BY c.fecha DESC, c.id_carga DESC",
            [Database::proyectoId()]
        );
    }

    private function guardarProgramacion(int $idVehiculo, int $kmActual): void
    {
        $valores = $_POST['proximo_mantenimiento'] ?? [];
        if (!is_array($valores)) return;
        $db = Database::programa();
        $pid = Database::proyectoId();
        foreach (FLOTA_TIPOS_MANTENIMIENTO as $tipo => $cfg) {
            $proximo = (int)($valores[$tipo] ?? 0);
            if ($proximo <= 0) continue;
            if ($proximo < $kmActual) {
                throw new InvalidArgumentException(
                    "El próximo kilometraje de {$cfg['nombre']} no puede ser menor al actual."
                );
            }
            $db->execute(
                "INSERT INTO sag_flota_programacion_mantenimiento
                    (id_proyecto,id_vehiculo,tipo,intervalo_km,proximo_km,activo)
                 VALUES (?,?,?,?,?,1)
                 ON DUPLICATE KEY UPDATE
                    intervalo_km=VALUES(intervalo_km),proximo_km=VALUES(proximo_km),activo=1",
                [$pid, $idVehiculo, $tipo, (int)$cfg['cada_km'], $proximo]
            );
        }
    }

    // ════════════════════════════════════════════════════════════
    //  CÁLCULO DE ALERTA DE MANTENIMIENTO
    // ════════════════════════════════════════════════════════════
    /**
     * Por cada tipo (FLOTA_TIPOS_MANTENIMIENTO), busca el último mantenimiento
     * realizado al vehículo. Calcula próximo km y restantes.
     * Devuelve el caso "peor" (más cerca/vencido).
     */
    private function calcularAlertaVehiculo(array $vehiculo, array $todosMantos): array
    {
        $kmActual = (int)($vehiculo['km_actual'] ?? 0);
        $tiposCfg = defined('FLOTA_TIPOS_MANTENIMIENTO') ? FLOTA_TIPOS_MANTENIMIENTO : [];
        $programacion = $vehiculo['programacion'] ?? [];

        // Filtrar mantenimientos de ESTE vehículo
        $mantosVeh = array_filter($todosMantos, fn($m) => (int)$m['id_vehiculo'] === (int)$vehiculo['id_vehiculo']);

        $peor = [
            'nivel' => 'ok',                // ok | cerca | vencido
            'tipo'  => null,
            'msg'   => 'Todos los mantenimientos al día',
        ];

        foreach ($tiposCfg as $key => $cfg) {
            $cadaKm = (int)($cfg['cada_km'] ?? 0);
            if ($cadaKm <= 0) continue;

            // Último mantenimiento de ese tipo
            $ultimoKm = 0;
            foreach ($mantosVeh as $m) {
                if (($m['tipo'] ?? '') === $key && (int)$m['km_al_realizar'] > $ultimoKm) {
                    $ultimoKm = (int)$m['km_al_realizar'];
                }
            }
            // Si nunca se hizo este tipo, asumir desde 0
            $proximoKm    = isset($programacion[$key])
                ? (int)$programacion[$key]['proximo_km']
                : $ultimoKm + $cadaKm;
            $kmRestantes  = $proximoKm - $kmActual;
            $pctRestante  = $cadaKm > 0 ? ($kmRestantes / $cadaKm) : 1;

            // Determinar nivel para este tipo
            if ($kmRestantes < 0) {
                $nivel = 'vencido';
                $msg = $cfg['nombre'] . " excedido por " . number_format(abs($kmRestantes)) . " km";
            } elseif ($pctRestante <= 0.10) {
                $nivel = 'cerca';
                $msg = $cfg['nombre'] . " en " . number_format($kmRestantes) . " km";
            } else {
                continue;  // Está OK, no entra al peor
            }

            // Reemplazar si es peor que el actual
            $rangNivel = ['ok' => 0, 'cerca' => 1, 'vencido' => 2];
            if ($rangNivel[$nivel] > $rangNivel[$peor['nivel']]) {
                $peor = ['nivel' => $nivel, 'tipo' => $key, 'msg' => $msg];
            }
        }

        return $peor;
    }

    // ════════════════════════════════════════════════════════════
    //  CRUD VEHÍCULO
    // ════════════════════════════════════════════════════════════
    public function guardarVehiculo(): void
    {
        try {
            $this->requireCsrf();
            $this->requireRole(self::ROLES_GESTION);
            $this->requireSchema();

            $db  = Database::programa();
            $pid = Database::proyectoId();

            $id    = (int)$this->getPost('id_vehiculo', 0);
            $datos = [
                'placa'            => trim((string)$this->getPost('placa', '')),
                'marca'            => trim((string)$this->getPost('marca', '')),
                'modelo'           => trim((string)$this->getPost('modelo', '')),
                'anio'             => (int)$this->getPost('anio', 0) ?: null,
                'color'            => trim((string)$this->getPost('color', '')),
                'vin'              => trim((string)$this->getPost('vin', '')),
                'tipo_combustible' => trim((string)$this->getPost('tipo_combustible', 'gasolina')),
                'km_actual'        => (int)$this->getPost('km_actual', 0),
                'estado'           => trim((string)$this->getPost('estado', 'activo')),
                'vence_seguro'     => $this->getPost('vence_seguro', '') ?: null,
                'observaciones'    => trim((string)$this->getPost('observaciones', '')),
            ];

            // Validación de catálogos
            $combs = defined('FLOTA_COMBUSTIBLES') ? array_keys(FLOTA_COMBUSTIBLES) : ['gasolina'];
            if (!in_array($datos['tipo_combustible'], $combs, true)) {
                $datos['tipo_combustible'] = 'gasolina';
            }
            $estados = defined('FLOTA_ESTADOS_VEHICULO') ? array_keys(FLOTA_ESTADOS_VEHICULO) : ['activo'];
            if (!in_array($datos['estado'], $estados, true)) {
                $datos['estado'] = 'activo';
            }

            if ($datos['placa'] === '') {
                $this->error('La placa es obligatoria.');
                return;
            }
            if ($datos['km_actual'] < 0) {
                $this->error('El kilometraje no puede ser negativo.');
                return;
            }
            $anioActual = (int)date('Y') + 1;
            if ($datos['anio'] !== null && ($datos['anio'] < 1950 || $datos['anio'] > $anioActual)) {
                $this->error('El año del vehículo no es válido.');
                return;
            }

            // Una baja lógica conserva la placa y su historial. Si intentan
            // registrar nuevamente esa placa, se reactiva el mismo expediente.
            $reactivado = false;
            if ($id === 0) {
                $existente = $db->fetchOne(
                    "SELECT id_vehiculo, activo
                       FROM sag_flota_vehiculos
                      WHERE id_proyecto = ? AND placa = ?
                      LIMIT 1",
                    [$pid, $datos['placa']]
                );
                if ($existente) {
                    if ((int)$existente['activo'] === 1) {
                        $this->error('Ya existe un vehículo activo con esa placa.');
                        return;
                    }
                    $id = (int)$existente['id_vehiculo'];
                    $reactivado = true;
                }
            }

            if ($id > 0) {
                // UPDATE
                $sets = array_map(fn($k) => "{$k} = :{$k}", array_keys($datos));
                $sql  = "UPDATE sag_flota_vehiculos SET " . implode(', ', $sets) . ", activo = 1
                          WHERE id_vehiculo = :id AND id_proyecto = :pid";
                $params = $datos + [':id' => $id, ':pid' => $pid];
                $params = array_combine(
                    array_map(fn($k) => str_starts_with($k, ':') ? $k : ":{$k}", array_keys($params)),
                    array_values($params)
                );
                $db->execute($sql, $params);
                $this->guardarProgramacion($id, $datos['km_actual']);
                $accion = $reactivado ? 'FLOTA_REACTIVA_VEHICULO' : 'FLOTA_UPD_VEHICULO';
                $this->logAction($accion, 'flota', "#{$id} placa={$datos['placa']}");
                $this->success(
                    $reactivado ? 'Vehículo reactivado y actualizado.' : 'Vehículo actualizado.',
                    ['id' => $id, 'reactivado' => $reactivado]
                );
            } else {
                // INSERT
                $datos['id_proyecto'] = $pid;
                $cols = array_keys($datos);
                $place = array_map(fn($c) => ":{$c}", $cols);
                $sql = "INSERT INTO sag_flota_vehiculos (" . implode(',', $cols) . ")
                        VALUES (" . implode(',', $place) . ")";
                $params = [];
                foreach ($datos as $k => $v) $params[":{$k}"] = $v;
                $db->execute($sql, $params);
                $newId = (int)$db->lastInsertId();
                $this->guardarProgramacion($newId, $datos['km_actual']);
                $this->logAction('FLOTA_NEW_VEHICULO', 'flota', "#{$newId} placa={$datos['placa']}");
                $this->success("Vehículo registrado.", ['id' => $newId]);
            }
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
        } catch (\Throwable $e) {
            error_log('FlotaController::guardarVehiculo — ' . $e->getMessage());
            if ((string)$e->getCode() === '23000') {
                $this->error('Ya existe un vehículo con esa placa en el programa activo.');
                return;
            }
            $this->error('No se pudo guardar el vehículo. Revise los datos e intente nuevamente.');
        }
    }

    public function eliminarVehiculo(): void
    {
        try {
            $this->requireCsrf();
            $this->requireRole(self::ROLES_GESTION);
            $this->requireSchema();
            $id  = (int)$this->getPost('id', 0);
            $db  = Database::programa();
            $pid = Database::proyectoId();
            // Soft delete (activo=0). No borramos para mantener integridad con viajes/mantenimientos.
            $db->execute(
                "UPDATE sag_flota_vehiculos SET activo = 0
                  WHERE id_vehiculo = ? AND id_proyecto = ?",
                [$id, $pid]
            );
            $this->logAction('FLOTA_DEL_VEHICULO', 'flota', "#{$id}");
            $this->success('Vehículo dado de baja.', ['id' => $id]);
        } catch (\Throwable $e) {
            error_log('FlotaController::eliminarVehiculo — ' . $e->getMessage());
            $this->error('No se pudo dar de baja el vehículo.');
        }
    }

    // ════════════════════════════════════════════════════════════
    //  CRUD VIAJE
    // ════════════════════════════════════════════════════════════
    public function guardarViaje(): void
    {
        try {
            $this->requireCsrf();
            $this->requireRole(self::ROLES_GESTION);
            $this->requireSchema();
            $db  = Database::programa();
            $pid = Database::proyectoId();

            $idVeh    = (int)$this->getPost('id_vehiculo', 0);
            $fecha    = trim((string)$this->getPost('fecha', date('Y-m-d')));
            $conductor= trim((string)$this->getPost('conductor', ''));
            $kmInicial= (int)$this->getPost('km_inicial', 0);
            $destino  = trim((string)$this->getPost('destino', ''));
            $proposito= trim((string)$this->getPost('proposito', ''));

            if ($idVeh <= 0 || $conductor === '') {
                $this->error('Vehículo y conductor son obligatorios.');
                return;
            }
            $vehiculo = $this->vehiculoDelPrograma($idVeh);
            if (!$vehiculo) {
                $this->error('El vehículo no pertenece al programa activo.');
                return;
            }
            if ($kmInicial < (int)$vehiculo['km_actual']) {
                $this->error('El kilometraje inicial no puede ser menor al registrado en el vehículo.');
                return;
            }
            $viajeActivo = $db->fetchOne(
                "SELECT id_viaje
                   FROM sag_flota_viajes
                  WHERE id_proyecto = ?
                    AND id_vehiculo = ?
                    AND estado = 'en_curso'
                    AND activo = 1
                  LIMIT 1",
                [$pid, $idVeh]
            );
            if ($viajeActivo) {
                $this->error('El vehículo ya tiene un viaje en curso.');
                return;
            }

            $db->execute(
                "INSERT INTO sag_flota_viajes
                    (id_proyecto, id_vehiculo, fecha, conductor, km_inicial, destino, proposito, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'en_curso')",
                [$pid, $idVeh, $fecha, $conductor, $kmInicial, $destino, $proposito]
            );
            $newId = (int)$db->lastInsertId();
            $this->logAction('FLOTA_NEW_VIAJE', 'flota', "#{$newId} veh={$idVeh}");
            $this->success('Viaje registrado.', ['id' => $newId]);
        } catch (\Throwable $e) {
            error_log('FlotaController::guardarViaje — ' . $e->getMessage());
            $this->error('No se pudo registrar el viaje.');
        }
    }

    /**
     * Cierra un viaje en curso: marca estado=finalizado, registra km_final,
     * y actualiza km_actual del vehículo si km_final > km_actual.
     */
    public function cerrarViaje(): void
    {
        try {
            $this->requireCsrf();
            $this->requireRole(self::ROLES_GESTION);
            $this->requireSchema();
            $db  = Database::programa();
            $pid = Database::proyectoId();

            $idViaje = (int)$this->getPost('id_viaje', 0);
            $kmFinal = (int)$this->getPost('km_final', 0);

            if ($idViaje <= 0 || $kmFinal <= 0) {
                $this->error('Datos incompletos.');
                return;
            }

            $viaje = $db->fetchOne(
                "SELECT * FROM sag_flota_viajes WHERE id_viaje = ? AND id_proyecto = ?",
                [$idViaje, $pid]
            );
            if (!$viaje) { $this->error('Viaje no encontrado.'); return; }
            if ($viaje['estado'] !== 'en_curso' || !(int)$viaje['activo']) {
                $this->error('El viaje ya fue cerrado o no está disponible.');
                return;
            }
            if ($kmFinal < (int)$viaje['km_inicial']) {
                $this->error('El km final no puede ser menor al inicial.');
                return;
            }

            $db->beginTransaction();
            try {
                $db->execute(
                    "UPDATE sag_flota_viajes
                        SET km_final = ?, estado = 'finalizado'
                      WHERE id_viaje = ? AND id_proyecto = ? AND estado = 'en_curso'",
                    [$kmFinal, $idViaje, $pid]
                );

                $db->execute(
                    "UPDATE sag_flota_vehiculos
                        SET km_actual = GREATEST(km_actual, ?)
                      WHERE id_vehiculo = ? AND id_proyecto = ?",
                    [$kmFinal, (int)$viaje['id_vehiculo'], $pid]
                );
                $db->commit();
            } catch (\Throwable $e) {
                $db->rollback();
                throw $e;
            }

            $this->logAction('FLOTA_CIERRA_VIAJE', 'flota', "#{$idViaje} km_final={$kmFinal}");
            $this->success('Viaje cerrado.', ['id' => $idViaje]);
        } catch (\Throwable $e) {
            error_log('FlotaController::cerrarViaje — ' . $e->getMessage());
            $this->error('No se pudo cerrar el viaje.');
        }
    }

    public function eliminarViaje(): void
    {
        try {
            $this->requireCsrf();
            $this->requireRole(self::ROLES_GESTION);
            $this->requireSchema();
            $id = (int)$this->getPost('id', 0);
            $db = Database::programa();
            $pid = Database::proyectoId();
            $db->execute(
                "UPDATE sag_flota_viajes
                    SET activo = 0
                  WHERE id_viaje = ? AND id_proyecto = ?",
                [$id, $pid]
            );
            $this->logAction('FLOTA_DEL_VIAJE', 'flota', "#{$id}");
            $this->success('Viaje eliminado.', ['id' => $id]);
        } catch (\Throwable $e) {
            error_log('FlotaController::eliminarViaje — ' . $e->getMessage());
            $this->error('No se pudo eliminar el viaje.');
        }
    }

    // ════════════════════════════════════════════════════════════
    //  CRUD MANTENIMIENTO
    // ════════════════════════════════════════════════════════════
    public function guardarMantenimiento(): void
    {
        try {
            $this->requireCsrf();
            $this->requireRole(self::ROLES_GESTION);
            $this->requireSchema();
            $db  = Database::programa();
            $pid = Database::proyectoId();

            $idVeh       = (int)$this->getPost('id_vehiculo', 0);
            $tipo        = trim((string)$this->getPost('tipo', ''));
            $fecha       = trim((string)$this->getPost('fecha', date('Y-m-d')));
            $kmRealizar  = (int)$this->getPost('km_al_realizar', 0);
            $costo       = (float)$this->getPost('costo', 0);
            $taller      = trim((string)$this->getPost('taller', ''));
            $descripcion = trim((string)$this->getPost('descripcion', ''));

            // Validar tipo contra catálogo
            $tipos = defined('FLOTA_TIPOS_MANTENIMIENTO') ? array_keys(FLOTA_TIPOS_MANTENIMIENTO) : [];
            if ($idVeh <= 0 || !in_array($tipo, $tipos, true)) {
                $this->error('Vehículo o tipo de mantenimiento inválido.');
                return;
            }
            if (!$this->vehiculoDelPrograma($idVeh)) {
                $this->error('El vehículo no pertenece al programa activo.');
                return;
            }
            if ($kmRealizar < 0 || $costo < 0) {
                $this->error('Kilometraje y costo deben ser valores positivos.');
                return;
            }

            $db->beginTransaction();
            try {
                $db->execute(
                    "INSERT INTO sag_flota_mantenimientos
                        (id_proyecto, id_vehiculo, fecha, tipo, km_al_realizar, costo, taller, descripcion)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$pid, $idVeh, $fecha, $tipo, $kmRealizar, $costo, $taller, $descripcion]
                );
                $newId = (int)$db->lastInsertId();

                if ($kmRealizar > 0) {
                    $db->execute(
                        "UPDATE sag_flota_vehiculos
                            SET km_actual = GREATEST(km_actual, ?)
                          WHERE id_vehiculo = ? AND id_proyecto = ?",
                        [$kmRealizar, $idVeh, $pid]
                    );
                }
                $intervalo = (int)(FLOTA_TIPOS_MANTENIMIENTO[$tipo]['cada_km'] ?? 0);
                if ($intervalo > 0) {
                    $db->execute(
                        "INSERT INTO sag_flota_programacion_mantenimiento
                            (id_proyecto,id_vehiculo,tipo,intervalo_km,ultimo_km,proximo_km,activo)
                         VALUES (?,?,?,?,?,?,1)
                         ON DUPLICATE KEY UPDATE
                            intervalo_km=VALUES(intervalo_km),
                            ultimo_km=VALUES(ultimo_km),
                            proximo_km=VALUES(proximo_km),
                            activo=1",
                        [$pid, $idVeh, $tipo, $intervalo, $kmRealizar, $kmRealizar + $intervalo]
                    );
                }
                $db->commit();
            } catch (\Throwable $e) {
                $db->rollback();
                throw $e;
            }

            $this->logAction('FLOTA_NEW_MANTO', 'flota', "#{$newId} veh={$idVeh} tipo={$tipo}");
            $this->success('Mantenimiento registrado.', ['id' => $newId]);
        } catch (\Throwable $e) {
            error_log('FlotaController::guardarMantenimiento — ' . $e->getMessage());
            $this->error('No se pudo registrar el mantenimiento.');
        }
    }

    public function eliminarMantenimiento(): void
    {
        try {
            $this->requireCsrf();
            $this->requireRole(self::ROLES_GESTION);
            $this->requireSchema();
            $id = (int)$this->getPost('id', 0);
            $db = Database::programa();
            $pid = Database::proyectoId();
            $db->execute(
                "UPDATE sag_flota_mantenimientos
                    SET activo = 0
                  WHERE id_mantenimiento = ? AND id_proyecto = ?",
                [$id, $pid]
            );
            $this->logAction('FLOTA_DEL_MANTO', 'flota', "#{$id}");
            $this->success('Mantenimiento eliminado.', ['id' => $id]);
        } catch (\Throwable $e) {
            error_log('FlotaController::eliminarMantenimiento — ' . $e->getMessage());
            $this->error('No se pudo eliminar el mantenimiento.');
        }
    }

    public function guardarCombustible(): void
    {
        try {
            $this->requireCsrf();
            $this->requireRole(self::ROLES_GESTION);
            $this->requireSchema();
            $db = Database::programa();
            $pid = Database::proyectoId();
            $idVehiculo = (int)$this->getPost('id_vehiculo', 0);
            $vehiculo = $this->vehiculoDelPrograma($idVehiculo);
            $galones = (float)$this->getPost('galones', 0);
            $precio = (float)$this->getPost('precio_galon', 0);
            $monto = (float)$this->getPost('monto', 0);
            $km = (int)$this->getPost('km_odometro', 0);

            if (!$vehiculo) { $this->error('El vehículo no pertenece al programa activo.'); return; }
            if ($galones <= 0 || $km < (int)$vehiculo['km_actual']) {
                $this->error('Revise los galones y la lectura del odómetro.');
                return;
            }
            if ($monto <= 0 && $precio > 0) $monto = round($galones * $precio, 2);
            if ($precio <= 0 && $monto > 0) $precio = round($monto / $galones, 2);

            $db->beginTransaction();
            try {
                $db->execute(
                    "INSERT INTO sag_flota_combustible
                        (id_proyecto,id_vehiculo,fecha,numero_vale,conductor,galones,
                         precio_galon,monto,km_odometro,estacion,numero_factura,observaciones)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                    [
                        $pid, $idVehiculo, $this->getPost('fecha', date('Y-m-d')),
                        $this->getPost('numero_vale'), $this->getPost('conductor'),
                        $galones, $precio, $monto, $km, $this->getPost('estacion'),
                        $this->getPost('numero_factura'), $this->getPost('observaciones'),
                    ]
                );
                $id = (int)$db->lastInsertId();
                $db->execute(
                    "UPDATE sag_flota_vehiculos SET km_actual=GREATEST(km_actual,?)
                      WHERE id_vehiculo=? AND id_proyecto=?",
                    [$km, $idVehiculo, $pid]
                );
                $db->commit();
            } catch (\Throwable $e) {
                $db->rollback();
                throw $e;
            }
            $this->logAction('FLOTA_NEW_COMBUSTIBLE', 'flota', "#{$id} veh={$idVehiculo}");
            $this->success('Carga de combustible registrada.', ['id' => $id]);
        } catch (\Throwable $e) {
            error_log('FlotaController::guardarCombustible — ' . $e->getMessage());
            $this->error('No se pudo registrar la carga de combustible.');
        }
    }

    public function eliminarCombustible(): void
    {
        try {
            $this->requireCsrf();
            $this->requireRole(self::ROLES_GESTION);
            $this->requireSchema();
            $id = (int)$this->getPost('id', 0);
            Database::programa()->execute(
                "UPDATE sag_flota_combustible SET activo=0
                  WHERE id_carga=? AND id_proyecto=?",
                [$id, Database::proyectoId()]
            );
            $this->logAction('FLOTA_DEL_COMBUSTIBLE', 'flota', "#{$id}");
            $this->success('Carga de combustible eliminada.');
        } catch (\Throwable $e) {
            error_log('FlotaController::eliminarCombustible — ' . $e->getMessage());
            $this->error('No se pudo eliminar la carga.');
        }
    }
}
