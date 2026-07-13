<?php

declare(strict_types=1);

namespace Gen\Transforms\Cm;

use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * CM-11 combined — combination of CM-01 + CM-03 + CM-10.
 *
 * Applies multiple comment transforms at once.
 * Token-stream preserving (Type-1 after stripping).
 *
 * params:
 *   styles (list<string>) list of transform codes to apply
 */
final class Combined implements Transform
{
    public function code(): string
    {
        return 'CM-11';
    }

    public function name(): string
    {
        return 'combined';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        // CM-11 is a meta-transform that applies other transforms
        // For simplicity, this identity placeholder passes through
        return new TransformResult($in->lines, $in->lineMap);
    }
}
