<?php
/**
 * Database — Singleton multi-conexión
 * Soporta: DB principal (auth) + DB del programa activo
 */
class Database
{
    /** @var PDO[] instancias activas por clave */
    private static array $instances = [];

    private function __construct() {}
    private function __clone() {}

    /**
     * Conexión a la base de datos principal (sag_main)
     */
    public static function main(): self
    {
        return self::getConnection('__main__', DB_MAIN);
    }

    /**
     * Conexión a la base de datos del programa activo
     * Se determina por $_SESSION['programa']['db']
     */
    public static function programa(): self
    {
        if (empty($_SESSION['programa']['db'])) {
            throw new RuntimeException('No hay programa activo en la sesión.');
        }

        $key = $_SESSION['programa']['db'];

        if (!isset(self::$instances[$key])) {
            $programas = PROGRAMAS;
            $progId    = $_SESSION['programa']['id'] ?? null;

            if (!$progId || !isset($programas[$progId])) {
                throw new RuntimeException("Programa '{$progId}' no configurado.");
            }

            $cfg = array_merge(DB_MAIN, [
                'database' => $programas[$progId]['db'],
            ]);
            self::getConnection($key, $cfg);
        }

        return self::$instances[$key];
    }

    /**
     * Alias legacy — devuelve la conexión del programa activo
     * (compatibilidad con los modelos existentes)
     */
    public static function getInstance(): self
    {
        return self::programa();
    }

    // ── Métodos de consulta ───────────────────────

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchOne(string $sql, array $params = []): array|false
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction(): void { $this->pdo->beginTransaction(); }
    public function commit(): void           { $this->pdo->commit(); }
    public function rollback(): void         { $this->pdo->rollBack(); }

    // ── Internos ──────────────────────────────────

    private PDO $pdo;

    private static function getConnection(string $key, array $cfg): self
    {
        if (!isset(self::$instances[$key])) {
            $obj = new self();
            $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['database']};charset={$cfg['charset']}";
            try {
                $obj->pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                error_log('DB Connection error: ' . $e->getMessage());
                http_response_code(503);
                die(json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']));
            }
            self::$instances[$key] = $obj;
        }
        return self::$instances[$key];
    }
}
