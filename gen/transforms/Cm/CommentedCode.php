<?php

declare(strict_types=1);

namespace Gen\Transforms\Cm;

use Gen\Lib\PhpTokens;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * CM-07 commented_code — inject commented-out code inside the clone.
 *
 * Token-stream preserving. Inserts // if (false) { ... } style commented
 * code blocks at safe boundary points (after opening braces, or on their
 * own line). The commented code looks plausible but is inert.
 *
 * params:
 *   lines (int) number of commented code lines to inject (default 2)
 */
final class CommentedCode implements Transform
{
    /** Plausible-looking commented-out code snippets. */
    private const SNIPPETS = [
        '$sum += $item[\'price\'];',
        '$result = compute($value);',
        'if ($debug) { log_debug($msg); }',
        'return array_filter($data, $fn);',
        '$count = count($items);',
        'foreach ($list as $el) { $acc += $el; }',
        '// $tmp = $a + $b;',
        '// $data = prepare($input);',
        '$total = array_sum($prices);',
        '// $idx = find($key, $arr);',
    ];

    public function code(): string
    {
        return 'CM-07';
    }

    public function name(): string
    {
        return 'commented_out_code';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $lines = max(1, min(6, (int)($params['lines'] ?? 2)));

        $outLines = [];
        $outMap   = [];

        foreach ($in->lines as $idx => $line) {
            $src = $in->lineMap[$idx] ?? -1;

            // Insert commented code after a line ending with '{' or ';'
            $trimmed = rtrim($line);
            if (str_ends_with($trimmed, '{') || str_ends_with($trimmed, ';')) {
                $outLines[] = $line;
                $outMap[]   = $src;

                // Insert $lines of commented code, indented to match
                $indent = $this->detectIndent($line);
                for ($i = 0; $i < $lines; $i++) {
                    $snippet = $rng->pick(self::SNIPPETS);
                    $outLines[] = $indent . '// ' . $snippet;
                    $outMap[]   = -1; // inserted line
                }
            } else {
                $outLines[] = $line;
                $outMap[]   = $src;
            }
        }

        return new TransformResult($outLines, $outMap);
    }

    /**
     * Detect the leading indentation of a line.
     */
    private function detectIndent(string $line): string
    {
        if (preg_match('/^(\s+)/', $line, $m)) {
            return $m[1];
        }
        return '';
    }
}
