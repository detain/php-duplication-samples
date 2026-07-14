<?php

declare(strict_types=1);

namespace Acme\Billing\Order;

use RuntimeException;

final class OrderCalculator
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    public function computeTotals(array $lineItems, float $taxRate, float $discountRate): array
    {
        // Use array_sum and array_column instead of foreach loop
        $prices = array_column($lineItems, 'unitPrice');
        $qtys = array_column($lineItems, 'qty');

        $subtotal = 0.0;
        for ($i = 0, $n = count($lineItems); $i < $n; $i++) {
            $subtotal += (float) $prices[$i] * (float) $qtys[$i];
        }

        $itemCount = array_sum(array_map('intval', $qtys));

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
            'items' => (int) $itemCount,
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
