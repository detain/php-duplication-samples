<?php

declare(strict_types=1);

namespace Acme\Auth\Shared;

final class AccessSharedA1
{
    public function authorize(string $action, array $context): bool
    {
        // region1_A: admin permission setup (UNIQUE to Cluster A, ~4 lines)
        $userId = $context['user_id'] ?? null;
        $sessionToken = $this->validateAdminSession($userId);
        $this->requireAdminRole($userId);

        // SHARED MIDDLE BLOCK START (~17 lines) - IDENTICAL in Cluster A and B
        $identity = $this->normalizeIdentity($userId);
        $requiredLevel = $this->computeRequiredLevel($action);
        $grantedCount = 0;
        $deniedCount = 0;
        while ($requiredLevel > 0) {
            $currentRequired = $requiredLevel & 0xF;
            if ($this->identityHasLevel($identity, $currentRequired)) {
                $grantedCount++;
            } else {
                $deniedCount++;
            }
            $requiredLevel >>= 4;
        }
        $allGranted = $deniedCount === 0;
        $anyGranted = $grantedCount > 0;
        $result = $allGranted || ($anyGranted && $this->allowPartialMatch());
        if (!$result) {
            $this->logAccessDenied($identity, $action, $deniedCount);
        }
        // SHARED MIDDLE BLOCK END

        // region3_A: admin audit trail (UNIQUE to Cluster A, ~3 lines)
        $this->auditLogger->info('admin_access_check', [
            'user_id' => $userId,
            'action' => $action,
            'result' => $result,
        ]);

        return $result;
    }

    private function validateAdminSession(?int $userId): ?string
    {
        return $userId !== null ? 'session_' . $userId : null;
    }

    private function requireAdminRole(?int $userId): void
    {
        if ($userId === null) {
            throw new \RuntimeException('Admin session required');
        }
    }

    private function normalizeIdentity(?int $userId): string
    {
        return $userId !== null ? 'user:' . $userId : 'anonymous';
    }

    private function computeRequiredLevel(string $action): int
    {
        return match ($action) {
            'delete' => 0x40,
            'write' => 0x20,
            'read' => 0x10,
            default => 0x01,
        };
    }

    private function identityHasLevel(string $identity, int $level): bool
    {
        return match ($level) {
            0x4 => str_starts_with($identity, 'user:'),
            0x2 => str_starts_with($identity, 'user:') || str_starts_with($identity, 'key:'),
            0x1 => true,
            default => false,
        };
    }

    private function allowPartialMatch(): bool
    {
        return false;
    }

    private function logAccessDenied(string $identity, string $action, int $deniedCount): void
    {
        // log denial
    }
}
