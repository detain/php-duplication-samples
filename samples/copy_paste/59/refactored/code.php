<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Comprehensive sanitization utility combining HTML sanitization,
 * markdown processing, and input cleaning capabilities.
 */
final class HtmlSanitizer
{
    private const DEFAULT_ALLOWED_TAGS = [
        'a', 'abbr', 'acronym', 'address', 'b', 'blockquote', 'br',
        'caption', 'cite', 'code', 'col', 'colgroup', 'dd', 'del',
        'dfn', 'div', 'dl', 'dt', 'em', 'fieldset', 'h1', 'h2', 'h3',
        'h4', 'h5', 'h6', 'hr', 'i', 'img', 'ins', 'kbd', 'li', 'map',
        'ol', 'p', 'pre', 'q', 'samp', 'small', 'span', 'strong', 'sub',
        'sup', 'table', 'tbody', 'td', 'tfoot', 'th', 'thead', 'tr', 'tt',
        'ul', 'var',
    ];

    private const DEFAULT_ALLOWED_ATTRIBUTES = [
        'href', 'src', 'alt', 'title', 'class', 'id', 'style',
        'width', 'height', 'align', 'valign', 'bgcolor', 'border',
        'cellpadding', 'cellspacing', 'rel', 'target', 'name',
    ];

    private const ALLOWED_PROTOCOLS = ['http', 'https', 'mailto'];

    private static array $allowedTags = self::DEFAULT_ALLOWED_TAGS;
    private static array $allowedAttributes = self::DEFAULT_ALLOWED_ATTRIBUTES;

    // ==================== HTML SANITIZATION ====================

    public static function sanitize(string $html): string
    {
        $html = strip_tags($html);

        $allowedTags = '<' . implode('><', self::$allowedTags) . '>';
        $html = strip_tags($html, $allowedTags);

        $html = self::sanitizeAttributes($html);
        $html = self::removeJavascriptProtocol($html);
        $html = self::removeEventHandlers($html);
        $html = self::removeDangerousCss($html);
        $html = self::removeVbscriptProtocol($html);

        return $html;
    }

    public static function sanitizeAttributes(string $html): string
    {
        return preg_replace_callback(
            '/<(\w+)([^>]*)>/i',
            function ($matches) {
                $tag = $matches[1];
                $attributes = $matches[2];
                $sanitizedAttrs = [];

                if (preg_match_all('/(\w+)=["\']([^"\']*)["\']/', $attributes, $attrMatches)) {
                    foreach ($attrMatches[1] as $index => $attrName) {
                        $attrValue = $attrMatches[2][$index];

                        if (in_array(strtolower($attrName), self::$allowedAttributes)) {
                            $attrValue = htmlspecialchars($attrValue, ENT_QUOTES, 'UTF-8');

                            if (self::isDangerousAttribute($attrName, $attrValue)) {
                                continue;
                            }

                            $sanitizedAttrs[] = $attrName . '="' . $attrValue . '"';
                        }
                    }
                }

                $attrString = !empty($sanitizedAttrs) ? ' ' . implode(' ', $sanitizedAttrs) : '';
                return '<' . $tag . $attrString . '>';
            },
            $html
        );
    }

    private static function isDangerousAttribute(string $name, string $value): bool
    {
        $name = strtolower($name);

        if (in_array($name, ['onclick', 'onload', 'onerror', 'onmouseover'])) {
            return true;
        }

        $dangerousProtocols = ['javascript', 'vbscript', 'data'];

        foreach ($dangerousProtocols as $protocol) {
            if (stripos($value, $protocol . ':') !== false) {
                return true;
            }
        }

        return false;
    }

    private static function removeJavascriptProtocol(string $html): string
    {
        return preg_replace('/javascript\s*:/i', '', $html);
    }

    private static function removeEventHandlers(string $html): string
    {
        return preg_replace('/\bon\w+\s*=\s*[^>]*>/i', '>', $html);
    }

    private static function removeDangerousCss(string $html): string
    {
        return preg_replace('/style\s*=\s*["\'][^"\']*expression\([^)]*\)[^"\']*["\']/i', '', $html);
    }

    private static function removeVbscriptProtocol(string $html): string
    {
        return preg_replace('/vbscript\s*:/i', '', $html);
    }

    // ==================== ESCAPE METHODS ====================

    public static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    public static function escapeAttribute(string $text): string
    {
        return htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
    }

    public static function stripAllTags(string $html): string
    {
        return strip_tags($html);
    }

    public static function allowOnlyText(string $html): string
    {
        return strip_tags($html, '<br><p>');
    }

    // ==================== LINK METHODS ====================

    public static function makeLinksClickable(string $text): string
    {
        return preg_replace(
            '/(https?:\/\/[^\s<]+)/i',
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
            $text
        );
    }

    // ==================== CSS METHODS ====================

    public static function cleanCss(string $css): string
    {
        $css = preg_replace('/expression\s*\([^)]*\)/', '', $css);
        $css = preg_replace('/javascript\s*:/i', '', $css);
        $css = preg_replace('/behavior\s*:[^;]*;/i', '', $css);
        $css = preg_replace('/filter\s*:[^;]*;/i', '', $css);

        return $css;
    }

    // ==================== MARKDOWN METHODS ====================

    public static function sanitizeMarkdown(string $markdown): string
    {
        $markdown = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $markdown);
        $markdown = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $markdown);
        $markdown = preg_replace('/on\w+\s*=\s*[^>]*>/i', '', $markdown);

        return $markdown;
    }

    // ==================== INPUT SANITIZATION ====================

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

    public static function sanitizeInteger(mixed $input, ?int $min = null, ?int $max = null): ?int
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

        return filter_var($url, FILTER_SANITIZE_URL) ?: null;
    }

    public static function sanitizeFilename(string $filename, string $replacement = '_'): string
    {
        $filename = preg_replace('/[^\w\.\-]/', $replacement, $filename);
        $filename = preg_replace('/' . preg_quote($replacement, '/') . '+/', $replacement, $filename);
        $filename = trim($filename, $replacement . '.');

        if (empty($filename)) {
            return 'file';
        }

        return $filename;
    }

    public static function sanitizeSlug(string $input, string $replacement = '-'): string
    {
        $slug = mb_strtolower($input);
        $slug = preg_replace('/[^\pL\pN]+/u', $replacement, $slug);
        $slug = trim($slug, $replacement);

        return empty($slug) ? 'untitled' : $slug;
    }

    // ==================== CONFIGURATION ====================

    public static function setAllowedTags(array $tags): void
    {
        self::$allowedTags = $tags;
    }

    public static function setAllowedAttributes(array $attributes): void
    {
        self::$allowedAttributes = $attributes;
    }

    public static function resetToDefaults(): void
    {
        self::$allowedTags = self::DEFAULT_ALLOWED_TAGS;
        self::$allowedAttributes = self::DEFAULT_ALLOWED_ATTRIBUTES;
    }
}
