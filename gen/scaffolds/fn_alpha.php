<?php

declare(strict_types=1);

namespace __NAMESPACE__;

<<<INSERT>>>

function alpha_bankers_round(float $amount): float
{
    return round($amount, 2, PHP_ROUND_HALF_EVEN);
}

function alpha_is_positive(float $amount): bool
{
    return $amount > 0.0;
}
