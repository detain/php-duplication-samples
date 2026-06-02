<?php
declare(strict_types=1);

namespace App\Database\Legacy;

use mysqli;
use RuntimeException;

final class MysqliOopConnection
{
    private static ?mysqli $instance = null;
    private static ?string $lastHost = null;
    private static ?string $lastDatabase = null;

    public static function getInstance(
        string $host = 'localhost',
        string $username = 'root',
        string $password = '',
        string $database = '',
        int $port = 3306
    ): mysqli {
        if (self::$instance !== null && self::$instance instanceof mysqli) {
            if (self::$instance->ping() && self::$lastHost === $host && self::$lastDatabase === $database) {
                return self::$instance;
            }

            self::$instance->close();
        }

        self::$lastHost = $host;
        self::$lastDatabase = $database;

        $connection = new mysqli($host, $username, $password, $database, $port);

        if ($connection->connect_error) {
            throw new RuntimeException(
                'MySQL connection failed: ' . $connection->connect_error .
                ' (Error code: ' . $connection->connect_errno . ')'
            );
        }

        if (!$connection->set_charset('utf8mb4')) {
            throw new RuntimeException('Failed to set charset: ' . $connection->error);
        }

        $connection->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);
        $connection->options(MYSQLI_READ_DEFAULT_FILE, '/etc/mysql/my.cnf');

        self::$instance = $connection;

        return $connection;
    }

    public static function createWithOptions(array $options): mysqli
    {
        $host = $options['host'] ?? 'localhost';
        $username = $options['username'] ?? 'root';
        $password = $options['password'] ?? '';
        $database = $options['database'] ?? '';
        $port = $options['port'] ?? 3306;

        $connection = new mysqli();

        if (isset($options['timeout'])) {
            $connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, (int)$options['timeout']);
        }

        if (isset($options['read_timeout'])) {
            $connection->options(MYSQLI_READ_TIMEOUT, (int)$options['read_timeout']);
        }

        if (isset($options['write_timeout'])) {
            $connection->options(MYSQLI_WRITE_TIMEOUT, (int)$options['write_timeout']);
        }

        $connection->real_connect($host, $username, $password, $database, $port);

        if ($connection->connect_error) {
            throw new RuntimeException(
                'MySQL connection failed: ' . $connection->connect_error
            );
        }

        $connection->set_charset('utf8mb4');

        self::$instance = $connection;

        return $connection;
    }

    public static function close(): void
    {
        if (self::$instance !== null && self::$instance instanceof mysqli) {
            self::$instance->close();
            self::$instance = null;
        }
    }
}
