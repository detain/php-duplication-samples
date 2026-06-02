<?php
declare(strict_types=1);

namespace App\Database\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use RuntimeException;

final class FullOrmConnection
{
    private static ?EntityManager $entityManager = null;

    public static function create(array $params): EntityManager
    {
        $config = new Configuration();

        $config->setProxyDir('/tmp/proxies');
        $config->setProxyNamespace('App\Proxies');
        $config->setAutoGenerateProxyClasses(true);

        $mappingDriver = new SimplifiedXmlDriver([
            __DIR__ . '/../Mappings' => 'App\Entity',
        ]);
        $config->setMetadataDriverImpl($mappingDriver);

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_mysql',
            'host' => $params['host'] ?? 'localhost',
            'user' => $params['user'] ?? 'root',
            'password' => $params['password'] ?? '',
            'database' => $params['database'] ?? '',
            'charset' => 'utf8mb4',
        ], $config);

        self::$entityManager = EntityManager::create($connection, $config);

        return self::$entityManager;
    }

    public static function getEntityManager(): EntityManager
    {
        if (self::$entityManager === null) {
            throw new RuntimeException('FullOrmConnection not initialized');
        }
        return self::$entityManager;
    }

    public static function close(): void
    {
        if (self::$entityManager !== null) {
            self::$entityManager->close();
            self::$entityManager = null;
        }
    }
}
