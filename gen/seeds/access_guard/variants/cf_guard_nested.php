<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * CF-03 variant: the same authorization decision as the pristine guard-clause
 * payload, re-expressed as nested conditionals with a single result variable.
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardNestedVariant
{
    // <<<PAYLOAD:access_guard>>>
    public function authorize(array $user, array $resource): bool
    {
        $allowed = false;
        if (isset($user['id'])) {
            if (($user['status'] ?? '') === 'active') {
                if (in_array('admin', $user['roles'] ?? [], true)) {
                    $allowed = true;
                } elseif (($resource['ownerId'] ?? null) === $user['id']) {
                    $allowed = true;
                } elseif (in_array($resource['id'] ?? '', $user['grants'] ?? [], true)) {
                    $allowed = true;
                }
            }
        }
        return $allowed;
    }
    // <<<END-PAYLOAD>>>
}
