<?php

declare(strict_types=1);

namespace Gen\Transforms\Ws;

use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * WS-07 line_join — join adjacent lines into single lines.
 *
 * Token-stream preserving (still Type-1). Finds adjacent statements and joins
 * them with a single space, removing intermediate newlines.
 *
 * params:
 *   style   (string)  'joinPairs' - join two adjacent single-line statements
 *   count   (int)     number of pairs to join (default 1)
 */
final class LineJoin implements Transform
{
    public function code(): string
    {
        return 'WS-07';
    }

    public function name(): string
    {
        return 'line_join';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $count = max(1, (int)($params['count'] ?? 1));

        $lines = $in->lines;
        $lineMap = $in->lineMap;
        $joined = 0;
        $i = 0;

        while ($i < count($lines) - 1 && $joined < $count) {
            $cur = rtrim($lines[$i]);
            $next = trim($lines[$i + 1]);

            // Only join if current line ends with semicolon and next is not empty
            if (str_ends_with($cur, ';') && $next !== '' && !str_starts_with($next, '//') && !str_starts_with($next, '/*')) {
                // Join these two lines
                $newLine = $cur . ' ' . $next;
                $lines[$i] = $newLine;
                array_splice($lines, $i + 1, 1);
                array_splice($lineMap, $i + 1, 1);
                $joined++;
            } else {
                $i++;
            }
        }

        return new TransformResult($lines, $lineMap);
    }
}
