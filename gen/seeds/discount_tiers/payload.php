<?php

declare(strict_types=1);

namespace Acme\Seed\DiscountTiers;

/**
 * Seed payload: compute discount rate based on quantity tiers.
 * Higher quantity = higher discount.
 */
final class DiscountTiersSeed
{
    // <<<PAYLOAD:discount_tiers>>>
    public function computeDiscount(int $quantity, array $pricePerUnit): array
    {
        $tiers = [
            ['min_qty' => 100, 'discount' => 0.20],
            ['min_qty' => 50, 'discount' => 0.15],
            ['min_qty' => 20, 'discount' => 0.10],
            ['min_qty' => 10, 'discount' => 0.05],
        ];
        $discountRate = 0.0;
        foreach ($tiers as $tier) {
            if ($quantity >= $tier['min_qty']) {
                $discountRate = $tier['discount'];
                break;
            }
        }
        $subtotal = $quantity * $pricePerUnit['unit'];
        $discount = round($subtotal * $discountRate, 2);
        return [
            'quantity' => $quantity,
            'unit_price' => $pricePerUnit['unit'],
            'subtotal' => $subtotal,
            'discount_rate' => $discountRate,
            'discount_amount' => $discount,
            'total' => $subtotal - $discount,
        ];
    }
    // <<<END-PAYLOAD>>>
}
