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
 * ST-09 expr_tweak — change `>=` to `>`, `+1` to `+2`, or similar small
 * expression edits (Type-3).
 *
 * This is a micro-semantic change that alters the boundary conditions slightly.
 *
 * params:
 *   tweaks (list<{op:string, find:string, replace:string}>)  specific replacements
 *   or auto-generate from: 'comparison', 'arith_offset', 'bool_flip'
 */
final class ExprTweak implements Transform
{
    private const TWEAK_POOL = [
        // Comparison
        ['op' => '>=', 'find' => '>=', 'replace' => '>'],
        ['op' => '>', 'find' => '>', 'replace' => '>='],
        ['op' => '<=', 'find' => '<=', 'replace' => '<'],
        ['op' => '<', 'find' => '<', 'replace' => '<='],
        // Arithmetic offsets
        ['op' => '+1', 'find' => '+ 1', 'replace' => '+ 2'],
        ['op' => '-1', 'find' => '- 1', 'replace' => '- 2'],
        ['op' => '*2', 'find' => '* 2', 'replace' => '* 3'],
        // Boolean
        ['op' => '===', 'find' => '===', 'replace' => '=='],
        ['op' => '!==', 'find' => '!==', 'replace' => '!='],
    ];

    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'ST-09';
    }

    public function name(): string
    {
        return 'expr_tweak';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $tweaks = $params['tweaks'] ?? null;

        if ($tweaks === null) {
            // Auto-generate: pick one or two tweaks from the pool.
            $count = $rng->int(1, 2);
            $tweaks = [];
            $pool = self::TWEAK_POOL;
            for ($i = 0; $i < $count; $i++) {
                if ($pool === []) {
                    break;
                }
                $pick = $rng->int(0, count($pool) - 1);
                $tweaks[] = $pool[$pick];
                array_splice($pool, $pick, 1);
            }
        }

        if (!is_array($tweaks) || $tweaks === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $text = $in->text();

        // Apply replacements at the token level to be precise.
        $rebuilt = '';
        foreach (PhpTokens::rawTokens($text) as $t) {
            if (is_string($t)) {
                $rebuilt .= $t;
                continue;
            }
            $tokenStr = $t[1];
            $applied = false;
            foreach ($tweaks as $tw) {
                $find = (string)($tw['find'] ?? '');
                $replace = (string)($tw['replace'] ?? '');
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
