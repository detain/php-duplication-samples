<?php

declare(strict_types=1);

namespace Acme\Billing\Config;

final class BillingClean
{
    /** @var array<string,float> */
    private array $rates = [
        'US-CA' => 0.0725,
        'US-NY' => 0.04,
        'US-TX' => 0.0625,
        'DE' => 0.19,
        'GB' => 0.20,
        'FR' => 0.20,
        'JP' => 0.10,
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
        if ($region === '' || $rate < 0) {
            return;
        }
        $this->rates[$region] = $rate;
    }

    public function isSupported(string $region): bool
    {
        return isset($this->rates[$region]);
    }
}
