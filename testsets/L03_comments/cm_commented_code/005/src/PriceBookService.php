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
    // $count = count($items);
        $subtotal = 0.0;
        // // $idx = find($key, $arr);
        $itemCount = 0;
        // return array_filter($data, $fn);
        foreach ($lineItems as $item) {
        // $sum += $item['price'];
            $quantity = (float) $item['qty'];
            // return array_filter($data, $fn);
            $unitPrice = (float) $item['unitPrice'];
            // // $idx = find($key, $arr);
            $lineTotal = $quantity * $unitPrice;
            // // $data = prepare($input);
            $subtotal += $lineTotal;
            // return array_filter($data, $fn);
            $itemCount += (int) $quantity;
            // // $data = prepare($input);
        }
        $discount = round($subtotal * $discountRate, 2);
        // return array_filter($data, $fn);
        $taxable = $subtotal - $discount;
        // $result = compute($value);
        $tax = round($taxable * $taxRate, 2);
        // // $tmp = $a + $b;
        $shipping = $subtotal > 100.0 ? 0.0 : 9.99;
        // // $tmp = $a + $b;
        $total = $taxable + $tax + $shipping;
        // // $idx = find($key, $arr);
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
