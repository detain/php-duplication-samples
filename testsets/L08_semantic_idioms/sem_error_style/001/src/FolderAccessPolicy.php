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

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
