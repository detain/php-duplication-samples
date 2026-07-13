<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * SEM-05 variant: di_variants - the same authorization decision expressed
 * with dependency injection style. Inline RoleChecker and GrantChecker logic
 * to avoid extra class dependencies that won't exist in extracted payload.
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardSemDiVariant
{
    // <<<PAYLOAD:access_guard>>>
    public function authorize(array $user, array $resource): bool
    {
        // Inline DI-style checks - actual DI would use injected RoleChecker/GrantChecker
        if (!isset($user['id'])) {
            return false;
        }
        if (($user['status'] ?? '') !== 'active') {
            return false;
        }
        // Role check (RoleChecker would be injected in full DI setup)
        if (in_array('admin', $user['roles'] ?? [], true)) {
            return true;
        }
        if (($resource['ownerId'] ?? null) === $user['id']) {
            return true;
        }
        // Grant check (GrantChecker would be injected in full DI setup)
        if (in_array($resource['id'] ?? '', $user['grants'] ?? [], true)) {
            return true;
        }
        return false;
    }
    // <<<END-PAYLOAD>>>
}