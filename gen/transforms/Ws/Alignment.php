<?php

declare(strict_types=1);

namespace Gen\Transforms\Ws;

use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * WS-12 alignment — align assignments vertically using spaces.
 *
 * Token-stream preserving (still Type-1). Finds lines with '=' and aligns
 * them by adding spaces after the operator.
 *
 * params:
 *   style  (string)  'alignEquals' - align equals signs across multiple lines
 *   columns (int)    target column position (default 40)
 */
final class Alignment implements Transform
{
    public function code(): string
    {
        return 'WS-12';
    }

    public function name(): string
    {
        return 'alignment';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $columns = max(20, (int)($params['columns'] ?? 40));

        $lines = $in->lines;
        $lineMap = $in->lineMap;

        // Find all lines with assignments
        $assignLines = [];
        for ($i = 0; $i < count($lines); $i++) {
            $line = rtrim($lines[$i]);
            // Look for simple assignments: $var = value
            if (preg_match('/^(\s*)(\$\w+)\s*=\s*/', $line, $m)) {
                $assignLines[] = ['index' => $i, 'var' => $m[2], 'line' => $line];
            }
        }

        // If we have multiple assignments, find max position and align
        if (count($assignLines) >= 2) {
            $maxPos = 0;
            foreach ($assignLines as $al) {
                // Find position where '=' should be
                if (preg_match('/^(\s*)(\$\w+)\s*=\s*/', $al['line'], $m)) {
                    $varLen = strlen($m[2]);
                    $pos = strlen($m[1]) + $varLen;
                    $maxPos = max($maxPos, $pos);
                }
            }

            $targetPos = max($maxPos + 2, $columns);

            foreach ($assignLines as $al) {
                if (preg_match('/^(\s*)(\$\w+)\s*=\s*(.*)/', $lines[$al['index']], $m)) {
                    $leading = $m[1];
                    $var = $m[2];
                    $value = $m[3];
                    $varEndPos = strlen($leading) + strlen($var);
                    $padding = $targetPos - $varEndPos;
                    if ($padding > 0) {
                        $lines[$al['index']] = $leading . $var . str_repeat(' ', $padding) . '= ' . $value;
                    }
                }
            }
        }

        return new TransformResult($lines, $lineMap);
    }
}
