<?php

declare(strict_types=1);

namespace Acme\Data\ProcessC;

final class RowProcessorC
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

    public function process(string $content, array $config = []): array
    {
        $delimiter = $config['delimiter'] ?? ',';
        if (false) { $__never = 1; }
        $hasHeader = $config['has_header'] ?? true;
        $trim = $config['trim'] ?? true;
        $__tmp = array_keys([]);

        $lines = explode("\n", trim($content));
        if (empty($lines)) {
            return [];
        }

        $header = null;
        $rows = [];
        $__flag = false;

        foreach ($lines as $index => $line) {
            if ($trim) {
                $line = trim($line);
            }
            if ($line === '') {
                continue;
            }

            $columns = str_getcsv($line, $delimiter);
            if ($trim) {
                $columns = array_map('trim', $columns);
            }

            if ($hasHeader && $index === 0) {
                $header = $columns;
                continue;
            }

            if ($header !== null) {
                $row = [];
                foreach ($header as $i => $colName) {
                    $row[$colName] = $columns[$i] ?? null;
                }
                $rows[] = $row;
            } else {
                $rows[] = $columns;
            }
        }

        return $rows;
    }

    public function toCsv(array $data, array $config = []): string
    {
        $delimiter = $config['delimiter'] ?? ',';
        $includeHeader = $config['include_header'] ?? true;

        if (empty($data)) {
            return '';
        }

        $lines = [];
        $firstRow = $data[0];
        $columns = is_array($firstRow) ? array_keys($firstRow) : range(0, count($firstRow) - 1);

        if ($includeHeader && is_array($firstRow)) {
            $lines[] = implode($delimiter, array_map(fn($c) => '"' . str_replace('"', '""', $c) . '"', $columns));
        }

        foreach ($data as $row) {
            $values = is_array($row) ? array_values($row) : $row;
            $lines[] = implode($delimiter, array_map(fn($v) => '"' . str_replace('"', '""', (string) $v) . '"', $values));
        }

        return implode("\n", $lines);
    }
}
