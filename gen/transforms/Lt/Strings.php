<?php

declare(strict_types=1);

namespace Gen\Transforms\Lt;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * LT-02 string — change string literal values within the payload (Type-2).
 *
 * The AST confirms the target values really are string literals (not digits
 * inside an identifier); the swap is applied at the token level to
 * T_CONSTANT_ENCAPSED_STRING tokens only, preserving formatting and line count.
 *
 * params:
 *   replacements (list<{find:string, replace:string, nth?:int}>)
 *      nth (1-based) selects which matching literal to change; default: all.
 */
final class Strings implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'LT-02';
    }

    public function name(): string
    {
        return 'string';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $replacements = $params['replacements'] ?? [];
        if (!is_array($replacements) || $replacements === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $text = $in->text();

        // Per-find running counters (for the optional nth selector).
        $counters = [];
        $rebuilt = '';
        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                continue;
            }
            if ($t[0] === T_CONSTANT_ENCAPSED_STRING) {
                $val = $t[1];
                foreach ($replacements as $r) {
                    $find = (string)($r['find'] ?? '');
                    if ($find === '' || $find !== $val) {
                        continue;
                    }
                    $counters[$find] = ($counters[$find] ?? 0) + 1;
                    $nth = isset($r['nth']) ? (int)$r['nth'] : null;
                    if ($nth !== null && $counters[$find] !== $nth) {
                        continue;
                    }
                    $replace = (string)($r['replace'] ?? '');
                    $rebuilt .= $replace;
                    continue 2;
                }
            }
            $rebuilt .= $t[1];
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
