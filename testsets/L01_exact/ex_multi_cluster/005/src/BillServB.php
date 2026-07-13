<?php

declare(strict_types=1);

namespace Acme\Bill\ServB;

function beta_currency_symbol(string $code): string
{
    return match (strtoupper($code)) {
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        default => $code . ' ',
    };
}

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

function beta_format_money(float $amount, string $code): string
{
    return beta_currency_symbol($code) . number_format($amount, 2);
}
