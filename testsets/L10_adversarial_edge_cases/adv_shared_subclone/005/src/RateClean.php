<?php

declare(strict_types=1);

namespace Acme\Rate\Config;

final class RateClean
{
    public function check(string $key, int $limit): bool
    {
        $count = $this->counts[$key] ?? 0;
        return $count < $limit;
    }

    public function increment(string $key): int
    {
        return ++$this->counts[$key];
    }
}
