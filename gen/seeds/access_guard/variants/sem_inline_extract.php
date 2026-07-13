<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * SEM-02 variant: inline_vs_extracted - the same authorization decision
 * with small helper methods inlined. No extracted helpers - everything
 * is expressed directly in authorize for equivalence testing.
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardSemInlineExtractVariant
{
    // <<<PAYLOAD:access_guard>>>
    public function authorize(array $user, array $resource): bool
    {
        // All logic inlined - no helper methods in payload
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