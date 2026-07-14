<?php

declare(strict_types=1);

namespace Acme\Mass;

final class DistractorA
{
    private array $items = [];
    private int $position = 0;

    public function __construct() { }

    public function addItem(mixed $item): void
    {
        $this->items[] = $item;
    }

    public function filterNonEmpty(array $items): array
    {
        $filtered = [];
        foreach ($items as $index => $item) {
            if ($item !== null && $item !== '') {
                $filtered[$index] = $item;
            }
        }
        return $filtered;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function clearItems(): void
    {
        $this->items = [];
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function hasItem(mixed $item): bool
    {
        return in_array($item, $this->items, true);
    }

    public function removeItem(mixed $item): void
    {
        $this->items = array_values(array_filter($this->items, fn($i) => $i !== $item));
    }

    public function getItemAt(int $index): mixed
    {
        return $this->items[$index] ?? null;
    }

    public function setItemAt(int $index, mixed $item): void
    {
        if ($index >= 0 && $index < count($this->items)) {
            $this->items[$index] = $item;
        }
    }

    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    public function last(): mixed
    {
        return $this->items[count($this->items) - 1] ?? null;
    }

    public function push(mixed $item): void
    {
        $this->items[] = $item;
    }

    public function pop(): mixed
    {
        return array_pop($this->items);
    }

    public function shift(): mixed
    {
        return array_shift($this->items);
    }

    public function unshift(mixed $item): void
    {
        array_unshift($this->items, $item);
    }

    public function slice(int $offset, ?int $length = null): array
    {
        return array_slice($this->items, $offset, $length);
    }

    public function splice(int $offset, int $length = 0, mixed $replacement = []): array
    {
        return array_splice($this->items, $offset, $length, $replacement);
    }

    public function merge(array $other): void
    {
        $this->items = array_merge($this->items, $other);
    }

    public function reverse(): void
    {
        $this->items = array_reverse($this->items);
    }

    public function shuffle(): void
    {
        shuffle($this->items);
    }

    public function sort(): void
    {
        sort($this->items);
    }

    public function rsort(): void
    {
        rsort($this->items);
    }

    public function unique(): void
    {
        $this->items = array_values(array_unique($this->items));
    }

    public function flip(): array
    {
        return array_flip($this->items);
    }

    public function keys(): array
    {
        return array_keys($this->items);
    }

    public function values(): array
    {
        return array_values($this->items);
    }

    public function map(callable $fn): array
    {
        return array_map($fn, $this->items);
    }

    public function filter(callable $fn): array
    {
        return array_filter($this->items, $fn);
    }

    public function reduce(callable $fn, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $fn, $initial);
    }

    public function walk(callable $fn): void
    {
        array_walk($this->items, $fn);
    }

    public function find(callable $fn): mixed
    {
        foreach ($this->items as $item) {
            if ($fn($item)) {
                return $item;
            }
        }
        return null;
    }

    public function findIndex(callable $fn): int
    {
        foreach ($this->items as $index => $item) {
            if ($fn($item)) {
                return $index;
            }
        }
        return -1;
    }

    public function every(callable $fn): bool
    {
        foreach ($this->items as $item) {
            if (!$fn($item)) {
                return false;
            }
        }
        return true;
    }

    public function some(callable $fn): bool
    {
        foreach ($this->items as $item) {
            if ($fn($item)) {
                return true;
            }
        }
        return false;
    }

    public function none(callable $fn): bool
    {
        return !$this->some($fn);
    }

    public function partition(callable $fn): array
    {
        $pass = [];
        $fail = [];
        foreach ($this->items as $item) {
            if ($fn($item)) {
                $pass[] = $item;
            } else {
                $fail[] = $item;
            }
        }
        return [$pass, $fail];
    }

    public function groupBy(callable $fn): array
    {
        $groups = [];
        foreach ($this->items as $item) {
            $key = (string)$fn($item);
            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            $groups[$key][] = $item;
        }
        return $groups;
    }

    public function countBy(callable $fn): array
    {
        $counts = [];
        foreach ($this->items as $item) {
            $key = (string)$fn($item);
            if (!isset($counts[$key])) {
                $counts[$key] = 0;
            }
            $counts[$key]++;
        }
        return $counts;
    }

    public function sum(): float
    {
        return array_sum($this->items);
    }

    public function average(): float
    {
        $count = count($this->items);
        return $count > 0 ? array_sum($this->items) / $count : 0.0;
    }

    public function min(): mixed
    {
        return count($this->items) > 0 ? min($this->items) : null;
    }

    public function max(): mixed
    {
        return count($this->items) > 0 ? max($this->items) : null;
    }

    public function label(): string
    {
        return strtolower(str_replace('\\', '.', static::class));
    }
}
