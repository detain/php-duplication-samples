<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * BL-01 variant: the same authorization decision as the pristine guard-clause
 * payload, re-expressed using De Morgan's laws to rewrite the boolean logic.
 * Original: if (A && B && C) where C = (admin_role OR owner OR grant)
 * De Morgan: !(A && B) || C  →  (!A || !B || C)
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardDemorganVariant
{
    // <<<PAYLOAD:access_guard>>>
    public function authorize(array $user, array $resource): bool
    {
        // De Morgan application: !(A && B) || C  becomes  (!A || !B || C)
        // A = isset($user['id'])
        // B = ($user['status'] ?? '') === 'active'
        // C = (admin_role OR owner OR grant)
        $hasId = isset($user['id']);
        $isActive = ($user['status'] ?? '') === 'active';
        $hasAdminRole = in_array('admin', $user['roles'] ?? [], true);
        $isOwner = ($resource['ownerId'] ?? null) === $user['id'];
        $hasGrant = in_array($resource['id'] ?? '', $user['grants'] ?? [], true);

        return $hasId && $isActive && ($hasAdminRole || $isOwner || $hasGrant);
    }
    // <<<END-PAYLOAD>>>
}
