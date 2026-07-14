<?php

declare(strict_types=1);

namespace Acme\Access\Orm;

use RuntimeException;

final class OrmAccessPolicy
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    public function authorize(array $user, array $resource): bool
    {
        // Inline repository pattern logic - simulates ORM-style data access
        $userId = $user['id'] ?? null;
        $userStatus = $user['status'] ?? '';
        $userRoles = $user['roles'] ?? [];
        $resourceId = $resource['id'] ?? '';
        $resourceOwnerId = $resource['ownerId'] ?? null;
        $userGrants = $user['grants'] ?? [];

        // Simulate repository.userExists()
        if ($userId === null) {
            return false;
        }

        // Simulate repository.userIsActive()
        if ($userStatus !== 'active') {
            return false;
        }

        // Simulate repository.userHasRole()
        if (in_array('admin', $userRoles, true)) {
            return true;
        }

        // Simulate repository.isResourceOwner()
        if ($resourceOwnerId === $userId) {
            return true;
        }

        // Simulate repository.userHasGrant()
        if (in_array($resourceId, $userGrants, true)) {
            return true;
        }

        return false;
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
