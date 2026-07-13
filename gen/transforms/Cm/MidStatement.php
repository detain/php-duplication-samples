<?php

declare(strict_types=1);

namespace Gen\Transforms\Cm;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * CM-08 mid_statement — comment inside wrapped call arguments.
 *
 * Token-stream preserving. Finds a multi-argument function call and adds
 * a block comment between arguments or after an argument. Only affects
 * lines with function calls; lines without calls pass through unchanged.
 *
 * params:
 *   style (string) arg_comment | between_args (default arg_comment)
 */
final class MidStatement implements Transform
{
    public function code(): string
    {
        return 'CM-08';
    }

    public function name(): string
    {
        return 'mid_statement_comment';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $style = (string)($params['style'] ?? 'arg_comment');

        $outLines = [];
        $outMap   = [];

        foreach ($in->lines as $idx => $line) {
            $src = $in->lineMap[$idx] ?? -1;

            if ($this->lineHasCall($line)) {
                $newLine = $this->injectComment($line, $rng, $style);
                if ($newLine !== null) {
                    $outLines[] = $newLine;
                    $outMap[]   = $src;
                    continue;
                }
            }

            $outLines[] = $line;
            $outMap[]   = $src;
        }

        return new TransformResult($outLines, $outMap);
    }

    /**
     * Check if a line contains a function call (T_STRING followed by open paren).
     */
    private function lineHasCall(string $line): bool
    {
        $tokens = PhpTokens::rawTokens($line);
        for ($i = 0; $i < count($tokens) - 1; $i++) {
            $t = $tokens[$i];
            $n = $tokens[$i + 1];
            if (is_array($t) && $t[0] === T_STRING && $n === '(') {
                return true;
            }
            if (is_array($t) && $t[0] === T_VARIABLE && $n === '->') {
                for ($j = $i + 2; $j < count($tokens); $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING && $tokens[$j + 1] === '(') {
                        return true;
                    }
                    if (!is_string($tokens[$j]) || $tokens[$j] === '(') {
                        break;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Inject a block comment into a line that has a function call.
     *
     * @return string|null modified line, or null if no injection possible
     */
    private function injectComment(string $line, Rng $rng, string $style): ?string
    {
        $tokens = PhpTokens::rawTokens($line);

        if ($style === 'between_args') {
            return $this->injectBetweenArgs($tokens, $rng);
        }

        return $this->injectAfterArg($tokens, $rng);
    }

    /**
     * Inject block comment after the first argument.
     */
    private function injectAfterArg(array $tokens, Rng $rng): ?string
    {
        $buf    = '';
        $depth  = 0;
        $foundArgEnd = false;
        $commentAdded = false;
        $comment = '/* ' . $rng->pick(['see note', 'temp value', 'N/A', 'reserved', 'TBD']) . ' */';

        for ($i = 0; $i < count($tokens); $i++) {
            $t = $tokens[$i];

            if (is_string($t)) {
                if ($t === '(' || $t === '[' || $t === '{') {
                    $depth++;
                    $buf .= $t;
                } elseif ($t === ')' || $t === ']' || $t === '}') {
                    $depth--;
                    $buf .= $t;
                } elseif ($t === ',' && $depth === 1 && !$foundArgEnd) {
                    $buf .= $t . ' ' . $comment;
                    $commentAdded = true;
                    $foundArgEnd = true;
                } else {
                    $buf .= $t;
                }
                continue;
            }

            [$id, $text] = $t;
            if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
                $buf .= $text;
                continue;
            }

            if ($depth === 1 && !$foundArgEnd && !$commentAdded) {
                $buf .= $text;
                $foundArgEnd = true;
            } else {
                $buf .= $text;
            }
        }

        if (!$commentAdded) {
            $buf = $this->appendCommentAfterFirstArg($tokens, $comment);
            if ($buf !== null) {
                return $buf;
            }
        }

        return $commentAdded ? $buf : null;
    }

    /**
     * Fallback: append comment after first argument by re-walking tokens.
     */
    private function appendCommentAfterFirstArg(array $tokens, string $comment): ?string
    {
        $buf   = '';
        $depth = 0;
        $i     = 0;

        while ($i < count($tokens)) {
            $t = $tokens[$i];
            if (is_string($t)) {
                if ($t === '(') {
                    $buf .= $t;
                    $depth++;
                    if ($depth === 1) {
                        $i++;
                        break;
                    }
                } elseif ($t === ')' && $depth > 0) {
                    $depth--;
                }
                $buf .= $t;
                $i++;
                continue;
            }
            [$id, $text] = $t;
            if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
                $buf .= $text;
                $i++;
                continue;
            }
            $buf .= $text;
            $i++;
        }

        while ($i < count($tokens)) {
            $t = $tokens[$i];
            if (is_string($t)) {
                if ($t === '[' || $t === '{') {
                    $buf .= $t;
                    $depth++;
                } elseif ($t === ']' || $t === '}') {
                    $depth--;
                    $buf .= $t;
                } elseif ($t === ',' && $depth === 0) {
                    $buf .= $t . ' ' . $comment;
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        $tj = $tokens[$j];
                        $buf .= is_string($tj) ? $tj : $tj[1];
                    }
                    return $buf;
                } else {
                    $buf .= $t;
                }
                $i++;
                continue;
            }
            [$id, $text] = $t;
            if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
                $buf .= $text;
                $i++;
                continue;
            }
            $buf .= $text;
            $i++;
        }

        return null;
    }

    /**
     * Inject block comment between the first two arguments.
     */
    private function injectBetweenArgs(array $tokens, Rng $rng): ?string
    {
        $buf   = '';
        $depth = 0;
        $commentAdded = false;
        $comment = '/* ' . $rng->pick(['see note', 'temp', 'N/A', 'reserved', 'TBD', 'skip']) . ' */';

        for ($i = 0; $i < count($tokens); $i++) {
            $t = $tokens[$i];

            if (is_string($t)) {
                if ($t === '(' || $t === '[' || $t === '{') {
                    $depth++;
                    $buf .= $t;
                } elseif ($t === ')' || $t === ']' || $t === '}') {
                    $depth--;
                    $buf .= $t;
                } elseif ($t === ',' && $depth === 1 && !$commentAdded) {
                    $buf .= $t . ' ' . $comment;
                    $commentAdded = true;
                } else {
                    $buf .= $t;
                }
                continue;
            }

            [$id, $text] = $t;
            if ($id === T_WHITESPACE || $id === T_COMMENT || $id === T_DOC_COMMENT) {
                $buf .= $text;
                continue;
            }

            $buf .= $text;
        }

        return $commentAdded ? $buf : null;
    }
}
