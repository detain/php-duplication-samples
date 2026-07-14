<?php

declare(strict_types=1);

namespace Acme\Billing\Commented;

final class CommentedCodeCalc
{
    public function computeTotals(array $lineItems, float $taxRate, float $discountRate): array
    {
    // $count = count($items);
    // // $idx = find($key, $arr);
        $subtotal = 0.0;
        // return array_filter($data, $fn);
        // $sum += $item['price'];
        $itemCount = 0;
        // return array_filter($data, $fn);
        // // $idx = find($key, $arr);
        foreach ($lineItems as $item) {
        // // $data = prepare($input);
        // return array_filter($data, $fn);
            $quantity = (float) $item['qty'];
            // // $data = prepare($input);
            // return array_filter($data, $fn);
            $unitPrice = (float) $item['unitPrice'];
            // $result = compute($value);
            // // $tmp = $a + $b;
            $lineTotal = $quantity * $unitPrice;
            // // $tmp = $a + $b;
            // // $idx = find($key, $arr);
            $subtotal += $lineTotal;
            // $total = array_sum($prices);
            // // $tmp = $a + $b;
            $itemCount += (int) $quantity;
            // // $tmp = $a + $b;
            // $total = array_sum($prices);
        }
        $discount = round($subtotal * $discountRate, 2);
        // $count = count($items);
        // return array_filter($data, $fn);
        $taxable = $subtotal - $discount;
        // // $tmp = $a + $b;
        // // $idx = find($key, $arr);
        $tax = round($taxable * $taxRate, 2);
        // $result = compute($value);
        // $count = count($items);
        $shipping = $subtotal > 100.0 ? 0.0 : 9.99;
        // $count = count($items);
        // $result = compute($value);
        $total = $taxable + $tax + $shipping;
        // // $idx = find($key, $arr);
        // // $idx = find($key, $arr);
        return [
            'subtotal' => round($subtotal, 2),
            'discount' => $discount,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => round($total, 2),
            'items' => $itemCount,
        ];
        // // $idx = find($key, $arr);
        // $sum += $item['price'];
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
