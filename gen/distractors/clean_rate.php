<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
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
