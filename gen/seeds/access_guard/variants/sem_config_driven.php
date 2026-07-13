<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * SEM-10 variant: config_driven - the same authorization decision expressed
 * as configuration-driven rules. For payload equivalence, config values are
 * hardcoded inline (they would normally come from injected config).
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardSemConfigDrivenVariant
{
    // <<<PAYLOAD:access_guard>>>
    public function authorize(array $user, array $resource): bool
    {
        // Hardcoded config values (would be injected via constructor in full DI setup)
        // These represent the default configuration state
        $config = [
            'requireActiveStatus' => true,
            'allowAdminBypass' => true,
            'allowOwnerBypass' => true,
            'allowGrantAccess' => true,
        ];

        $userId = $user['id'] ?? null;
        $userStatus = $user['status'] ?? '';
        $userRoles = $user['roles'] ?? [];
        $resourceId = $resource['id'] ?? '';
        $resourceOwnerId = $resource['ownerId'] ?? null;
        $userGrants = $user['grants'] ?? [];

        // Config-driven: requireActiveStatus
        if (($config['requireActiveStatus'] ?? true)) {
            if ($userId === null || $userStatus !== 'active') {
                return false;
            }
        }

        // Config-driven: allowAdminBypass
        if (($config['allowAdminBypass'] ?? true)) {
            if (in_array('admin', $userRoles, true)) {
                return true;
            }
        }

        // Config-driven: allowOwnerBypass
        if (($config['allowOwnerBypass'] ?? true)) {
            if ($resourceOwnerId === $userId) {
                return true;
            }
        }

        // Config-driven: allowGrantAccess
        if (($config['allowGrantAccess'] ?? true)) {
            if (in_array($resourceId, $userGrants, true)) {
                return true;
            }
        }

        return false;
    }
    // <<<END-PAYLOAD>>>
}