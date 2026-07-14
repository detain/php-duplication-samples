<?php

declare(strict_types=1);

namespace Gen\Transforms\Leg;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class EachToForeach implements Transform
{
    public function code(): string
    {
        return 'LEG-05';
    }

    public function name(): string
    {
        return 'each_to_foreach';
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
            '/\bwhile\s*\(\s*list\s*\(\s*\$([^,]+)\s*,\s*\$([^)]+)\s*\)\s*=\s*each\s*\(\s*\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*\)\s*\)/',
            'foreach ($$3 as $$1 => $$2)',
            $rebuilt
        );

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
