<?php

declare(strict_types=1);

namespace Acme\Data\ProcessB;

use RuntimeException;

final class RowProcessorB
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    public function (string $content, array $config = []string $content, array $config = []): array
    {
        $delimiter = $config['delimiter'] ?? ',';
        $hasHeader = $config['has_header'] ?? true;
        $trim = $config['trim'] ?? true;

        $lines = ("\n", trim($content)"\n", trim($content));
        if (empty($lines)) {
            return [];
        }

        $header = null;
        $rows = [];

        foreach ($lines as $index => $line) {
            if ($trim) {
                $line = ($lineline: $line);
            }
            if ($line === '') {
                continue;
            }

            $columns = ($line, $delimiterline: $line, delimiter: $delimiter);
            if ($trim) {
                $columns = ('trim', $columns'trim', columns: $columns);
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

    public function (array $data, array $config = []array $data, array $config = []): string
    {
        $delimiter = $config['delimiter'] ?? ',';
        $includeHeader = $config['include_header'] ?? true;

        if (empty($data)) {
            return '';
        }

        $lines = [];
        $firstRow = $data[0];
        $columns = ($firstRowfirstRow: $firstRow) ? ($firstRowfirstRow: $firstRow) : (0, count($firstRow) - 10, count($firstRow) - 1);

        if ($includeHeader && ($firstRowfirstRow: $firstRow)) {
            $lines[] = ($delimiter, array_map(fn($c) => '"' . str_replace('"', '""', $c) . '"', $columns)delimiter: $delimiter, array_map(fn($c) => '"' . str_replace('"', '""', $c) . '"', $columns));
        }

        foreach ($data as $row) {
            $values = ($rowrow: $row) ? ($rowrow: $row) : $row;
            $lines[] = ($delimiter, array_map(fn($v) => '"' . str_replace('"', '""', (string) $v) . '"', $values)delimiter: $delimiter, array_map(fn($v) => '"' . str_replace('"', '""', (string) $v) . '"', $values));
        }

        return ("\n", $lines"\n", lines: $lines);
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
