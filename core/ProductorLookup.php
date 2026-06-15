<?php
/**
 * Consulta unificada de personas productoras por DNI.
 */
class ProductorLookup
{
    public static function buscar(string $dniRaw): array
    {
        $dni = preg_replace('/\D/', '', $dniRaw);
        if (strlen($dni) !== 13) {
            throw new InvalidArgumentException('El DNI debe tener 13 dígitos.');
        }

        $db  = Database::main();
        $pid = Database::proyectoId();

        $actual = $db->fetchOne(
            "SELECT id_beneficiario, nombre, apellido, dni, fecha_nacimiento, sexo,
                    id_departamento, id_municipio, id_organizacion, aldea, telefono, estado
               FROM sag_beneficiarios
              WHERE REPLACE(dni, '-', '') = ? AND id_proyecto = ?
              LIMIT 1",
            [$dni, $pid]
        );
        if ($actual) {
            return self::resultado('beneficiario_actual', 'Productor encontrado en los beneficiarios del programa actual.', $actual);
        }

        $otro = $db->fetchOne(
            "SELECT b.nombre, b.apellido, b.dni, b.fecha_nacimiento, b.sexo,
                    b.id_departamento, b.id_municipio, b.id_organizacion, b.aldea, b.telefono,
                    p.sigla AS programa_sigla, p.nombre AS programa_nombre
               FROM sag_beneficiarios b
               JOIN sag_proyectos p ON p.id_proyecto = b.id_proyecto
              WHERE REPLACE(b.dni, '-', '') = ? AND b.id_proyecto <> ?
              ORDER BY b.id_beneficiario DESC
              LIMIT 1",
            [$dni, $pid]
        );
        if ($otro) {
            return self::resultado('beneficiario_otro_pip', 'Productor encontrado en otro programa SAG.', $otro);
        }

        // Registros internos generados por otros módulos del sistema.
        if ($db->tablaExiste('sag_cap_participantes')) {
            try {
                $participante = $db->fetchOne(
                    "SELECT nombre, apellido, dni, edad, sexo, id_organizacion, telefono
                       FROM sag_cap_participantes
                      WHERE REPLACE(dni, '-', '') = ?
                      ORDER BY id_participante DESC
                      LIMIT 1",
                    [$dni]
                );
                if ($participante) {
                    return self::resultado('capacitacion', 'Productor encontrado en participantes de capacitaciones.', $participante);
                }
            } catch (Throwable $e) {
                error_log('ProductorLookup capacitaciones - ' . $e->getMessage());
            }
        }

        if ($db->tablaExiste('sag_asistencias_tecnicas')) {
            try {
                $asistencia = $db->fetchOne(
                    "SELECT productor_nombre AS nombre, productor_apellido AS apellido,
                            productor_dni AS dni, productor_edad AS edad, productor_sexo AS sexo,
                            id_organizacion, productor_telefono AS telefono
                       FROM sag_asistencias_tecnicas
                      WHERE REPLACE(productor_dni, '-', '') = ?
                      ORDER BY id_at DESC
                      LIMIT 1",
                    [$dni]
                );
                if ($asistencia) {
                    return self::resultado('asistencia', 'Productor encontrado en asistencias técnicas.', $asistencia);
                }
            } catch (Throwable $e) {
                error_log('ProductorLookup asistencias - ' . $e->getMessage());
            }
        }

        // El censo contiene datos personales más completos que Entregas.
        if ($db->tablaExiste('sag_censo_nacional')) {
            try {
                $censo = $db->fetchOne(
                    "SELECT dni, nombres, apellidos, fecha_nacimiento, sexo,
                            codigo_departamento, codigo_municipio, codigo_aldea, etnia
                       FROM sag_censo_nacional
                      WHERE REPLACE(dni, '-', '') = ?
                      LIMIT 1",
                    [$dni]
                );
                if ($censo) {
                    return self::resultado('censo', 'Persona encontrada en el censo nacional.', $censo);
                }
            } catch (Throwable $e) {
                error_log('ProductorLookup censo - ' . $e->getMessage());
            }
        }

        if ($db->tablaExiste('sag_trazaragro_movimientos')) {
            try {
                $entrega = $db->fetchOne(
                    "SELECT m.destino_dni AS dni, m.destino_nombre, m.destino_persona,
                            m.destino_departamento, m.destino_municipio,
                            p.sigla AS programa_sigla, p.nombre AS programa_nombre
                       FROM sag_trazaragro_movimientos m
                       LEFT JOIN sag_proyectos p ON p.id_proyecto = m.id_proyecto
                      WHERE REPLACE(m.destino_dni, '-', '') = ?
                        AND m.estado_local = 'entregado'
                      ORDER BY (m.id_proyecto = ?) DESC,
                               COALESCE(m.fecha_revision_local, m.fecha_autorizacion, m.synced_at) DESC
                      LIMIT 1",
                    [$dni, $pid]
                );
                if ($entrega) {
                    return self::resultadoEntrega($entrega);
                }
            } catch (Throwable $e) {
                error_log('ProductorLookup entregas - ' . $e->getMessage());
                $entrega = $db->fetchOne(
                    "SELECT destino_dni AS dni, destino_nombre, destino_persona,
                            destino_departamento, destino_municipio
                       FROM sag_trazaragro_movimientos
                      WHERE REPLACE(destino_dni, '-', '') = ?
                        AND estado_local = 'entregado'
                      ORDER BY COALESCE(fecha_revision_local, fecha_autorizacion, synced_at) DESC
                      LIMIT 1",
                    [$dni]
                );
                if ($entrega) {
                    return self::resultadoEntrega($entrega);
                }
            }
        }

        return [
            'source'   => 'ninguno',
            'found'    => false,
            'verified' => false,
            'message'  => 'La identidad no aparece en los registros internos ni en el censo. Puede completar los datos manualmente.',
            'persona'  => null,
        ];
    }

    private static function resultado(string $source, string $message, array $persona): array
    {
        if (!empty($persona['fecha_nacimiento'])) {
            try {
                $persona['edad'] = (new DateTime($persona['fecha_nacimiento']))->diff(new DateTime('today'))->y;
            } catch (Throwable $e) {
                $persona['edad'] = null;
            }
        }

        return [
            'source'   => $source,
            'found'    => true,
            'verified' => true,
            'message'  => $message,
            'persona'  => $persona,
        ];
    }

    private static function resultadoEntrega(array $entrega): array
    {
        $nombreCompleto = trim((string) ($entrega['destino_nombre'] ?: $entrega['destino_persona']));
        $nombreCompleto = trim((string) preg_replace('/[;,\s-]*\d{4}-?\d{4}-?\d{5}\s*$/', '', $nombreCompleto));
        [$entrega['nombre'], $entrega['apellido']] = self::separarNombreCompleto($nombreCompleto);
        return self::resultado('entrega', 'Productor encontrado en los registros de entregas.', $entrega);
    }

    private static function separarNombreCompleto(string $nombreCompleto): array
    {
        $partes = preg_split('/\s+/', trim($nombreCompleto)) ?: [];
        $total  = count($partes);
        if ($total <= 1) return [$nombreCompleto, ''];
        if ($total === 2) return [$partes[0], $partes[1]];

        // En nombres hispanos de tres palabras suele ser nombre + dos apellidos.
        if ($total === 3) {
            return [$partes[0], implode(' ', array_slice($partes, 1))];
        }

        $corte = (int) ceil($total / 2);
        return [
            implode(' ', array_slice($partes, 0, $corte)),
            implode(' ', array_slice($partes, $corte)),
        ];
    }
}
