<?php

declare(strict_types=1);

namespace Gen\Transforms\Cm;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * CM-06 text_changed — same comment slots, different prose.
 *
 * Token-stream preserving (different tokens but same structure). Finds existing
 * // comments and replaces the comment text (keeping the // prefix) with
 * different words of the same approximate length, using the rng to pick
 * replacement words from a small vocabulary list.
 *
 * params:
 *   count (int) how many comment texts to change (default 2)
 */
final class TextChanged implements Transform
{
    /** Vocabulary of replacement words for comment text. */
    private const VOCAB = [
        // 1-3 chars
        'ok', 'id', 'x', 'y', 'z', 'i', 'a', 'b', 'c', 'n', 'v',
        // 4 chars
        'init', 'item', 'data', 'proc', 'load', 'save', 'check', 'calc',
        // 5-6 chars
        'value', 'count', 'total', 'state', 'input', 'reset', 'index',
        // 7-8 chars
        'process', 'handler', 'compute', 'collect', 'extract', 'compare',
        // 9+ chars
        'calculate', 'normalize', 'aggregate', 'transform', 'serialize',
    ];

    public function code(): string
    {
        return 'CM-06';
    }

    public function name(): string
    {
        return 'comment_text_change';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $count = max(1, (int)($params['count'] ?? 2));

        $outLines = [];
        $outMap   = [];
        $changed = 0;

        foreach ($in->lines as $idx => $line) {
            $src = $in->lineMap[$idx] ?? -1;

            if ($changed < $count && $this->hasLineComment($line)) {
                $newLine = $this->replaceCommentText($line, $rng);
                if ($newLine !== $line) {
                    $changed++;
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
     * Check if a line contains a // comment.
     */
    private function hasLineComment(string $line): bool
    {
        $tokens = PhpTokens::rawTokens($line);
        foreach ($tokens as $t) {
            if (is_array($t) && $t[0] === T_COMMENT && str_starts_with($t[1], '//')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Replace the text of a // comment with a random word of similar length.
     */
    private function replaceCommentText(string $line, Rng $rng): string
    {
        $tokens = PhpTokens::rawTokens($line);
        $buf    = '';
        $changed = false;

        for ($i = 0; $i < count($tokens); $i++) {
            $t = $tokens[$i];

            if (is_string($t)) {
                $buf .= $t;
                continue;
            }

            [$id, $text] = $t;

            if ($id === T_COMMENT && str_starts_with($text, '//')) {
                $replacement = $this->pickReplacement($text, $rng);
                $buf .= $replacement;
                $changed = true;
                continue;
            }

            $buf .= $text;
        }

        return $buf;
    }

    /**
     * Pick a replacement comment text of similar length to the original.
     *
     * // comment text looks like: "// text here\n" or "// text here"
     */
    private function pickReplacement(string $commentText, Rng $rng): string
    {
        // Extract the text after "// "
        if (preg_match('#^//(.*)$#', $commentText, $m)) {
            $oldText = rtrim($m[1], " \t");
            $oldLen  = strlen($oldText);

            // Find a word of similar length from vocabulary
            $candidates = array_filter(self::VOCAB, fn($w) => abs(strlen($w) - $oldLen) <= 3);
            if ($candidates === []) {
                $candidates = self::VOCAB;
            }

            $replacement = $rng->pick(array_values($candidates));
            return '// ' . $replacement . ' ';
        }

        return $commentText;
    }
}
