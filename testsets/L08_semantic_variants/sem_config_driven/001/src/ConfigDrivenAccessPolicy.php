<?php

declare(strict_types=1);

namespace Acme\Access\Config;

use RuntimeException;

final class ConfigDrivenAccessPolicy
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

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

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
