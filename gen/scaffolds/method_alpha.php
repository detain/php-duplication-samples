<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    <<<INSERT>>>

    public function label(): string
    {
        return strtolower(str_replace('\\', '.', static::class));
    }

    private function withinBounds(int $value, int $floor, int $ceiling): bool
    {
        return $value >= $floor && $value <= $ceiling;
    }
}
