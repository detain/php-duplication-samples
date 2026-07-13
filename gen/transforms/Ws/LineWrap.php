<?php

declare(strict_types=1);

namespace Gen\Transforms\Ws;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * WS-06 line_wrap — break one statement's call-argument list across lines.
 *
 * Token-stream preserving (still Type-1). Operates line-by-line on a single
 * statement, splitting the outermost call's argument list at top-level commas
 * (never inside a nested call, array, or string). The 'args' style is the
 * common re-wrap the request centers on; 'method_chain'/'args_and_arrays'
 * currently fall back to 'args'.
 *
 * params:
 *   style      (string)  informational; all styles use arg-wrapping
 *   occurrence (int, default 1)  which wrappable statement line to target
 */
final class LineWrap implements Transform
{
    public function code(): string
    {
        return 'WS-06';
    }

    public function name(): string
    {
        return 'line_wrap';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $occurrence = max(1, (int)($params['occurrence'] ?? 1));

        $seen = 0;
        $outLines = [];
        $outMap   = [];
        foreach ($in->lines as $idx => $line) {
            $src = $in->lineMap[$idx] ?? -1;
            $wrapped = null;
            if (str_ends_with(rtrim($line), ';')) {
                $candidate = $this->wrap($line);
                if ($candidate !== null) {
                    $seen++;
                    if ($seen === $occurrence) {
                        $wrapped = $candidate;
                    }
                }
            }
            if ($wrapped === null) {
                $outLines[] = $line;
                $outMap[]   = $src;
            } else {
                foreach ($wrapped as $wl) {
                    $outLines[] = $wl;
                    $outMap[]   = $src;
                }
            }
        }

        return new TransformResult($outLines, $outMap);
    }

    /**
     * Wrap the outermost call's argument list of a single statement line.
     *
     * @return list<string>|null null if the line has no wrappable call
     */
    private function wrap(string $line): ?array
    {
        preg_match('/^(\s*)/', $line, $m);
        $baseIndent = $m[1];
        $argIndent  = $baseIndent . '    ';

        $tokens = PhpTokens::rawTokens($line);
        $depth = 0;
        $callOpen = null;   // token index of the call's '('
        $prevMeaningful = null;

        for ($i = 0; $i < count($tokens); $i++) {
            $t = $tokens[$i];
            $isParen = ($t === '(');
            $isClose = ($t === ')');
            if (is_string($t)) {
                if ($t === '(' || $t === '[' || $t === '{') {
                    if ($isParen && $depth === 0 && $callOpen === null && $this->isCall($prevMeaningful)) {
                        $callOpen = $i;
                    }
                    $depth++;
                } elseif ($t === ')' || $t === ']' || $t === '}') {
                    $depth--;
                }
                $prevMeaningful = $t;
                continue;
            }
            if ($t[0] === T_WHITESPACE || $t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT) {
                continue;
            }
            $prevMeaningful = $t;
        }

        if ($callOpen === null) {
            return null;
        }

        // Find matching ')'
        $depth = 0;
        $callClose = null;
        for ($i = $callOpen; $i < count($tokens); $i++) {
            $t = $tokens[$i];
            if ($t === '(' || $t === '[' || $t === '{') {
                $depth++;
            } elseif ($t === ')' || $t === ']' || $t === '}') {
                $depth--;
                if ($depth === 0) {
                    $callClose = $i;
                    break;
                }
            }
        }
        if ($callClose === null) {
            return null;
        }

        // Split inner tokens (callOpen+1 .. callClose-1) at top-level commas.
        $args = [];
        $buf = '';
        $depth = 0;
        for ($i = $callOpen + 1; $i < $callClose; $i++) {
            $t = $tokens[$i];
            $txt = is_string($t) ? $t : $t[1];
            if (is_string($t)) {
                if ($t === '(' || $t === '[' || $t === '{') {
                    $depth++;
                } elseif ($t === ')' || $t === ']' || $t === '}') {
                    $depth--;
                } elseif ($t === ',' && $depth === 0) {
                    $args[] = trim($buf);
                    $buf = '';
                    continue;
                }
            }
            $buf .= $txt;
        }
        if (trim($buf) !== '') {
            $args[] = trim($buf);
        }

        if (count($args) < 2) {
            return null; // nothing meaningful to wrap
        }

        $prefix = '';
        for ($i = 0; $i <= $callOpen; $i++) {
            $prefix .= is_string($tokens[$i]) ? $tokens[$i] : $tokens[$i][1];
        }
        $suffix = '';
        for ($i = $callClose + 1; $i < count($tokens); $i++) {
            $suffix .= is_string($tokens[$i]) ? $tokens[$i] : $tokens[$i][1];
        }

        $out = [rtrim($prefix)];
        $last = count($args) - 1;
        foreach ($args as $k => $arg) {
            $out[] = $argIndent . $arg . ($k === $last ? '' : ',');
        }
        $out[] = $baseIndent . ')' . $suffix;

        return $out;
    }

    private function isCall(mixed $prev): bool
    {
        if ($prev === ')' || $prev === ']') {
            return true;
        }
        if (is_array($prev)) {
            return $prev[0] === T_STRING || $prev[0] === T_VARIABLE;
        }
        return false;
    }
}
