<?php

declare(strict_types=1);

namespace Gen\Transforms\Leg;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class EregiToPregMatch implements Transform
{
    public function code(): string
    {
        return 'LEG-03';
    }

    public function name(): string
    {
        return 'eregi_to_preg';
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

        $rebuilt = preg_replace_callback(
            '/\beregi\s*\(\s*([\'"])([^\'"]*)\1\s*,\s*(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*(,\s*[^)]+)?\)/',
            function ($m) {
                $pattern = $m[2];
                $subject = $m[3];
                $extra = $m[4] ?? '';
                $delim = '/';
                $escaped = preg_quote($pattern, $delim);
                return "preg_match('/{$escaped}/i', {$subject}{$extra})";
            },
            $rebuilt
        );

        $rebuilt = preg_replace('/\beregi\s*\(/', 'preg_match(', $rebuilt);

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
