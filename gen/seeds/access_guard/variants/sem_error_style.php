<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * SEM-04 variant: error_style - the same authorization decision expressed
 * with exceptions instead of boolean return. A try/catch wrapper translates
 * exceptions back to boolean for equivalence with the original.
 * Uses built-in Exception class for compatibility with payload extraction.
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardSemErrorStyleVariant
{
    // <<<PAYLOAD:access_guard>>>
    public function authorize(array $user, array $resource): bool
    {
        try {
            // Inline authorizeOrThrow logic using built-in Exception
            if (!isset($user['id'])) {
                throw new \Exception('User identity not found');
            }
            if (($user['status'] ?? '') !== 'active') {
                throw new \Exception('User status is not active');
            }
            if (in_array('admin', $user['roles'] ?? [], true)) {
                return true; // authorized by role
            }
            if (($resource['ownerId'] ?? null) === $user['id']) {
                return true; // authorized by ownership
            }
            if (in_array($resource['id'] ?? '', $user['grants'] ?? [], true)) {
                return true; // authorized by grant
            }
            throw new \Exception('No authorization grant matched');
        } catch (\Exception $e) {
            return false;
        }
    }
    // <<<END-PAYLOAD>>>
}