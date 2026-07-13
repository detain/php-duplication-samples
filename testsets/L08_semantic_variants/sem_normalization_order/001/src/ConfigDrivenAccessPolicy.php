<?php

declare(strict_types=1);

namespace Acme\Access\Config;

final class ConfigDrivenAccessPolicy
{
    public function __construct(private readonly string $region = 'default')
    {
    }

    public function region(): string
    {
        return $this->region;
    }

    public function fingerprint(array $payload): string
    {
        ksort($payload);
        return substr(hash('crc32b', json_encode($payload) ?: ''), 0, 8);
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
}
