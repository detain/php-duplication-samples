<?php
declare(strict_types=1);

namespace App\Database\Connection;

use PDO;
use RuntimeException;

interface DatabaseConnection
{
    public function connect(): PDO;
    public function disconnect(): void;
    public function isConnected(): bool;
}

final class ProductionConnection implements DatabaseConnection
{
    private ?PDO $pdo = null;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = array_merge([
            'host' => 'localhost',
            'port' => 3306,
            'database' => '',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'persistent' => true,
        ], $config);
    }

    public function connect(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['port'],
            $this->config['database'],
            $this->config['charset']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if ($this->config['persistent']) {
            $options[PDO::ATTR_PERSISTENT] = true;
        }

        $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $options);

        return $this->pdo;
    }

    public function disconnect(): void
    {
        $this->pdo = null;
    }

    public function isConnected(): bool
    {
        if ($this->pdo === null) {
            return false;
        }

        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
