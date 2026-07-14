<?php

declare(strict_types=1);

namespace Acme\Seed\NumberGen;

final class NumberGenSeed
{
    // <<<PAYLOAD:number_gen>>>
    /**
     * Generate a range of numbers as an array.
     * Payload: returns a full array (not a generator).
     */
    public function generate(int $start, int $count): array
    {
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = $start + $i;
        }
        return $result;
    }
    // <<<END-PAYLOAD>>>
}
