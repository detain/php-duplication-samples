<?php

declare(strict_types=1);

namespace Gen\Transforms\Cm;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * CM-02 block_added — add a multi-line block comment inside the clone.
 *
 * Inserts a block comment at a safe boundary point. Token-stream preserving.
 *
 * params:
 *   lines (int)  number of comment lines in block (default 3)
 */
final class BlockAdded implements Transform
{
    private const BLOCK_TEXTS = [
        ' Internal note ', ' Legacy hook ', ' Temporary workaround ',
        ' Debug section ', ' Performance logging ', ' Feature flag ',
        ' Retry handler ', ' Cache layer ', ' Validation stub ',
    ];

    public function code(): string
    {
        return 'CM-02';
    }

    public function name(): string
    {
        return 'block_added';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $lines = (int)($params['lines'] ?? 3);

        $text = $in->text();
        $safeBoundaries = PhpTokens::safeLineBoundaries($text);

        if ($safeBoundaries === [] || $lines <= 0) {
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

        // Pick one random safe position
        $boundary = $rng->pick($safePositions);

        // Build output lines and lineMap
        $outLines = [];
        $outMap   = [];
        $lineCount = count($in->lines);

        // Determine indentation from first non-blank line
        $indent = '';
        foreach ($in->lines as $line) {
            if (trim($line) !== '') {
                preg_match('/^(\s*)/', $line, $m);
                $indent = $m[1];
                break;
            }
        }

        // Build block comment
        $baseText = $rng->pick(self::BLOCK_TEXTS);
        $blockLines = [$indent . '/*' . $baseText];
        for ($i = 1; $i < $lines - 1; $i++) {
            $blockLines[] = $indent . ' *';
        }
        $blockLines[] = $indent . ' */';

        // Insert block at boundary
        for ($i = 0; $i < $lineCount; $i++) {
            if ($i === $boundary - 1) {
                // After line $i (which is line number $boundary - 1 + 1 = $boundary)
                // Insert block
                foreach ($blockLines as $bl) {
                    $outLines[] = $bl;
                    $outMap[]   = -1;
                }
            }
            $outLines[] = $in->lines[$i];
            $outMap[]   = $in->lineMap[$i] ?? -1;
        }

        // If boundary is at the end (after last line), insert block there
        if ($boundary > $lineCount) {
            foreach ($blockLines as $bl) {
                $outLines[] = $bl;
                $outMap[]   = -1;
            }
        }

        return new TransformResult($outLines, $outMap);
    }
}
