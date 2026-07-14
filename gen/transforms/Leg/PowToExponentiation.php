<?php

declare(strict_types=1);

namespace Gen\Transforms\Leg;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class PowToExponentiation implements Transform
{
    public function code(): string
    {
        return 'LEG-02';
    }

    public function name(): string
    {
        return 'pow_to_exponent';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $text = $in->text();
        $rebuilt = '';

        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                continue;
            }
            $rebuilt .= $t[1];
        }

        $rebuilt = preg_replace(
            '/\bpow\s*\(\s*([^,]+)\s*,\s*([^)]+)\s*\)/',
            '($1 ** $2)',
            $rebuilt
        );

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
