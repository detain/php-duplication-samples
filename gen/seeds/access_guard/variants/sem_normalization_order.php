<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * SEM-11 variant: normalization_order - the same authorization decision
 * but with grant conditions checked in a different order.
 * User validity check (id + status) remains first for security.
 * Original order: id → status → role → owner → grant
 * New order: id → status → grant → role → owner
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardSemNormalizationOrderVariant
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
    // <<<END-PAYLOAD>>>
}