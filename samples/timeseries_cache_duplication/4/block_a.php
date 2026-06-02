<?php
declare(strict_types=1);

namespace App\Database\Timeseries;

/**
 * Timeseries repository using Redis.
 * Demonstrates "Rate limiting" in Redis style.
 */
final class RedisRateLimitRepository
{
    private \Redis $redis;
    private string $prefix;

    public function __construct(\Redis $redis, string $prefix = 'ratelimit')
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }

    /**
     * Check if rate limit is exceeded.
     *
     * @param string $key
     * @param int $limit
     * @param int $window
     * @return bool
     */
    public function isRateLimited(string $key, int $limit, int $window): bool
    {
        $key = $this->prefix . ':' . $key;
        $now = time();
        $windowStart = $now - $window;

        $this->redis->zremrangebyscore($key, '-inf', (string) $windowStart);

        $count = $this->redis->zcard($key);

        if ($count >= $limit) {
            return true;
        }

        $this->redis->zadd($key, [$now => $now]);
        $this->redis->expire($key, $window);

        return false;
    }

    /**
     * Get current count.
     *
     * @param string $key
     * @param int $window
     * @return int
     */
    public function getCount(string $key, int $window): int
    {
        $key = $this->prefix . ':' . $key;
        $now = time();
        $windowStart = $now - $window;

        $this->redis->zremrangebyscore($key, '-inf', (string) $windowStart);

        return $this->redis->zcard($key);
    }
}
