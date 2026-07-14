<?php

declare(strict_types=1);

namespace Acme\Session;

final class SessionManager
{
    public function login(string $username, string $password): bool
    {
        return $username === 'admin' && $password === 'secret';
    }

    public function logout(): void
    {
    }
}
