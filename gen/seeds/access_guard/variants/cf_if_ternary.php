<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * CF-01 variant: the same authorization decision as the pristine guard-clause
 * payload, re-expressed using ternary operators instead of if/else statements.
 * The full logic uses nested ternaries for early-return semantics.
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardIfTernaryVariant
{
    // <<<PAYLOAD:access_guard>>>
    public function authorize(array $user, array $resource): bool
    {
        return !isset($user['id'])
            ? false
            : (($user['status'] ?? '') !== 'active'
                ? false
                : (in_array('admin', $user['roles'] ?? [], true)
                    ? true
                    : ((($resource['ownerId'] ?? null) === $user['id'])
                        ? true
                        : (in_array($resource['id'] ?? '', $user['grants'] ?? [], true)
                            ? true
                            : false))));
    }
    // <<<END-PAYLOAD>>>
}
