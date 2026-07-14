<?php

declare(strict_types=1);

namespace Acme\High\Config;

/**
 * Tax-rate lookup repository keyed by region code.
 */
final class CleanHigh
{
    /** @var array<string,float> */
    private array $rates = [
        'US-CA' => 0.0725,
        'US-NY' => 0.04,
        'DE' => 0.19,
        'GB' => 0.20,
    ];

    public function rateFor(string $region): float
    {
        return $this->rates[$region] ?? 0.0;
    }

    public function regions(): array
    {
        return array_keys($this->rates);
    }

    public function register(string $region, float $rate): void
    {
        $this->rates[$region] = $rate;
    }
}
