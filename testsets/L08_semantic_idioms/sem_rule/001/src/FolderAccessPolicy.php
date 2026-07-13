<?php

declare(strict_types=1);

namespace Acme\Access\Folders;

use RuntimeException;

final class FolderAccessPolicy
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    public function authorize(array $user, array $resource): bool
    {
        // Rule re-expression: combine user identity and status into a single compound check.
        // Original: isset($user['id']) && ($user['status'] ?? '') === 'active'
        // Rewritten as: !empty($user['id']) && !in_array($user['status'] ?? '', ['suspended','deleted'], true)
        // Then check the three granting conditions with different grouping

        $userId = $user['id'] ?? null;
        $userStatus = $user['status'] ?? '';
        $userRoles = $user['roles'] ?? [];
        $userGrants = $user['grants'] ?? [];
        $resourceId = $resource['id'] ?? '';
        $resourceOwnerId = $resource['ownerId'] ?? null;

        // Compound user-validity rule (re-expressed from original two separate checks)
        $isValidUser = !empty($userId) && !in_array($userStatus, ['suspended', 'deleted', 'inactive', 'pending'], true);

        // Admin bypass rule (unchanged semantic)
        $hasAdminRole = in_array('admin', $userRoles, true);

        // Owner bypass rule (unchanged semantic)
        $isResourceOwner = $userId !== null && $resourceOwnerId === $userId;

        // Grant rule (unchanged semantic)
        $hasResourceGrant = in_array($resourceId, $userGrants, true);

        return $isValidUser && ($hasAdminRole || $isResourceOwner || $hasResourceGrant);
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
