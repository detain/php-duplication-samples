<?php
declare(strict_types=1);

namespace Acme\Billing\Unified;

class LineItemProcessor
{
    public function processLineItem(array $item, array $config): array
    {
        $quantity = (int)($item['quantity'] ?? 0);
        $unitPrice = (float)($item['unit_price'] ?? 0.0);
        $taxRate = (float)($config['tax_rate'] ?? 0.0);
        $subtotal = $quantity * $unitPrice;
        $tax = $subtotal * $taxRate;
        $total = $subtotal + $tax;
        return [
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ];
    }
}
