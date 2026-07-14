<?php

declare(strict_types=1);

namespace Acme\Data\Map;

final class RowMapper
{
    public function parseLine(string $line): array
    {
        return str_getcsv($line);
    }

    public function toArray(string $csv): array
    {
        $lines = explode("\n", $csv);
        return array_map(fn($l) => str_getcsv($l), $lines);
    }
}
