<?php

declare(strict_types=1);

namespace Acme\Seed\CsvExport;

/**
 * Seed payload: export an array of records to CSV format.
 * Uses proper escaping for fields containing delimiters or quotes.
 */
final class CsvExportSeed
{
    // <<<PAYLOAD:csv_export>>>
    public function exportToCsv(array $records, array $headers, string $delimiter): string
    {
        if (empty($records) && empty($headers)) {
            return '';
        }
        $lines = [];
        if (!empty($headers)) {
            $lines[] = $this->formatRow($headers, $delimiter);
        }
        foreach ($records as $record) {
            $values = array_values($record);
            $lines[] = $this->formatRow($values, $delimiter);
        }
        return implode("\n", $lines);
    }

    private function formatRow(array $values, string $delimiter): string
    {
        $escaped = array_map(function ($value) use ($delimiter) {
            $str = (string) $value;
            if (strpos($str, $delimiter) !== false || strpos($str, '"') !== false || strpos($str, "\n") !== false) {
                $str = '"' . str_replace('"', '""', $str) . '"';
            }
            return $str;
        }, $values);
        return implode($delimiter, $escaped);
    }
    // <<<END-PAYLOAD>>>
}