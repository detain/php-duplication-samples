<?php

declare(strict_types=1);

namespace Gen\Transforms\Rn;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * RN-05 case_convention — convert snake_case ↔ camelCase for identifiers that
 * appear in the payload (Type-2).
 *
 * This does not rename all identifiers globally; it only converts identifiers
 * that are already in the payload, using a consistent direction (snake→camel
 * or camel→snake). This preserves the structure while changing the naming
 * convention of the identifiers that are present.
 *
 * params:
 *   direction (string)  'snake_to_camel' or 'camel_to_snake' (default: snake_to_camel)
 */
final class CaseStyle implements Transform
{
    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'RN-05';
    }

    public function name(): string
    {
        return 'case_convention';
    }

    private static function snakeToCamel(string $s): string
    {
        return lcfirst(str_replace('_', '', ucwords($s, '_')));
    }

    private static function camelToSnake(string $s): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $s));
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $direction = $params['direction'] ?? 'snake_to_camel';

        $text = $in->text();

        // Collect all T_STRING and T_VARIABLE tokens that look like identifiers.
        $identifiers = [];
        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                continue;
            }
            if ($t[0] === T_STRING || $t[0] === T_VARIABLE) {
                $identifiers[$t[1]] = true;
            }
        }

        $conversions = [];
        foreach (array_keys($identifiers) as $ident) {
            if ($direction === 'snake_to_camel' && str_contains($ident, '_')) {
                $converted = self::snakeToCamel($ident);
                if ($converted !== $ident) {
                    $conversions[$ident] = $converted;
                }
            } elseif ($direction === 'camel_to_snake' && preg_match('/[A-Z]/', $ident)) {
                $converted = self::camelToSnake($ident);
                if ($converted !== $ident) {
                    $conversions[$ident] = $converted;
                }
            }
        }

        if ($conversions === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        file_put_contents('/tmp/casestyle_apply.log', "=== APPLY ===\nTEXT:\n{$text}\n\nCONVERSIONS:\n" . print_r($conversions, true), FILE_APPEND);

        // Apply at token level.
        $rebuilt = '';
        $skipNextString = false;
        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t) || $t[0] === T_WHITESPACE) {
                $rebuilt .= is_string($t) ? $t : $t[1];
                continue;
            }
            // T_FUNCTION keyword: skip the next T_STRING (the function name).
            if ($t[0] === T_FUNCTION) {
                $skipNextString = true;
                $rebuilt .= $t[1];
                continue;
            }
            if ($skipNextString && $t[0] === T_STRING) {
                $skipNextString = false;
                $rebuilt .= $t[1];
                continue;
            }
            $skipNextString = false;
            if (($t[0] === T_STRING || $t[0] === T_VARIABLE) && isset($conversions[$t[1]])) {
                $rebuilt .= $conversions[$t[1]];
            } else {
                $rebuilt .= $t[1];
            }
        }

        $lines = explode("\n", $rebuilt);
        return new TransformResult($lines, $in->lineMap);
    }
}
