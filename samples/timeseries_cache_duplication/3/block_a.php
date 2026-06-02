<?php
declare(strict_types=1);

namespace App\Database\Timeseries;

/**
 * Timeseries repository using Redis.
 * Demonstrates "Cache operations" in Redis style.
 */
final class RedisCacheRepository
{
    private \Redis $redis;
    private string $prefix;

    public function __construct(\Redis $redis, string $prefix = 'cache')
    {
        $this->redis = $redis;
        $this->prefix = $prefix;
    }

    /**
     * Set cache value.
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $key = $this->prefix . ':' . $key;

        $serialized = serialize($value);

        return $this->redis->setex($key, $ttl, $serialized);
    }

    /**
     * Get cache value.
     *
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key): mixed
    {
        $key = $this->prefix . ':' . $key;

        $value = $this->redis->get($key);

        if ($value === false) {
            return null;
        }

        return unserialize($value);
    }

    /**
     * Delete cache value.
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        $key = $this->prefix . ':' . $key;

        return $this->redis->del($key) > 0;
    }

    /**
     * Get TTL.
     *
     * @param string $key
     * @return int
     */
    public function ttl(string $key): int
    {
        $key = $this->prefix . ':' . $key;

        return $this->redis->ttl($key);
    }
}
