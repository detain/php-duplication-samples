<?php
declare(strict_types=1);

namespace App\Database\Custom;

use PDO;
use RuntimeException;

final class LazyConnection
{
    private static ?PDO $connection = null;
    private static ?array $config = null;

    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    public static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        if (self::$config === null) {
            throw new RuntimeException('LazyConnection not configured');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            self::$config['host'] ?? 'localhost',
            self::$config['port'] ?? 3306,
            self::$config['database'] ?? '',
            self::$config['charset'] ?? 'utf8mb4'
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        self::$connection = new PDO(
            $dsn,
            self::$config['username'] ?? 'root',
            self::$config['password'] ?? '',
            $options
        );

        return self::$connection;
    }

    public static function reset(): void
    {
        self::$connection = null;
        self::$config = null;
    }
}
