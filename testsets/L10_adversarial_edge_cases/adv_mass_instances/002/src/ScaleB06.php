<?php

declare(strict_types=1);

namespace Acme\Scale\CloneB;

use RuntimeException;

final class ScaleB06
{
    private int $counter = 0;
    private array $buffer = [];
    private bool $active = false;
    private string $label = '';
    private int $threshold = 100;
    private array $history = [];
    private float $metric = 0.0;

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
    private function getMetric(): float { return $this->metric; }
    private function setMetric(float $m): void { $this->metric = $m; }
    private function addMetric(float $delta): void { $this->metric += $delta; }
    private function isMetricPositive(): bool { return $this->metric > 0; }
    private function resetMetric(): void { $this->metric = 0.0; }
    private function multiplyMetric(float $factor): void { $this->metric *= $factor; }
    private function divideMetric(float $divisor): void { if ($divisor != 0.0) $this->metric /= $divisor; }
    private function sqrtMetric(): void { $this->metric = sqrt($this->metric); }
    private function absMetric(): void { $this->metric = abs($this->metric); }
    private function negateMetric(): void { $this->metric = -$this->metric; }
    private function roundMetric(int $precision = 0): void { $this->metric = round($this->metric, $precision); }
    private function floorMetric(): void { $this->metric = floor($this->metric); }
    private function ceilMetric(): void { $this->metric = ceil($this->metric); }
    private function metricToInt(): int { return (int)$this->metric; }
    private function metricToString(): string { return (string)$this->metric; }
    private function metricEquals(float $other): bool { return abs($this->metric - $other) < 0.0001; }
    private function metricGreater(float $other): bool { return $this->metric > $other; }
    private function metricLess(float $other): bool { return $this->metric < $other; }
    private function metricBetween(float $a, float $b): bool { return $a <= $this->metric && $this->metric <= $b; }
    private function clampMetric(float $min, float $max): void { $this->metric = max($min, min($max, $this->metric)); }
    private function getMetricPercent(): float { return $this->metric * 100.0; }
    private function getMetricRatio(): float { return $this->counter > 0 ? $this->metric / $this->counter : 0.0; }
    private function getCombinedMetric(): float { return $this->metric + $this->counter; }
    private function getNormalizedMetric(float $max): float { return $max > 0 ? $this->metric / $max : 0.0; }
    private function invertMetric(): void { $this->metric = -$this->metric; }
    private function scaleMetric(float $factor): void { $this->metric *= $factor; }
    private function addToBufferIf(mixed $item, bool $condition): void { if ($condition) $this->buffer[] = $item; }
    private function removeFromBufferWhere(callable $predicate): void { $this->buffer = array_values(array_filter($this->buffer, $predicate)); }
    private function getBufferSum(): float { return array_sum($this->buffer); }
    private function getBufferAverage(): float { $count = count($this->buffer); return $count > 0 ? array_sum($this->buffer) / $count : 0.0; }
    private function getBufferMax(): mixed { return count($this->buffer) > 0 ? max($this->buffer) : null; }
    private function getBufferMin(): mixed { return count($this->buffer) > 0 ? min($this->buffer) : null; }
    private function bufferContains(mixed $item): bool { return in_array($item, $this->buffer, true); }
    private function getBufferIndexOf(mixed $item): int { return array_search($item, $this->buffer, true); }
    private function bufferMap(callable $fn): array { return array_map($fn, $this->buffer); }
    private function bufferFilter(callable $fn): array { return array_filter($this->buffer, $fn); }
    private function bufferReduce(callable $fn, mixed $initial): mixed { return array_reduce($this->buffer, $fn, $initial); }
    private function bufferFlip(): array { return array_flip($this->buffer); }
    private function bufferUnique(): array { return array_unique($this->buffer); }
    private function bufferCountUnique(): int { return count(array_unique($this->buffer)); }
    private function bufferReverse(): array { return array_reverse($this->buffer); }
    private function bufferShuffle(): void { shuffle($this->buffer); }
    private function bufferSlice(int $offset, ?int $length = null): array { return array_slice($this->buffer, $offset, $length); }
    private function bufferChunk(int $size): array { return array_chunk($this->buffer, $size); }
    private function bufferMerge(array $other): void { $this->buffer = array_merge($this->buffer, $other); }
    private function bufferIntersect(array $other): void { $this->buffer = array_intersect($this->buffer, $other); }
    private function bufferDiff(array $other): void { $this->buffer = array_diff($this->buffer, $other); }
    private function bufferPad(int $size, mixed $value): void { $this->buffer = array_pad($this->buffer, $size, $value); }
    private function bufferFill(int $start, int $num, mixed $value): void { $this->buffer = array_fill($start, $num, $value); }
    private function bufferKeys(): array { return array_keys($this->buffer); }
    private function bufferValues(): array { return array_values($this->buffer); }
    private function bufferIsEmpty(): bool { return empty($this->buffer); }
    private function bufferIsNotEmpty(): bool { return !empty($this->buffer); }
    private function bufferCount(): int { return count($this->buffer); }

    /** Compute the result for the given inputs. */
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
