<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    // <<<NEARMISS>>>
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
            $taxableInBracket = max(0, min($remaining, $ceiling) - $floor);
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
            'net' => round($income + $roundedTax, 2),
        ];
    }
    // <<<END-NEARMISS>>>

    public function formatMoney(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }
}
