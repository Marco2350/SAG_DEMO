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
     * Conexión del programa activo.
     * Tras la unificación todos los programas viven en sag_main, por lo que
     * esta conexión es la misma que main(); la separación entre programas la
     * da la columna id_proyecto (ver self::proyectoId()).
     */
    public static function programa(): self
    {
        if (empty($_SESSION['programa']['id_proyecto'])) {
            throw new RuntimeException('No hay programa activo en la sesión.');
        }
        return self::main();
    }

    /**
     * id_proyecto del programa activo en sesión.
     * Usar para filtrar/sellar consultas por programa.
     */
    public static function proyectoId(): int
    {
        $id = (int) ($_SESSION['programa']['id_proyecto'] ?? 0);
        if ($id <= 0) {
            throw new RuntimeException('No hay programa activo en la sesión.');
        }
        return $id;
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

    /**
     * Verifica si una tabla existe en la base actual.
     * Útil para que los controladores muestren un mensaje amable cuando
     * una migración aún no fue aplicada (en lugar de un PDO fatal).
     */
    public function tablaExiste(string $nombre): bool
    {
        try {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$nombre]);
            return (bool) $stmt->fetch(PDO::FETCH_NUM);
        } catch (\Throwable $e) {
            return false;
        }
    }

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
                    PDO::ATTR_PERSISTENT         => true,
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
