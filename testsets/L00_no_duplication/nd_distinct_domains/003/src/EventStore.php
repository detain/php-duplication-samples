<?php

declare(strict_types=1);

namespace Acme\Events\Store;

/**
 * Payroll pay-period calendar helper.
 */
final class EventStore
{
    public function periodsInYear(string $frequency): int
    {
        return match ($frequency) {
            'weekly' => 52,
            'biweekly' => 26,
            'semimonthly' => 24,
            'monthly' => 12,
            default => 0,
        };
    }

    public function nextPayday(\DateTimeImmutable $from, int $intervalDays): \DateTimeImmutable
    {
        return $from->add(new \DateInterval('P' . $intervalDays . 'D'));
    }
}
