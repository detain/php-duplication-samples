<?php

declare(strict_types=1);

namespace App\Sanitization;

class InputSanitizer
{
    public static function sanitizeString(string $input, array $options = []): string
    {
        $trim = $options['trim'] ?? true;
        $stripTags = $options['strip_tags'] ?? true;
        $escapeHtml = $options['escape_html'] ?? false;
        $normalizeWhitespace = $options['normalize_whitespace'] ?? false;

        if ($trim) {
            $input = trim($input);
        }

        if ($stripTags) {
            $input = strip_tags($input);
        }

        if ($normalizeWhitespace) {
            $input = preg_replace('/\s+/', ' ', $input);
        }

        if ($escapeHtml) {
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }

        return $input;
    }

    public static function sanitizeArray(array $input, array $options = []): array
    {
        $sanitized = [];

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value, $options);
            } elseif (is_string($value)) {
                $sanitized[$key] = self::sanitizeString($value, $options);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    public static function sanitizeInteger(mixed $input, int $min = null, int $max = null): ?int
    {
        if ($input === null || $input === '') {
            return null;
        }

        $intValue = filter_var($input, FILTER_VALIDATE_INT);

        if ($intValue === false) {
            return null;
        }

        if ($min !== null && $intValue < $min) {
            return $min;
        }

        if ($max !== null && $intValue > $max) {
            return $max;
        }

        return $intValue;
    }

    public static function sanitizeFloat(mixed $input, float $min = null, float $max = null): ?float
    {
        if ($input === null || $input === '') {
            return null;
        }

        $floatValue = filter_var($input, FILTER_VALIDATE_FLOAT);

        if ($floatValue === false) {
            return null;
        }

        if ($min !== null && $floatValue < $min) {
            return $min;
        }

        if ($max !== null && $floatValue > $max) {
            return $max;
        }

        return $floatValue;
    }

    public static function sanitizeBoolean(mixed $input): bool
    {
        if (is_bool($input)) {
            return $input;
        }

        if (is_string($input)) {
            $lower = strtolower(trim($input));
            return in_array($lower, ['true', '1', 'yes', 'on'], true);
        }

        return (bool) $input;
    }

    public static function sanitizeEmail(string $email): ?string
    {
        $email = trim(strtolower($email));
        $sanitized = filter_var($email, FILTER_SANITIZE_EMAIL);

        if ($sanitized === false || !filter_var($sanitized, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $sanitized;
    }

    public static function sanitizeUrl(string $url, array $allowedProtocols = ['http', 'https']): ?string
    {
        $url = trim($url);

        $parsed = parse_url($url);

        if ($parsed === false || !isset($parsed['scheme']) || !isset($parsed['host'])) {
            return null;
        }

        if (!in_array(strtolower($parsed['scheme']), $allowedProtocols, true)) {
            return null;
        }

        $sanitized = filter_var($url, FILTER_SANITIZE_URL);

        if ($sanitized === false) {
            return null;
        }

        return $sanitized;
    }

    public static function sanitizePhone(string $phone, bool $international = true): ?string
    {
        $phone = preg_replace('/[^\d+]/', '', $phone);

        if ($international && !str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        if ($international) {
            if (!preg_match('/^\+\d{10,15}$/', $phone)) {
                return null;
            }
        } else {
            if (!preg_match('/^\d{10,11}$/', $phone)) {
                return null;
            }
        }

        return $phone;
    }

    public static function sanitizeHtml(string $input, array $allowedTags = []): string
    {
        $input = strip_tags($input, '<' . implode('><', $allowedTags) . '>');

        $input = preg_replace('/on\w+\s*=\s*[^>]*>/i', '>', $input);

        $input = preg_replace('/javascript\s*:/i', '', $input);

        return $input;
    }

    public static function sanitizeFilename(string $filename, string $replacement = '_'): string
    {
        $filename = preg_replace('/[^\w\.\-]/', $replacement, $filename);

        $filename = preg_replace('/' . preg_quote($replacement, '/') . '+/', $replacement, $filename);

        $filename = trim($filename, $replacement . '.');

        if (empty($filename)) {
            return 'file';
        }

        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 250) . '.' . $ext;
        }

        return $filename;
    }

    public static function sanitizeSlug(string $input, string $replacement = '-'): string
    {
        $slug = mb_strtolower($input);

        $slug = preg_replace('/[^\pL\pN]+/u', $replacement, $slug);

        $slug = trim($slug, $replacement);

        if (empty($slug)) {
            return 'untitled';
        }

        return $slug;
    }

    public static function truncate(string $input, int $length, string $suffix = '...'): string
    {
        if (mb_strlen($input) <= $length) {
            return $input;
        }

        return mb_substr($input, 0, $length - mb_strlen($suffix)) . $suffix;
    }

    public static function removeControlCharacters(string $input): string
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
    }

    public static function normalizeLineEndings(string $input, string $style = "\n"): string
    {
        $input = str_replace(["\r\n", "\r"], "\n", $input);

        if ($style !== "\n") {
            $input = str_replace("\n", $style, $input);
        }

        return $input;
    }
}
