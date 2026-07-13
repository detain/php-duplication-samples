<?php

declare(strict_types=1);

namespace Acme\Access\Scattered;

use RuntimeException;

final class ScatteredAccessPolicy
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    public function authorize(array $user, array $resource): bool
    {
        // Scattered concerns: UserValidator, ResourceOwnershipChecker, AccessGrants
        // Each conceptual class handles its own validation domain

        // UserValidator concern: check user identity and status (inlined)
        $userId = $user['id'] ?? null;
        $userStatus = $user['status'] ?? '';
        if (!isset($userId) || $userStatus !== 'active') {
            return false;
        }

        // ResourceOwnershipChecker concern: check if user owns the resource (inlined)
        $resourceOwnerId = $resource['ownerId'] ?? null;
        if ($resourceOwnerId === $userId) {
            return true;
        }

        // AccessGrants concern: admin role grants access (inlined)
        if (in_array('admin', $user['roles'] ?? [], true)) {
            return true;
        }

        // AccessGrants concern: direct grant for resource (inlined)
        if (in_array($resource['id'] ?? '', $user['grants'] ?? [], true)) {
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
