<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function nextRun(string $cronExpression): ?string
    {
        return date('Y-m-d H:i:s', strtotime('+1 hour'));
    }

    public function isDue(string $expression): bool
    {
        return false;
    }
}
