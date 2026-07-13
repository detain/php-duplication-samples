<?php

declare(strict_types=1);

namespace Acme\Access\Projects;

final class ProjectAccessPolicy
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
        // Phase 1: USER_VALIDITY - check user identity and status
        if (!isset($user['id']) || ($user['status'] ?? '') !== 'active') {
            return false;
        }

        // Phase 2: ROLE_CHECK - admin role grants immediate access
        if (in_array('admin', $user['roles'] ?? [], true)) {
            return true;
        }

        // Phase 3: OWNER_CHECK - resource owner has access
        if (($resource['ownerId'] ?? null) === $user['id']) {
            return true;
        }

        // Phase 4: GRANT_CHECK - direct resource grant grants access
        if (in_array($resource['id'] ?? '', $user['grants'] ?? [], true)) {
            return true;
        }

        return false;
    }
}
