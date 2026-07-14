<?php

declare(strict_types=1);

namespace Acme\Sec\AccessA;

final class AccessCheckerA
{
    public function check(array $user, string $permission, array $context = []): bool
    {
        $roles = $user['roles'] ?? [];
        foreach ($roles as $role) {
            if ($this->roleHasPermission($role, $permission)) {
                if ($this->checkContext($role, $permission, $context)) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function roleHasPermission(string $role, string $permission): bool
    {
        $permissions = $this->getRolePermissions($role);
        return in_array($permission, $permissions, true)
            || in_array('*', $permissions, true);
    }

    protected function getRolePermissions(string $role): array
    {
        return $this->permissionMap[$role] ?? [];
    }

    protected function checkContext(string $role, string $permission, array $context): bool
    {
        if (isset($context['owner_id'])) {
            $userId = $context['user_id'] ?? null;
            $ownerId = $context['owner_id'];
            if ($userId !== null && $userId === $ownerId) {
                return true;
            }
        }
        return false;
    }

    /** @var array<string,list<string>> */
    private array $permissionMap = [
        'admin' => ['*'],
        'editor' => ['articles.edit', 'articles.delete', 'comments.edit'],
        'viewer' => ['articles.view', 'comments.view'],
    ];

    public function label(): string
    {
        return strtolower(str_replace('\\', '.', static::class));
    }

    private function withinBounds(int $value, int $floor, int $ceiling): bool
    {
        return $value >= $floor && $value <= $ceiling;
    }
}
