<?php

declare(strict_types=1);

namespace Gen\Transforms\Lt;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

/**
 * LT-03 array_values — change array literal contents (Type-2).
 *
 * Finds array literal nodes in the payload and replaces their values with
 * a transformed set. This works on array() and [] syntax. Values are replaced
 * while preserving the array structure.
 *
 * params:
 *   replacements (list<{find:string, replace:string}>)
 *      find/replace are the full array literal text (e.g., "['a','b']")
 *   or:
 *   mapping (array<string,string>)  findValue => replaceValue for single values
 */
final class Arrays implements Transform
{
    public function __construct()
    {
    }

    public function code(): string
    {
        return 'LT-03';
    }

    public function name(): string
    {
        return 'array_values';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $replacements = $params['replacements'] ?? [];
        $mapping = $params['mapping'] ?? [];

        if (!is_array($replacements) && !is_array($mapping)) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $text = $in->text();

        // If replacements is a list of {find, replace}, do full-array replacement.
        if (is_array($replacements) && $replacements !== []) {
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

        // If mapping is provided, do per-value replacement within arrays.
        if ($mapping === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $rebuilt = '';
        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                continue;
            }
            if ($t[0] === T_CONSTANT_ENCAPSED_STRING && isset($mapping[$t[1]])) {
                $rebuilt .= $mapping[$t[1]];
            } else {
                $rebuilt .= $t[1];
            }
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
