<?php

declare(strict_types=1);

namespace Acme\Query\Clean;

final class QueryCleanY
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_ENV[$key]) || isset($_SERVER[$key]);
    }
}
