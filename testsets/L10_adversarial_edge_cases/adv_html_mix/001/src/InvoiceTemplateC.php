<?php

declare(strict_types=1);

namespace Acme\Billing\TplC;

final class InvoiceTemplateC
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
}
