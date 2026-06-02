<?php
declare(strict_types=1);

namespace App\Database\Laravel;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\EloquentServiceProvider;
use Illuminate\Support\ServiceProvider;

final class LaravelDbConnection
{
    private static ?DatabaseManager $dbManager = null;

    public static function configure(array $config): void
    {
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
        ];

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql', array_merge($defaultConfig, $config));
    }

    public static function getDbManager(): DatabaseManager
    {
        if (self::$dbManager === null) {
            self::$dbManager = app('db');
        }
        return self::$dbManager;
    }

    public static function table(string $table): \Illuminate\Database\Query\Builder
    {
        return DB::table($table);
    }
}
