<?php

declare(strict_types=1);

namespace Acme\Access\Normalized;

use RuntimeException;

final class NormalizedOrderAccessPolicy
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

        // User validity check (must be first - security invariant)
        if ($userId === null || $userStatus !== 'active') {
            return false;
        }

        // Normalization: check grant access first (different from original)
        if ($resourceId !== '' && in_array($resourceId, $userGrants, true)) {
            return true;
        }

        // Normalization: check roles second (different from original role check position)
        if (in_array('admin', $userRoles, true)) {
            return true;
        }

        // Normalization: check owner last (different from original)
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
