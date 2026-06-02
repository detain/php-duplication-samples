<?php
declare(strict_types=1);

namespace App\Database\Connection\Sharding;

use PDO;
use RuntimeException;

final class ShardConnection
{
    private array $shards = [];
    private int $shardCount;

    public function __construct(array $shardConfigs)
    {
        $this->shards = $shardConfigs;
        $this->shardCount = count($shards);
    }

    public function getShard(string $key): PDO
    {
        $shardIndex = abs(crc32($key) % $this->shardCount);
        $config = $this->shards[$shardIndex];

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'] ?? 3306,
            $config['database']
        );

        return new PDO(
            $dsn,
            $config['username'] ?? 'root',
            $config['password'] ?? ''
        );
    }

    public function getAllShards(): array
    {
        $connections = [];
        foreach ($this->shards as $config) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $config['host'],
                $config['port'] ?? 3306,
                $config['database']
            );
            $connections[] = new PDO(
                $dsn,
                $config['username'] ?? 'root',
                $config['password'] ?? ''
            );
        }
        return $connections;
    }
}
