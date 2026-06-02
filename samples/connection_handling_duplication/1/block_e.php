<?php
declare(strict_types=1);

namespace App\Database\Eloquent;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Connection as IlluminateConnection;
use Illuminate\Support\Fluent;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

final class EloquentConnection
{
    private static ?DB $instance = null;
    private static ?Container $container = null;

    public static function getInstance(array $config): DB
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::$instance = new DB();

        $defaultConfig = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => '',
            'username' => 'root',
            'password' => '',
            'port' => 3306,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
        ];

        $config = array_merge($defaultConfig, $config);

        self::$instance->addConnection($config);

        self::$instance->setAsGlobal();

        if (self::$container === null) {
            self::$container = new Container();
            self::$instance->setContainer(self::$container);
        }

        self::$instance->setEventDispatcher(new Dispatcher(self::$container));

        self::$instance->bootEloquent();

        return self::$instance;
    }

    public static function getConnection(string $name = 'default'): IlluminateConnection
    {
        $db = self::getInstance([
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'test',
        ]);

        return $db->connection($name);
    }

    public static function configurePool(array $config): void
    {
        $db = self::getInstance($config);

        $db->connection()->setQueryGrammar(new \Illuminate\Database\Query\Grammars\MySqlGrammar());
        $db->connection()->setSchemaGrammar(new \Illuminate\Database\Schema\Grammars\MySqlGrammar());
    }

    public static function close(): void
    {
        if (self::$instance !== null) {
            self::$instance->disconnect();
            self::$instance = null;
        }
    }
}
