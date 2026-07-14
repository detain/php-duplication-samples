<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    // <<<NEARMISS>>>
    public function generate(array $data, array $options): string
    {
        $title = $options['title'] ?? 'Report';
        $includeHeader = $options['include_header'] ?? true;
        $includeFooter = $options['include_footer'] ?? false;
        $format = $options['format'] ?? 'text';

        $output = '';

        if ($includeHeader) {
            $output .= $this->renderHeader($title, $format);
        }

        $output .= $this->renderBody($data, $format);

        if ($includeFooter) {
            $output .= $this->renderFooter(count($data), $format);
        }

        return $output;
    }

    protected function renderHeader(string $title, string $format): string
    {
        $line = str_repeat('-', 60);
        $centered = str_pad($title, 60, ' ', STR_PAD_BOTH);
        if ($format === 'html') {
            return "<h2>{$title}</h2>\n";
        }
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
            foreach ($row as $key => $val) {
                $len = mb_strlen((string) $val);
                if ($len > ($widths[$key] ?? 0)) {
                    $widths[$key] = $len;
                }
            }
        }

        $output = '';
        if ($format === 'text') {
            $header = '| ';
            foreach ($columns as $col) {
                $header .= str_pad((string) $col, $widths[$col]) . ' | ';
            }
            $output .= $header . "\n";
            $output .= str_repeat('-', mb_strlen($header)) . "\n";
        }

        foreach ($data as $row) {
            if ($format === 'html') {
                $output .= '<tr>';
                foreach ($columns as $col) {
                    $val = htmlspecialchars((string) ($row[$col] ?? ''));
                    $output .= "<td>{$val}</td>";
                }
                $output .= "</tr>\n";
            } else {
                $output .= '| ';
                foreach ($columns as $col) {
                    $val = (string) ($row[$col] ?? '');
                    $output .= str_pad($val, $widths[$col]) . ' | ';
                }
                $output .= "\n";
            }
        }

        return $output;
    }

    protected function renderFooter(int $rowCount, string $format): string
    {
        $timestamp = date('Y-m-d H:i:s');
        if ($format === 'html') {
            return "<p>Created at {$timestamp} | {$rowCount} items</p>\n";
        }
        return str_repeat('-', 60) . "\nCreated: {$timestamp} | Count: {$rowCount}\n";
    }
    // <<<END-NEARMISS>>>
}
