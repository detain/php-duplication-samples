<?php

declare(strict_types=1);

namespace Gen\Transforms;

/**
 * Input to a transform: the payload region as lines, plus a line map from the
 * current output lines back to the original payload's line indices.
 */
final class TransformInput
{
    /**
     * @param list<string> $lines   one string per line (no trailing newline)
     * @param list<int>    $lineMap output-line index -> source-line index (-1 = inserted)
     */
    public function __construct(
        public array $lines,
        public array $lineMap,
    ) {
    }

    /** Identity input straight from a pristine payload. */
    public static function fromLines(array $lines): self
    {
        return new self(array_values($lines), range(0, max(0, count($lines) - 1)));
    }

    public function text(): string
    {
        return implode("\n", $this->lines);
    }
}
