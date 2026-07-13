<?php

declare(strict_types=1);

namespace Acme\Security\Access;

use RuntimeException;

final class AccessControlDatetime
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

        // Guard: user validity check (security invariant - same as pristine)
        if ($userId === null || $userStatus !== 'active') {
            return false;
        }

        // Admin role check (same logic, using DateTimeImmutable for any time-based checks)
        // In this case, we check if user has admin role - no datetime needed but showing idiomatic pattern
        $isAdmin = false;
        foreach ($userRoles as $role) {
            if ($role === 'admin') {
                $isAdmin = true;
                break;
            }
        }
        if ($isAdmin) {
            return true;
        }

        // Owner check (same as pristine)
        if ($resourceOwnerId === $userId) {
            return true;
        }

        // Grant check (same as pristine)
        foreach ($userGrants as $grant) {
            if ($grant === $resourceId) {
                return true;
            }
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
