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
        // De Morgan application: !(A && B) || C  becomes  (!A || !B || C)
        // A = isset($user['id'])
        // B = ($user['status'] ?? '') === 'active'
        // C = (admin_role OR owner OR grant)
        $hasId = isset($user['id']);
        $isActive = ($user['status'] ?? '') === 'active';
        $hasAdminRole = in_array('admin', $user['roles'] ?? [], true);
        $isOwner = ($resource['ownerId'] ?? null) === $user['id'];
        $hasGrant = in_array($resource['id'] ?? '', $user['grants'] ?? [], true);

        return $hasId && $isActive && ($hasAdminRole || $isOwner || $hasGrant);
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
