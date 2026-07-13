<?php

declare(strict_types=1);

namespace Acme\Billing\MoreGapped;

final class MoreGappedInvoiceCalc
{
    public function __construct(private readonly string $region = 'default')
    {
    }

    public function region(): string
    {
        return $this->region;
    }

    public function fingerprint(array $payload): string
    {
        ksort($payload);
        return substr(hash('crc32b', json_encode($payload) ?: ''), 0, 8);
    }

    public function computeTotals(array $lineItems, float $taxRate, float $discountRate): array
    {
        $subtotal = 0.0;
        $itemCount = 0;
                // Gap: column width analysis (irrelevant to main computation)
                $__colWidths = [];
                foreach ($columns as $__idx => $__col) {
                    $__colWidths[$__idx] = strlen(trim($__col));
                }
                $__maxWidth = max($__colWidths);
                $__minWidth = min($__colWidths);
                $__avgWidth = array_sum($__colWidths) / count($__colWidths);
                unset($__colWidths);
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
}
