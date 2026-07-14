<?php

declare(strict_types=1);

namespace Acme\Core\LimitB;

use RuntimeException;

final class LimitCheckerB
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

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

        return max(
            0,
            $limit - $active
        );
    }

    private [$buckets = [];]

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
