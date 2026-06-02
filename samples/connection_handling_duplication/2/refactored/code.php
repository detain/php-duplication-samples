<?php
declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;

interface ConnectionFactoryInterface
{
    public function create(array $config): PDO;
    public function validate(array $config): bool;
}

final class ConnectionFactory implements ConnectionFactoryInterface
{
    private static array $drivers = [
        'mysql' => MySqlConnection::class,
        'pgsql' => PgSqlConnection::class,
        'sqlite' => SqliteConnection::class,
    ];

    public static function registerDriver(string $driver, string $class): void
    {
        self::$drivers[$driver] = $class;
    }

    public function create(array $config): PDO
    {
        $driver = $config['driver'] ?? 'mysql';

        if (!isset(self::$drivers[$driver])) {
            throw new RuntimeException("Unsupported driver: {$driver}");
        }

        $factoryClass = self::$drivers[$driver];

        return $factoryClass::connect($config);
    }

    public function validate(array $config): bool
    {
        $required = ['driver', 'host', 'database', 'username'];
        foreach ($required as $field) {
            if (empty($config[$field])) {
                return false;
            }
        }
        return true;
    }
}

final class MySqlConnection
{
    public static function connect(array $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'] ?? 3306,
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );

        return new PDO(
            $dsn,
            $config['username'],
            $config['password'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
}

final class PgSqlConnection
{
    public static function connect(array $config): PDO
    {
        $dsn = sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            $config['host'],
            $config['port'] ?? 5432,
            $config['database']
        );

        return new PDO($dsn, $config['username'], $config['password'] ?? '');
    }
}

final class SqliteConnection
{
    public static function connect(array $config): PDO
    {
        $dsn = sprintf('sqlite:%s', $config['database']);
        return new PDO($dsn);
    }
}
