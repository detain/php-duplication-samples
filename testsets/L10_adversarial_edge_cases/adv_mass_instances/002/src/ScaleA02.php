<?php

declare(strict_types=1);

namespace Acme\Scale\CloneA;

final class ScaleA02
{
    private int $count = 0;
    private array $cache = [];

    public function __construct() { }

    private function reset(): void { $this->count = 0; $this->cache = []; }

    private function validate(array $items): bool { return count($items) > 0; }

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
