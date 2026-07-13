<?php

declare(strict_types=1);

namespace __NAMESPACE__;

function beta_currency_symbol(string $code): string
{
    return match (strtoupper($code)) {
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        default => $code . ' ',
    };
}

<<<INSERT>>>

function beta_format_money(float $amount, string $code): string
{
    return beta_currency_symbol($code) . number_format($amount, 2);
}
