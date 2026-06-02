<?php
declare(strict_types=1);

namespace App\Database\Laravel;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Connection;

final class LaravelFacadeConnection
{
    public static function setup(array $config): void
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
            'strict' => true,
            'engine' => 'InnoDB',
        ];

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql', array_merge($defaultConfig, $config));
    }

    public static function getConnection(): Connection
    {
        return DB::connection();
    }

    public static function reconnect(): Connection
    {
        return DB::reconnect('mysql');
    }

    public static function enableQueryLog(): void
    {
        DB::connection()->enableQueryLog();
    }

    public static function getQueryLog(): array
    {
        return DB::connection()->getQueryLog();
    }

    public static function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
