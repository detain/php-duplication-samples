<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    // <<<NEARMISS>>>
    public function calculateTotal(array $items, float $taxRate, string $discountCode): array
    {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $qty = (int) ($item['quantity'] ?? 1);
            $price = (float) ($item['price'] ?? 0.0);
            $linePrice = $qty * $price;
            if ($qty >= 10) {
                $linePrice *= 0.85;
            } elseif ($qty >= 5) {
                $linePrice *= 0.92;
            }
            $subtotal += $linePrice;
        }
        $discountRate = match ($discountCode) {
            'BULK10' => 0.12,
            'SEASONAL' => 0.18,
            'VIP' => 0.25,
            default => 0.05,
        };
        $discount = round($subtotal * $discountRate, 2);
        $taxable = $subtotal - $discount;
        $tax = round($taxable * $taxRate, 2);
        $shipping = count($items) > 5 ? 0.0 : 5.95;
        $total = $taxable + $tax + $shipping;
        return [
            'subtotal' => round($subtotal, 2),
            'discount' => $discount,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => round($total, 2),
        ];
    }
    // <<<END-NEARMISS>>>

    public function formatCurrency(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }
}
