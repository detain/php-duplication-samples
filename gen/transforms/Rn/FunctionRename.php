<?php

declare(strict_types=1);

namespace Gen\Transforms\Rn;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * RN-03 function_rename — simple rename of the function/method name
 * by finding the function keyword and renaming the next identifier (Type-2).
 *
 * params:
 *   rename (string)  new function/method name (no $)
 */
final class FunctionRename implements Transform
{
    public function code(): string
    {
        return 'RN-03';
    }

    public function name(): string
    {
        return 'function_rename';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $rename = $params['rename'] ?? '';
        if ($rename === '' || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $rename)) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $text = $in->text();

        // Find the function name after T_FUNCTION token.
        $rebuilt = '';
        $prevToken = null;
        $foundFunction = false;
        $oldName = null;

        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                $prevToken = $t;
                continue;
            }

            // Detect T_FUNCTION.
            if ($t[0] === T_FUNCTION) {
                $foundFunction = true;
                $rebuilt .= $t[1];
                $prevToken = $t;
                continue;
            }

            // After T_FUNCTION, the next T_STRING is the function name.
            if ($foundFunction && $t[0] === T_STRING) {
                $oldName = $t[1];
                $rebuilt .= $rename;
                $foundFunction = false;
                $prevToken = $t;
                continue;
            }

            // Also handle ampersand for reference returns.
            if ($foundFunction && $t[0] === T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG) {
                $rebuilt .= $t[1];
                $prevToken = $t;
                continue;
            }

            $rebuilt .= $t[1];
            $prevToken = $t;
        }

        // If no function was found, return identity.
        if ($oldName === null) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // If name didn't change, return identity.
        if ($oldName === $rename) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
