<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Comprehensive string manipulation utility combining string formatting,
 * URL handling, and text transformation capabilities.
 */
final class StringHelper
{
    private const SMALL_WORDS = ['a', 'an', 'the', 'and', 'but', 'or', 'nor', 'at', 'by', 'for', 'to', 'of', 'in'];

    // ==================== CORE STRING METHODS ====================

    public static function truncate(string $text, int $length, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length - mb_strlen($suffix)) . $suffix;
    }

    public static function limit(string $text, int $limit = 100): string
    {
        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return mb_substr($text, 0, $limit) . '...';
    }

    public static function words(string $text, int $limit = 100): string
    {
        $words = preg_split('/\s+/', $text);
        $count = count($words);

        if ($count <= $limit) {
            return $text;
        }

        return implode(' ', array_slice($words, 0, $limit)) . '...';
    }

    public static function truncateWords(string $text, int $wordCount, string $suffix = '...'): string
    {
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) <= $wordCount) {
            return $text;
        }

        return implode(' ', array_slice($words, 0, $wordCount)) . $suffix;
    }

    // ==================== SLUG METHODS ====================

    public static function slugify(string $text, int $maxLength = 100): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
        $text = trim($text, '-');

        if ($maxLength > 0 && mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
            $text = trim($text, '-');
        }

        return $text;
    }

    // ==================== CASE CONVERSION ====================

    public static function camelToSnake(string $input): string
    {
        $output = preg_replace('/([a-z])([A-Z])/', '$1_$2', $input);
        return mb_strtolower($output);
    }

    public static function snakeToCamel(string $input, bool $capitalizeFirst = false): string
    {
        $result = str_replace(' ', '', ucwords(str_replace('_', ' ', $input)));

        if (!$capitalizeFirst) {
            $result = lcfirst($result);
        }

        return $result;
    }

    public static function capitalize(string $text): string
    {
        return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
    }

    public static function titleCase(string $text, array $exceptions = []): string
    {
        $words = explode(' ', mb_strtolower($text));
        $smallWords = array_merge(self::SMALL_WORDS, $exceptions);

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

    // ==================== PADDING & ALIGNMENT ====================

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

    // ==================== CHECK METHODS ====================

    public static function startsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    public static function endsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }

    public static function contains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }

    public static function isEmail(string $text): bool
    {
        return filter_var($text, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function isUrl(string $text): bool
    {
        return filter_var($text, FILTER_VALIDATE_URL) !== false;
    }

    // ==================== HTML METHODS ====================

    public static function stripTags(string $text): string
    {
        return strip_tags($text);
    }

    public static function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    public static function unescapeHtml(string $text): string
    {
        return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    }

    public static function excerpt(string $text, string $phrase, int $radius = 100): string
    {
        $phrasePos = mb_stripos($text, $phrase);

        if ($phrasePos === false) {
            return self::limit($text, $radius * 2);
        }

        $startPos = max(0, $phrasePos - $radius);
        $endPos = min(mb_strlen($text), $phrasePos + mb_strlen($phrase) + $radius);

        $excerpt = mb_substr($text, $startPos, $endPos - $startPos);

        if ($startPos > 0) {
            $excerpt = '...' . $excerpt;
        }

        if ($endPos < mb_strlen($text)) {
            $excerpt = $excerpt . '...';
        }

        return $excerpt;
    }

    public static function highlightPhrase(string $text, string $phrase, string $before = '<mark>', string $after = '</mark>'): string
    {
        if (empty($phrase)) {
            return $text;
        }

        $escapedPhrase = preg_quote($phrase, '/');
        return preg_replace("/({$escapedPhrase})/i", $before . '$1' . $after, $text);
    }

    // ==================== MASKING METHODS ====================

    public static function maskEmail(string $email): string
    {
        $parts = explode('@', $email);

        if (count($parts) !== 2) {
            return $email;
        }

        $name = $parts[0];
        $domain = $parts[1];

        $maskedName = self::mask($name);
        return $maskedName . '@' . $domain;
    }

    public static function mask(string $text, string $maskChar = '*', int $percent = 50): string
    {
        $length = mb_strlen($text);
        $maskCount = (int) ceil($length * $percent / 100);

        $result = '';
        for ($i = 0; $i < $length; $i++) {
            if ($i < $maskCount) {
                $result .= $maskChar;
            } else {
                $result .= $text[$i];
            }
        }

        return $result;
    }

    // ==================== URL METHODS ====================

    public static function parseQuery(string $query): array
    {
        parse_str($query, $params);
        return $params;
    }

    public static function buildQuery(array $params, bool $encode = true): string
    {
        if ($encode) {
            return http_build_query($params);
        }

        $parts = [];
        foreach ($params as $key => $value) {
            $parts[] = "{$key}={$value}";
        }

        return implode('&', $parts);
    }

    public static function encode(string $value): string
    {
        return urlencode($value);
    }

    public static function decode(string $value): string
    {
        return urldecode($value);
    }

    public static function encodePath(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    public static function decodePath(string $path): string
    {
        return implode('/', array_map('rawurldecode', explode('/', $path)));
    }

    // ==================== REPLACE METHODS ====================

    public static function replaceAll(string $text, string $search, string $replace): string
    {
        return str_replace($search, $replace, $text);
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

    // ==================== RANDOM METHODS ====================

    public static function random(int $length = 16): string
    {
        $bytes = random_bytes($length);
        return bin2hex($bytes);
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

    // ==================== COUNT METHODS ====================

    public static function countWords(string $text): int
    {
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
}
