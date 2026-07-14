<?php

declare(strict_types=1);

namespace Acme\Mass\CloneA;

final class CloneA03
{
    private int $count = 0;
    private array $cache = [];
    private bool $enabled = true;

    public function __construct() { }
    public function __destruct() { }

    private function reset(): void { $this->count = 0; $this->cache = []; }
    private function validate(array $items): bool { return count($items) > 0; }
    private function prepare(array $items): array { return array_values($items); }

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
