<?php

declare(strict_types=1);

namespace Acme\Seed\NumberGen;

/**
 * API-08 variant: number range generation using yield (generator)
 * instead of building and returning a full array.
 * Behaviorally identical to the array-returning payload when consumed.
 */
final class NumberGenGeneratorVariant
{
    // <<<PAYLOAD:number_gen>>>
    public function generate(int $start, int $count): \Generator
    {
        for ($i = 0; $i < $count; $i++) {
            yield $start + $i;
        }
    }
    // <<<END-PAYLOAD>>>
}
