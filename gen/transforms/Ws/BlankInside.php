<?php

declare(strict_types=1);

namespace Gen\Transforms\Ws;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * WS-03 blank_inside — insert blank lines between statements INSIDE the clone.
 *
 * Token-stream preserving (still Type-1). Blanks are only inserted at safe line
 * boundaries (never inside a string literal / heredoc / multi-line comment) and
 * only between real statements (after a line ending in ';' or '{').
 *
 * params:
 *   blank_lines (int, default 1)  blank lines to insert at each chosen point
 *   points      (int, default 1)  how many insertion points to use (spread evenly)
 */
final class BlankInside implements Transform
{
    public function code(): string
    {
        return 'WS-03';
    }

    public function name(): string
    {
        return 'blank_inside';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $blankLines = max(1, (int)($params['blank_lines'] ?? 1));
        $points     = max(1, (int)($params['points'] ?? 1));

        $lines = $in->lines;
        $text  = $in->text();
        $safe  = PhpTokens::safeLineBoundaries($text); // keys 1..N-1

        // Candidate boundaries: after a statement/brace line, before a non-blank line.
        $candidates = [];
        foreach ($safe as $i => $isSafe) {
            if (!$isSafe) {
                continue;
            }
            $cur  = rtrim($lines[$i - 1]);
            $next = $lines[$i] ?? '';
            $curEnds = str_ends_with($cur, ';') || str_ends_with($cur, '{');
            if ($curEnds && trim($cur) !== '' && trim($next) !== '') {
                $candidates[] = $i;
            }
        }

        if ($candidates === []) {
            return new TransformResult($in->lines, $in->lineMap);
        }

        $chosen = $this->spread($candidates, $points);

        $outLines = [];
        $outMap   = [];
        foreach ($lines as $idx => $line) {
            $outLines[] = $line;
            $outMap[]   = $in->lineMap[$idx] ?? -1;
            $boundary = $idx + 1; // boundary AFTER 1-based line ($idx+1)
            if (in_array($boundary, $chosen, true)) {
                for ($b = 0; $b < $blankLines; $b++) {
                    $outLines[] = '';
                    $outMap[]   = -1;
                }
            }
        }

        return new TransformResult($outLines, $outMap);
    }

    /**
     * Evenly pick $count boundaries from $candidates (deterministic).
     *
     * @param list<int> $candidates
     * @return list<int>
     */
    private function spread(array $candidates, int $count): array
    {
        $n = count($candidates);
        if ($count >= $n) {
            return $candidates;
        }
        $chosen = [];
        for ($k = 0; $k < $count; $k++) {
            $pos = (int)floor(($k + 1) * $n / ($count + 1));
            $pos = max(0, min($n - 1, $pos));
            $chosen[$candidates[$pos]] = true;
        }
        $keys = array_keys($chosen);
        sort($keys);
        return $keys;
    }
}
