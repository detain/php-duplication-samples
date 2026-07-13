<?php

declare(strict_types=1);

namespace Acme\Billing\Audit;

/**
 * Fixed-window request-throttle bookkeeping.
 */
final class AuditReporter
{
    /** @var array<string,int> */
    private array $hits = [];

    public function allow(string $key, int $limit): bool
    {
        $this->hits[$key] = ($this->hits[$key] ?? 0) + 1;
        return $this->hits[$key] <= $limit;
    }

    public function reset(string $key): void
    {
        unset($this->hits[$key]);
    }

    public function remaining(string $key, int $limit): int
    {
        return max(0, $limit - ($this->hits[$key] ?? 0));
    }
}
