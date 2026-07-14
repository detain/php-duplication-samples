<?php

declare(strict_types=1);

namespace Acme\Seed\InventoryReservation;

/**
 * Seed payload: reserve inventory items for an order.
 * Checks availability and creates reservation with expiry.
 */
final class InventoryReservationSeed
{
    // <<<PAYLOAD:inventory_reservation>>>
    public function reserveInventory(array $items, array $inventory, int $ttlSeconds): array
    {
        $reserved = [];
        $failed = [];
        $now = time();
        foreach ($items as $item) {
            $sku = $item['sku'];
            $qty = $item['quantity'];
            if (!isset($inventory[$sku])) {
                $failed[] = ['sku' => $sku, 'reason' => 'not_found'];
                continue;
            }
            $available = $inventory[$sku]['quantity'] - ($inventory[$sku]['reserved'] ?? 0);
            if ($available < $qty) {
                $failed[] = ['sku' => $sku, 'reason' => 'insufficient_stock', 'available' => $available];
                continue;
            }
            $reserved[] = [
                'sku' => $sku,
                'quantity' => $qty,
                'expires_at' => $now + $ttlSeconds,
            ];
        }
        return [
            'success' => empty($failed),
            'reserved' => $reserved,
            'failed' => $failed,
            'reserved_count' => count($reserved),
        ];
    }
    // <<<END-PAYLOAD>>>
}
