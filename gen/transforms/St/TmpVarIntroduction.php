<?php

declare(strict_types=1);

namespace Gen\Transforms\St;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * ST-11 tmp_var_introduction — introduce temp variables vs inline expressions (Type-3).
 *
 * Rewrites between inline expressions like $tax = ($subtotal - $discount) * $r
 * and the split form with an intermediate variable:
 *   $taxable = $subtotal - $discount;
 *   $tax = $taxable * $r;
 *
 * params:
 *   style (string)       'temp_variable' (default) or 'direct'
 *   rename (string)      optional temp variable name base (default: 'tmp')
 */
final class TmpVarIntroduction implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'ST-11';
    }

    public function name(): string
    {
        return 'tmp_var_introduction';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $style = (string)($params['style'] ?? 'temp_variable');
        $rename = (string)($params['rename'] ?? 'tmp');
        $useTempVar = $style === 'temp_variable';

        $text = $in->text();

        if ($useTempVar) {
            // Convert inline to temp variable form
            // Pattern: $result = ($a - $b) * $c -> $__tmp = $a - $b; $result = $__tmp * $c;
            $pattern = '/(\$\w+)\s*=\s*\(([^)]+)\)\s*\*\s*([^;]+);/';
            $rebuilt = preg_replace_callback($pattern, function ($m) use ($rename) {
                $resultVar = $m[1];
                $expr1 = $m[2];
                $expr2 = trim($m[3]);
                $tmpVar = '$' . $rename;
                return "$tmpVar = $expr1;\n        $resultVar = $tmpVar * $expr2;";
            }, $text);

            if ($rebuilt !== null && $rebuilt !== $text) {
                $lines = explode("\n", $rebuilt);
                return new TransformResult($lines, $in->lineMap);
            }

            // Handle simpler pattern: $x = $a * $b + $c -> may split
            $pattern2 = '/(\$\w+)\s*=\s*(\$\w+)\s*([*+])\s*(\$\w+)\s*([+])\s*(\$\w+);/';
            $rebuilt = preg_replace_callback($pattern2, function ($m) use ($rename) {
                $resultVar = $m[1];
                $expr1 = $m[2] . $m[3] . $m[4];
                $expr2 = $m[6];
                $tmpVar = '$' . $rename;
                return "$tmpVar = $expr1;\n        $resultVar = $tmpVar $m[5] $expr2;";
            }, $text);

            if ($rebuilt !== null) {
                $lines = explode("\n", $rebuilt);
                return new TransformResult($lines, $in->lineMap);
            }
        } else {
            // Convert temp variable form back to inline
            // Pattern: $tmp = $a - $b; $result = $tmp * $c; -> $result = ($a - $b) * $c;
            $pattern = '/\$' . preg_quote($rename, '/') . '\s*=\s*([^;]+);\s*(\$\w+)\s*=\s*\$' . preg_quote($rename, '/') . '\s*([*+])\s*([^;]+);/';
            $rebuilt = preg_replace($pattern, '$2 = ($1) $3 $4;', $text);

            if ($rebuilt !== null && $rebuilt !== $text) {
                $lines = explode("\n", $rebuilt);
                return new TransformResult($lines, $in->lineMap);
            }
        }

        return new TransformResult($in->lines, $in->lineMap);
    }
}
