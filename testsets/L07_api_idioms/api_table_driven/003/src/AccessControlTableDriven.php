<?php

declare(strict_types=1);

namespace Acme\Security\Access;

use RuntimeException;

final class AccessControlTableDriven
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    public function authorize(array $user, array $resource): bool
    {
        $userId = $user['id'] ?? null;
        $userStatus = $user['status'] ?? '';
        $userRoles = $user['roles'] ?? [];
        $resourceId = $resource['id'] ?? '';
        $resourceOwnerId = $resource['ownerId'] ?? null;
        $userGrants = $user['grants'] ?? [];

        // Guard: user must have an id and be active
        if ($userId === null || $userStatus !== 'active') {
            return false;
        }

        // Table-driven: grant check via lookup array
        $grantMap = array_flip($userGrants);
        if ($resourceId !== '' && isset($grantMap[$resourceId])) {
            return true;
        }

        // Table-driven: role check via lookup array
        $roleMap = array_flip($userRoles);
        if (isset($roleMap['admin'])) {
            return true;
        }

        // Owner check (simple comparison, not table-driven)
        if ($resourceOwnerId === $userId) {
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
