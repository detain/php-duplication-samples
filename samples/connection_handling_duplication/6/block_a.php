<?php
declare(strict_types=1);

namespace App\Database\Connection\Config;

use RuntimeException;

final class ConfigBasedConnection
{
    public static function fromEnvironment(): self
    {
        $config = [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => (int)(getenv('DB_PORT') ?: 3306),
            'database' => getenv('DB_DATABASE') ?: '',
            'username' => getenv('DB_USERNAME') ?: 'root',
            'password' => getenv('DB_PASSWORD') ?: '',
            'charset' => 'utf8mb4',
        ];

        return new self($config);
    }

    public static function fromFile(string $path): self
    {
        if (!file_exists($path)) {
            throw new RuntimeException("Config file not found: {$path}");
        }

        $data = json_decode(file_get_contents($path), true);
        return new self($data);
    }
}
