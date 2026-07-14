<?php

declare(strict_types=1);

namespace Acme\Seed\WeekdayScheduler;

/**
 * Seed payload: find next occurrence of a weekday from a given date.
 * Days: 0=Sunday, 1=Monday, ..., 6=Saturday
 */
final class WeekdaySchedulerSeed
{
    // <<<PAYLOAD:weekday_scheduler>>>
    public function nextWeekday(int $targetWeekday, ?int $fromTimestamp = null): int
    {
        $from = $fromTimestamp ?? time();
        $currentDay = (int) date('w', $from);
        $daysUntil = ($targetWeekday - $currentDay + 7) % 7;
        if ($daysUntil === 0) {
            $daysUntil = 7;
        }
        return $from + ($daysUntil * 86400);
    }
    // <<<END-PAYLOAD>>>
}
