<?php

declare(strict_types=1);

namespace Gen\Transforms\Cm;

use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * CM-10 annotations — @param/@return annotations.
 *
 * Adds or modifies docblock annotations on the cloned symbol.
 * Token-stream preserving (Type-1 after stripping).
 *
 * params:
 *   params  (list<string>) @param annotations
 *   returns (string)       @return annotation
 */
final class Annotations implements Transform
{
    public function code(): string
    {
        return 'CM-10';
    }

    public function name(): string
    {
        return 'annotations';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $paramTags = $params['params'] ?? [];
        $returnTag = $params['returns'] ?? '@return void';

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

        $block = [$indent . '/**'];
        foreach ($paramTags as $p) {
            $block[] = $indent . ' * @param ' . $p;
        }
        $block[] = $indent . ' * ' . $returnTag;
        $block[] = $indent . ' */';

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
