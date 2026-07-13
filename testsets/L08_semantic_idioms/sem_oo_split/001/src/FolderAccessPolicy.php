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

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
