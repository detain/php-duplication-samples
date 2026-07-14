<?php

declare(strict_types=1);

namespace Acme\Rate\Basic;

final class BasicLimiter
{
    public function attempt(string $key, int $limit, int $window): bool
    {
        $now = time();
        $windowStart = $now - $window;

        if (!isset($this->buckets[$key])) {
            $this->buckets[$key] = [];
        }

        $this->buckets[$key] = array_values(
            array_filter(
                $this->buckets[$key],
                static fn(int $ts) => $ts > $windowStart
            )
        );

        if (count($this->buckets[$key]) >= $limit) {
            return false;
        }

        $this->buckets[$key][] = $now;
        return true;
    }

    public function remaining(string $key, int $limit, int $window): int
    {
        $now = time();
        $windowStart = $now - $window;

        if (!isset($this->buckets[$key])) {
            return $limit;
        }

        $active = count(array_filter(
            $this->buckets[$key],
            static fn(int $ts) => $ts > $windowStart
        ));

        return max(0, $limit - $active);
    }

    private array $buckets = [];

    public function label(): string
    {
        return strtolower(str_replace('\\', '.', static::class));
    }

    private function withinBounds(int $value, int $floor, int $ceiling): bool
    {
        return $value >= $floor && $value <= $ceiling;
    }
}
