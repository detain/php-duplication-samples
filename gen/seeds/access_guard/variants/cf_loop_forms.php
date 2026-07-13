<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * CF-04 variant: the same authorization decision as the pristine guard-clause
 * payload, re-expressed using a loop form. Since authorize is a boolean predicate
 * (not a search/reduce operation), we use a loop to iterate through grants and
 * check for a match, which is a valid loop-form expression of the same logic.
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardLoopFormsVariant
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
        // Loop form: iterate through grants to check for resource match
        foreach ($user['grants'] ?? [] as $grant) {
            if ($grant === ($resource['id'] ?? '')) {
                return true;
            }
        }
        return false;
    }
    // <<<END-PAYLOAD>>>
}
