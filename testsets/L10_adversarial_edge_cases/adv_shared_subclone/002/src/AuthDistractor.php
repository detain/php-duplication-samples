<?php

declare(strict_types=1);

namespace Acme\Auth\Roles;

final class AuthDistractor
{
    public function checkRoleAccess(string $role, string $permission): bool
    {
        $roleHierarchy = $this->buildRoleHierarchy();
        $effectivePermissions = $this->resolveEffectivePermissions($role, $roleHierarchy);
        $hasPermission = in_array($permission, $effectivePermissions, true);
        if (!$hasPermission) {
            $this->recordAccessFailure($role, $permission);
        }
        return $hasPermission;
    }

    private function buildRoleHierarchy(): array
    {
        return [
            'admin' => ['read', 'write', 'delete', 'manage'],
            'moderator' => ['read', 'write'],
            'user' => ['read'],
        ];
    }

    private function resolveEffectivePermissions(string $role, array $hierarchy): array
    {
        $perms = $hierarchy[$role] ?? [];
        return $perms;
    }

    private function recordAccessFailure(string $role, string $permission): void
    {
        // record failure
    }

    public function getRoleDisplayName(string $role): string
    {
        return ucfirst($role);
    }
}
