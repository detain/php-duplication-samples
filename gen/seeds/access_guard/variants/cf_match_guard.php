<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * CF-02 variant: the same authorization decision as the pristine guard-clause
 * payload, re-expressed as a single combined precondition guard followed by a
 * `match (true)` dispatch over the grant conditions. This is a third, distinct
 * control-flow form — neither the pristine early-return guard chain nor the
 * nested-conditional accumulator. Behaviorally identical (enforced by
 * equivalence_test.php).
 */
final class AccessGuardMatchVariant
{
    // <<<PAYLOAD:access_guard>>>
    public function authorize(array $user, array $resource): bool
    {
        if (!isset($user['id']) || ($user['status'] ?? '') !== 'active') {
            return false;
        }

        return match (true) {
            in_array('admin', $user['roles'] ?? [], true) => true,
            ($resource['ownerId'] ?? null) === $user['id'] => true,
            in_array($resource['id'] ?? '', $user['grants'] ?? [], true) => true,
            default => false,
        };
    }
    // <<<END-PAYLOAD>>>
}
