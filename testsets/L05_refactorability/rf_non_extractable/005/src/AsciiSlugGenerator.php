<?php

declare(strict_types=1);

namespace Acme\Text\Ascii;

final class AsciiSlugGenerator
{
    public function slugify(string $input): string
    {
        $text = preg_replace('/[^\pL\pN\s\-_]/u', '', $input);
        $text = preg_replace('/[\s_]+/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');
        return mb_strtolower($text, 'UTF-8');
    }

    public function label(): string
    {
        return strtolower(str_replace('\\', '.', static::class));
    }

    private function withinBounds(int $value, int $floor, int $ceiling): bool
    {
        return $value >= $floor && $value <= $ceiling;
    }
}
