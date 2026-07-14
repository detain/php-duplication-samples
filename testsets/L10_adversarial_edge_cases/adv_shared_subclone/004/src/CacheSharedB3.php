<?php

declare(strict_types=1);

namespace Acme\Cache\Shared;

final class CacheSharedB3
{
    public function __construct(private readonly string $region = 'default')
    {
    }

    public function region(): string
    {
        return $this->region;
    }

    public function fingerprint(array $payload): string
    {
        ksort($payload);
        return substr(hash('crc32b', json_encode($payload) ?: ''), 0, 8);
    }

    public function get(string $key): mixed
    {
        $item = $this->store[$key] ?? null;
        if ($item === null) {
            return null;
        }
        if ($item['expires_at'] !== null && $item['expires_at'] < time()) {
            unset($this->store[$key]);
            return null;
        }
        return $item['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $expiresAt = $ttl !== null ? time() + $ttl : null;
        $this->store[$key] = [
            'value' => $value,
            'expires_at' => $expiresAt,
            'created_at' => time(),
        ];
    }

    public function invalidate(string $key): bool
    {
        if (isset($this->store[$key])) {
            unset($this->store[$key]);
            return true;
        }
        return false;
    }

    public function clear(): void
    {
        $this->store = [];
    }

    public function prune(): int
    {
        $now = time();
        $pruned = 0;
        foreach ($this->store as $key => $item) {
            if ($item['expires_at'] !== null && $item['expires_at'] < $now) {
                unset($this->store[$key]);
                $pruned++;
            }
        }
        return $pruned;
    }

    private array $store = [];
}
