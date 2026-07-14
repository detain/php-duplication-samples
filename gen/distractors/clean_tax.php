<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function calculateNetIncome(float $gross, float $rate): float
    {
        return round($gross * (1 - $rate), 2);
    }

    public function convertCurrency(float $amount, float $rate): float
    {
        return round($amount * $rate, 2);
    }
}
