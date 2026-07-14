<?php

declare(strict_types=1);

namespace Acme\Billing\Shared;

final class SharedCloneA3
{
    public function computeTotals(array $lineItems, float $taxRate, float $discountRate): array
    {
        // region1_A: invoice-specific setup (UNIQUE to Cluster A, ~3 lines)
        $invoiceId = $this->generateInvoiceId();
        $this->beginTransaction();

        // SHARED MIDDLE BLOCK START (~18 lines) - IDENTICAL in Cluster A and B
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
        $result = [
            'subtotal' => round($subtotal, 2),
            'discount' => $discount,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => round($total, 2),
            'items' => $itemCount,
        ];
        // SHARED MIDDLE BLOCK END

        // region3_A: invoice-specific close (TRUNCATED by tail_trim:2, now ~1 line)
        $result['invoice_id'] = $invoiceId;

        return $result;
    }

    private function generateInvoiceId(): string
    {
        return 'INV-' . time();
    }

    private function beginTransaction(): void
    {
        // transaction begin
    }

    private function commitTransaction(): void
    {
        // transaction commit
    }

    private function formatForInvoice(array $data): array
    {
        return [
            'invoice_id' => $data['invoice_id'] ?? 'UNKNOWN',
            'total' => $data['total'],
        ];
    }
}
