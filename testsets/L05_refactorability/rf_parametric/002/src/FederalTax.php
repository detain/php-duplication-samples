<?php

declare(strict_types=1);

namespace Acme\Tax\Federal;

final class FederalTax
{
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

    public function label(): string
    {
        return strtolower(str_replace('\\', '.', static::class));
    }

    private function withinBounds(int $value, int $floor, int $ceiling): bool
    {
        return $value >= $floor && $value <= $ceiling;
    }
}
