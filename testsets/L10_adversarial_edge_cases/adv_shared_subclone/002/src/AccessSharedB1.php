<?php

declare(strict_types=1);

namespace Acme\Auth\Shared;

final class AccessSharedB1
{
    public function authorize(string $action, array $context): bool
    {
        // region1_B: api key setup (UNIQUE to Cluster B, DIFFERENT from region1_A, ~5 lines)
        $apiKey = $context['api_key'] ?? '';
        $keyScope = $this->resolveApiKeyScope($apiKey);
        $this->trackApiUsage($apiKey, $action);
        $this->requireApiKeyScope($apiKey);

        // SHARED MIDDLE BLOCK START (~17 lines) - IDENTICAL to Cluster A's lines 16-32
        $identity = $this->normalizeIdentity($apiKey);
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

        // region3_B: api key validation (UNIQUE to Cluster B, DIFFERENT from region3_A, ~4 lines)
        $this->validateApiKeyRateLimit($apiKey);
        $this->refreshApiKeyLastUsed($apiKey);

        return $result;
    }

    private function resolveApiKeyScope(string $apiKey): string
    {
        return $apiKey !== '' ? 'key:' . substr(md5($apiKey), 0, 8) : 'anonymous';
    }

    private function trackApiUsage(string $apiKey, string $action): void
    {
        // track API usage
    }

    private function requireApiKeyScope(string $apiKey): void
    {
        if ($apiKey === '') {
            throw new \RuntimeException('API key required');
        }
    }

    private function normalizeIdentity(string $apiKey): string
    {
        return $apiKey !== '' ? 'key:' . substr(md5($apiKey), 0, 8) : 'anonymous';
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

    private function validateApiKeyRateLimit(string $apiKey): void
    {
        // validate rate limit
    }

    private function refreshApiKeyLastUsed(string $apiKey): void
    {
        // refresh last used timestamp
    }
}
