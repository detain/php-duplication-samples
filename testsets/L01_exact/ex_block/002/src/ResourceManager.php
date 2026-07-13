<?php

declare(strict_types=1);

namespace Acme\Auth\Resources;

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

function alpha_bankers_round(float $amount): float
{
    return round($amount, 2, PHP_ROUND_HALF_EVEN);
}

function alpha_is_positive(float $amount): bool
{
    return $amount > 0.0;
}
