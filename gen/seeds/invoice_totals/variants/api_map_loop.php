<?php

declare(strict_types=1);

namespace Acme\Seed\InvoiceTotals;

/**
 * API-01 variant: the same invoice totalling expressed with array_reduce
 * instead of an explicit foreach accumulation. Behaviorally identical to the
 * pristine payload (enforced by equivalence_test.php).
 */
final class InvoiceTotalsMapVariant
{
    // <<<PAYLOAD:invoice_totals>>>
    public function computeTotals(array $lineItems, float $taxRate, float $discountRate): array
    {
        $accumulated = array_reduce(
            $lineItems,
            static function (array $carry, array $item): array {
                $carry['subtotal'] += (float) $item['qty'] * (float) $item['unitPrice'];
                $carry['items'] += (int) (float) $item['qty'];
                return $carry;
            },
            ['subtotal' => 0.0, 'items' => 0]
        );
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
