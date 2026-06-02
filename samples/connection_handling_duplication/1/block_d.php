<?php
declare(strict_types=1);

namespace App\Database\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Logging\Middleware;
use Psr\Log\LoggerInterface;

final class DoctrineConnection
{
    private static ?Connection $instance = null;

    public static function getInstance(array $params): Connection
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $defaultParams = [
            'driver' => 'pdo_mysql',
            'host' => 'localhost',
            'user' => 'root',
            'password' => '',
            'database' => '',
            'charset' => 'utf8mb4',
            'defaultTableOptions' => [
                'charset' => 'utf8mb4',
                'collate' => 'utf8mb4_unicode_ci',
            ],
        ];

        $params = array_merge($defaultParams, $params);

        try {
            self::$instance = DriverManager::getConnection($params);
        } catch (DbalException $e) {
            throw new \RuntimeException(
                'Doctrine DBAL connection failed: ' . $e->getMessage(),
                0,
                $e
            );
        }

        return self::$instance;
    }

    public static function createWithConfiguration(array $params, ?Configuration $config = null): Connection
    {
        try {
            $connection = DriverManager::getConnection($params, $config);

            $connection->executeStatement("SET NAMES 'utf8mb4'");

            return $connection;
        } catch (DbalException $e) {
            throw new \RuntimeException(
                'Doctrine connection with config failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public static function createWithLogging(array $params, LoggerInterface $logger): Connection
    {
        $config = new Configuration();

        $loggingMiddleware = new Middleware($logger);
        $config->setMiddlewares([$loggingMiddleware]);

        return self::createWithConfiguration($params, $config);
    }

    public static function close(): void
    {
        if (self::$instance !== null) {
            self::$instance->close();
            self::$instance = null;
        }
    }
}
