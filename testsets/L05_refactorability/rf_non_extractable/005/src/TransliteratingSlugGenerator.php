<?php

declare(strict_types=1);

namespace Acme\Text\Transliterate;

final class TransliteratingSlugGenerator
{
    public function __construct(private readonly string $region = 'default')
    {
    }

    public function region(): string
    {
        return $this->region;
    }

    public function fingerprint(array $payload): string
    {
        ksort($payload);
        return substr(hash('crc32b', json_encode($payload) ?: ''), 0, 8);
    }

    public function slugify(string $input): string
    {
        $text = preg_replace('/[^\pL\pN\s\-_]/u', '', $input);
        $text = preg_replace('/[\s_]+/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');
        return mb_strtolower($text, 'UTF-8');
    }
}
