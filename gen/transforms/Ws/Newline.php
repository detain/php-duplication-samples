<?php

declare(strict_types=1);

namespace Gen\Transforms\Ws;

use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * WS-10 newline — change line ending style.
 *
 * params:
 *   style ('unix'|'CRLF'|'CR', default 'unix')
 *
 * Since we work line-by-line in the generator, the actual EOL char won't appear
 * in individual lines. Instead, we simulate "missing final newline" cases by
 * adding/removing a trailing empty line:
 *   - 'unix':  no trailing empty line (clean \n terminator)
 *   - 'CRLF':  add an extra empty line at end (representing CRLF as \n\n where
 *              the second \n is the "missing" one that would form \r\n)
 *   - 'CR':    same as CRLF — add extra empty line
 *
 * Token-stream preserving (best approximation for line-based generator).
 */
final class Newline implements Transform
{
    public function code(): string
    {
        return 'WS-10';
    }

    public function name(): string
    {
        return 'newline_style';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $style = $params['style'] ?? 'unix';

        $lines = $in->lines;
        $map   = $in->lineMap;

        if ($style === 'CRLF' || $style === 'CR') {
            // Append an empty "inserted" line to represent the missing EOL char.
            $lines[] = '';
            $map[]   = -1;
        }
        // 'unix' style: leave as-is (no trailing empty line).

        return new TransformResult($lines, $map);
    }
}
