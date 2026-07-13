<?php

declare(strict_types=1);

namespace Gen\Transforms;

/**
 * Output of a transform: emitted payload lines plus the updated line map.
 */
final class TransformResult
{
    /**
     * @param list<string> $lines
     * @param list<int>    $lineMap output-line index -> source-line index (-1 = inserted)
     */
    public function __construct(
        public array $lines,
        public array $lineMap,
    ) {
    }

    public function toInput(): TransformInput
    {
        return new TransformInput($this->lines, $this->lineMap);
    }

    public function text(): string
    {
        return implode("\n", $this->lines);
    }

    public function lineCount(): int
    {
        return count($this->lines);
    }
}
