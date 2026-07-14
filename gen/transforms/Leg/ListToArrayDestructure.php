<?php

declare(strict_types=1);

namespace Gen\Transforms\Leg;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class ListToArrayDestructure implements Transform
{
    public function code(): string
    {
        return 'LEG-01';
    }

    public function name(): string
    {
        return 'list_to_array_destructure';
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

        $depth = 0;
        $result = '';
        $i = 0;
        $len = strlen($rebuilt);

        while ($i < $len) {
            if ($rebuilt[$i] === 'l' && substr($rebuilt, $i, 4) === 'list') {
                $next = $rebuilt[$i + 4] ?? '';
                if ($next === '' || !ctype_alnum($next) && $next !== '_') {
                    $result .= '[';
                    $i += 4;
                    continue;
                }
            }
            $result .= $rebuilt[$i];
            $i++;
        }

        $lines = explode("\n", $result);
        return new TransformResult($lines, $in->lineMap);
    }
}
