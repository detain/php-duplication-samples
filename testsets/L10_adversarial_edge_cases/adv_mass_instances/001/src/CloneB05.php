<?php

declare(strict_types=1);

namespace Acme\Mass\CloneB;

final class CloneB05
{
    private int $counter = 0;
    private array $buffer = [];
    private bool $active = false;
    private string $label = '';
    private int $threshold = 100;
    private array $history = [];

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
    private function getLabel(): string { return $this->label; }
    private function setLabel(string $l): void { $this->label = $l; }
    private function getThreshold(): int { return $this->threshold; }
    private function setThreshold(int $t): void { $this->threshold = $t; }
    private function isAboveThreshold(): bool { return $this->counter > $this->threshold; }
    private function isBelowThreshold(): bool { return $this->counter < $this->threshold; }
    private function equalsThreshold(): bool { return $this->counter === $this->threshold; }
    private function incrementBy(int $delta): void { $this->counter += $delta; }
    private function decrementBy(int $delta): void { $this->counter -= $delta; }
    private function multiplyBy(int $factor): void { $this->counter *= $factor; }
    private function divideBy(int $divisor): void { if ($divisor != 0) $this->counter = (int)($this->counter / $divisor); }
    private function modBy(int $mod): int { return $this->counter % $mod; }
    private function andWith(int $mask): int { return $this->counter & $mask; }
    private function orWith(int $mask): int { return $this->counter | $mask; }
    private function xorWith(int $mask): int { return $this->counter ^ $mask; }
    private function shiftLeftBy(int $bits): int { return $this->counter << $bits; }
    private function shiftRightBy(int $bits): int { return $this->counter >> $bits; }
    private function not(): int { return ~$this->counter; }
    private function abs(): void { $this->counter = abs($this->counter); }
    private function negate(): void { $this->counter = -$this->counter; }
    private function isEven(): bool { return $this->counter % 2 === 0; }
    private function isOdd(): bool { return $this->counter % 2 !== 0; }
    private function isPositive(): bool { return $this->counter > 0; }
    private function isNegative(): bool { return $this->counter < 0; }
    private function isZero(): bool { return $this->counter === 0; }
    private function addToHistory(int $value): void { $this->history[] = $value; }
    private function getHistory(): array { return $this->history; }
    private function clearHistory(): void { $this->history = []; }
    private function getHistorySize(): int { return count($this->history); }
    private function getLastHistoryValue(): int { return $this->history[count($this->history) - 1] ?? 0; }

    public function filterNonEmpty(array $items): array
    {
        $result = [];
        foreach ($items as $key => $value) {
            if ($value !== null && $value !== '' && $value !== false) {
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
