<?php

declare(strict_types=1);

namespace Acme\Seed\SortingEngine;

/**
 * SEM-02 variant: insertion sort implementation.
 * Behaviorally identical to the bubble sort payload (both sort ascending).
 */
final class SortingEngineInsertionSortVariant
{
    // <<<PAYLOAD:sorting_engine>>>
    public function sort(array $items): array
    {
        $arr = $items;
        $n = count($arr);
        for ($i = 1; $i < $n; $i++) {
            $key = $arr[$i];
            $j = $i - 1;
            while ($j >= 0 && $arr[$j] > $key) {
                $arr[$j + 1] = $arr[$j];
                $j--;
            }
            $arr[$j + 1] = $key;
        }
        return $arr;
    }
    // <<<END-PAYLOAD>>>
}
