<?php

declare(strict_types=1);

namespace App\Helpers;

class UrlHelper
{
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

    public static function parseUrl(string $url): ?array
    {
        $parsed = parse_url($url);

        if ($parsed === false) {
            return null;
        }

        return $parsed;
    }

    public static function buildUrl(string $scheme, string $host, string $path = '', array $query = [], string $fragment = ''): string
    {
        $url = "{$scheme}://{$host}";

        if (!empty($path)) {
            $url .= '/' . ltrim($path, '/');
        }

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        if (!empty($fragment)) {
            $url .= '#' . $fragment;
        }

        return $url;
    }

    public static function addQueryParams(string $url, array $params): string
    {
        $parsed = parse_url($url);
        $queryParams = [];

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
        }

        $queryParams = array_merge($queryParams, $params);

        $newUrl = '';
        if (isset($parsed['scheme'])) {
            $newUrl .= $parsed['scheme'] . '://';
        }
        if (isset($parsed['host'])) {
            $newUrl .= $parsed['host'];
        }
        if (isset($parsed['path'])) {
            $newUrl .= $parsed['path'];
        }
        if (!empty($queryParams)) {
            $newUrl .= '?' . http_build_query($queryParams);
        }
        if (isset($parsed['fragment'])) {
            $newUrl .= '#' . $parsed['fragment'];
        }

        return $newUrl;
    }

    public static function removeQueryParams(string $url, array $paramsToRemove): string
    {
        $parsed = parse_url($url);
        $queryParams = [];

        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
        }

        foreach ($paramsToRemove as $param) {
            unset($queryParams[$param]);
        }

        $newUrl = '';
        if (isset($parsed['scheme'])) {
            $newUrl .= $parsed['scheme'] . '://';
        }
        if (isset($parsed['host'])) {
            $newUrl .= $parsed['host'];
        }
        if (isset($parsed['path'])) {
            $newUrl .= $parsed['path'];
        }
        if (!empty($queryParams)) {
            $newUrl .= '?' . http_build_query($queryParams);
        }
        if (isset($parsed['fragment'])) {
            $newUrl .= '#' . $parsed['fragment'];
        }

        return $newUrl;
    }

    public static function isAbsolute(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }

    public static function isRelative(string $url): bool
    {
        return !self::isAbsolute($url);
    }

    public static function absoluteUrl(string $base, string $relative): string
    {
        if (self::isAbsolute($relative)) {
            return $relative;
        }

        $parsed = parse_url($base);

        if (str_starts_with($relative, '//')) {
            return ($parsed['scheme'] ?? 'https') . ':' . $relative;
        }

        $path = $parsed['path'] ?? '';
        $pathParts = explode('/', $path);
        array_pop($pathParts);

        if (str_starts_with($relative, '/')) {
            $pathParts = [];
        }

        $relativeParts = explode('/', ltrim($relative, '/'));

        foreach ($relativeParts as $part) {
            if ($part === '..') {
                array_pop($pathParts);
            } elseif ($part !== '.') {
                $pathParts[] = $part;
            }
        }

        $newPath = implode('/', $pathParts);

        return ($parsed['scheme'] ?? 'https') . '://'
            . ($parsed['host'] ?? '')
            . '/' . ltrim($newPath, '/');
    }

    public static function getDomain(string $url): ?string
    {
        $parsed = parse_url($url);

        if (!isset($parsed['host'])) {
            return null;
        }

        return $parsed['host'];
    }

    public static function getSubdomain(string $url): ?string
    {
        $domain = self::getDomain($url);

        if ($domain === null) {
            return null;
        }

        $parts = explode('.', $domain);

        if (count($parts) < 3) {
            return null;
        }

        return $parts[0];
    }

    public static function getTld(string $url): ?string
    {
        $domain = self::getDomain($url);

        if ($domain === null) {
            return null;
        }

        $parts = explode('.', $domain);
        return end($parts) ?: null;
    }

    public static function isHttps(string $url): bool
    {
        $parsed = parse_url($url);
        return ($parsed['scheme'] ?? '') === 'https';
    }

    public static function makeHttps(string $url): string
    {
        return preg_replace('/^http:/', 'https:', $url, 1);
    }

    public static function makeHttp(string $url): string
    {
        return preg_replace('/^https:/', 'http:', $url, 1);
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
}
