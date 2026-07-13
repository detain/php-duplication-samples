<?php

declare(strict_types=1);

namespace Acme\Seed\CsvImport;

/**
 * Seed payload: parse one delimited CSV row into a normalized column map.
 */
final class CsvImportSeed
{
    // <<<PAYLOAD:csv_import>>>
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
    // <<<END-PAYLOAD>>>
}
