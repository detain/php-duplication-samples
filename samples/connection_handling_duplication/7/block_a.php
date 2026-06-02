<?php
declare(strict_types=1);

namespace App\Database\Connection\Pool;

use PDO;
use RuntimeException;

final class ConnectionPool
{
    private array $pool = [];
    private int $size = 0;
    private int $maxSize;
    private array $config;

    public function __construct(array $config, int $maxSize = 5)
    {
        $this->config = $config;
        $this->maxSize = $maxSize;
    }

    public function getConnection(): PDO
    {
        foreach ($this->pool as $key => $pdo) {
            try {
                $pdo->query('SELECT 1');
                return $pdo;
            } catch (\Exception $e) {
                unset($this->pool[$key]);
                $this->size--;
            }
        }

        if ($this->size >= $this->maxSize) {
            usleep(50000);
            return $this->getConnection();
        }

        $pdo = $this->createConnection();
        $this->pool[] = $pdo;
        $this->size++;

        return $pdo;
    }

    private function createConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['host'] ?? 'localhost',
            $this->config['port'] ?? 3306,
            $this->config['database'] ?? '',
            'utf8mb4'
        );

        return new PDO(
            $dsn,
            $this->config['username'] ?? 'root',
            $this->config['password'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function closeAll(): void
    {
        $this->pool = [];
        $this->size = 0;
    }
}
