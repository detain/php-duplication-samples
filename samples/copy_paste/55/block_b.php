<?php

declare(strict_types=1);

namespace App\Helpers;

class TextFormatter
{
    public static function titleCase(string $text, array $exceptions = []): string
    {
        $words = explode(' ', mb_strtolower($text));
        $smallWords = array_merge(['a', 'an', 'the', 'and', 'but', 'or', 'nor', 'at', 'by', 'for', 'to', 'of', 'in'], $exceptions);

        foreach ($words as $index => $word) {
            if ($index === 0 || !in_array($word, $smallWords, true)) {
                $words[$index] = mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
            }
        }

        return implode(' ', $words);
    }

    public static function sentenceCase(string $text): string
    {
        $text = mb_strtolower($text);

        if (preg_match('/^([a-z])/', $text)) {
            $text = mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
        }

        return $text;
    }

    public static function removeWhitespace(string $text, bool $trim = true): string
    {
        $text = preg_replace('/\s+/', ' ', $text);

        if ($trim) {
            $text = trim($text);
        }

        return $text;
    }

    public static function normalizeSpaces(string $text): string
    {
        $text = preg_replace('/[\t\n\r]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    public static function truncateWords(string $text, int $wordCount, string $suffix = '...'): string
    {
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) <= $wordCount) {
            return $text;
        }

        return implode(' ', array_slice($words, 0, $wordCount)) . $suffix;
    }

    public static function highlightPhrase(string $text, string $phrase, string $before = '<mark>', string $after = '</mark>'): string
    {
        if (empty($phrase)) {
            return $text;
        }

        $escapedPhrase = preg_quote($phrase, '/');
        return preg_replace("/({$escapedPhrase})/i", $before . '$1' . $after, $text);
    }

    public static function indent(string $text, int $spaces = 4, string $char = ' '): string
    {
        $padding = str_repeat($char, $spaces);
        $lines = explode("\n", $text);

        return implode("\n", array_map(
            fn($line) => $line !== '' ? $padding . $line : $line,
            $lines
        ));
    }

    public static function wrapText(string $text, int $width = 80, string $break = "\n"): string
    {
        $words = preg_split('/\s+/', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            if (mb_strlen($currentLine) + mb_strlen($word) + 1 <= $width) {
                $currentLine = empty($currentLine) ? $word : $currentLine . ' ' . $word;
            } else {
                if (!empty($currentLine)) {
                    $lines[] = $currentLine;
                }
                $currentLine = $word;
            }
        }

        if (!empty($currentLine)) {
            $lines[] = $currentLine;
        }

        return implode($break, $lines);
    }

    public static function padLeft(string $text, int $length, string $char = ' '): string
    {
        return str_pad($text, $length, $char, STR_PAD_LEFT);
    }

    public static function padRight(string $text, int $length, string $char = ' '): string
    {
        return str_pad($text, $length, $char, STR_PAD_RIGHT);
    }

    public static function padBoth(string $text, int $length, string $char = ' '): string
    {
        return str_pad($text, $length, $char, STR_PAD_BOTH);
    }

    public static function reverse(string $text): string
    {
        $length = mb_strlen($text);
        $reversed = '';

        while ($length > 0) {
            $length--;
            $reversed .= mb_substr($text, $length, 1);
        }

        return $reversed;
    }

    public static function shuffle(string $text): string
    {
        $chars = mb_str_split($text);
        shuffle($chars);
        return implode('', $chars);
    }

    public static function countWords(string $text): int
    {
        preg_match_all('/\s+/', trim($text), $matches);
        $spaces = count($matches[0]);
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        return count($words);
    }

    public static function countCharacters(string $text, bool $includeSpaces = true): int
    {
        if ($includeSpaces) {
            return mb_strlen($text);
        }

        return mb_strlen(preg_replace('/\s/', '', $text));
    }

    public static function randomString(int $length, string $chars = 'alphanumeric'): string
    {
        $charSets = [
            'alphanumeric' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            'alpha' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'numeric' => '0123456789',
            'hex' => '0123456789abcdef',
        ];

        $charSet = $charSets[$chars] ?? $chars;
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $charSet[random_int(0, strlen($charSet) - 1)];
        }

        return $result;
    }

    public static function toAscii(string $text): string
    {
        $transliteration = [
            'Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A',
            'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E',
            'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O',
            'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U',
        ];

        return strtr($text, $transliteration);
    }
}
