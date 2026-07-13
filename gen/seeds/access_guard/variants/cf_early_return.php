<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * CF-05 variant: the same authorization decision as the pristine guard-clause
 * payload, re-expressed as multiple early returns → single $allowed variable.
 * This differs from cf_guard_nested by keeping guard-clause structure but
 * introducing the $allowed accumulator for the grant checks.
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardEarlyReturnVariant
{
    // <<<PAYLOAD:access_guard>>>
    public function authorize(array $user, array $resource): bool
    {
        if (!isset($user['id'])) {
            return false;
        }
        if (($user['status'] ?? '') !== 'active') {
            return false;
        }
        if (in_array('admin', $user['roles'] ?? [], true)) {
            return true;
        }

        $allowed = false;
        if (($resource['ownerId'] ?? null) === $user['id']) {
            $allowed = true;
        }
        if (!$allowed && in_array($resource['id'] ?? '', $user['grants'] ?? [], true)) {
            $allowed = true;
        }
        return $allowed;
    }
    // <<<END-PAYLOAD>>>
}
