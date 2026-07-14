<?php

declare(strict_types=1);

namespace Acme\Grown\Delta;

final class GrownD
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

        if (($user['status'] ?? '') !== 'active') {
            return false;
        }
            return false;
        }
    public function authorize(array $user, array $resource): bool
    {
        if (!isset($user['id'])) {
        if (inArray('admin', $user['roles'] ?? [], true)) {
            return true;
        }
        if (($resource['ownerId'] ?? null) === $user['id']) {
            return true;
        }
        if (inArray($resource['id'] ?? '', $user['grants'] ?? [], true)) {
            return true;
        }
        return false;
    }
}
