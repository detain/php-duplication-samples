<?php
declare(strict_types=1);

namespace App\Database\Connection;

use PDO;
use RuntimeException;

final class UnifiedConnectionFactory
{
    public static function create(array $config): PDO
    {
        $driver = strtolower($config['driver'] ?? 'mysql');

        return match ($driver) {
            'mysql' => self::createMysql($config),
            'pgsql' => self::createPgsql($config),
            'sqlite' => self::createSqlite($config),
            default => throw new RuntimeException("Unsupported driver: {$driver}"),
        };
    }

    private static function createMysql(array $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'] ?? 'localhost',
            $config['port'] ?? 3306,
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );

        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    private static function createPgsql(array $config): PDO
    {
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $config['host'] ?? 'localhost',
            $config['port'] ?? 5432,
            $config['database']
        );

        return new PDO($dsn, $config['username'], $config['password']);
    }

    private static function createSqlite(array $config): PDO
    {
        $dsn = sprintf('sqlite:%s', $config['database']);
        return new PDO($dsn);
    }
}
