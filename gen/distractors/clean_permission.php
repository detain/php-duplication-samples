<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function hasRole(array $user, string $role): bool
    {
        return in_array($role, $user['roles'] ?? [], true);
    }

    public function isAdmin(array $user): bool
    {
        return $this->hasRole($user, 'admin');
    }
}
