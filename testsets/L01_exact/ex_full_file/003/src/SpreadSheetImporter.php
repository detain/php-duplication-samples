<?php

declare(strict_types=1);

namespace Acme\Import\Sheets;

final class SpreadSheetImporter
{
    public function __construct(private readonly string $region = 'default')
    {
    }

    public function region(): string
    {
        return $this->region;
    }

    public function fingerprint(array $payload): string
    {
        ksort($payload);
        return substr(hash('crc32b', json_encode($payload) ?: ''), 0, 8);
    }

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
}
