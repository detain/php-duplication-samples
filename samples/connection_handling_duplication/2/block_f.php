<?php
declare(strict_types=1);

namespace App\Database\Custom;

use PDO;
use PDOException;
use RuntimeException;

final class ReplicaConnection
{
    private static ?PDO $primary = null;
    private static array $replicas = [];
    private static int $replicaIndex = 0;
    private static ?array $config = null;

    public static function configure(array $config): void
    {
        self::$config = $config;
        self::$replicas = $config['replicas'] ?? [];
    }

    public static function getPrimary(): PDO
    {
        if (self::$primary !== null) {
            return self::$primary;
        }

        if (self::$config === null) {
            throw new RuntimeException('ReplicaConnection not configured');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            self::$config['primary']['host'] ?? 'localhost',
            self::$config['primary']['port'] ?? 3306,
            self::$config['primary']['database'] ?? '',
            'utf8mb4'
        );

        self::$primary = new PDO(
            $dsn,
            self::$config['primary']['username'] ?? 'root',
            self::$config['primary']['password'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        return self::$primary;
    }

    public static function getReplica(): PDO
    {
        if (empty(self::$replicas)) {
            return self::getPrimary();
        }

        $replicaConfig = self::$replicas[self::$replicaIndex % count(self::$replicas)];
        self::$replicaIndex++;

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $replicaConfig['host'],
            $replicaConfig['port'] ?? 3306,
            $replicaConfig['database'] ?? '',
            'utf8mb4'
        );

        return new PDO(
            $dsn,
            $replicaConfig['username'] ?? 'root',
            $replicaConfig['password'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public static function closeAll(): void
    {
        self::$primary = null;
        self::$replicas = [];
    }
}
