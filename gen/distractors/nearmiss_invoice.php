<?php

declare(strict_types=1);

namespace __NAMESPACE__;

/**
 * Summarises revenue, cost and margin for a set of invoice line items.
 */
final class __CLASS__
{
    // <<<NEARMISS>>>
    public function summarizeMargins(array $lineItems, float $costRate): array
    {
        $revenue = 0.0;
        $cost = 0.0;
        foreach ($lineItems as $item) {
            $line = (float) $item['qty'] * (float) $item['unitPrice'];
            $revenue += $line;
            $cost += $line * $costRate;
        }
        $margin = $revenue - $cost;
        $ratio = $revenue > 0.0 ? $margin / $revenue : 0.0;
        return [
            'revenue' => round($revenue, 2),
            'margin' => round($margin, 2),
            'ratio' => round($ratio, 4),
        ];
    }
    // <<<END-NEARMISS>>>

    public function currencyCode(): string
    {
        return 'USD';
    }
}
