<?php

declare(strict_types=1);

namespace Acme\Seed\IniSizeParser;

/**
 * Seed payload: parse INI-style size strings like "1M", "2G", "512K".
 * Returns bytes. Supports K, M, G, T suffixes (case-insensitive).
 */
final class IniSizeParserSeed
{
    // <<<PAYLOAD:ini_size_parser>>>
    public function parseIniSize(string $size): int
    {
        $size = trim($size);
        if ($size === '') {
            return 0;
        }
        $lastChar = strtoupper(substr($size, -1));
        $multipliers = ['K' => 1024, 'M' => 1048576, 'G' => 1073741824, 'T' => 1099511627776];
        if (isset($multipliers[$lastChar]) && is_numeric(substr($size, 0, -1))) {
            return (int) ((float) substr($size, 0, -1) * $multipliers[$lastChar]);
        }
        return (int) $size;
    }
    // <<<END-PAYLOAD>>>
}