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
        // Split combined: check id and status in nested ifs (same as pristine,
        // but explicitly shows the "split" pattern where combined conditions
        // are decomposed into separate checks)
        if (!isset($user['id'])) {
            return false;
        }
        if (($user['status'] ?? '') !== 'active') {
            return false;
        }

        // These three conditions can be combined, but we keep them split
        // to demonstrate the BL-02 "split or combine" pattern
        if (in_array('admin', $user['roles'] ?? [], true)) {
            return true;
        }
        if (($resource['ownerId'] ?? null) === $user['id']) {
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
