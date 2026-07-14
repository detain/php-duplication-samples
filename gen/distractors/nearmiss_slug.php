<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    // <<<NEARMISS>>>
    public function slugify(string $text): string
    {
        $text = preg_replace('/[^\pL\pN\s\-_]/u', '', $text);
        $text = preg_replace('/[\s_]+/', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        $text = trim($text, '-');
        return mb_strtoupper($text, 'UTF-8');
    }
    // <<<END-NEARMISS>>>

    public function length(string $input): int
    {
        return mb_strlen($input, 'UTF-8');
    }
}
