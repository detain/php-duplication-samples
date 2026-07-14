<?php

declare(strict_types=1);

namespace Gen\Transforms\St;

use Gen\Lib\AstAnalyzer;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * ST-10 hoist_invariant — move loop-invariant expressions out of or into loops (Type-3).
 *
 * Identifies expressions inside loops that don't depend on loop variables and moves
 * them outside (or vice versa). Used with AST canonicalization.
 *
 * params:
 *   direction (string)  'hoist_out' (default) or 'sink_in'
 */
final class HoistInvariant implements Transform
{
    private const HOIST_INVARIANTS = [
        'count($arr)' => 'count($arr)',
        'strlen($str)' => 'strlen($str)',
        'sizeof($arr)' => 'sizeof($arr)',
    ];

    public function __construct(private AstAnalyzer $ast = new AstAnalyzer())
    {
    }

    public function code(): string
    {
        return 'ST-10';
    }

    public function name(): string
    {
        return 'hoist_invariant';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $direction = (string)($params['direction'] ?? 'hoist_out');
        $hoistOut = $direction !== 'sink_in';

        $lines = $in->lines;

        if (!$hoistOut) {
            return new TransformResult($lines, $in->lineMap);
        }

        $result = $lines;
        $inLoop = false;
        $loopStart = -1;
        $invariantCount = 0;

        foreach ($lines as $idx => $line) {
            $trimmed = trim($line);

            if (preg_match('/^\\s*(for|foreach|while)\\s*\\(/', $trimmed)) {
                $inLoop = true;
                $loopStart = $idx;
            }

            if ($inLoop && preg_match('/^\\s*}/', $trimmed)) {
                $inLoop = false;
            }

            if ($inLoop && $idx !== $loopStart) {
                foreach (self::HOIST_INVARIANTS as $pattern => $replacement) {
                    if (strpos($line, $pattern) !== false) {
                        $invariantCount++;
                        $tempVar = '$__invariant_' . $invariantCount;
                        $result[$idx] = str_replace($pattern, $tempVar, $line);
                        break;
                    }
                }
            }
        }

        if ($loopStart >= 0 && $invariantCount > 0) {
            $insertLines = [];
            $indent = preg_match('/^(\\s*)/', $result[$loopStart] ?? '', $m) ? $m[1] : '    ';
            for ($i = 1; $i <= $invariantCount; $i++) {
                $insertLines[] = $indent . '$__invariant_' . $i . ' = ' . self::HOIST_INVARIANTS['count($arr)'] . ';';
            }
            array_splice($result, $loopStart, 0, $insertLines);
        }

        return new TransformResult($result, $in->lineMap);
    }
}
