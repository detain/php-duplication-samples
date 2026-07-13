<?php

declare(strict_types=1);

namespace Gen\Transforms\Cm;

use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * CM-03 docblock — add a docblock on the cloned symbol.
 *
 * Comment-only (still Type-1 after comment stripping). The docblock is inserted
 * immediately before the first non-blank payload line (the symbol signature),
 * so the cloned region grows but the stripped token stream is unchanged.
 *
 * params:
 *   style   (string) 'minimal' | 'full'  (default 'minimal')
 *   summary (string) one-line summary text
 *   tags    (list<string>) extra lines for 'full' (e.g. "@param int $x count")
 */
final class Docblock implements Transform
{
    public function code(): string
    {
        return 'CM-03';
    }

    public function name(): string
    {
        return 'docblock';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $style   = (string)($params['style'] ?? 'minimal');
        $summary = (string)($params['summary'] ?? 'Compute the result for the given inputs.');
        $tags    = $params['tags'] ?? [];

        // Find the indentation of the first non-blank payload line.
        $firstIdx = 0;
        foreach ($in->lines as $i => $line) {
            if (trim($line) !== '') {
                $firstIdx = $i;
                break;
            }
        }
        preg_match('/^(\s*)/', $in->lines[$firstIdx] ?? '', $m);
        $indent = $m[1];

        if ($style === 'minimal') {
            $block = [$indent . '/** ' . $summary . ' */'];
        } else {
            $block = [$indent . '/**'];
            $block[] = $indent . ' * ' . $summary;
            if ($tags !== []) {
                $block[] = $indent . ' *';
                foreach ($tags as $tag) {
                    $block[] = $indent . ' * ' . $tag;
                }
            }
            $block[] = $indent . ' */';
        }

        $outLines = [];
        $outMap   = [];
        foreach ($in->lines as $i => $line) {
            if ($i === $firstIdx) {
                foreach ($block as $bl) {
                    $outLines[] = $bl;
                    $outMap[]   = -1;
                }
            }
            $outLines[] = $line;
            $outMap[]   = $in->lineMap[$i] ?? -1;
        }

        return new TransformResult($outLines, $outMap);
    }
}
