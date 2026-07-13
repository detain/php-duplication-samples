<?php

declare(strict_types=1);

namespace Gen\Transforms\Ws;

use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * WS-01 blank_before — insert blank lines BEFORE the clone region.
 *
 * params:
 *   blank_lines (int, default 1)  number of blank lines to insert
 *   occurrence (int, default 1)   which gap to target (1 = before first line)
 */
final class BlankBefore implements Transform
{
    public function code(): string
    {
        return 'WS-01';
    }

    public function name(): string
    {
        return 'blank_before';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $blankLines = max(1, (int)($params['blank_lines'] ?? 1));
        $occurrence = max(1, (int)($params['occurrence'] ?? 1));

        // There is exactly one gap before the clone: between "nothing" and the
        // first line.  occurrence=1 means "the first gap" — which is always the
        // one before line 0.
        if ($occurrence !== 1 || $in->lines === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $outLines = [];
        $outMap   = [];

        for ($b = 0; $b < $blankLines; $b++) {
            $outLines[] = '';
            $outMap[]   = -1;
        }

        foreach ($in->lines as $idx => $line) {
            $outLines[] = $line;
            $outMap[]   = $in->lineMap[$idx] ?? -1;
        }

        return new TransformResult($outLines, $outMap);
    }
}
