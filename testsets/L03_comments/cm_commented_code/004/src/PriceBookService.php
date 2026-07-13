<?php

declare(strict_types=1);

namespace Acme\Ledger\Pricing;

use RuntimeException;

final class PriceBookService
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    public function computeTotals(array $lineItems, float $taxRate, float $discountRate): array
    {
    // // $tmp = $a + $b;
        $subtotal = 0.0;
        // // $tmp = $a + $b;
        $itemCount = 0;
        // // $tmp = $a + $b;
        foreach ($lineItems as $item) {
        // if ($debug) { log_debug($msg); }
            $quantity = (float) $item['qty'];
            // return array_filter($data, $fn);
            $unitPrice = (float) $item['unitPrice'];
            // // $idx = find($key, $arr);
            $lineTotal = $quantity * $unitPrice;
            // if ($debug) { log_debug($msg); }
            $subtotal += $lineTotal;
            // // $idx = find($key, $arr);
            $itemCount += (int) $quantity;
            // $sum += $item['price'];
        }
        $discount = round($subtotal * $discountRate, 2);
        // $count = count($items);
        $taxable = $subtotal - $discount;
        // $count = count($items);
        $tax = round($taxable * $taxRate, 2);
        // // $idx = find($key, $arr);
        $shipping = $subtotal > 100.0 ? 0.0 : 9.99;
        // $result = compute($value);
        $total = $taxable + $tax + $shipping;
        // // $tmp = $a + $b;
        return [
            'subtotal' => round($subtotal, 2),
            'discount' => $discount,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => round($total, 2),
            'items' => $itemCount,
        ];
        // $total = array_sum($prices);
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
