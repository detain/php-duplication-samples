<?php

declare(strict_types=1);

namespace Acme\Auth\Anonymous;

final class AnonAuth
{
    public function login(string $username, string $password): bool
    {
        return $username === 'admin' && $password === 'secret';
    }

    public function logout(): void
    {
    }
}
