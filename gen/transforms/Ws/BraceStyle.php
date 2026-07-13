<?php

declare(strict_types=1);

namespace Gen\Transforms\Ws;

use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * WS-11 brace_style — change brace position for functions (K&R ↔ Allman).
 *
 * params:
 *   style ('knr'|'allman', default 'allman')
 *     - knr:     K&R style — brace stays on same line as function signature
 *                e.g.  function foo() {
 *     - allman:  Allman style — brace moves to its own line
 *                e.g.  function foo()
 *                {
 *
 * Only handles functions. Finds lines containing `function NAME(...)` and
 * moves the `{` to its own line (allman) or joins with previous line (knr).
 *
 * Token-stream preserving (Type-1).
 */
final class BraceStyle implements Transform
{
    public function code(): string
    {
        return 'WS-11';
    }

    public function name(): string
    {
        return 'brace_style';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $style = $params['style'] ?? 'allman';

        $outLines = [];
        $outMap   = [];

        $total = count($in->lines);
        for ($i = 0; $i < $total; $i++) {
            $line = $in->lines[$i];

            if ($this->isFunctionOpen($line)) {
                if ($style === 'allman') {
                    // allman: push the brace onto a new line.
                    $braceLine = '{';
                    $outLines[] = $line;
                    $outMap[]   = $in->lineMap[$i] ?? -1;
                    $outLines[] = $braceLine;
                    $outMap[]   = -1;
                } else {
                    // knr: ensure brace is at end of the function signature line.
                    $modified = rtrim($line);
                    if (!str_ends_with($modified, '{')) {
                        $modified .= ' {';
                    }
                    $outLines[] = $modified;
                    $outMap[]   = $in->lineMap[$i] ?? -1;
                }
            } else {
                $outLines[] = $line;
                $outMap[]   = $in->lineMap[$i] ?? -1;
            }
        }

        return new TransformResult($outLines, $outMap);
    }

    /**
     * Detect a line that opens a function body with `{`.
     * Matches patterns like:
     *   function foo() {
     *   function foo()   {
     *   private static function bar() {
     */
    private function isFunctionOpen(string $line): bool
    {
        $trimmed = trim($line);
        // Must contain '{' and look like a function signature ending.
        if (!str_ends_with($trimmed, '{')) {
            return false;
        }
        // Must start with function keyword (possibly with visibility/static modifiers).
        if (!preg_match('/^(public|private|protected|static|final|abstract|#|\/\\*)?\s*function\b/i', $trimmed)) {
            return false;
        }
        return true;
    }
}
