<?php

declare(strict_types=1);

namespace Acme\Billing\Compound;

final class CmRnCompound
{
    public function computeTotals(array $line_items, float $tax_rate, float $discount_rate): array
    {
        $subtotal = 0.0;
        $item_count = 0;
        foreach ($line_items as $item) {
            $quantity = (float) $item['qty'];
            $unit_price = (float) $item['unitPrice'];
            $line_total = $quantity * $unit_price;
            $subtotal += $line_total;
            $item_count += (int) $quantity;
        }
        $discount = round($subtotal * $discount_rate, 2);
        $taxable = $subtotal - $discount;
        $tax = round($taxable * $tax_rate, 2);
        $shipping = $subtotal > 100.0 ? 0.0 : 9.99;
        $total = $taxable + $tax + $shipping;
        return [
            'subtotal' => round($subtotal, 2),
            'discount' => $discount,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => round($total, 2),
            'items' => $item_count,
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
