<?php

declare(strict_types=1);

namespace Acme\Billing\Reports;

final class InvoiceDistractor
{
    public function summarizeMargins(array $lineItems, float $costRate): array
    {
        $revenue = 0.0;
        $cost = 0.0;
        $itemCount = 0;
        foreach ($lineItems as $item) {
            $quantity = (float) $item['qty'];
            $unitPrice = (float) $item['unitPrice'];
            $lineRevenue = $quantity * $unitPrice;
            $lineCost = $lineRevenue * $costRate;
            $revenue += $lineRevenue;
            $cost += $lineCost;
            $itemCount++;
        }
        $margin = $revenue - $cost;
        $ratio = $revenue > 0.0 ? $margin / $revenue : 0.0;
        return [
            'revenue' => round($revenue, 2),
            'cost' => round($cost, 2),
            'margin' => round($margin, 2),
            'ratio' => round($ratio, 4),
            'items' => $itemCount,
        ];
    }

    public function currencyCode(): string
    {
        return 'USD';
    }

    public function formatReport(array $data): string
    {
        return sprintf(
            'Revenue: %s | Cost: %s | Margin: %s (%.2f%%)',
            $data['revenue'],
            $data['cost'],
            $data['margin'],
            $data['ratio'] * 100
        );
    }
}
