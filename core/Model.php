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

    public function __construct()
    {
        // Por defecto usa la BD del programa activo
        $this->db = Database::programa();
    }

    public function getAll(string $orderBy = ''): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE activo = 1";
        if ($orderBy) $sql .= " ORDER BY {$orderBy}";
        return $this->db->fetchAll($sql);
    }

    public function findById(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    public function insert(array $data): int
    {
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
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        return $this->db->execute(
            "UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = ?",
            [...array_values($data), $id]
        );
    }

    public function softDelete(int $id): int
    {
        return $this->db->execute(
            "UPDATE {$this->table} SET activo = 0 WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) AS total FROM {$this->table}";
        if ($where) $sql .= " WHERE {$where}";
        $row = $this->db->fetchOne($sql, $params);
        return (int) ($row['total'] ?? 0);
    }
}
