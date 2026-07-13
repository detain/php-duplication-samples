<?php

declare(strict_types=1);

namespace Acme\Import\Services;

final class ImportServiceTop
{
    public function parseRow(string $line, string $delimiter): array
    {
        $record = [];
        $columns = explode($delimiter, $line);
        $index = 0;
        $filled = 0;
        foreach ($columns as $column) {
            $value = trim($column);
            if ($value === '') {
                $record['col_' . $index] = null;
                $index++;
                continue;
            }
            $normalized = preg_replace('/\s+/', ' ', $value);
            $record['col_' . $index] = strtolower($normalized);
            $filled++;
            $index++;
        }
        $record['__count'] = $index;
        $record['__filled'] = $filled;
        $record['__empty'] = $index - $filled;
        return $record;
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
