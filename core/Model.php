<?php
/**
 * Model base — SAG Programas
 * Usa Database::programa() por defecto (la BD del programa activo)
 */
abstract class Model
{
    protected Database $db;
    protected string   $table      = '';
    protected string   $primaryKey = 'id';

    /**
     * Si es true, todas las operaciones base se filtran/sellan por id_proyecto
     * (la BD es única y los programas se distinguen por esta columna).
     */
    protected bool   $scoped       = true;
    protected string $proyectoCol  = 'id_proyecto';

    public function __construct()
    {
        // BD única (sag_main); el aislamiento por programa lo da id_proyecto.
        $this->db = Database::programa();
    }

    /** id_proyecto activo, o 0 si el modelo no está scoped. */
    protected function pid(): int
    {
        return $this->scoped ? Database::proyectoId() : 0;
    }

    public function getAll(string $orderBy = ''): array
    {
        $sql    = "SELECT * FROM {$this->table} WHERE activo = 1";
        $params = [];
        if ($this->scoped) {
            $sql .= " AND {$this->proyectoCol} = ?";
            $params[] = $this->pid();
        }
        if ($orderBy) $sql .= " ORDER BY {$orderBy}";
        return $this->db->fetchAll($sql, $params);
    }

    public function findById(int $id): array|false
    {
        $sql    = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $params = [$id];
        if ($this->scoped) {
            $sql .= " AND {$this->proyectoCol} = ?";
            $params[] = $this->pid();
        }
        return $this->db->fetchOne($sql, $params);
    }

    public function insert(array $data): int
    {
        // Sella el proyecto activo si el modelo está scoped y no viene explícito.
        if ($this->scoped && !array_key_exists($this->proyectoCol, $data)) {
            $data = [$this->proyectoCol => $this->pid()] + $data;
        }
        $cols         = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $this->db->execute(
            "INSERT INTO {$this->table} ({$cols}) VALUES ({$placeholders})",
            array_values($data)
        );
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): int
    {
        $set    = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        $sql    = "UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = ?";
        $params = [...array_values($data), $id];
        if ($this->scoped) {
            $sql .= " AND {$this->proyectoCol} = ?";
            $params[] = $this->pid();
        }
        return $this->db->execute($sql, $params);
    }

    public function softDelete(int $id): int
    {
        $sql    = "UPDATE {$this->table} SET activo = 0 WHERE {$this->primaryKey} = ?";
        $params = [$id];
        if ($this->scoped) {
            $sql .= " AND {$this->proyectoCol} = ?";
            $params[] = $this->pid();
        }
        return $this->db->execute($sql, $params);
    }

    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) AS total FROM {$this->table}";
        if ($this->scoped) {
            $sql .= " WHERE {$this->proyectoCol} = ?";
            array_unshift($params, $this->pid());
            if ($where) $sql .= " AND ({$where})";
        } elseif ($where) {
            $sql .= " WHERE {$where}";
        }
        $row = $this->db->fetchOne($sql, $params);
        return (int) ($row['total'] ?? 0);
    }
}
