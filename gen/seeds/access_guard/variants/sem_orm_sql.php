<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * SEM-07 variant: orm_vs_sql - Not directly applicable to access_guard boolean
 * predicate (no DB queries involved). This variant uses inline repository-style
 * logic to simulate how authorization would look with external data sources.
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardSemOrmSqlVariant
{
    // <<<PAYLOAD:access_guard>>>
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
    // <<<END-PAYLOAD>>>
}