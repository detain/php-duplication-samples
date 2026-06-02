<?php
declare(strict_types=1);

namespace App\Database\Timeseries\Refactored;

interface LeaderboardRepositoryInterface
{
    public function add(string $member, float $score): bool;
    public function rank(string $member): ?int;
    public function top(int $count = 10): array;
}

final class LeaderboardRepositoryFactory
{
    public static function create(string $type, array $config = []): LeaderboardRepositoryInterface
    {
        return match ($type) {
            'redis' => new \App\Database\Timeseries\RedisLeaderboardRepository($config['redis']),
            default => throw new \RuntimeException("Unknown leaderboard type: {$type}"),
        };
    }
}
