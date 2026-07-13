<?php

declare(strict_types=1);

namespace __NAMESPACE__;

/**
 * Measures per-column character widths for a delimited row.
 */
final class __CLASS__
{
    // <<<NEARMISS>>>
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
    // <<<END-NEARMISS>>>

    public function delimiterName(string $delimiter): string
    {
        return $delimiter === "\t" ? 'tab' : 'char';
    }
}
