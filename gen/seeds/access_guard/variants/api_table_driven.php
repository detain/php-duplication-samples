<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * API-05 variant: the same authorization decision expressed with a lookup
 * array instead of an if/else-if chain. Behaviorally identical to the
 * pristine payload (enforced by equivalence_test.php).
 */
final class AccessGuardApiTableDrivenVariant
{
    // <<<PAYLOAD:access_guard>>>
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
    // <<<END-PAYLOAD>>>
}
