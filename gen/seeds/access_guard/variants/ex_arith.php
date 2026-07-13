<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * EX-01 variant: arithmetic rewrite.
 *
 * NOTE: This seed (authorize boolean predicate) does not involve arithmetic
 * operations. The authorization logic is purely boolean (isset, comparison,
 * in_array checks). Arithmetic rewriting is not applicable to this seed.
 *
 * This variant is provided for completeness of the variant taxonomy but
 * preserves the original logic since no arithmetic transformation is possible.
 */
final class AccessGuardArithVariant
{
    // <<<PAYLOAD:access_guard>>>
    public function authorize(array $user, array $resource): bool
    {
        // No arithmetic operations in this boolean predicate seed.
        // The logic remains unchanged as arithmetic rewrite is N/A.
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
