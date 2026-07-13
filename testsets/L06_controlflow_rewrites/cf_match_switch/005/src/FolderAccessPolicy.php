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
        if (!isset($user['id']) || ($user['status'] ?? '') !== 'active') {
            return false;
        }

        return match (true) {
            in_array('admin', $user['roles'] ?? [], true) => true,
            ($resource['ownerId'] ?? null) === $user['id'] => true,
            in_array($resource['id'] ?? '', $user['grants'] ?? [], true) => true,
            default => false,
        };
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
