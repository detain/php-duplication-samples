<?php

declare(strict_types=1);

namespace Gen\Transforms\Nu;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * NU-02 coalesce_assignment — change $a = $a ?? $b to $a ??= $b and vice versa (Type-2).
 *
 * Rewrites null-coalescing self-assignment to the shorthand compound form, or
 * expands the shorthand back to explicit form. Token-level rewrite only.
 *
 * params:
 *   direction (string)  'to_shorthand' (default) or 'to_explicit'
 */
final class CoalesceAssignment implements Transform
{
    public function code(): string
    {
        return 'NU-02';
    }

    public function name(): string
    {
        return 'coalesce_assignment';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $direction = (string)($params['direction'] ?? 'to_shorthand');
        $toShorthand = $direction !== 'to_explicit';

        $text = $in->text();
        $rebuilt = '';
        $changed = false;

        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                continue;
            }

            $tokenStr = $t[1];

            // Detect $var = $var ?? $value pattern (coalesce self-assignment)
            // We need multi-token context, so we'll do a text-based heuristic approach
            $rebuilt .= $tokenStr;
        }

        if ($toShorthand) {
            // Match patterns like: $x = $x ?? $y;
            $pattern = '/(\$\w+)\s*=\s*\\1\s*\?\?\s*([^;]+);/';
            $rebuilt = preg_replace($pattern, '$1 ??= $2;', $text);
            if ($rebuilt === null) {
                $rebuilt = $text;
            }
        } else {
            // Match $x ??= $y; and expand to $x = $x ?? $y;
            $pattern = '/(\$\w+)\s*\?\?=\s*([^;]+);/';
            $rebuilt = preg_replace($pattern, '$1 = $1 ?? $2;', $text);
            if ($rebuilt === null) {
                $rebuilt = $text;
            }
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
