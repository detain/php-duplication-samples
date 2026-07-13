<?php

declare(strict_types=1);

namespace Acme\Access\Folders;

use RuntimeException;

final class FolderAccessPolicy
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
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

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
