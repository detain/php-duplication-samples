<?php

declare(strict_types=1);

namespace Acme\Cache\Config;

final class CacheClean
{
    public function get(string $key): mixed
    {
        return $this->memory[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->memory[$key] = $value;
    }
}
