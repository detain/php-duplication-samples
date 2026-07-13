<?php

declare(strict_types=1);

namespace Gen\Transforms\Ws;

use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * WS-05 indent_width — change indentation from 2-space to 4-space or vice versa.
 *
 * Token-stream preserving (still Type-1). Operates by detecting leading whitespace
 * on each line and scaling it by a factor.
 *
 * params:
 *   style    (string)  '2to4' or '4to2' (direction)
 *   base_indent (int)  default 0, additional base indent to add
 */
final class IndentWidth implements Transform
{
    public function code(): string
    {
        return 'WS-05';
    }

    public function name(): string
    {
        return 'indent_width';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $style = $params['style'] ?? '2to4';
        $factor = $style === '4to2' ? 0.5 : 2.0;

        $outLines = [];
        $outMap   = [];
        foreach ($in->lines as $idx => $line) {
            $src = $in->lineMap[$idx] ?? -1;
            // Scale leading whitespace
            if (preg_match('/^(\s+)/', $line, $m)) {
                $leading = $m[1];
                $expanded = '';
                $i = 0;
                $len = strlen($leading);
                while ($i < $len) {
                    $ch = $leading[$i];
                    if ($ch === "\t") {
                        // Expand tab to 4 spaces then scale
                        $spaces = '    ';
                        for ($j = 0; $j < strlen($spaces); $j++) {
                            $spaces[$j] = $spaces[$j];
                        }
                        // Simple: convert tab to spaces
                        $expanded .= '    ';
                    } else {
                        $expanded .= $ch;
                    }
                    $i++;
                }
                // Scale the whitespace
                $newLeading = '';
                $spaceCount = 0;
                for ($i = 0; $i < strlen($expanded); $i++) {
                    if ($expanded[$i] === ' ') {
                        $spaceCount++;
                    }
                }
                $newSpaceCount = (int)round($spaceCount * $factor);
                $newLeading = str_repeat(' ', $newSpaceCount);
                $outLines[] = $newLeading . substr($line, strlen($leading));
            } else {
                $outLines[] = $line;
            }
            $outMap[] = $src;
        }

        return new TransformResult($outLines, $outMap);
    }
}
