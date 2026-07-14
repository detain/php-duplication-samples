<?php

declare(strict_types=1);

namespace Acme\Util\Norm;

final class TextNormalizer
{
    public function normalize(string $input): string
    {
        $text = preg_replace('/[^\pL\pN\s]/u', '', $input);
        $text = preg_replace('/[\s]+/', '-', $text);
        return mb_strtolower(trim($text, '-'), 'UTF-8');
    }

    public function length(string $input): int
    {
        return mb_strlen($input, 'UTF-8');
    }
}
