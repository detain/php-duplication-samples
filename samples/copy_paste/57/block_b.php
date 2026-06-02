<?php

declare(strict_types=1);

namespace App\Collections;

class Collection implements \IteratorAggregate, \Countable, \ArrayAccess
{
    protected array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public static function make(array $items = []): self
    {
        return new self($items);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    public function toArray(): array
    {
        return array_map(function ($item) {
            return $item instanceof self ? $item->toArray() : $item;
        }, $this->items);
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function first(): mixed
    {
        if (empty($this->items)) {
            return null;
        }

        return reset($this->items);
    }

    public function last(): mixed
    {
        if (empty($this->items)) {
            return null;
        }

        return end($this->items);
    }

    public function firstKey(): mixed
    {
        if (empty($this->items)) {
            return null;
        }

        reset($this->items);
        return key($this->items);
    }

    public function lastKey(): mixed
    {
        if (empty($this->items)) {
            return null;
        }

        end($this->items);
        return key($this->items);
    }

    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->items));
    }

    public function filter(callable $callback = null): self
    {
        if ($callback === null) {
            return new self(array_filter($this->items));
        }

        return new self(array_filter($this->items, $callback));
    }

    public function reject(callable $callback): self
    {
        return $this->filter(fn($item) => !$callback($item));
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    public function find(callable $callback): mixed
    {
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    public function findKey(callable $callback): mixed
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) {
                return $key;
            }
        }

        return null;
    }

    public function contains(mixed $value): bool
    {
        return in_array($value, $this->items, true);
    }

    public function pluck(string $key): self
    {
        $result = [];

        foreach ($this->items as $item) {
            if (is_array($item) && isset($item[$key])) {
                $result[] = $item[$key];
            } elseif (is_object($item) && isset($item->$key)) {
                $result[] = $item->$key;
            }
        }

        return new self($result);
    }

    public function groupBy(string $key): self
    {
        $result = [];

        foreach ($this->items as $item) {
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

    public function indexBy(string $key): self
    {
        $result = [];

        foreach ($this->items as $item) {
            if (is_array($item) && isset($item[$key])) {
                $result[$item[$key]] = $item;
            } elseif (is_object($item) && isset($item->$key)) {
                $result[$item->$key] = $item;
            }
        }

        return new self($result);
    }

    public function unique(): self
    {
        return new self(array_values(array_unique($this->items, SORT_REGULAR)));
    }

    public function flatten(int $depth = -1): self
    {
        $result = [];

        foreach ($this->items as $value) {
            if (is_array($value)) {
                if ($depth === -1) {
                    $result = array_merge($result, (new self($value))->flatten()->all());
                } elseif ($depth > 0) {
                    $result = array_merge($result, (new self($value))->flatten($depth - 1)->all());
                } else {
                    $result[] = $value;
                }
            } else {
                $result[] = $value;
            }
        }

        return new self($result);
    }

    public function merge(array $items): self
    {
        return new self(array_merge($this->items, $items));
    }

    public function push(mixed $item): self
    {
        $items = $this->items;
        $items[] = $item;
        return new self($items);
    }

    public function pop(): mixed
    {
        return array_pop($this->items);
    }

    public function shift(): mixed
    {
        return array_shift($this->items);
    }

    public function unshift(mixed $item): self
    {
        $items = $this->items;
        array_unshift($items, $item);
        return new self($items);
    }

    public function only(array $keys): self
    {
        return new self(array_intersect_key($this->items, array_flip($keys)));
    }

    public function except(array $keys): self
    {
        return new self(array_diff_key($this->items, array_flip($keys)));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        return $default;
    }

    public function set(string $key, mixed $value): self
    {
        $items = $this->items;
        $items[$key] = $value;
        return new self($items);
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function isNotEmpty(): bool
    {
        return !empty($this->items);
    }
}
