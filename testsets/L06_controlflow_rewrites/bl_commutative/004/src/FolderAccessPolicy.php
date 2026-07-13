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
        // Reordered: check isActive before hasId (commutative within guard context)
        if (($user['status'] ?? '') !== 'active') {
            return false;
        }
        if (!isset($user['id'])) {
            return false;
        }
        // Reordered: check owner before admin role (commutative OR)
        if (($resource['ownerId'] ?? null) === $user['id']) {
            return true;
        }
        if (in_array('admin', $user['roles'] ?? [], true)) {
            return true;
        }
        if (in_array($resource['id'] ?? '', $user['grants'] ?? [], true)) {
            return true;
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
