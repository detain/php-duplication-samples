<?php

declare(strict_types=1);

namespace Acme\Seed\InvoiceTotals;

/**
 * API-02 variant: invoice totalling expressed using varied string functions.
 * Uses sprintf for intermediate formatting, with type-casting to maintain
 * numeric return types identical to the pristine payload.
 * Behaviorally identical to the pristine payload (enforced by equivalence_test.php).
 */
final class InvoiceTotalsStringsVariant
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
        return [
            'subtotal' => (float) sprintf('%.2f', $subtotal),
            'discount' => (float) sprintf('%.2f', $discount),
            'tax' => (float) sprintf('%.2f', $tax),
            'shipping' => (float) sprintf('%.2f', $shipping),
            'total' => (float) sprintf('%.2f', $total),
            'items' => $itemCount,
        ];
    }
    // <<<END-PAYLOAD>>>
}
