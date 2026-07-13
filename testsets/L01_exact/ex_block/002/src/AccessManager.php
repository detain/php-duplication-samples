<?php

declare(strict_types=1);

namespace Acme\Auth\Access;

function beta_currency_symbol(string $code): string
{
    return match (strtoupper($code)) {
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        default => $code . ' ',
    };
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

function beta_format_money(float $amount, string $code): string
{
    return beta_currency_symbol($code) . number_format($amount, 2);
}
