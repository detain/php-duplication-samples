<?php
declare(strict_types=1);

namespace App\Database\Timeseries\Refactored;

interface RateLimitRepositoryInterface
{
    public function isRateLimited(string $key, int $limit, int $window): bool;
    public function getCount(string $key, int $window): int;
}

final class RateLimitRepositoryFactory
{
    public static function create(string $type, array $config = []): RateLimitRepositoryInterface
    {
        return match ($type) {
            'redis' => new \App\Database\Timeseries\RedisRateLimitRepository($config['redis']),
            default => throw new \RuntimeException("Unknown rate limit type: {$type}"),
        };
    }
}
