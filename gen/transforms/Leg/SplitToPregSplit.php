<?php

declare(strict_types=1);

namespace Gen\Transforms\Leg;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class SplitToPregSplit implements Transform
{
    public function code(): string
    {
        return 'LEG-04';
    }

    public function name(): string
    {
        return 'split_to_preg_split';
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
            '/\bsplit\s*\(\s*([\'"])([^\'"]*)\1\s*,\s*([^,)]+)\s*(,\s*[^)]+)?\)/',
            'preg_split(\'/$2/\', $3$4)',
            $rebuilt
        );

        $rebuilt = preg_replace('/\bsplit\s*\(/', 'preg_split(', $rebuilt);

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
