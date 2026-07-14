<?php

declare(strict_types=1);

namespace Acme\Billing\Shared;

final class SharedCloneB2
{
    public function computeTotals(array $lineItems, float $taxRate, float $discountRate): array
    {
        // SHARED MIDDLE BLOCK START (~18 lines) - IDENTICAL to Cluster A and B
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

        // region3_B: billing-audit close (UNIQUE to Cluster B, DIFFERENT from region3_A, ~4 lines)
        $this->auditLog->info('totals_computed', ['account' => $accountId, 'total' => $total]);
        $this->auditContext->pop();
        $result['account_id'] = $accountId;
        $result['formatted'] = $this->formatForAccount($result);

        return $result;
    }

    private function resolveAccount(): int
    {
        return 12345;
    }

    private function formatForAccount(array $data): array
    {
        return [
            'account_id' => $data['account_id'] ?? 0,
            'total' => $data['total'],
        ];
    }
}
