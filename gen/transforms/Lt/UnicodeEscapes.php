<?php

declare(strict_types=1);

namespace Gen\Transforms\Lt;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * LT-06 unicode_escapes — change unicode representations like é / \u00e9 / &#233; (Type-2).
 *
 * Rewrites unicode character representations between literal characters, unicode
 * escape sequences (\uXXXX), and HTML entities (&#DDD;).
 * Only the representation changes — character is semantically equivalent.
 *
 * params:
 *   replacements (list<{find:string, replace:string}>)  character replacements
 *      e.g., {"find": "'café'", "replace": "'caf\\u00e9'"}
 *      or {"find": "'café'", "replace": "'caf&#233;'"}
 */
final class UnicodeEscapes implements Transform
{
    public function code(): string
    {
        return 'LT-06';
    }

    public function name(): string
    {
        return 'unicode_escapes';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $replacements = $params['replacements'] ?? [];
        if (!is_array($replacements) || $replacements === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $text = $in->text();

        // Apply string literal replacements
        foreach ($replacements as $r) {
            $find = (string)($r['find'] ?? '');
            $replace = (string)($r['replace'] ?? '');
            if ($find !== '' && $find !== $replace) {
                $text = str_replace($find, $replace, $text);
            }
        }

        $lines = explode("\n", $text);
        return new TransformResult($lines, $in->lineMap);
    }
}
