<?php

declare(strict_types=1);

namespace Acme\Commerce\SubB;

use RuntimeException;

final class SubtotalCollectorB
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    public function calculateTotal(array $items, float $taxRate, string $discountCode): array
    {
        $subtotal = 0.0;
        $itemCount = 0;
        foreach ($items as $item) {
            $qty = (int) ($item['quantity'] ?? 1);
            $price = (float) ($item['price'] ?? 0.0);
            $linePrice = $qty * $price;
            if ($qty >= 10) {
                $linePrice *= 0.90;
            } elseif ($qty >= 5) {
                $linePrice *= 0.95;
            }
            $subtotal += $linePrice;
            $itemCount += $qty;
        }
        $discountRate = match ($discountCode) {
            'BULK10' => 0.10,
            'SEASONAL' => 0.15,
            'VIP' => 0.20,
            default => 0.0,
        };
        $discount = round($subtotal * $discountRate, 2);
        $taxable = $subtotal - $discount;
        $tax = round($taxable * $taxRate, 2);
        $shipping = $itemCount > 5 ? 0.0 : 5.95;
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

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
