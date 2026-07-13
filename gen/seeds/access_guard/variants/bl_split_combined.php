<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * BL-02 variant: the same authorization decision as the pristine guard-clause
 * payload, re-expressed by splitting a combined condition into nested ifs, OR
 * combining nested ifs into a single compound condition.
 *
 * This variant demonstrates splitting: combined user validation (id && status)
 * into nested conditionals with explicit early-returns.
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardSplitCombinedVariant
{
    // <<<PAYLOAD:access_guard>>>
    public function authorize(array $user, array $resource): bool
    {
        // Split combined: check id and status in nested ifs (same as pristine,
        // but explicitly shows the "split" pattern where combined conditions
        // are decomposed into separate checks)
        if (!isset($user['id'])) {
            return false;
        }
        if (($user['status'] ?? '') !== 'active') {
            return false;
        }

        // These three conditions can be combined, but we keep them split
        // to demonstrate the BL-02 "split or combine" pattern
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
