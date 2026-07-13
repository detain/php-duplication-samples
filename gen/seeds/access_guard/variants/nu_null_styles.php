<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * NU-01 variant: the same authorization decision as the pristine guard-clause
 * payload, re-expressed using PHP null-coalescing operator ?? style.
 * isset($x) ? $x : $default  →  $x ?? $default
 * isset($user['id']) → ($user['id'] ?? null) !== null
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardNullStylesVariant
{
    // <<<PAYLOAD:access_guard>>>
    public function authorize(array $user, array $resource): bool
    {
        // Null-coalescing style: ($user['id'] ?? null) !== null  instead of  isset($user['id'])
        if (($user['id'] ?? null) === null) {
            return false;
        }
        if (($user['status'] ?? '') !== 'active') {
            return false;
        }
        if (in_array('admin', $user['roles'] ?? [], true)) {
            return true;
        }
        if (($resource['ownerId'] ?? null) === $user['id']) {
            return true;
        }
        // Null-coalescing for grants check
        $resourceId = $resource['id'] ?? '';
        $grants = $user['grants'] ?? [];
        if (in_array($resourceId, $grants, true)) {
            return true;
        }
        return false;
    }
    // <<<END-PAYLOAD>>>
}
