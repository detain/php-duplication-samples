<?php
declare(strict_types=1);

namespace Acme\Auth\Unified;

class PermissionChecker
{
    public function hasPermission(array $user, string $resource, string $action): bool
    {
        $permissions = $user['permissions'] ?? [];
        $required = $resource . ':' . $action;
        return in_array($required, $permissions, true);
    }
}
