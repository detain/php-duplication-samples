<?php

declare(strict_types=1);

namespace Gen\Transforms\Lt;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * LT-07 boolean_constants — change boolean representations like TRUE/false/1/0 (Type-2).
 *
 * Rewrites boolean literal representations between PHP constants (TRUE/FALSE/true/false)
 * and integer literals (1/0). Only the representation changes — boolean semantics are equivalent.
 *
 * params:
 *   replacements (list<{find:string, replace:string}>)  boolean replacements
 *      e.g., {"find": "TRUE", "replace": "true"}
 *      or {"find": "true", "replace": "1"}
 */
final class BooleanConstants implements Transform
{
    public function code(): string
    {
        return 'LT-07';
    }

    public function name(): string
    {
        return 'boolean_constants';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $replacements = $params['replacements'] ?? [];
        if (!is_array($replacements) || $replacements === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $text = $in->text();

        // Apply token-level replacements for boolean-like identifiers and integers
        $rebuilt = '';
        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                continue;
            }
            $tokenStr = $t[1];
            $applied = false;

            // T_TRUE, T_FALSE for true/false keywords
            // Also match T_LNUMBER for 1/0 integers
            if ($t[0] === T_TRUE || $t[0] === T_FALSE || $t[0] === T_LNUMBER) {
                foreach ($replacements as $r) {
                    $find = (string)($r['find'] ?? '');
                    $replace = (string)($r['replace'] ?? '');
                    if ($find !== '' && $tokenStr === $find) {
                        $rebuilt .= $replace;
                        $applied = true;
                        break;
                    }
                }
            }

            if (!$applied) {
                $rebuilt .= $tokenStr;
            }
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
