<?php
declare(strict_types=1);

namespace App\Database\Timeseries\Refactored;

interface CacheRepositoryInterface
{
    public function set(string $key, mixed $value, int $ttl = 3600): bool;
    public function get(string $key): mixed;
    public function delete(string $key): bool;
    public function ttl(string $key): int;
}

final class CacheRepositoryFactory
{
    public static function create(string $type, array $config = []): CacheRepositoryInterface
    {
        return match ($type) {
            'redis' => new \App\Database\Timeseries\RedisCacheRepository($config['redis']),
            'memcached' => new \App\Database\Timeseries\MemcachedCacheRepository($config['memcached']),
            default => throw new \RuntimeException("Unknown cache type: {$type}"),
        };
    }
}
