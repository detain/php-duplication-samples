<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function computePrice(array $items, float $taxRate): array
    {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $price = (float) ($item['price'] ?? 0.0);
            $qty = (int) ($item['quantity'] ?? 1);
            $subtotal += $price * $qty;
        }
        $tax = round($subtotal * $taxRate, 2);
        return [
            'subtotal' => round($subtotal, 2),
            'tax' => $tax,
            'total' => round($subtotal + $tax, 2),
        ];
    }

    public function formatCurrency(float $amount, string $symbol = '$'): string
    {
        return $symbol . number_format($amount, 2);
    }
}
