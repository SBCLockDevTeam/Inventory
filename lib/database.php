<?php
require_once __DIR__ . '/../config/secrets.php';

// Database configuration and connection using PDO
class Database {
    private $host;
    private $db;
    private $user;
    private $pass;
    private $charset = 'utf8mb4';
    private $pdo;
    private $stmt;
    private $error;

    public function __construct() {
        $this->host = DB_HOST;
        $this->db = DB_NAME;
        $this->user = DB_USER;
        $this->pass = DB_PASS;
        
        $dsn = "mysql:host={$this->host};dbname={$this->db};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            $this->pdo = null;
        }
    }

    public function query($sql) {
        if ($this->pdo === null) {
            throw new Exception("Database connection failed: " . $this->error);
        }
        $this->stmt = $this->pdo->prepare($sql);
    }

    public function bind($parameter, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($parameter, $value, $type);
    }

    public function execute() {
        return $this->stmt->execute();
    }

    public function queryOne() {
        $this->execute();
        return $this->stmt->fetch();
    }

    public function queryAll() {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    public function queryCount() {
        return $this->stmt->rowCount();
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollback() {
        return $this->pdo->rollback();
    }
}
?>
