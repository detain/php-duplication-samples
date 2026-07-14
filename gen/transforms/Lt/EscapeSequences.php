<?php

declare(strict_types=1);

namespace Gen\Transforms\Lt;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * LT-05 escape_sequences — change escape sequences like \n / \r\n / PHP_EOL (Type-2).
 *
 * Rewrites newline escape sequences between \n, \r\n, and PHP_EOL constant.
 * Only the escape representation changes — actual newlines are semantically equivalent.
 *
 * params:
 *   replacements (list<{find:string, replace:string}>)  escape sequence replacements
 *      e.g., {"find": "\"\\n\"", "replace": "\"\\r\\n\""}
 *      or {"find": "\"\\n\"", "replace": "PHP_EOL"}
 */
final class EscapeSequences implements Transform
{
    public function code(): string
    {
        return 'LT-05';
    }

    public function name(): string
    {
        return 'escape_sequences';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $replacements = $params['replacements'] ?? [];
        if (!is_array($replacements) || $replacements === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $text = $in->text();

        // Apply replacements at token level to be precise
        $rebuilt = '';
        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                continue;
            }
            $tokenStr = $t[1];
            $applied = false;
            foreach ($replacements as $r) {
                $find = (string)($r['find'] ?? '');
                $replace = (string)($r['replace'] ?? '');
                if ($find !== '' && $tokenStr === $find) {
                    $rebuilt .= $replace;
                    $applied = true;
                    break;
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
