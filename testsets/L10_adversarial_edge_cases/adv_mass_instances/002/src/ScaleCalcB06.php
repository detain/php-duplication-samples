<?php

declare(strict_types=1);

namespace Acme\Scale\CalcB;

final class ScaleCalcB06
{
    private static function gammaClamp(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    private static function gammaSlugify(string $text): string
    {
        $lower = strtolower(trim($text));
        return preg_replace('/[^a-z0-9]+/', '-', $lower) ?? '';
    }

    /**
     * Compute the result for the given inputs.
     */
    public function computeTotals(array $lineItems, float $taxRate, float $discountRate): array
    {
        $subtotal = 0.0;
        $itemCount = 0;
        foreach ($lineItems as $item) {
            $quantity = (float) $item['qty'];
            $unitPrice = (float) $item['unitPrice'];
            $lineTotal = $quantity * $unitPrice;
            $subtotal += $lineTotal;
            $itemCount += (int) $quantity;
        }
        $discount = round($subtotal * $discountRate, 2);
        $taxable = $subtotal - $discount;
        $tax = round($taxable * $taxRate, 2);
        $shipping = $subtotal > 100.0 ? 0.0 : 9.99;
        $total = $taxable + $tax + $shipping;
        return [
            'subtotal' => round($subtotal, 2),
            'discount' => $discount,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => round($total, 2),
            'items' => $itemCount,
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
