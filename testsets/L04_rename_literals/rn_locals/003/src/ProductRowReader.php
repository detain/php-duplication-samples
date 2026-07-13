<?php

declare(strict_types=1);

namespace Acme\Import\Products;

final class ProductRowReader
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
        $row = [];
        $cells = explode($delimiter, $line);
        $index = 0;
        $filled = 0;
        foreach ($cells as $cell) {
            $token = trim($cell);
            if ($token === '') {
                $row['col_' . $index] = null;
                $index++;
                continue;
            }
            $cleaned = preg_replace('/\s+/', ' ', $token);
            $row['col_' . $index] = strtolower($cleaned);
            $filled++;
            $index++;
        }
        $row['__count'] = $index;
        $row['__filled'] = $filled;
        $row['__empty'] = $index - $filled;
        return $row;
    }
}
