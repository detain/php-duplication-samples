<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * SEM-03 variant: oo_split - the same authorization decision split across
 * methods in a single class. But for the equivalence test, only the authorize
 * method logic is inlined. The semantic split is in the comments explaining
 * the logical phases: USER_VALIDITY, ROLE_CHECK, OWNER_CHECK, GRANT_CHECK.
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardSemOoSplitVariant
{
    // <<<PAYLOAD:access_guard>>>
    public function authorize(array $user, array $resource): bool
    {
        // Phase 1: USER_VALIDITY - check user identity and status
        if (!isset($user['id']) || ($user['status'] ?? '') !== 'active') {
            return false;
        }

        // Phase 2: ROLE_CHECK - admin role grants immediate access
        if (in_array('admin', $user['roles'] ?? [], true)) {
            return true;
        }

        // Phase 3: OWNER_CHECK - resource owner has access
        if (($resource['ownerId'] ?? null) === $user['id']) {
            return true;
        }

        // Phase 4: GRANT_CHECK - direct resource grant grants access
        if (in_array($resource['id'] ?? '', $user['grants'] ?? [], true)) {
            return true;
        }

        return false;
    }
    // <<<END-PAYLOAD>>>
}