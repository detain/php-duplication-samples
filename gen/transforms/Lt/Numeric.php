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
 * LT-01 numeric — change numeric literal constants (Type-2).
 *
 * The AST confirms the target values really are numeric literals (not digits
 * inside a string or identifier); the swap is applied at the token level to
 * T_LNUMBER/T_DNUMBER tokens only, preserving formatting and line count.
 *
 * params:
 *   replacements (list<{find:string, replace:string, nth?:int}>)
 *      nth (1-based) selects which matching literal to change; default: all.
 */
final class Numeric implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'LT-01';
    }

    public function name(): string
    {
        return 'numeric';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $replacements = $params['replacements'] ?? [];
        if (!is_array($replacements) || $replacements === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $text = $in->text();
        $literals = array_flip($this->ast->numericLiterals($text));

        // Per-find running counters (for the optional nth selector).
        $counters = [];
        $rebuilt = '';
        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                continue;
            }
            if ($t[0] === T_LNUMBER || $t[0] === T_DNUMBER) {
                $val = $t[1];
                foreach ($replacements as $r) {
                    $find = (string)($r['find'] ?? '');
                    if ($find === '' || $find !== $val) {
                        continue;
                    }
                    if (!isset($literals[$find])) {
                        throw new \RuntimeException("LT-01: '{$find}' is not a numeric literal in the payload");
                    }
                    $counters[$find] = ($counters[$find] ?? 0) + 1;
                    $nth = isset($r['nth']) ? (int)$r['nth'] : null;
                    if ($nth !== null && $counters[$find] !== $nth) {
                        continue;
                    }
                    $rebuilt .= (string)$r['replace'];
                    continue 2;
                }
            }
            $rebuilt .= $t[1];
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
