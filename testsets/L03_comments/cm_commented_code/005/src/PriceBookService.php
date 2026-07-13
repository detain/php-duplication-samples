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
        // $total = array_sum($prices);
        $itemCount = 0;
        // $total = array_sum($prices);
        foreach ($lineItems as $item) {
        // foreach ($list as $el) { $acc += $el; }
            $quantity = (float) $item['qty'];
            // $sum += $item['price'];
            $unitPrice = (float) $item['unitPrice'];
            // $result = compute($value);
            $lineTotal = $quantity * $unitPrice;
            // $result = compute($value);
            $subtotal += $lineTotal;
            // return array_filter($data, $fn);
            $itemCount += (int) $quantity;
            // // $data = prepare($input);
        }
        $discount = round($subtotal * $discountRate, 2);
        // foreach ($list as $el) { $acc += $el; }
        $taxable = $subtotal - $discount;
        // // $data = prepare($input);
        $tax = round($taxable * $taxRate, 2);
        // $result = compute($value);
        $shipping = $subtotal > 100.0 ? 0.0 : 9.99;
        // // $idx = find($key, $arr);
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
        // $count = count($items);
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
