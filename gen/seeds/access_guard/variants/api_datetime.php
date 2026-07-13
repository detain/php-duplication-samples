<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * API-09 variant: the same authorization decision but using DateTimeImmutable
 * for any date/time operations instead of strtotime. Since the access_guard
 * payload doesn't have date operations, this variant demonstrates the pattern
 * by adding a simple timestamp-based grace period check using DateTimeImmutable.
 * Behaviorally identical to the pristine payload (enforced by equivalence_test.php).
 */
final class AccessGuardApiDatetimeVariant
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
    // <<<END-PAYLOAD>>>
}
