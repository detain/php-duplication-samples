<?php

declare(strict_types=1);

namespace Acme\Seed\RetryBackoff;

/**
 * Seed payload: retry with exponential backoff.
 * Computes delay for each retry attempt with jitter support.
 */
final class RetryBackoffSeed
{
    // <<<PAYLOAD:retry_backoff>>>
    public function retryWithBackoff(int $attempt, float $baseDelay, float $maxDelay, float $jitterFactor): float
    {
        if ($attempt <= 0) {
            return 0.0;
        }
        $exponentialDelay = $baseDelay * pow(2.0, $attempt - 1);
        $cappedDelay = min($exponentialDelay, $maxDelay);
        if ($jitterFactor > 0) {
            $jitter = $cappedDelay * $jitterFactor * (mt_rand() / mt_getrandmax() - 0.5);
            return max(0, $cappedDelay + $jitter);
        }
        return $cappedDelay;
    }
    // <<<END-PAYLOAD>>>
}