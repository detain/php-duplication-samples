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
}
