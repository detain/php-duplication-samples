<?php
declare(strict_types=1);

namespace App\Database\Timeseries;

/**
 * Timeseries repository using Memcached.
 * Demonstrates "Cache operations" in Memcached style.
 */
final class MemcachedCacheRepository
{
    private \Memcached $memcached;
    private string $prefix;

    public function __construct(\Memcached $memcached, string $prefix = 'cache')
    {
        $this->memcached = $memcached;
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

        return $this->memcached->set($key, $value, $ttl);
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

        $value = $this->memcached->get($key);

        if ($this->memcached->getResultCode() === \Memcached::RES_NOTFOUND) {
            return null;
        }

        return $value;
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

        return $this->memcached->delete($key);
    }
}
