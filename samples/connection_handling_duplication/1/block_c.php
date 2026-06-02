<?php
declare(strict_types=1);

namespace App\Database\Standard;

use PDO;
use PDOException;
use RuntimeException;

final class PdoConnection
{
    private static ?PDO $instance = null;
    private static ?string $dsn = null;

    public static function getInstance(string $dsn, string $username = '', string $password = ''): PDO
    {
        if (self::$instance !== null && self::$dsn === $dsn) {
            return self::$instance;
        }

        self::$dsn = $dsn;

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
            PDO::MYSQL_ATTR_COMPRESS => true,
        ];

        try {
            self::$instance = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'PDO connection failed: ' . $e->getMessage(),
                0,
                $e
            );
        }

        return self::$instance;
    }

    public static function createFromConfig(array $config): PDO
    {
        $driver = $config['driver'] ?? 'mysql';
        $host = $config['host'] ?? 'localhost';
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';
        $port = $config['port'] ?? 3306;

        $dsn = "{$driver}:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4'",
        ];

        if (!empty($config['persistent'])) {
            $options[PDO::ATTR_PERSISTENT] = true;
        }

        if (!empty($config['timeout'])) {
            $options[PDO::ATTR_TIMEOUT] = (int)$config['timeout'];
        }

        try {
            return new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'PDO connection from config failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public static function close(): void
    {
        self::$instance = null;
        self::$dsn = null;
    }
}
