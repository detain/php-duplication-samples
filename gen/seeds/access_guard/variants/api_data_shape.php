<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * API-07 variant: the same authorization data expressed as a stdClass DTO
 * instead of an associative array. Behaviorally identical output structure
 * (enforced by equivalence_test.php).
 */
final class AccessGuardApiDataShapeVariant
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
    // <<<END-PAYLOAD>>>
}
