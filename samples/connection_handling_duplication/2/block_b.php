<?php
declare(strict_types=1);

namespace App\Database\Eloquent;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

final class EloquentOrmConnection
{
    private static ?DB $instance = null;
    private static array $models = [];

    public static function boot(array $config): DB
    {
        $instance = new DB();

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
        ];

        $instance->addConnection(array_merge($defaultConfig, $config));
        $instance->setAsGlobal();
        $instance->setEventDispatcher(new Dispatcher(new Container()));
        $instance->bootEloquent();

        self::$instance = $instance;

        return $instance;
    }

    public static function registerModel(string $class): void
    {
        self::$models[] = $class;
    }

    public static function getModels(): array
    {
        return self::$models;
    }
}
