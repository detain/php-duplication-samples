<?php
declare(strict_types=1);

namespace App\Database\Symfony;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\Bundle\DoctrineBundle\Registry;

final class SymfonyDbalConnectionFactory
{
    private static ?Connection $connection = null;

    public static function create(array $params): Connection
    {
        $params = array_merge([
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'port' => 3306,
            'user' => 'root',
            'password' => '',
            'database' => '',
            'charset' => 'utf8mb4',
            'server_version' => '8.0',
        ], $params);

        $connection = DriverManager::getConnection($params);

        self::$connection = $connection;

        return $connection;
    }

    public static function createWithSchema(string $dbName): Connection
    {
        $connection = self::create(['database' => $dbName]);
        $connection->executeStatement("USE {$dbName}");
        return $connection;
    }
}
