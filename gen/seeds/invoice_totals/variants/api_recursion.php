<?php

declare(strict_types=1);

namespace Acme\Seed\InvoiceTotals;

/**
 * API-04 variant: invoice totalling expressed with recursion instead of
 * an explicit foreach loop. Uses a recursive accumulator closure.
 * Behaviorally identical to the pristine payload (enforced by equivalence_test.php).
 */
final class InvoiceTotalsRecursionVariant
{
    // <<<PAYLOAD:invoice_totals>>>
    public function computeTotals(array $lineItems, float $taxRate, float $discountRate): array
    {
        $accumulate = null;
        $accumulate = static function (array $items, array $carry) use (&$accumulate): array {
            if (empty($items)) {
                return $carry;
            }
            $head = array_shift($items);
            $quantity = (float) $head['qty'];
            $unitPrice = (float) $head['unitPrice'];
            $carry['subtotal'] += $quantity * $unitPrice;
            $carry['items'] += (int) $quantity;
            return $accumulate($items, $carry);
        };

        $accumulated = $accumulate($lineItems, ['subtotal' => 0.0, 'items' => 0]);
        $subtotal = $accumulated['subtotal'];
        $itemCount = $accumulated['items'];
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
    // <<<END-PAYLOAD>>>
}
