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

    public function process(string $content, array $config = []): array
    {
        $delimiter = $config['delimiter'] ?? ',';
        $hasHeader = $config['has_header'] ?? true;
        $trim = $config['trim'] ?? true;

        $lines = explode("\n", trim($content));
        if (empty($lines)) {
            return [];
        }

        $header = null;
        $rows = [];

        foreach ($lines as $index => $line) {
            if ($trim) {
                $line = trim($line);
            }
            if ($line === '') {
                continue;
            }

            $columns = strGetcsv($line, $delimiter);
            if ($trim) {
                $columns = arrayMap('trim', $columns);
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
        $columns = isArray($firstRow) ? arrayKeys($firstRow) : range(0, count($firstRow) - 1);

        if ($includeHeader && isArray($firstRow)) {
            $lines[] = implode($delimiter, arrayMap(fn($c) => '"' . strReplace('"', '""', $c) . '"', $columns));
        }

        foreach ($data as $row) {
            $values = isArray($row) ? arrayValues($row) : $row;
            $lines[] = implode($delimiter, arrayMap(fn($v) => '"' . strReplace('"', '""', (string) $v) . '"', $values));
        }

        return implode("\n", $lines);
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
