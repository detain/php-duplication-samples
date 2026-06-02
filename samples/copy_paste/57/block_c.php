<?php

declare(strict_types=1);

namespace App\Collections;

class LazyCollection implements \IteratorAggregate, \Countable
{
    protected ?array $cached = null;
    protected int $position = 0;

    public function __construct(protected iterable $source)
    {
    }

    public static function make(iterable $source): self
    {
        return new self($source);
    }

    public function all(): array
    {
        if ($this->cached === null) {
            $this->cached = $this->resolve();
        }

        return $this->cached;
    }

    protected function resolve(): array
    {
        if (is_array($this->source)) {
            return $this->source;
        }

        $result = [];
        foreach ($this->source as $item) {
            $result[] = $item;
        }

        return $result;
    }

    public function count(): int
    {
        return count($this->all());
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->all());
    }

    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->all()));
    }

    public function filter(callable $callback = null): self
    {
        if ($callback === null) {
            return new self(array_filter($this->all()));
        }

        return new self(array_filter($this->all(), $callback));
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->all(), $callback, $initial);
    }

    public function find(callable $callback): mixed
    {
        foreach ($this->all() as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    public function contains(mixed $value): bool
    {
        return in_array($value, $this->all(), true);
    }

    public function first(): mixed
    {
        $items = $this->all();

        if (empty($items)) {
            return null;
        }

        return reset($items);
    }

    public function last(): mixed
    {
        $items = $this->all();

        if (empty($items)) {
            return null;
        }

        return end($items);
    }

    public function take(int $count): self
    {
        return new self(array_slice($this->all(), 0, $count));
    }

    public function skip(int $count): self
    {
        return new self(array_slice($this->all(), $count));
    }

    public function unique(): self
    {
        return new self(array_values(array_unique($this->all(), SORT_REGULAR)));
    }

    public function flatten(): self
    {
        $result = [];

        foreach ($this->all() as $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $result[] = $item;
                }
            } else {
                $result[] = $value;
            }
        }

        return new self($result);
    }

    public function groupBy(string $key): self
    {
        $result = [];

        foreach ($this->all() as $item) {
            $groupKey = null;

            if (is_array($item) && isset($item[$key])) {
                $groupKey = $item[$key];
            } elseif (is_object($item) && isset($item->$key)) {
                $groupKey = $item->$key;
            }

            if ($groupKey !== null) {
                $result[$groupKey][] = $item;
            }
        }

        return new self($result);
    }

    public function pluck(string $key): self
    {
        $result = [];

        foreach ($this->all() as $item) {
            if (is_array($item) && isset($item[$key])) {
                $result[] = $item[$key];
            } elseif (is_object($item) && isset($item->$key)) {
                $result[] = $item->$key;
            }
        }

        return new self($result);
    }

    public function keyBy(string $key): self
    {
        $result = [];

        foreach ($this->all() as $item) {
            if (is_array($item) && isset($item[$key])) {
                $result[$item[$key]] = $item;
            } elseif (is_object($item) && isset($item->$key)) {
                $result[$item->$key] = $item;
            }
        }

        return new self($result);
    }

    public function isEmpty(): bool
    {
        return empty($this->all());
    }

    public function isNotEmpty(): bool
    {
        return !empty($this->all());
    }

    public function each(callable $callback): self
    {
        foreach ($this->all() as $key => $item) {
            $callback($item, $key);
        }

        return $this;
    }

    public function pipe(callable $callback): mixed
    {
        return $callback($this);
    }

    public function tap(callable $callback): self
    {
        $callback($this);
        return $this;
    }

    public function chunk(int $size): self
    {
        $chunks = [];

        foreach (array_chunk($this->all(), $size) as $chunk) {
            $chunks[] = new self($chunk);
        }

        return new self($chunks);
    }

    public function reverse(): self
    {
        return new self(array_reverse($this->all()));
    }

    public function sort(callable $callback = null): self
    {
        $items = $this->all();

        if ($callback !== null) {
            usort($items, $callback);
        } else {
            sort($items);
        }

        return new self($items);
    }

    public function sortBy(string $key, string $direction = 'asc'): self
    {
        $items = $this->all();

        usort($items, function ($a, $b) use ($key, $direction) {
            $aValue = is_array($a) ? ($a[$key] ?? null) : ($a->$key ?? null);
            $bValue = is_array($b) ? ($b[$key] ?? null) : ($b->$key ?? null);

            $comparison = $aValue <=> $bValue;

            return $direction === 'desc' ? -$comparison : $comparison;
        });

        return new self($items);
    }
}
