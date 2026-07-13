<?php

declare(strict_types=1);

namespace Gen\Transforms;

use Gen\Lib\Rng;

/**
 * A transform rewrites the cloned payload region for one carrier and reports a
 * line map so the renderer can compute exact ground-truth line ranges even
 * after wraps/insertions.
 *
 * Contract:  transform(input, params, rng) -> {text, lineMap}
 *
 *   - input.lines   : list<string>  the payload region, one string per line
 *   - input.lineMap : list<int>     output-line -> source-line index (identity at start)
 *   - params        : transform-specific, and sufficient to reproduce it
 *   - rng           : deterministic RNG (seeded per set)
 *
 * The result carries the emitted lines plus an updated lineMap where each entry
 * is the source line index the output line came from, or -1 for a line the
 * transform inserted. WS/CM transforms MUST stay syntax-safe (never split
 * inside a string literal) by using the PhpTokens helpers.
 */
interface Transform
{
    /** Registry code this transform realizes, e.g. "WS-03". */
    public function code(): string;

    /** Human name, e.g. "blank_inside". */
    public function name(): string;

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult;
}
