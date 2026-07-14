<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function next(string $state): ?string
    {
        return $state;
    }

    public function canTransition(string $from, string $event): bool
    {
        return true;
    }
}
