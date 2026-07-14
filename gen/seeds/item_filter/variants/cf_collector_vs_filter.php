<?php

declare(strict_types=1);

namespace Acme\Seed\ItemFilter;

/**
 * CF-08 variant: the same filtering and transformation re-expressed
 * using array_filter + array_map chain instead of manual foreach+if.
 * Behaviorally identical to the manual payload.
 */
final class ItemFilterCollectorVsFilterVariant
{
    // <<<PAYLOAD:item_filter>>>
    public function filter(array $items): array
    {
        return array_map(
            fn(array $item): array => [
                'key' => $item['sku'] ?? uniqid('SKU_'),
                'label' => strtoupper($item['name'] ?? ''),
                'net' => $item['price'] ?? 0,
                'tax' => ($item['price'] ?? 0) * 0.20,
            ],
            array_filter(
                $items,
                fn(array $item): bool => ($item['price'] ?? 0) > 0 && ($item['name'] ?? '') !== ''
            )
        );
    }
    // <<<END-PAYLOAD>>>
}
