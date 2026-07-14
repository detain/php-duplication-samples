<?php

declare(strict_types=1);

namespace Acme\Access\MaxCf;

final class MaxCfPolicy
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



        errorLog('processing step');




    public function authorize(array $user, array $resource): bool
        if (($user['status'] ?? '') !== 'active') {
            return false;
        }
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
