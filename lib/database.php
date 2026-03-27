<?php
/**
 * DatabaseHelper
 * 
 * Provides database access with prepared statements
 * Prevents SQL injection and ensures consistent error handling
 */

class DatabaseHelper {
    private static $connection = null;
    private static $last_error = '';

    /**
     * Initialize database connection
     * Requires database credentials from /config/secrets.php
     */
    public static function init() {
        if (self::$connection !== null) {
            return;
        }

        require_once __DIR__ . '/../config/secrets.php';

        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            self::$connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            self::$last_error = 'Database connection failed: ' . $e->getMessage();
            error_log(self::$last_error);
            die('Database connection error');
        }
    }

    /**
     * Execute INSERT/UPDATE/DELETE with prepared statement
     * @param string $sql SQL query with ? placeholders
     * @param array $params Parameter values
     * @param string $types Type string (i=int, s=string, d=double, b=blob)
     * @return int Number of affected rows
     */
    public static function execute($sql, $params = [], $types = '') {
        self::init();
        
        try {
            $stmt = self::$connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            self::$last_error = 'Query error: ' . $e->getMessage();
            error_log(self::$last_error . ' | SQL: ' . $sql);
            return 0;
        }
    }

    /**
     * Query all results
     * @param string $sql SQL query with ? placeholders
     * @param array $params Parameter values
     * @param string $types Type string
     * @return array Array of rows
     */
    public static function queryAll($sql, $params = [], $types = '') {
        self::init();
        
        try {
            $stmt = self::$connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            self::$last_error = 'Query error: ' . $e->getMessage();
            error_log(self::$last_error . ' | SQL: ' . $sql);
            return [];
        }
    }

    /**
     * Query single result
     * @param string $sql SQL query with ? placeholders
     * @param array $params Parameter values
     * @param string $types Type string
     * @return array|null Single row or null
     */
    public static function queryOne($sql, $params = [], $types = '') {
        self::init();
        
        try {
            $stmt = self::$connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            self::$last_error = 'Query error: ' . $e->getMessage();
            error_log(self::$last_error . ' | SQL: ' . $sql);
            return null;
        }
    }

    /**
     * Query count
     * @param string $sql SQL query with ? placeholders
     * @param array $params Parameter values
     * @param string $types Type string
     * @return int Count result
     */
    public static function queryCount($sql, $params = [], $types = '') {
        self::init();
        
        try {
            $stmt = self::$connection->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            self::$last_error = 'Query error: ' . $e->getMessage();
            error_log(self::$last_error . ' | SQL: ' . $sql);
            return 0;
        }
    }

    /**
     * Get the last inserted row ID
     * @return int|string Last insert ID
     */
    public static function getLastInsertId() {
        self::init();
        return self::$connection->lastInsertId();
    }

    /**
     * Get last error
     * @return string
     */
    public static function getLastError() {
        return self::$last_error;
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction() {
        self::init();
        self::$connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit() {
        self::init();
        self::$connection->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback() {
        self::init();
        self::$connection->rollBack();
    }
}
?>