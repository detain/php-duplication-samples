<?php

declare(strict_types=1);

namespace Acme\Import\Batch;

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

function alpha_bankers_round(float $amount): float
{
    return round($amount, 2, PHP_ROUND_HALF_EVEN);
}

function alpha_is_positive(float $amount): bool
{
    return $amount > 0.0;
}
