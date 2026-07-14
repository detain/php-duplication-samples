<?php

declare(strict_types=1);

namespace Acme\Scale\CloneB;

final class ScaleB02
{
    private int $counter = 0;
    private array $buffer = [];

    public function __construct() { }

    private function increment(): void { $this->counter++; }
    private function getBufferSize(): int { return count($this->buffer); }
    private function isBufferEmpty(): bool { return empty($this->buffer); }
    private function clearBuffer(): void { $this->buffer = []; }
    private function addToBuffer(mixed $item): void { $this->buffer[] = $item; }
    private function getFromBuffer(int $index): mixed { return $this->buffer[$index] ?? null; }
    private function removeFromBuffer(int $index): void { array_splice($this->buffer, $index, 1); }
    private function getBufferContents(): array { return $this->buffer; }
    private function setBuffer(array $data): void { $this->buffer = $data; }

    public function filterNonEmpty(array $data): array
    {
        $output = [];
        foreach ($data as $k => $v) {
            if ($v !== null && $v !== '' && $v !== false) {
                $output[$k] = $v;
            }
        }
        return $output;
    }

    public function label(): string
    {
        return strtolower(str_replace('\\', '.', static::class));
    }

    private function withinBounds(int $value, int $floor, int $ceiling): bool
    {
        return $value >= $floor && $value <= $ceiling;
    }
}
