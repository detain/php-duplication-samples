<?php

declare(strict_types=1);

namespace Gen\Transforms\Cm;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * CM-01 line_added — add single-line comments at random positions.
 *
 * Adds // or # style comments at safe line boundaries (after ; or { lines,
 * before non-blank). Token-stream preserving (Type-1 after stripping).
 *
 * params:
 *   count (int)  how many comment lines to add (default 1)
 *   style (string) '//' or '#' (default '//')
 */
final class LineAdded implements Transform
{
    private const RANDOM_WORDS = [
        ' TODO ', ' FIXME ', ' NOTE ', ' NOCOMMIT ', ' EXPERIMENTAL ',
        ' OPTIMIZE ', ' HACK ', ' REVIEW ', ' TEMP ', ' WIP ',
    ];

    public function code(): string
    {
        return 'CM-01';
    }

    public function name(): string
    {
        return 'line_added';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $count = (int)($params['count'] ?? 1);
        $style = (string)($params['style'] ?? '//');

        $text = $in->text();
        $safeBoundaries = PhpTokens::safeLineBoundaries($text);

        if ($safeBoundaries === [] || $count <= 0) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // Collect safe positions (1-based line boundaries)
        $safePositions = [];
        foreach ($safeBoundaries as $lineNum => $isSafe) {
            if ($isSafe) {
                $safePositions[] = $lineNum;
            }
        }

        if ($safePositions === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        // Pick random safe positions (no duplicates)
        $shuffled = $safePositions;
        shuffle($shuffled);
        $chosen = array_slice($shuffled, 0, min($count, count($shuffled)));

        // Build output lines and lineMap
        $outLines = [];
        $outMap   = [];
        $lineCount = count($in->lines);

        // selected boundaries inserted, tracking insertion offset
        $insertedAt = [];
        foreach ($chosen as $boundary) {
            // boundary is 1-based line number between line $boundary and $boundary+1
            // Lines are 0-indexed, so boundary $b means after line $b-1
            $insertedAt[$boundary] = true;
        }

        $insertCount = 0;
        for ($i = 0; $i < $lineCount; $i++) {
            $outLines[] = $in->lines[$i];
            $outMap[]   = $in->lineMap[$i] ?? -1;

            // After line $i (0-indexed), check if boundary $i+1 was chosen
            $boundaryNum = $i + 1;
            if (isset($insertedAt[$boundaryNum])) {
                $word = $rng->pick(self::RANDOM_WORDS);
                $comment = $style . $word;
                $outLines[] = $comment;
                $outMap[]   = -1;
                $insertCount++;
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
