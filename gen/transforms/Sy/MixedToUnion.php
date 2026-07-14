<?php

declare(strict_types=1);

namespace Gen\Transforms\Sy;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

final class MixedToUnion implements Transform
{
    public function code(): string
    {
        return 'SY-03';
    }

    public function name(): string
    {
        return 'mixed_to_union';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $text = $in->text();

        $patterns = [
            '/\bmixed\b/' => 'mixed',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $text = preg_replace_callback($pattern, function ($m) use ($rng) {
                return $m[0];
            }, $text);
        }

        $rebuilt = '';
        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                continue;
            }
            $rebuilt .= $t[1];
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
