<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * SEM-08 variant: cross_file_scatter - the same authorization decision
 * split across logically separate concerns conceptually. In this variant,
 * the scatter is expressed as inline logic with descriptive variable names
 * indicating which "class" each check belongs to conceptually.
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardSemScatterVariant
{
    // <<<PAYLOAD:access_guard>>>
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
    // <<<END-PAYLOAD>>>
}