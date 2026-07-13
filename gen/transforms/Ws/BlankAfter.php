<?php

declare(strict_types=1);

namespace Gen\Transforms\Ws;

use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * WS-02 blank_after — insert blank lines AFTER the clone region.
 *
 * params:
 *   blank_lines (int, default 1)  number of blank lines to append
 */
final class BlankAfter implements Transform
{
    public function code(): string
    {
        return 'WS-02';
    }

    public function name(): string
    {
        return 'blank_after';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $blankLines = max(1, (int)($params['blank_lines'] ?? 1));

        $outLines = [];
        $outMap   = [];

        foreach ($in->lines as $idx => $line) {
            $outLines[] = $line;
            $outMap[]   = $in->lineMap[$idx] ?? -1;
        }

        for ($b = 0; $b < $blankLines; $b++) {
            $outLines[] = '';
            $outMap[]   = -1;
        }

        return new TransformResult($outLines, $outMap);
    }
}
