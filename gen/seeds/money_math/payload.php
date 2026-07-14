<?php

declare(strict_types=1);

namespace Acme\Seed\MoneyMath;

/**
 * Seed payload: money calculations using integer cents to avoid floating point
 * errors. Handles tax computation and currency rounding correctly.
 */
final class MoneyMathSeed
{
    // <<<PAYLOAD:money_math>>>
    public function computeMoney(float $amount, float $taxRate, int $quantity): array
    {
        $unitCents = (int) round($amount * 100);
        $subtotalCents = $unitCents * $quantity;
        $taxCents = (int) round($subtotalCents * $taxRate);
        $totalCents = $subtotalCents + $taxCents;
        return [
            'unit_price' => $unitCents / 100,
            'subtotal' => $subtotalCents / 100,
            'tax' => $taxCents / 100,
            'total' => $totalCents / 100,
            'quantity' => $quantity,
        ];
    }
    // <<<END-PAYLOAD>>>
}