<?php

declare(strict_types=1);

namespace Gen\Lib;

/**
 * Loads the clonable region between sentinel comments from a payload/variant
 * file:
 *
 *   // <<<PAYLOAD:name>>>
 *   ...region...
 *   // <<<END-PAYLOAD>>>
 *
 * The generator lifts the region between the sentinels (exclusive), so the
 * ground-truth region is exactly the code a detector should see, with no
 * sentinel leakage.
 */
final class Payload
{
    /**
     * @return list<string> the region lines (sentinels excluded), leading
     *                      indentation preserved as written in the file
     */
    public static function region(string $file): array
    {
        if (!is_file($file)) {
            throw new \RuntimeException("payload file not found: {$file}");
        }
        $raw = file_get_contents($file);
        if ($raw === false) {
            throw new \RuntimeException("cannot read payload: {$file}");
        }
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $raw));

        $start = null;
        $end = null;
        foreach ($lines as $i => $line) {
            if ($start === null && preg_match('/<<<PAYLOAD:[A-Za-z0-9_]+>>>/', $line)) {
                $start = $i;
                continue;
            }
            if ($start !== null && str_contains($line, '<<<END-PAYLOAD>>>')) {
                $end = $i;
                break;
            }
        }
        if ($start === null || $end === null) {
            throw new \RuntimeException("payload sentinels not found in {$file}");
        }

        $region = array_slice($lines, $start + 1, $end - $start - 1);

        // Trim common leading indentation so callers can re-indent to a marker.
        return self::dedent($region);
    }

    /**
     * Remove the minimum shared leading-whitespace prefix across non-blank lines.
     *
     * @param list<string> $lines
     * @return list<string>
     */
    public static function dedent(array $lines): array
    {
        $min = null;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            preg_match('/^(\s*)/', $line, $m);
            $len = strlen($m[1]);
            $min = $min === null ? $len : min($min, $len);
        }
        if (!$min) {
            return array_values($lines);
        }
        $out = [];
        foreach ($lines as $line) {
            $out[] = trim($line) === '' ? '' : substr($line, $min);
        }
        return array_values($out);
    }
}
