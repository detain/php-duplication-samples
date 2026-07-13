<?php

declare(strict_types=1);

namespace Acme\Sheets\Extraction;

function gamma_clamp(int $value, int $min, int $max): int
{
    return max($min, min($max, $value));
}

function gamma_slugify(string $text): string
{
    $lower = strtolower(trim($text));
    return preg_replace('/[^a-z0-9]+/', '-', $lower) ?? '';
}

function parseRow(string $line, string $delimiter): array
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
