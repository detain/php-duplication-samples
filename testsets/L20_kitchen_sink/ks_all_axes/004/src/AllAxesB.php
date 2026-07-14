<?php

declare(strict_types=1);

namespace Acme\AllAxes\Beta;

use RuntimeException;

final class AllAxesB
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

        if (($user['status'] ?? '') !== 'active') {
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

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
