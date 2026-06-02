<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Comprehensive array manipulation utility combining array operations,
 * collection handling, and lazy evaluation capabilities.
 */
final class ArrayHelper
{
    // ==================== ACCESSOR METHODS ====================

    public static function first(array $array): mixed
    {
        if (empty($array)) {
            return null;
        }

        return reset($array);
    }

    public static function last(array $array): mixed
    {
        if (empty($array)) {
            return null;
        }

        return end($array);
    }

    public static function firstKey(array $array): mixed
    {
        if (empty($array)) {
            return null;
        }

        reset($array);
        return key($array);
    }

    public static function lastKey(array $array): mixed
    {
        if (empty($array)) {
            return null;
        }

        end($array);
        return key($array);
    }

    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        return $default;
    }

    // ==================== EXTRACTION METHODS ====================

    public static function pluck(array $array, string $key): array
    {
        $result = [];

        foreach ($array as $item) {
            if (is_array($item) && isset($item[$key])) {
                $result[] = $item[$key];
            } elseif (is_object($item) && isset($item->$key)) {
                $result[] = $item->$key;
            }
        }

        return $result;
    }

    public static function indexBy(array $array, string $key): array
    {
        $result = [];

        foreach ($array as $item) {
            if (is_array($item) && isset($item[$key])) {
                $result[$item[$key]] = $item;
            } elseif (is_object($item) && isset($item->$key)) {
                $result[$item->$key] = $item;
            }
        }

        return $result;
    }

    public static function groupBy(array $array, string $key): array
    {
        $result = [];

        foreach ($array as $item) {
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

        return $result;
    }

    // ==================== FUNCTIONAL METHODS ====================

    public static function map(array $array, callable $callback): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $result[$key] = $callback($value, $key);
        }

        return $result;
    }

    public static function filter(array $array, callable $callback): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    public static function reduce(array $array, callable $callback, mixed $initial = null): mixed
    {
        $accumulator = $initial;

        foreach ($array as $key => $value) {
            $accumulator = $callback($accumulator, $value, $key);
        }

        return $accumulator;
    }

    public static function find(array $array, callable $callback): mixed
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return null;
    }

    public static function findKey(array $array, callable $callback): mixed
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $key;
            }
        }

        return null;
    }

    // ==================== SET OPERATIONS ====================

    public static function contains(array $array, mixed $value): bool
    {
        return in_array($value, $array, true);
    }

    public static function unique(array $array): array
    {
        return array_values(array_unique($array, SORT_REGULAR));
    }

    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    // ==================== FLATTENING METHODS ====================

    public static function flatten(array $array, int $depth = -1): array
    {
        $result = [];

        foreach ($array as $value) {
            if (is_array($value)) {
                if ($depth === -1) {
                    $result = array_merge($result, self::flatten($value));
                } elseif ($depth > 0) {
                    $result = array_merge($result, self::flatten($value, $depth - 1));
                } else {
                    $result[] = $value;
                }
            } else {
                $result[] = $value;
            }
        }

        return $result;
    }

    // ==================== DOT NOTATION METHODS ====================

    public static function set(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $current[$k] = $value;
            } else {
                if (!isset($current[$k]) || !is_array($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
        }
    }

    public static function has(array $array, string $key): bool
    {
        $keys = explode('.', $key);
        $current = $array;

        foreach ($keys as $k) {
            if (!is_array($current) || !array_key_exists($k, $current)) {
                return false;
            }
            $current = $current[$k];
        }

        return true;
    }

    public static function forget(array &$array, string $key): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach (array_slice($keys, 0, -1) as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                return;
            }
            $current = &$current[$k];
        }

        unset($current[end($keys)]);
    }

    // ==================== CHUNKING METHODS ====================

    public static function chunk(array $array, int $size): array
    {
        return array_chunk($array, $size, true);
    }

    public static function split(array $array): array
    {
        $middle = (int) floor(count($array) / 2);
        return [
            array_slice($array, 0, $middle),
            array_slice($array, $middle),
        ];
    }

    // ==================== SORTING METHODS ====================

    public static function sort(array $array, ?callable $callback = null): array
    {
        if ($callback !== null) {
            usort($array, $callback);
        } else {
            sort($array);
        }

        return $array;
    }

    public static function sortBy(array $array, string $key, string $direction = 'asc'): array
    {
        usort($array, function ($a, $b) use ($key, $direction) {
            $aValue = is_array($a) ? ($a[$key] ?? null) : ($a->$key ?? null);
            $bValue = is_array($b) ? ($b[$key] ?? null) : ($b->$key ?? null);

            $comparison = $aValue <=> $bValue;

            return $direction === 'desc' ? -$comparison : $comparison;
        });

        return $array;
    }

    public static function reverse(array $array): array
    {
        return array_reverse($array, true);
    }

    // ==================== AGGREGATE METHODS ====================

    public static function sum(array $array, ?string $key = null): int|float
    {
        if ($key === null) {
            return array_sum($array);
        }

        return array_sum(self::pluck($array, $key));
    }

    public static function avg(array $array, ?string $key = null): float
    {
        $count = $key === null ? count($array) : count(self::pluck($array, $key));

        if ($count === 0) {
            return 0;
        }

        return self::sum($array, $key) / $count;
    }

    public static function min(array $array, ?string $key = null): mixed
    {
        if ($key === null) {
            return empty($array) ? null : min($array);
        }

        $values = self::pluck($array, $key);
        return empty($values) ? null : min($values);
    }

    public static function max(array $array, ?string $key = null): mixed
    {
        if ($key === null) {
            return empty($array) ? null : max($array);
        }

        $values = self::pluck($array, $key);
        return empty($values) ? null : max($values);
    }

    // ==================== HELPER METHODS ====================

    public static function isAssoc(array $array): bool
    {
        if (empty($array)) {
            return true;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    public static function divide(array $array): array
    {
        return [array_keys($array), array_values($array)];
    }

    public static function zip(array ...$arrays): array
    {
        return array_map(null, ...$arrays);
    }

    public static function collapse(array $array): array
    {
        $result = [];

        foreach ($array as $values) {
            if (is_array($values)) {
                $result = array_merge($result, $values);
            }
        }

        return $result;
    }

    public static function pad(array $array, int $size, mixed $value = null): array
    {
        return array_pad($array, $size, $value);
    }

    public static function prepend(array $array, mixed $value): array
    {
        array_unshift($array, $value);
        return $array;
    }

    public static function push(array $array, mixed ...$values): array
    {
        foreach ($values as $value) {
            $array[] = $value;
        }

        return $array;
    }
}
