<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/secrets.php';

/**
 * PDO database wrapper.
 *
 * All queries must use this class — never use raw PDO or mysqli elsewhere.
 * Always use named placeholders (:name) in SQL — never concatenate user data.
 *
 * Usage (two styles both supported):
 *
 * Style A — fluent prepare/bind/execute:
 *   $db->query('SELECT * FROM items WHERE public_code = :code');
 *   $db->bind(':code', 'a1b2c3d4e5');
 *   $row = $db->queryOne();
 *
 * Style B — inline SQL + params array (convenience shorthand):
 *   $row  = $db->queryOne('SELECT * FROM items WHERE id = ?', [42]);
 *   $rows = $db->queryAll('SELECT id, name FROM brands ORDER BY name ASC');
 *   $db->execute('DELETE FROM items WHERE id = ?', [42]);
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

    /**
     * Prepare a SQL statement for subsequent bind()/execute()/queryOne()/queryAll() calls.
     *
     * @param string $sql SQL with named (:name) or positional (?) placeholders.
     */
    public function query(string $sql): void
    {
        if ($this->pdo === null) {
            throw new \RuntimeException('Database connection failed: ' . $this->error);
        }
        $this->stmt = $this->pdo->prepare($sql);
    }

    /**
     * Bind a single value to a named placeholder on the already-prepared statement.
     *
     * @param string   $parameter Placeholder name including colon, e.g. ':id'.
     * @param mixed    $value     Value to bind.
     * @param int|null $type      PDO::PARAM_* constant; auto-detected when null.
     */
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

    /**
     * Prepare (if SQL provided) then execute the statement.
     * Returns the number of affected rows.
     *
     * @param string|null $sql    Optional SQL to prepare before executing.
     * @param array       $params Optional positional or named parameter values.
     */
    public function execute(?string $sql = null, array $params = []): int
    {
        if ($sql !== null) {
            $this->query($sql);
        }
        $this->stmt->execute($params ?: null);
        return $this->stmt->rowCount();
    }

    /**
     * Prepare (if SQL provided), execute, and return a single row or null.
     *
     * @param string|null $sql    Optional SQL to prepare before executing.
     * @param array       $params Optional positional or named parameter values.
     */
    public function queryOne(?string $sql = null, array $params = []): ?array
    {
        if ($sql !== null) {
            $this->query($sql);
        }
        $this->stmt->execute($params ?: null);
        $row = $this->stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Prepare (if SQL provided), execute, and return all rows as an array.
     *
     * @param string|null $sql    Optional SQL to prepare before executing.
     * @param array       $params Optional positional or named parameter values.
     */
    public function queryAll(?string $sql = null, array $params = []): array
    {
        if ($sql !== null) {
            $this->query($sql);
        }
        $this->stmt->execute($params ?: null);
        return $this->stmt->fetchAll();
    }

    /**
     * Return the row count from the last executed statement.
     */
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