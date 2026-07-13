<?php

declare(strict_types=1);

namespace Gen\Lib;

/**
 * Deterministic RNG wrapper.
 *
 * Seeded once per set with mt_srand($rng_seed) (matching the existing
 * synthetic-fuzz convention in bench/). Transforms draw from this so that
 * re-running the generator with the same seed is byte-identical (D4).
 */
final class Rng
{
    public function __construct(int $seed)
    {
        mt_srand($seed);
    }

    /** Inclusive integer in [$min, $max]. */
    public function int(int $min, int $max): int
    {
        if ($min >= $max) {
            return $min;
        }
        return mt_rand($min, $max);
    }

    /** Pick one element deterministically. */
    public function pick(array $items): mixed
    {
        if ($items === []) {
            throw new \RuntimeException('Rng::pick on empty array');
        }
        $keys = array_keys($items);
        return $items[$keys[$this->int(0, count($keys) - 1)]];
    }

    public function bool(): bool
    {
        return $this->int(0, 1) === 1;
    }
}
