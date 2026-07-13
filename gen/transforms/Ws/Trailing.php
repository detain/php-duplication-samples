<?php

declare(strict_types=1);

namespace Gen\Transforms\Ws;

use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * WS-09 trailing — add or remove trailing whitespace on lines.
 *
 * params:
 *   style ('add_some'|'remove_all'|'mixed', default 'add_some')
 *     - add_some:  add trailing space to ~50% of lines (random via rng)
 *     - remove_all: strip ALL trailing whitespace from every line
 *     - mixed:      ~50% add, ~50% remove
 *
 * Token-stream preserving (Type-1).
 */
final class Trailing implements Transform
{
    public function code(): string
    {
        return 'WS-09';
    }

    public function name(): string
    {
        return 'trailing_space';
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $style = $params['style'] ?? 'add_some';

        $outLines = [];
        $outMap   = [];

        foreach ($in->lines as $idx => $line) {
            $sourceIdx = $in->lineMap[$idx] ?? -1;
            $modified  = $this->transformLine($line, $style, $rng);
            $outLines[] = $modified;
            $outMap[]   = $sourceIdx;
        }

        return new TransformResult($outLines, $outMap);
    }

    private function transformLine(string $line, string $style, Rng $rng): string
    {
        return match ($style) {
            'add_some'  => $this->addSome($line, $rng),
            'remove_all' => $this->removeAll($line),
            'mixed'     => $rng->bool() ? $this->addSome($line, $rng) : $this->removeAll($line),
            default     => $this->addSome($line, $rng),
        };
    }

    private function addSome(string $line, Rng $rng): string
    {
        // Add trailing space to ~50% of lines.
        if ($rng->bool()) {
            return $line . ' ';
        }
        return $line;
    }

    private function removeAll(string $line): string
    {
        return rtrim($line);
    }
}
