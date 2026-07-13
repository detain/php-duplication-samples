<?php

declare(strict_types=1);

namespace Acme\Access\State;

use RuntimeException;

final class StateMachineAccessPolicy
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    public function authorize(array $user, array $resource): bool
    {
        // State constants as string literals (avoids self:: outside class scope)
        $STATE_UNKNOWN = 'UNKNOWN';
        $STATE_USER_VALID = 'USER_VALID';
        $STATE_ROLE_CHECKED = 'ROLE_CHECKED';
        $STATE_OWNER_CHECKED = 'OWNER_CHECKED';
        $STATE_GRANT_CHECKED = 'GRANT_CHECKED';
        $STATE_ALLOWED = 'ALLOWED';
        $STATE_DENIED = 'DENIED';

        $state = $STATE_UNKNOWN;
        $userId = $user['id'] ?? null;
        $userStatus = $user['status'] ?? '';
        $userRoles = $user['roles'] ?? [];
        $resourceId = $resource['id'] ?? '';
        $resourceOwnerId = $resource['ownerId'] ?? null;
        $userGrants = $user['grants'] ?? [];

        while (true) {
            switch ($state) {
                case $STATE_UNKNOWN:
                    if ($userId === null) {
                        $state = $STATE_DENIED;
                        break;
                    }
                    $state = $STATE_USER_VALID;
                    break;

                case $STATE_USER_VALID:
                    if ($userStatus !== 'active') {
                        $state = $STATE_DENIED;
                        break;
                    }
                    $state = $STATE_ROLE_CHECKED;
                    break;

                case $STATE_ROLE_CHECKED:
                    if (in_array('admin', $userRoles, true)) {
                        $state = $STATE_ALLOWED;
                        break;
                    }
                    $state = $STATE_OWNER_CHECKED;
                    break;

                case $STATE_OWNER_CHECKED:
                    if ($resourceOwnerId === $userId) {
                        $state = $STATE_ALLOWED;
                        break;
                    }
                    $state = $STATE_GRANT_CHECKED;
                    break;

                case $STATE_GRANT_CHECKED:
                    if (in_array($resourceId, $userGrants, true)) {
                        $state = $STATE_ALLOWED;
                        break;
                    }
                    $state = $STATE_DENIED;
                    break;

                case $STATE_ALLOWED:
                    return true;

                case $STATE_DENIED:
                    return false;

                default:
                    return false;
            }
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
