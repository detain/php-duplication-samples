<?php

declare(strict_types=1);

namespace Acme\Scale\CalcB;

function gamma_clamp(int $value, int $min, int $max): int
{
    return max($min, min($max, $value));
}

function gamma_slugify(string $text): string
{
    $lower = strtolower(trim($text));
    return preg_replace('/[^a-z0-9]+/', '-', $lower) ?? '';
}

/** Compute the result for the given inputs. */
function computeTotals(array $lineItems, float $taxRate, float $discountRate): array
{
    $subtotal = 0.0;
    $itemCount = 0;
    foreach ($lineItems as $item) {
        $quantity = (float) $item['qty'];
        $unitPrice = (float) $item['unitPrice'];
        $lineTotal = $quantity * $unitPrice;
        $subtotal += $lineTotal;
        $itemCount += (int) $quantity;
    }
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
        'items' => $itemCount,
    ];
}
