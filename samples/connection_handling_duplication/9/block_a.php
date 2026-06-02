<?php
declare(strict_types=1);

namespace App\Database\Connection\Ssl;

use PDO;
use RuntimeException;

final class SslConnection
{
    public static function createWithSsl(array $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'] ?? 3306,
            $config['database']
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_SSL_KEY => $config['ssl_key'] ?? null,
            PDO::MYSQL_ATTR_SSL_CERT => $config['ssl_cert'] ?? null,
            PDO::MYSQL_ATTR_SSL_CA => $config['ssl_ca'] ?? null,
        ];

        return new PDO($dsn, $config['username'], $config['password'], $options);
    }

    public static function createWithoutSsl(array $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'] ?? 3306,
            $config['database']
        );

        return new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
}
