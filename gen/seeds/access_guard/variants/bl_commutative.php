<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * BL-03 variant: the same authorization decision as the pristine guard-clause
 * payload, re-expressed with reordered conditions in boolean expressions.
 * Commutative law: ($a && $b) === ($b && $a), ($a || $b) === ($b || $a)
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardCommutativeVariant
{
    // <<<PAYLOAD:access_guard>>>
    public function authorize(array $user, array $resource): bool
    {
        // Reordered: check isActive before hasId (commutative within guard context)
        if (($user['status'] ?? '') !== 'active') {
            return false;
        }
        if (!isset($user['id'])) {
            return false;
        }
        // Reordered: check owner before admin role (commutative OR)
        if (($resource['ownerId'] ?? null) === $user['id']) {
            return true;
        }
        if (in_array('admin', $user['roles'] ?? [], true)) {
            return true;
        }
        if (in_array($resource['id'] ?? '', $user['grants'] ?? [], true)) {
            return true;
        }
        return false;
    }
    // <<<END-PAYLOAD>>>
}
