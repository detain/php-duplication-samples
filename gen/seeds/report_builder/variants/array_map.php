<?php

declare(strict_types=1);

namespace Acme\Seed\ReportBuilder;

final class ReportBuilderSeed
{
    // <<<PAYLOAD:report_builder>>>
    public function generate(array $data, array $options): string
    {
        $title = $options['title'] ?? 'Report';
        $includeHeader = ($options['include_header'] ?? true) === true;
        $includeFooter = ($options['include_footer'] ?? true) === true;
        $format = $options['format'] ?? 'text';

        $output = $includeHeader ? $this->renderHeader($title, $format) : '';
        $output .= $this->renderBody($data, $format);
        $output .= $includeFooter ? $this->renderFooter(count($data), $format) : '';

        return $output;
    }

    protected function renderHeader(string $title, string $format): string
    {
        if ($format === 'html') {
            return sprintf("<h1>%s</h1>\n", htmlspecialchars($title));
        }
        $line = str_repeat('=', 60);
        $centered = str_pad($title, 60, ' ', STR_PAD_BOTH);
        return "{$line}\n{$centered}\n{$line}\n";
    }

    protected function renderBody(array $data, string $format): string
    {
        if (empty($data)) {
            return "No data available.\n";
        }

        $columns = array_keys($data[0] ?? []);
        $widths = [];
        foreach ($columns as $col) {
            $widths[$col] = mb_strlen((string) $col);
        }

        foreach ($data as $row) {
            foreach ($row as $k => $v) {
                $len = mb_strlen((string) $v);
                if ($len > $widths[$k]) {
                    $widths[$k] = $len;
                }
            }
        }

        $out = '';
        if ($format === 'text') {
            $hdr = '| ' . implode(' | ', array_map(fn($c) => str_pad($c, $widths[$c]), $columns)) . ' |';
            $out .= $hdr . "\n";
            $out .= str_repeat('-', mb_strlen($hdr)) . "\n";
        }

        foreach ($data as $row) {
            if ($format === 'html') {
                $out .= '<tr>';
                foreach ($columns as $col) {
                    $out .= '<td>' . htmlspecialchars((string) ($row[$col] ?? '')) . '</td>';
                }
                $out .= "</tr>\n";
            } else {
                $cells = array_map(fn($c) => str_pad((string) ($row[$c] ?? ''), $widths[$c]), $columns);
                $out .= '| ' . implode(' | ', $cells) . " |\n";
            }
        }

        return $out;
    }

    protected function renderFooter(int $rowCount, string $format): string
    {
        $ts = date('Y-m-d H:i:s');
        return $format === 'html'
            ? "<p>Generated at {$ts} | {$rowCount} row(s)</p>\n"
            : str_repeat('=', 60) . "\nGenerated: {$ts} | Rows: {$rowCount}\n";
    }
    // <<<END-PAYLOAD>>>
}
