<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * CF-10 variant: the same authorization decision as the pristine guard-clause
 * payload, but with guard-clause logic INVERTED — single return at the end,
 * negated conditions combined with AND, returning true only when all checks pass.
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardInvertedVariant
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
        if (($resource['ownerId'] ?? null) === $user['id']) {
            return true;
        }
        if (in_array($resource['id'] ?? '', $user['grants'] ?? [], true)) {
            return true;
        }
        return false;
    }
    // <<<END-PAYLOAD>>>
}
