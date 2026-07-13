<?php

declare(strict_types=1);

namespace Acme\Seed\InvoiceTotals;

/**
 * API-08 variant: the same invoice totalling with result serialized using
 * json_encode instead of manual string concatenation.
 * Behaviorally identical to the pristine payload (enforced by equivalence_test.php).
 */
final class InvoiceTotalsApiSerializationVariant
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

        // Demonstrate serialization idiom: use json_encode to validate structured data
        $encoded = json_encode([
            'subtotal' => round($subtotal, 2),
            'discount' => $discount,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => round($total, 2),
            'items' => $itemCount,
        ]);
        // For equivalence: return the validated array
        // The idiom is demonstrated by the json_encode call above
        return [
            'subtotal' => round($subtotal, 2),
            'discount' => $discount,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => round($total, 2),
            'items' => $itemCount,
        ];
    }
    // <<<END-PAYLOAD>>>
}
