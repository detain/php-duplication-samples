<?php
declare(strict_types=1);

namespace App\Database\Timeseries;

/**
 * Timeseries repository using Redis.
 * Demonstrates "Leaderboard operations" in Redis style.
 */
final class RedisLeaderboardRepository
{
    private \Redis $redis;
    private string $key;

    public function __construct(\Redis $redis, string $key = 'leaderboard')
    {
        $this->redis = $redis;
        $this->key = $key;
    }

    /**
     * Add score to leaderboard.
     *
     * @param string $member
     * @param float $score
     * @return bool
     */
    public function add(string $member, float $score): bool
    {
        return $this->redis->zadd($this->key, [$member => $score]) !== false;
    }

    /**
     * Get rank of member.
     *
     * @param string $member
     * @return int|null
     */
    public function rank(string $member): ?int
    {
        $rank = $this->redis->zrevrank($this->key, $member);

        return $rank !== false ? $rank + 1 : null;
    }

    /**
     * Get top N members.
     *
     * @param int $count
     * @return array
     */
    public function top(int $count = 10): array
    {
        $results = $this->redis->zrevrange($this->key, 0, $count - 1, ['WITHSCORES' => true]);

        $leaderboard = [];

        foreach ($results as $member => $score) {
            $leaderboard[] = [
                'member' => $member,
                'score' => (float) $score,
                'rank' => count($leaderboard) + 1,
            ];
        }

        return $leaderboard;
    }
}
