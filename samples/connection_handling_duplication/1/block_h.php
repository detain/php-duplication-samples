<?php
declare(strict_types=1);

namespace App\Database\Custom;

use PDO;
use PDOException;
use RuntimeException;

final class CustomConnectionPool
{
    private static array $pool = [];
    private static array $config = [];
    private static int $minConnections = 2;
    private static int $maxConnections = 10;
    private static int $activeConnections = 0;

    public static function configure(array $config): void
    {
        self::$config = $config;
        self::$minConnections = $config['min_connections'] ?? 2;
        self::$maxConnections = $config['max_connections'] ?? 10;
    }

    public static function getConnection(string $poolName = 'default'): PDO
    {
        if (!isset(self::$pool[$poolName])) {
            self::$pool[$poolName] = [];
        }

        foreach (self::$pool[$poolName] as $key => $connection) {
            if ($connection instanceof PDO) {
                try {
                    $connection->query('SELECT 1');
                    return $connection;
                } catch (PDOException $e) {
                    unset(self::$pool[$poolName][$key]);
                    self::$activeConnections--;
                }
            }
        }

        if (self::$activeConnections >= self::$maxConnections) {
            usleep(100000);
            return self::getConnection($poolName);
        }

        $connection = self::createConnection($poolName);
        self::$pool[$poolName][] = $connection;
        self::$activeConnections++;

        return $connection;
    }

    private static function createConnection(string $poolName): PDO
    {
        $config = self::$config[$poolName] ?? self::$config;

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'] ?? 'localhost',
            $config['port'] ?? 3306,
            $config['database'] ?? '',
            $config['charset'] ?? 'utf8mb4'
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ];

        if (!empty($config['persistent'])) {
            $options[PDO::ATTR_PERSISTENT] = true;
        }

        try {
            return new PDO(
                $dsn,
                $config['username'] ?? 'root',
                $config['password'] ?? '',
                $options
            );
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Connection pool creation failed for {$poolName}: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public static function closePool(string $poolName = 'default'): void
    {
        if (isset(self::$pool[$poolName])) {
            foreach (self::$pool[$poolName] as $connection) {
                if ($connection instanceof PDO) {
                    $connection = null;
                    self::$activeConnections--;
                }
            }
            self::$pool[$poolName] = [];
        }
    }

    public static function closeAll(): void
    {
        foreach (self::$pool as $poolName => $connections) {
            self::closePool($poolName);
        }
    }

    public static function getActiveCount(): int
    {
        return self::$activeConnections;
    }
}
