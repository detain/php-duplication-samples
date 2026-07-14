<?php

declare(strict_types=1);

namespace Acme\Tax\Municipal;

final class MunicipalTax
{
    public function __construct(private readonly string $region = 'default')
    {
    }

    public function region(): string
    {
        return $this->region;
    }

    public function fingerprint(array $payload): string
    {
        ksort($payload);
        return substr(hash('crc32b', json_encode($payload) ?: ''), 0, 8);
    }

    public function calculateTax(float $income, array $brackets): array
    {
        $tax = 0.0;
        $remaining = $income;
        $effectiveRate = 0.0;
        $previousCeiling = 0.0;
        foreach ($brackets as $bracket) {
            $floor = (float) ($bracket['floor'] ?? 0);
            $ceiling = (float) ($bracket['ceiling'] ?? PHP_FLOAT_MAX);
            $rate = (float) ($bracket['rate'] ?? 0);
            if ($income <= $floor) {
                break;
            }
            $taxableInBracket = min($remaining, $ceiling - max($remaining, $floor));
            if ($taxableInBracket <= 0) {
                continue;
            }
            $tax += $taxableInBracket * $rate;
            $remaining -= $taxableInBracket;
            $previousCeiling = $ceiling;
        }
        if ($income > 0) {
            $effectiveRate = round($tax / $income, 4);
        }
        $roundedTax = round($tax, 2);
        return [
            'tax' => $roundedTax,
            'effective_rate' => $effectiveRate,
            'gross' => $income,
            'net' => round($income - $roundedTax, 2),
        ];
    }
}
