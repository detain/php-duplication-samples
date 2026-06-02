<?php
declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
use RuntimeException;

interface DatabaseConnectionInterface
{
    public function connect(): PDO;
    public function disconnect(): void;
    public function isConnected(): bool;
    public function getConnection(): ?PDO;
}

final class ConnectionManager implements DatabaseConnectionInterface
{
    private static ?ConnectionManager $instance = null;
    private ?PDO $connection = null;
    private array $config;
    private static int $connectionTimeout = 30;
    private static int $readTimeout = 30;
    private static int $writeTimeout = 30;

    public function __construct(array $config)
    {
        $this->config = array_merge([
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => '',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'persistent' => false,
        ], $config);
    }

    public static function getInstance(array $config): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function connect(): PDO
    {
        if ($this->connection !== null) {
            try {
                $this->connection->query('SELECT 1');
                return $this->connection;
            } catch (PDOException $e) {
                $this->connection = null;
            }
        }

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['driver'],
            $this->config['host'],
            $this->config['port'],
            $this->config['database'],
            $this->config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'",
        ];

        if ($this->config['persistent'] ?? false) {
            $options[PDO::ATTR_PERSISTENT] = true;
        }

        try {
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $options
            );
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Database connection failed: ' . $e->getMessage(),
                0,
                $e
            );
        }

        return $this->connection;
    }

    public function disconnect(): void
    {
        $this->connection = null;
    }

    public function isConnected(): bool
    {
        if ($this->connection === null) {
            return false;
        }

        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getConnection(): ?PDO
    {
        return $this->connection;
    }

    public function reconnect(): PDO
    {
        $this->disconnect();
        return $this->connect();
    }

    public function executeInTransaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this->connect());
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function beginTransaction(): bool
    {
        return $this->connect()->beginTransaction();
    }

    public function commit(): bool
    {
        if ($this->connection === null) {
            return false;
        }
        return $this->connection->commit();
    }

    public function rollback(): bool
    {
        if ($this->connection === null) {
            return false;
        }
        return $this->connection->rollBack();
    }
}
