<?php
declare(strict_types=1);

namespace App\Database\Standard;

use PDO;
use PDOException;
use RuntimeException;

final class RetryConnection
{
    private static ?PDO $connection = null;
    private static ?array $config = null;
    private static int $maxRetries = 3;
    private static int $retryDelayMs = 100;

    public static function configure(array $config, int $maxRetries = 3): void
    {
        self::$config = $config;
        self::$maxRetries = $maxRetries;
    }

    public static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        if (self::$config === null) {
            throw new RuntimeException('RetryConnection not configured');
        }

        $attempts = 0;
        $lastException = null;

        while ($attempts < self::$maxRetries) {
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    self::$config['host'] ?? 'localhost',
                    self::$config['port'] ?? 3306,
                    self::$config['database'] ?? '',
                    'utf8mb4'
                );

                self::$connection = new PDO(
                    $dsn,
                    self::$config['username'] ?? 'root',
                    self::$config['password'] ?? '',
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );

                return self::$connection;
            } catch (PDOException $e) {
                $lastException = $e;
                $attempts++;

                if ($attempts < self::$maxRetries) {
                    usleep(self::$retryDelayMs * 1000 * $attempts);
                }
            }
        }

        throw new RuntimeException(
            'Failed to connect after ' . self::$maxRetries . ' attempts: ' . $lastException?->getMessage(),
            0,
            $lastException
        );
    }

    public static function reset(): void
    {
        self::$connection = null;
    }
}
