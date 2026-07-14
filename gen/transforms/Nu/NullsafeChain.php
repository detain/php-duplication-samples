<?php

declare(strict_types=1);

namespace Gen\Transforms\Nu;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * NU-03 nullsafe_chain — change $x ? $x->y() : null to $x?->y() and vice versa (Type-2).
 *
 * Rewrites ternary null-check patterns to nullsafe operator, or expands
 * nullsafe back to explicit ternary. Token-level rewrite only.
 *
 * params:
 *   direction (string)  'to_nullsafe' (default) or 'to_explicit'
 */
final class NullsafeChain implements Transform
{
    public function code(): string
    {
        return 'NU-03';
    }

    public function name(): string
    {
        return 'nullsafe_chain';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $direction = (string)($params['direction'] ?? 'to_nullsafe');
        $toNullsafe = $direction !== 'to_explicit';

        $text = $in->text();

        if ($toNullsafe) {
            // Match patterns like: $x ? $x->method() : null
            // More specific pattern for property/method access
            $pattern = '/(\$\w+)\s*\?\s*\\1\s*->(\w+)\s*\(\)\s*:\s*null/';
            $rebuilt = preg_replace($pattern, '$1?->$2()', $text);

            // Also handle $x ? $x->property : null
            if ($rebuilt === null || $rebuilt === $text) {
                $pattern2 = '/(\$\w+)\s*\?\s*\\1\s*->(\w+)\s*:\s*null/';
                $rebuilt = preg_replace($pattern2, '$1?->$2', $text);
            }

            if ($rebuilt === null) {
                $rebuilt = $text;
            }
        } else {
            // Match $x?->method() and expand to $x ? $x->method() : null
            $pattern = '/(\$\w+)\?\->(\w+)\s*\(\)/';
            $rebuilt = preg_replace($pattern, '$1 ? $1->$2() : null', $text);

            // Also handle $x?->property
            if ($rebuilt === null || $rebuilt === $text) {
                $pattern2 = '/(\$\w+)\?\->(\w+)(?!\s*\()/';
                $rebuilt = preg_replace($pattern2, '$1 ? $1->$2 : null', $text);
            }

            if ($rebuilt === null) {
                $rebuilt = $text;
            }
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
