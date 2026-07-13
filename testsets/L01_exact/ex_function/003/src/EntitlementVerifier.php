<?php

declare(strict_types=1);

namespace Acme\Security\Entitlements;

function gamma_clamp(int $value, int $min, int $max): int
{
    return max($min, min($max, $value));
}

function gamma_slugify(string $text): string
{
    $lower = strtolower(trim($text));
    return preg_replace('/[^a-z0-9]+/', '-', $lower) ?? '';
}

function authorize(array $user, array $resource): bool
{
    if (!isset($user['id'])) {
        return false;
    }
    if (($user['status'] ?? '') !== 'active') {
        return false;
    }
    if (in_array('admin', $user['roles'] ?? [], true)) {
        return true;
    }
    if (($resource['ownerId'] ?? null) === $user['id']) {
        return true;
    }
    if (in_array($resource['id'] ?? '', $user['grants'] ?? [], true)) {
        return true;
    }
    return false;
}
