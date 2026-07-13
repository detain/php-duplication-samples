<?php

declare(strict_types=1);

namespace Acme\Seed\InvoiceTotals;

/**
 * API-03 variant: invoice totalling using string manipulation idioms
 * instead of array_ops or regex. Uses strpos, explode, substr, and
 * build keys via string concatenation.
 * Behaviorally identical to the pristine payload (enforced by equivalence_test.php).
 */
final class InvoiceTotalsRegexStringVariant
{
    // <<<PAYLOAD:invoice_totals>>>
    public function computeTotals(array $lineItems, float $taxRate, float $discountRate): array
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
        $keys = explode(',', 'subtotal,discount,tax,shipping,total,items');
        $result = [];
        foreach ($keys as $idx => $key) {
            $result[$key] = match ($key) {
                'subtotal' => round($subtotal, 2),
                'discount' => $discount,
                'tax' => $tax,
                'shipping' => $shipping,
                'total' => round($total, 2),
                'items' => $itemCount,
                default => null,
            };
        }
        return $result;
    }
    // <<<END-PAYLOAD>>>
}
