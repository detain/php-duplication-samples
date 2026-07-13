<?php

declare(strict_types=1);

namespace Acme\Import\Transform;

/**
 * Measures per-column character widths for a delimited row.
 */
final class DataTransformer
{
    public function measureRow(string $line, string $delimiter): array
    {
        $columns = explode($delimiter, $line);
        $widths = array_map(
            static fn (string $column): int => mb_strlen(trim($column)),
            $columns
        );
        $report = [];
        foreach ($widths as $index => $width) {
            $report['col_' . $index] = $width;
        }
        $report['__total'] = array_sum($widths);
        $report['__max'] = $widths === [] ? 0 : max($widths);
        return $report;
    }

    public function delimiterName(string $delimiter): string
    {
        return $delimiter === "\t" ? 'tab' : 'char';
    }
}
