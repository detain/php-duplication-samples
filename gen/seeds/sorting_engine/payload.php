<?php

declare(strict_types=1);

namespace Acme\Seed\SortingEngine;

final class SortingEngineSeed
{
    // <<<PAYLOAD:sorting_engine>>>
    /**
     * Sort an array of integers using bubble sort.
     * Payload: bubble sort — O(n^2) but simple.
     */
    public function sort(array $items): array
    {
        $arr = $items;
        $n = count($arr);
        for ($i = 0; $i < $n - 1; $i++) {
            for ($j = 0; $j < $n - $i - 1; $j++) {
                if ($arr[$j] > $arr[$j + 1]) {
                    $tmp = $arr[$j];
                    $arr[$j] = $arr[$j + 1];
                    $arr[$j + 1] = $tmp;
                }
            }
        }
        return $arr;
    }
    // <<<END-PAYLOAD>>>
}
