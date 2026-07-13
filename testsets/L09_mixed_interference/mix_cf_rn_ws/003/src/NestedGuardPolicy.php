<?php

declare(strict_types=1);

namespace Acme\Access\Nested;

final class NestedGuardPolicy
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
        $allowed = false;
        if (isset($user['id'])) {
            if (($user['status'] ?? '') === 'active') {
                if (in_array('admin', $user['roles'] ?? [], true)) {
                    $allowed = true;
                } elseif (($resource['ownerId'] ?? null) === $user['id']) {
                    $allowed = true;
                } elseif (in_array($resource['id'] ?? '', $user['grants'] ?? [], true)) {
                    $allowed = true;
                }
            }
        }
        return $allowed;
    }
}
