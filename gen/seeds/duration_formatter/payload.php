<?php

declare(strict_types=1);

namespace Acme\Seed\DurationFormatter;

/**
 * Seed payload: format seconds into human-readable duration.
 * Supports days, hours, minutes, seconds with configurable precision.
 */
final class DurationFormatterSeed
{
    // <<<PAYLOAD:duration_formatter>>>
    public function formatDuration(int $seconds, int $precision = 2): string
    {
        if ($seconds < 0) {
            return '-' . $this->formatDuration(-$seconds, $precision);
        }
        if ($seconds === 0) {
            return '0s';
        }
        $parts = [];
        $days = (int) ($seconds / 86400);
        $seconds %= 86400;
        $hours = (int) ($seconds / 3600);
        $seconds %= 3600;
        $minutes = (int) ($seconds / 60);
        $secs = $seconds % 60;
        if ($days > 0) { $parts[] = "{$days}d"; }
        if ($hours > 0) { $parts[] = "{$hours}h"; }
        if ($minutes > 0) { $parts[] = "{$minutes}m"; }
        if ($secs > 0 || empty($parts)) { $parts[] = "{$secs}s"; }
        return implode(' ', array_slice($parts, 0, $precision));
    }
    // <<<END-PAYLOAD>>>
}