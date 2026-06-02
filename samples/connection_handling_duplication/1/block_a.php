<?php
declare(strict_types=1);

namespace App\Database\Legacy;

use mysqli;
use RuntimeException;

final class MysqliProceduralConnection
{
    private static ?mysqli $instance = null;

    public static function getInstance(
        string $host = 'localhost',
        string $username = 'root',
        string $password = '',
        string $database = '',
        int $port = 3306
    ): mysqli {
        if (self::$instance !== null && self::$instance instanceof mysqli) {
            if (self::$instance->ping()) {
                return self::$instance;
            }
        }

        $connection = mysqli_connect($host, $username, $password, $database, $port);

        if ($connection === false) {
            throw new RuntimeException(
                'MySQL connection failed: ' . mysqli_connect_error() .
                ' (Error code: ' . mysqli_connect_errno() . ')'
            );
        }

        if (!mysqli_set_charset($connection, 'utf8mb4')) {
            throw new RuntimeException('Failed to set charset: ' . mysqli_error($connection));
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        self::$instance = $connection;

        return $connection;
    }

    public static function createWithTimeout(
        string $host,
        string $username,
        string $password,
        string $database,
        int $timeoutSeconds = 5
    ): mysqli {
        ini_set('mysql.connect_timeout', (string)$timeoutSeconds);

        $connection = @mysqli_connect($host, $username, $password, $database);

        if ($connection === false) {
            throw new RuntimeException(
                'MySQL connection timed out or failed: ' . mysqli_connect_error()
            );
        }

        mysqli_set_charset($connection, 'utf8mb4');
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        return $connection;
    }

    public static function close(): void
    {
        if (self::$instance !== null && self::$instance instanceof mysqli) {
            self::$instance->close();
            self::$instance = null;
        }
    }

    public static function isConnected(): bool
    {
        return self::$instance !== null && self::$instance instanceof mysqli && self::$instance->ping();
    }
}
