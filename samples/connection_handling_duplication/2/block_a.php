<?php
declare(strict_types=1);

namespace App\Database\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Logging\Middleware;
use Psr\Log\LoggerInterface;

final class EntityManagerConnection
{
    private static ?Connection $connection = null;
    private static ?object $entityManager = null;

    public static function getEntityManager(array $params): object
    {
        if (self::$entityManager !== null) {
            return self::$entityManager;
        }

        $config = new \Doctrine\ORM\Configuration();
        $config->setProxyDir('/tmp/doctrine_proxies');
        $config->setProxyNamespace('Proxies');
        $config->setAutoGenerateProxyClasses(true);

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'host' => $params['host'] ?? 'localhost',
            'user' => $params['user'] ?? 'root',
            'password' => $params['password'] ?? '',
            'database' => $params['database'] ?? '',
            'charset' => 'utf8mb4',
        ]);

        self::$connection = $connection;

        return self::$entityManager;
    }

    public static function getConnection(): Connection
    {
        if (self::$connection === null) {
            throw new \RuntimeException('EntityManager not initialized');
        }
        return self::$connection;
    }
}
