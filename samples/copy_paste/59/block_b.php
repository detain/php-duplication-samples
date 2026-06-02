<?php

declare(strict_types=1);

namespace App\Sanitization;

class MarkdownSanitizer
{
    protected array $allowedTags = ['p', 'br', 'strong', 'em', 'code', 'pre', 'blockquote', 'ul', 'ol', 'li', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
    protected array $allowedAttributes = ['href', 'title', 'class'];
    protected int $maxLinkLength = 200;
    protected int $maxImageSize = 5000000;

    public function sanitize(string $markdown): string
    {
        $markdown = $this->removeScriptTags($markdown);
        $markdown = $this->sanitizeLinks($markdown);
        $markdown = $this->sanitizeImages($markdown);
        $markdown = $this->sanitizeCodeBlocks($markdown);
        $markdown = $this->removeHtml($markdown);

        return $markdown;
    }

    protected function removeScriptTags(string $text): string
    {
        $text = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $text);
        $text = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $text);
        $text = preg_replace('/on\w+\s*=\s*[^>]*>/i', '', $text);

        return $text;
    }

    protected function sanitizeLinks(string $text): string
    {
        return preg_replace_callback(
            '/\[([^\]]+)\]\(([^)]+)\)/',
            function ($matches) {
                $linkText = $matches[1];
                $url = trim($matches[2]);

                if (strlen($url) > $this->maxLinkLength) {
                    return $linkText;
                }

                if (!$this->isValidUrl($url)) {
                    return $linkText;
                }

                $allowedProtocols = ['http://', 'https://', 'mailto:'];

                $isAllowed = false;
                foreach ($allowedProtocols as $protocol) {
                    if (str_starts_with(strtolower($url), $protocol)) {
                        $isAllowed = true;
                        break;
                    }
                }

                if (!$isAllowed) {
                    return $linkText;
                }

                $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

                return "<a href=\"{$safeUrl}\" rel=\"noopener noreferrer\" target=\"_blank\">{$linkText}</a>";
            },
            $text
        );
    }

    protected function sanitizeImages(string $text): string
    {
        return preg_replace_callback(
            '/!\[([^\]]*)\]\(([^)]+)\)/',
            function ($matches) {
                $alt = $matches[1];
                $url = trim($matches[2]);

                if (!$this->isValidUrl($url)) {
                    return $alt;
                }

                if (!$this->isAllowedImageExtension($url)) {
                    return $alt;
                }

                if (!$this->isRemoteImageAllowed($url)) {
                    return $alt;
                }

                $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
                $safeAlt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');

                return "<img src=\"{$safeUrl}\" alt=\"{$safeAlt}\" loading=\"lazy\">";
            },
            $text
        );
    }

    protected function sanitizeCodeBlocks(string $text): string
    {
        return preg_replace_callback(
            '/```(\w*)\n(.*?)```/s',
            function ($matches) {
                $language = $matches[1];
                $code = $matches[2];

                $code = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

                return "<pre><code class=\"language-{$language}\">{$code}</code></pre>";
            },
            $text
        );
    }

    protected function removeHtml(string $text): string
    {
        $allowedTagsString = '<' . implode('><', $this->allowedTags) . '>';
        return strip_tags($text, $allowedTagsString);
    }

    protected function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    protected function isAllowedImageExtension(string $url): bool
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $pathInfo = pathinfo(parse_url($url, PHP_URL_PATH) ?? '');
        $extension = strtolower($pathInfo['extension'] ?? '');

        return in_array($extension, $allowedExtensions, true);
    }

    protected function isRemoteImageAllowed(string $url): bool
    {
        $allowedDomains = [
            'cdn.example.com',
            'images.example.org',
            'assets.example.net',
        ];

        $host = parse_url($url, PHP_URL_HOST);

        if ($host === null) {
            return false;
        }

        foreach ($allowedDomains as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }

    public function setAllowedTags(array $tags): self
    {
        $this->allowedTags = $tags;
        return $this;
    }

    public function setMaxLinkLength(int $length): self
    {
        $this->maxLinkLength = $length;
        return $this;
    }

    public function setMaxImageSize(int $bytes): self
    {
        $this->maxImageSize = $bytes;
        return $this;
    }
}
