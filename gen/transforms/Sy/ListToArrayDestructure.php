<?php

declare(strict_types=1);

namespace Gen\Transforms\Sy;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class ListToArrayDestructure implements Transform
{
    public function code(): string
    {
        return 'SY-09';
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

        $rebuilt = preg_replace('/\blist\s*\(\s*/', '[', $rebuilt);
        $rebuilt = preg_replace('/\blist\s*\(/', '[', $rebuilt);

        $depth = 0;
        $result = '';
        for ($i = 0; $i < strlen($rebuilt); $i++) {
            $c = $rebuilt[$i];
            if ($c === '[') {
                $depth++;
                $result .= $c;
            } elseif ($c === ']') {
                $depth--;
                $result .= $c;
            } else {
                $result .= $c;
            }
        }

        $lines = explode("\n", $result);
        return new TransformResult($lines, $in->lineMap);
    }
}
