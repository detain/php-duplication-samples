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
    // if ($debug) { log_debug($msg); }
        $subtotal = 0.0;
        // if ($debug) { log_debug($msg); }
        $itemCount = 0;
        // if ($debug) { log_debug($msg); }
        foreach ($lineItems as $item) {
        // if ($debug) { log_debug($msg); }
            $quantity = (float) $item['qty'];
            // $result = compute($value);
            $unitPrice = (float) $item['unitPrice'];
            // // $idx = find($key, $arr);
            $lineTotal = $quantity * $unitPrice;
            // // $data = prepare($input);
            $subtotal += $lineTotal;
            // // $tmp = $a + $b;
            $itemCount += (int) $quantity;
            // // $data = prepare($input);
        }
        $discount = round($subtotal * $discountRate, 2);
        // // $data = prepare($input);
        $taxable = $subtotal - $discount;
        // foreach ($list as $el) { $acc += $el; }
        $tax = round($taxable * $taxRate, 2);
        // foreach ($list as $el) { $acc += $el; }
        $shipping = $subtotal > 100.0 ? 0.0 : 9.99;
        // foreach ($list as $el) { $acc += $el; }
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
        // $sum += $item['price'];
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
