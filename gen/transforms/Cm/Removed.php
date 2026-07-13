<?php

declare(strict_types=1);

namespace Gen\Transforms\Cm;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * CM-05 removed — strip comments from the payload.
 *
 * Token-stream preserving (still Type-1 after comment stripping). Uses
 * PhpTokens::rawTokens to walk the token stream safely without ever
 * splitting inside a string literal, heredoc, or multi-line comment.
 *
 * params:
 *   style (string) strip_all | strip_trailing | strip_docblock (default strip_all)
 */
final class Removed implements Transform
{
    public function code(): string
    {
        return 'CM-05';
    }

    public function name(): string
    {
        return 'comment_remove';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $style = (string)($params['style'] ?? 'strip_all');

        $outLines = [];
        $outMap   = [];

        foreach ($in->lines as $idx => $line) {
            $src = $in->lineMap[$idx] ?? -1;
            $stripped = $this->stripLine($line, $style);
            $outLines[] = $stripped;
            $outMap[]   = $src;
        }

        return new TransformResult($outLines, $outMap);
    }

    /**
     * Strip comments from a single line according to the given style.
     */
    private function stripLine(string $line, string $style): string
    {
        $tokens = PhpTokens::rawTokens($line);

        if ($style === 'strip_trailing') {
            return $this->stripTrailing($tokens);
        }

        if ($style === 'strip_docblock') {
            return $this->stripDocblock($tokens);
        }

        // strip_all: remove all T_COMMENT, T_DOC_COMMENT, and T_WHITESPACE
        return $this->stripAll($tokens);
    }

    /**
     * strip_all — remove all comment and whitespace tokens.
     * Multi-line block comments are replaced with newlines to preserve line count.
     */
    private function stripAll(array $tokens): string
    {
        $buf = '';
        $openComment = false;

        for ($i = 0; $i < count($tokens); $i++) {
            $t = $tokens[$i];

            if ($openComment) {
                if (is_array($t) && ($t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT)) {
                    $buf .= str_repeat("\n", substr_count($t[1], "\n"));
                    $openComment = false;
                }
                continue;
            }

            if (is_string($t)) {
                if ($t === '/*') {
                    $openComment = true;
                    continue;
                }
                $buf .= $t;
                continue;
            }

            [$id, $text] = $t;
            if ($id === T_COMMENT || $id === T_DOC_COMMENT) {
                if (str_starts_with($text, '/*')) {
                    $buf .= str_repeat("\n", substr_count($text, "\n"));
                    $openComment = true;
                }
                continue;
            }
            if ($id === T_WHITESPACE) {
                continue;
            }
            $buf .= $text;
        }

        return $buf;
    }

    /**
     * strip_trailing — remove only trailing slash-slash comments, preserve whitespace.
     * Keeps everything before the first // on each line.
     */
    private function stripTrailing(array $tokens): string
    {
        $buf = '';
        $skipRest = false;

        for ($i = 0; $i < count($tokens); $i++) {
            $t = $tokens[$i];

            if ($skipRest) {
                if (is_array($t) && ($t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT)) {
                    if (!str_starts_with($t[1], '/*')) {
                        $skipRest = false;
                    }
                } elseif ($t === '*/') {
                    $skipRest = false;
                }
                continue;
            }

            if (is_string($t)) {
                if ($t === '/*') {
                    $skipRest = true;
                    continue;
                }
                $buf .= $t;
                continue;
            }

            [$id, $text] = $t;
            if ($id === T_COMMENT || $id === T_DOC_COMMENT) {
                if (str_starts_with($text, '//')) {
                    return $this->rtrim($buf);
                }
                if (str_starts_with($text, '/*')) {
                    continue;
                }
                continue;
            }
            $buf .= $text;
        }

        return $this->rtrim($buf);
    }

    /**
     * strip_docblock — remove only docblock star-comment tokens.
     * Single-line docblocks are replaced with spaces to preserve line alignment.
     */
    private function stripDocblock(array $tokens): string
    {
        $buf = '';

        for ($i = 0; $i < count($tokens); $i++) {
            $t = $tokens[$i];

            if (is_array($t) && $t[0] === T_DOC_COMMENT) {
                $docblock = $t[1];
                $lines = substr_count($docblock, "\n");
                if ($lines > 0) {
                    $buf .= str_repeat("\n", $lines);
                }
                $buf .= str_repeat(' ', strlen($docblock));
                continue;
            }

            if (is_string($t)) {
                $buf .= $t;
                continue;
            }

            [$id, $text] = $t;
            if ($id === T_COMMENT && str_starts_with($text, '/*')) {
                continue;
            }
            $buf .= $text;
        }

        return $buf;
    }

    private function rtrim(string $s): string
    {
        return rtrim($s, " \t");
    }
}
