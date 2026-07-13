<?php

declare(strict_types=1);

namespace Gen\Transforms\Ws;

use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * WS-08 tabs — convert leading spaces to tabs or vice versa.
 *
 * Token-stream preserving (still Type-1). Operates on leading whitespace,
 * converting between tabs and spaces based on the style parameter.
 *
 * params:
 *   style  (string)  'spacesToTabs' or 'tabsToSpaces'
 *   indent (int)    spaces per tab (default 4)
 */
final class Tabs implements Transform
{
    public function code(): string
    {
        return 'WS-08';
    }

    public function name(): string
    {
        return 'tabs';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $style = $params['style'] ?? 'spacesToTabs';
        $indent = (int)($params['indent'] ?? 4);

        $outLines = [];
        $outMap   = [];
        foreach ($in->lines as $idx => $line) {
            $src = $in->lineMap[$idx] ?? -1;
            if (preg_match('/^(\s+)/', $line, $m)) {
                $leading = $m[1];
                $rest = substr($line, strlen($leading));

                if ($style === 'spacesToTabs') {
                    // Convert spaces to tabs (at least indent spaces at start of line)
                    $spaceCount = strlen($leading);
                    $tabCount = intdiv($spaceCount, $indent);
                    $newLeading = str_repeat("\t", $tabCount) . str_repeat(' ', $spaceCount % $indent);
                } else {
                    // Convert tabs to spaces
                    $newLeading = '';
                    for ($i = 0; $i < strlen($leading); $i++) {
                        if ($leading[$i] === "\t") {
                            $newLeading .= str_repeat(' ', $indent);
                        } else {
                            $newLeading .= $leading[$i];
                        }
                    }
                }
                $outLines[] = $newLeading . $rest;
            } else {
                $outLines[] = $line;
            }
            $outMap[] = $src;
        }

        return new TransformResult($outLines, $outMap);
    }
}
