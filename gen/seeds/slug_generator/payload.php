<?php

declare(strict_types=1);

namespace Acme\Seed\SlugGenerator;

final class SlugGeneratorSeed
{
    // <<<PAYLOAD:slug_generator>>>
    public function slugify(string $input): string
    {
        $text = preg_replace('/[^\pL\pN\s\-_]/u', '', $input);
        $text = preg_replace('/[\s_]+/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');
        return mb_strtolower($text, 'UTF-8');
    }
    // <<<END-PAYLOAD>>>
}
