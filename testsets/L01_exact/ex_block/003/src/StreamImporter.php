<?php

declare(strict_types=1);

namespace Acme\Import\Stream;

function beta_currency_symbol(string $code): string
{
    return match (strtoupper($code)) {
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        default => $code . ' ',
    };
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

function beta_format_money(float $amount, string $code): string
{
    return beta_currency_symbol($code) . number_format($amount, 2);
}
