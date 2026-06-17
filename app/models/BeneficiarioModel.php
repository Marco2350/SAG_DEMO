<?php
class BeneficiarioModel extends Model
{
    protected string $table      = 'sag_beneficiarios';
    protected string $primaryKey = 'id_beneficiario';

    public const SEXOS = ['M', 'F'];

    /** FROM + JOINs + WHERE compartidos por el listado y sus conteos. */
    private function buildListado(array $filtros): array
    {
        $where  = ['b.id_proyecto = ?', "b.estado = 'activo'"];
        $params = [Database::proyectoId()];

        if (!empty($filtros['id_organizacion'])) {
            $where[]  = 'b.id_organizacion = ?';
            $params[] = $filtros['id_organizacion'];
        }
        if (!empty($filtros['id_departamento'])) {
            $where[]  = 'b.id_departamento = ?';
            $params[] = $filtros['id_departamento'];
        }
        if (!empty($filtros['sexo'])) {
            $where[]  = 'b.sexo = ?';
            $params[] = $filtros['sexo'];
        }
        if (!empty($filtros['buscar'])) {
            $where[] = "(CONCAT(b.nombre,' ',b.apellido) LIKE ? OR b.dni LIKE ? OR b.aldea LIKE ? OR o.nombre LIKE ?)";
            $like = '%' . $filtros['buscar'] . '%';
            array_push($params, $like, $like, $like, $like);
        }

        $sql = "FROM sag_beneficiarios b
                INNER JOIN sag_departamentos  d ON d.id_departamento = b.id_departamento
                INNER JOIN sag_municipios     m ON m.id_municipio    = b.id_municipio
                LEFT  JOIN sag_organizaciones o ON o.id_organizacion = b.id_organizacion
                WHERE " . implode(' AND ', $where);
        return [$sql, $params];
    }

    /**
     * Listado paginado (protocolo DataTables server-side).
     * $orden: cláusula ORDER BY ya saneada por el controlador.
     */
    public function getListado(array $filtros = [], int $start = 0, int $length = 0, string $orden = 'b.nombre, b.apellido'): array
    {
        [$fromWhere, $params] = $this->buildListado($filtros);
        $limit = $length > 0 ? "LIMIT {$length} OFFSET " . max(0, $start) : '';

        return $this->db->fetchAll(
            "SELECT b.*,
                    d.nombre AS departamento,
                    m.nombre AS municipio,
                    o.nombre AS organizacion,
                    CONCAT(b.nombre,' ',b.apellido) AS nombre_completo,
                    TIMESTAMPDIFF(YEAR, b.fecha_nacimiento, CURDATE()) AS edad
             {$fromWhere}
             ORDER BY {$orden}
             {$limit}",
            $params
        );
    }

    /** Total de registros que cumplen los filtros (para recordsFiltered). */
    public function contarListado(array $filtros = []): int
    {
        [$fromWhere, $params] = $this->buildListado($filtros);
        return (int) ($this->db->fetchOne("SELECT COUNT(*) AS t {$fromWhere}", $params)['t'] ?? 0);
    }

    public function getDetalle(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT b.*,
                    d.nombre AS departamento,
                    m.nombre AS municipio,
                    o.nombre AS organizacion
             FROM sag_beneficiarios b
             INNER JOIN sag_departamentos  d ON d.id_departamento = b.id_departamento
             INNER JOIN sag_municipios     m ON m.id_municipio    = b.id_municipio
             LEFT  JOIN sag_organizaciones o ON o.id_organizacion = b.id_organizacion
             WHERE b.id_beneficiario = ? AND b.id_proyecto = ?",
            [$id, Database::proyectoId()]
        ) ?: false;
    }

    public function existeDNI(string $dni, int $excludeId = 0): bool
    {
        // Comparar sin guiones: la BD puede tener DNI legados con formato 0000-0000-00000
        $sql    = "SELECT COUNT(*) AS t FROM sag_beneficiarios
                   WHERE REPLACE(dni,'-','') = ? AND estado='activo' AND id_proyecto=?";
        $params = [preg_replace('/\D/', '', $dni), Database::proyectoId()];
        if ($excludeId > 0) { $sql .= " AND id_beneficiario != ?"; $params[] = $excludeId; }
        return ((int) ($this->db->fetchOne($sql, $params)['t'] ?? 0)) > 0;
    }

    public function guardar(array $data, int $id = 0): int
    {
        if ($id > 0) { $this->update($id, $data); return $id; }
        return $this->insert($data);
    }

    public function insertarMasivo(array $registros, int $createdBy): array
    {
        $ok = 0; $errores = [];
        $this->db->beginTransaction();
        try {
            foreach ($registros as $i => $r) {
                $fila = $i + 2;
                if (empty($r['nombre']) || empty($r['apellido'])) {
                    $errores[] = "Fila {$fila}: nombre y apellido son obligatorios."; continue;
                }
                $sexo = strtoupper(trim($r['sexo'] ?? ''));
                if (!in_array($sexo, self::SEXOS, true)) {
                    $errores[] = "Fila {$fila}: sexo debe ser M o F."; continue;
                }
                $idDep = (int) ($r['id_departamento'] ?? 0);
                $idMun = (int) ($r['id_municipio']    ?? 0);
                if (!$idDep || !$idMun || !$this->municipioValido($idMun, $idDep)) {
                    $errores[] = "Fila {$fila}: departamento/municipio no válidos."; continue;
                }
                $dni = preg_replace('/\D/', '', (string) ($r['dni'] ?? ''));
                if ($dni !== '' && strlen($dni) !== 13) {
                    $errores[] = "Fila {$fila}: el DNI debe tener 13 dígitos."; continue;
                }
                if ($dni !== '' && $this->existeDNI($dni)) {
                    $errores[] = "Fila {$fila}: DNI {$dni} ya está registrado."; continue;
                }
                // Etnia: validar contra catálogo si viene, si no NULL
                $etniaRaw = trim((string)($r['etnia'] ?? ''));
                $etnia    = null;
                if ($etniaRaw !== '' && defined('ETNIAS_HONDURAS')) {
                    $etniaKey = strtolower($etniaRaw);
                    if (array_key_exists($etniaKey, ETNIAS_HONDURAS)) {
                        $etnia = $etniaKey;
                    } else {
                        // Acepta también el nombre de etiqueta (ej. "Garífuna")
                        $reverso = array_change_key_case(array_flip(ETNIAS_HONDURAS), CASE_LOWER);
                        $etnia   = $reverso[$etniaKey] ?? null;
                    }
                }

                $this->insert([
                    'id_departamento' => $idDep,
                    'id_municipio'    => $idMun,
                    'id_organizacion' => ((int) ($r['id_organizacion'] ?? 0)) ?: null,
                    'nombre'          => trim($r['nombre']),
                    'apellido'        => trim($r['apellido']),
                    'dni'             => $dni ?: null,
                    'fecha_nacimiento'=> ($r['fecha_nacimiento'] ?? '') ?: null,
                    'sexo'            => $sexo,
                    'etnia'           => $etnia,
                    'telefono'        => $r['telefono']         ?? null,
                    'aldea'           => $r['aldea']            ?? null,
                    'estado'          => 'activo',
                    'created_by'      => $createdBy,
                ]);
                $ok++;
            }
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
        return ['ok' => $ok, 'errores' => $errores];
    }

    public function getResumen(): array
    {
        $pid = Database::proyectoId();
        $total = $this->db->fetchOne(
            "SELECT COUNT(*) AS t, SUM(sexo='M') AS hombres, SUM(sexo='F') AS mujeres
             FROM sag_beneficiarios WHERE estado='activo' AND id_proyecto=?",
            [$pid]
        );
        $orgs = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT id_organizacion) AS t
             FROM sag_beneficiarios WHERE estado='activo' AND id_organizacion IS NOT NULL AND id_proyecto=?",
            [$pid]
        );
        return [
            'total'   => (int) ($total['t']       ?? 0),
            'hombres' => (int) ($total['hombres']  ?? 0),
            'mujeres' => (int) ($total['mujeres']  ?? 0),
            'orgs'    => (int) ($orgs['t']         ?? 0),
        ];
    }
}
