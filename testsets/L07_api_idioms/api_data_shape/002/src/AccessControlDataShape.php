<?php

declare(strict_types=1);

namespace Acme\Security\Access;

use RuntimeException;

final class AccessControlDataShape
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

        // Guard: user validity check first (security invariant)
        if ($userId === null || $userStatus !== 'active') {
            return false;
        }

        // Role check using stdClass for structured data
        $roleDto = new \stdClass();
        foreach ($userRoles as $role) {
            $roleDto->{$role} = true;
        }
        if (!empty($roleDto->admin)) {
            return true;
        }

        // Grant check using stdClass for structured data
        $grantDto = new \stdClass();
        foreach ($userGrants as $grant) {
            $grantDto->{$grant} = true;
        }
        if (!empty($grantDto->{$resourceId})) {
            return true;
        }

        // Owner check
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
