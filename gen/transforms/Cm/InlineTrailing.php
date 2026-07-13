<?php

declare(strict_types=1);

namespace Gen\Transforms\Cm;

use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * CM-04 inline_trailing — add trailing comments on lines.
 *
 * Adds // or # style comments at the end of non-blank lines.
 * Token-stream preserving (Type-1 after stripping).
 *
 * params:
 *   count (int)    how many lines get trailing comments (default 2)
 *   style (string) '//' or '#' (default '//')
 */
final class InlineTrailing implements Transform
{
    private const COMMENTS = [
        ' TODO ', ' FIXME ', ' NOTE ', ' NOCOMMIT ', ' REVIEW ',
    ];

    public function code(): string
    {
        return 'CM-04';
    }

    public function name(): string
    {
        return 'inline_trailing';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $count = (int)($params['count'] ?? 2);
        $style = (string)($params['style'] ?? '//');

        $outLines = [];
        $outMap   = [];

        foreach ($in->lines as $i => $line) {
            $outLines[] = $line;
            $outMap[]   = $in->lineMap[$i] ?? -1;

            if (trim($line) !== '' && $i < $count) {
                $word = $rng->pick(self::COMMENTS);
                $outLines[] = $line . $style . $word;
                $outMap[]   = -1;
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
