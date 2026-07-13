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
        // Event dispatch: beforeAuthorize (conceptual - registered handlers would fire here)
        // $this->dispatch('beforeAuthorize', ['user' => $user, 'resource' => $resource]);

        // Core authorization logic (inlined from doAuthorize)
        if (!isset($user['id'])) {
            // Event dispatch: beforeAuthorize could short-circuit here
            return false;
        }
        if (($user['status'] ?? '') !== 'active') {
            return false;
        }
        if (in_array('admin', $user['roles'] ?? [], true)) {
            // Event dispatch: afterAuthorize with admin bypass result
            return true;
        }
        if (($resource['ownerId'] ?? null) === $user['id']) {
            return true;
        }
        if (in_array($resource['id'] ?? '', $user['grants'] ?? [], true)) {
            return true;
        }
        return false;

        // Event dispatch: afterAuthorize (conceptual - registered handlers would fire here)
        // $this->dispatch('afterAuthorize', ['result' => $result]);
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
