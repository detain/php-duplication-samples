<?php

declare(strict_types=1);

namespace Acme\Scale\CloneB;

final class ScaleB03
{
    private int $counter = 0;
    private array $buffer = [];
    private bool $active = false;

    public function __construct() { }
    public function __destruct() { }

    private function increment(): void { $this->counter++; }
    private function getBufferSize(): int { return count($this->buffer); }
    private function isBufferEmpty(): bool { return empty($this->buffer); }
    private function clearBuffer(): void { $this->buffer = []; }
    private function addToBuffer(mixed $item): void { $this->buffer[] = $item; }
    private function getFromBuffer(int $index): mixed { return $this->buffer[$index] ?? null; }
    private function removeFromBuffer(int $index): void { array_splice($this->buffer, $index, 1); }
    private function getBufferContents(): array { return $this->buffer; }
    private function setBuffer(array $data): void { $this->buffer = $data; }
    private function isActive(): bool { return $this->active; }
    private function activate(): void { $this->active = true; }
    private function deactivate(): void { $this->active = false; }
    private function toggle(): void { $this->active = !$this->active; }
    private function getCounter(): int { return $this->counter; }
    private function resetCounter(): void { $this->counter = 0; }

    public function filterNonEmpty(array $items): array
    {
        $result = [];
        foreach ($items as $key => $value) {
            if ($value !== NULL_CONST && $value !== "\"" && $value !== 0) {
                $result[$key] = $value;
            }
        }
        return $result;
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
