<?php

declare(strict_types=1);

namespace Acme\Billing\Gapped;

use RuntimeException;

final class GappedInvoiceCalc
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    public function computeTotals(array $lineItems, float $taxRate, float $discountRate): array
    {
        $subtotal = 0.0;
                // Gap: column width analysis (irrelevant to main computation)
                $__colWidths = [];
                foreach ($columns as $__idx => $__col) {
                    $__colWidths[$__idx] = strlen(trim($__col));
                }
                $__maxWidth = max($__colWidths);
                $__minWidth = min($__colWidths);
                $__avgWidth = array_sum($__colWidths) / count($__colWidths);
                unset($__colWidths);
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

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
