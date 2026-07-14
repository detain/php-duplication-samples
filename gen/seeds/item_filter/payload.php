<?php

declare(strict_types=1);

namespace Acme\Seed\ItemFilter;

final class ItemFilterSeed
{
    // <<<PAYLOAD:item_filter>>>
    /**
     * Filter items by active status and transform to summary form.
     * Payload: manual foreach with if-else collecting.
     */
    public function filter(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $price = $item['price'] ?? 0;
            $name = $item['name'] ?? '';
            if ($price > 0 && $name !== '') {
                $result[] = [
                    'key' => $item['sku'] ?? uniqid('SKU_'),
                    'label' => strtoupper($name),
                    'net' => $price,
                    'tax' => $price * 0.20,
                ];
            }
        }
        return $result;
    }
    // <<<END-PAYLOAD>>>
}
