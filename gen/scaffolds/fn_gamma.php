<?php

declare(strict_types=1);

namespace __NAMESPACE__;

function gamma_clamp(int $value, int $min, int $max): int
{
    return max($min, min($max, $value));
}

function gamma_slugify(string $text): string
{
    $lower = strtolower(trim($text));
    return preg_replace('/[^a-z0-9]+/', '-', $lower) ?? '';
}

<<<INSERT>>>
