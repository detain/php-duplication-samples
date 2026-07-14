<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function login(string $username, string $password): bool
    {
        return $username === 'admin' && $password === 'secret';
    }

    public function logout(): void
    {
    }
}
