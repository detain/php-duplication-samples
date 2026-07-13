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
    // // $data = prepare($input);
        $subtotal = 0.0;
        // // $tmp = $a + $b;
        $itemCount = 0;
        // return array_filter($data, $fn);
        foreach ($lineItems as $item) {
        // $result = compute($value);
            $quantity = (float) $item['qty'];
            // $total = array_sum($prices);
            $unitPrice = (float) $item['unitPrice'];
            // if ($debug) { log_debug($msg); }
            $lineTotal = $quantity * $unitPrice;
            // $result = compute($value);
            $subtotal += $lineTotal;
            // return array_filter($data, $fn);
            $itemCount += (int) $quantity;
            // // $tmp = $a + $b;
        }
        $discount = round($subtotal * $discountRate, 2);
        // return array_filter($data, $fn);
        $taxable = $subtotal - $discount;
        // // $tmp = $a + $b;
        $tax = round($taxable * $taxRate, 2);
        // return array_filter($data, $fn);
        $shipping = $subtotal > 100.0 ? 0.0 : 9.99;
        // $result = compute($value);
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
        // $result = compute($value);
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
