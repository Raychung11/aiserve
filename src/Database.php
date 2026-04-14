<?php
/**
 * Database connection class using PDO
 * Singleton pattern for single connection instance
 */
class Database {

    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
            }
        }
        return self::$instance;
    }

    /**
     * Execute a query with optional parameters
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Insert a row and return last insert ID
     */
    public static function insert(string $table, array $data): int {
        $cols    = implode(', ', array_keys($data));
        $holders = implode(', ', array_fill(0, count($data), '?'));
        $sql     = "INSERT INTO `{$table}` ({$cols}) VALUES ({$holders})";
        self::query($sql, array_values($data));
        return (int) self::getInstance()->lastInsertId();
    }

    /**
     * Update rows matching conditions
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int {
        $setParts = array_map(fn($col) => "`{$col}` = ?", array_keys($data));
        $set      = implode(', ', $setParts);
        $sql      = "UPDATE `{$table}` SET {$set} WHERE {$where}";
        $stmt     = self::query($sql, array_merge(array_values($data), $whereParams));
        return $stmt->rowCount();
    }

    /**
     * Insert or update (upsert) for esg_data
     */
    public static function upsert(string $table, array $data, array $uniqueKeys): int {
        $cols      = implode(', ', array_keys($data));
        $holders   = implode(', ', array_fill(0, count($data), '?'));
        $updateSet = array_map(
            fn($col) => "`{$col}` = VALUES(`{$col}`)",
            array_diff(array_keys($data), $uniqueKeys)
        );
        $updateStr = implode(', ', $updateSet);
        $sql = "INSERT INTO `{$table}` ({$cols}) VALUES ({$holders})
                ON DUPLICATE KEY UPDATE {$updateStr}";
        self::query($sql, array_values($data));
        return (int) self::getInstance()->lastInsertId();
    }
}
