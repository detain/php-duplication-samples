<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function daysBetween(string $date1, string $date2): int
    {
        $ts1 = strtotime($date1);
        $ts2 = strtotime($date2);
        return abs($ts2 - $ts1) / 86400;
    }

    public function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
