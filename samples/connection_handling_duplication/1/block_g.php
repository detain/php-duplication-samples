<?php
declare(strict_types=1);

namespace App\Database\Symfony;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;

final class SymfonyDbalConnection
{
    private static ?Connection $instance = null;

    public static function getInstance(array $params): Connection
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $params = array_merge([
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
        ], $params);

        $config = new Configuration();
        $config->setMiddlewares([]);

        self::$instance = DriverManager::getConnection($params, $config);

        return self::$instance;
    }

    public static function createWithEventListeners(array $params): Connection
    {
        $config = new Configuration();

        $connection = DriverManager::getConnection($params, $config);

        $connection->getEventManager()->addEventListener(
            [Events::postConnect],
            new class {
                public function postConnect(ConnectionEventArgs $args): void
                {
                    $args->getConnection()->executeStatement("SET NAMES 'utf8mb4'");
                }
            }
        );

        self::$instance = $connection;

        return $connection;
    }

    public static function createWithTransactionIsolation(array $params, int $isolationLevel): Connection
    {
        $connection = self::getInstance($params);

        $connection->setTransactionIsolation($isolationLevel);

        return $connection;
    }

    public static function close(): void
    {
        if (self::$instance !== null) {
            self::$instance->close();
            self::$instance = null;
        }
    }
}
