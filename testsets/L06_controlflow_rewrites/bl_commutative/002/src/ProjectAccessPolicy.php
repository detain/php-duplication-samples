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
        return !isset($user['id'])
            ? false
            : (($user['status'] ?? '') !== 'active'
                ? false
                : (in_array('admin', $user['roles'] ?? [], true)
                    ? true
                    : ((($resource['ownerId'] ?? null) === $user['id'])
                        ? true
                        : (in_array($resource['id'] ?? '', $user['grants'] ?? [], true)
                            ? true
                            : false))));
    }
}
