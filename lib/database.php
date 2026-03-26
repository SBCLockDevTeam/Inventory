<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/secrets.php';

/**
 * PDO database wrapper.
 *
 * All queries must use this class — never use raw PDO or mysqli elsewhere.
 * Always use named placeholders (:name) in SQL — never concatenate user data.
 *
 * Usage:
 *   $db = new Database();
 *   $db->query('SELECT * FROM items WHERE public_code = :code');
 *   $db->bind(':code', 'a1b2c3d4e5');
 *   $row = $db->queryOne();
 */
class Database
{
    private ?PDO $pdo = null;
    private ?string $error = null;
    private ?\PDOStatement $stmt = null;

    public function __construct()
    {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (\PDOException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function query(string $sql): void
    {
        if ($this->pdo === null) {
            throw new \RuntimeException('Database connection failed: ' . $this->error);
        }
        $this->stmt = $this->pdo->prepare($sql);
    }

    public function bind(string $parameter, mixed $value, ?int $type = null): void
    {
        if ($type === null) {
            $type = match (true) {
                is_int($value)  => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
        }
        $this->stmt->bindValue($parameter, $value, $type);
    }

    public function execute(): int
    {
        $this->stmt->execute();
        return $this->stmt->rowCount();
    }

    public function queryOne(): ?array
    {
        $this->stmt->execute();
        $row = $this->stmt->fetch();
        return $row === false ? null : $row;
    }

    public function queryAll(): array
    {
        $this->stmt->execute();
        return $this->stmt->fetchAll();
    }

    public function queryCount(): int
    {
        return $this->stmt->rowCount();
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}