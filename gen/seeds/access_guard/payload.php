<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * Seed payload: resource authorization written with early-return guard clauses.
 * The CF-03 variant expresses the same decision with nested conditionals.
 */
final class AccessGuardSeed
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
