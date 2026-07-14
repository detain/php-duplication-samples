<?php

declare(strict_types=1);

namespace Acme\Seed\StopwatchMetrics;

/**
 * Seed payload: stopwatch for measuring elapsed time and computing rates.
 */
final class StopwatchMetricsSeed
{
    // <<<PAYLOAD:stopwatch_metrics>>>
    public function trackElapsed(int $startMicrotime, int $endMicrotime): array
    {
        $elapsedUs = $endMicrotime - $startMicrotime;
        $elapsedSec = $elapsedUs / 1000000;
        return [
            'microseconds' => $elapsedUs,
            'seconds' => round($elapsedSec, 4),
            'rate' => $elapsedSec > 0 ? round(1000 / $elapsedSec, 2) : 0,
        ];
    }
    // <<<END-PAYLOAD>>>
}
