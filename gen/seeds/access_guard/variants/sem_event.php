<?php

declare(strict_types=1);

namespace Acme\Seed\AccessGuard;

/**
 * SEM-06 variant: event_indirection - the same authorization decision expressed
 * with event dispatch (beforeAuthorize/afterAuthorize). For payload equivalence,
 * only the authorize method body is inlined. The event bus logic is expressed
 * as comments explaining the dispatch pattern.
 * Behaviorally identical (enforced by equivalence_test.php).
 */
final class AccessGuardSemEventVariant
{
    // Event bus state (not used in extracted payload, but defined in class)
    private array $beforeHandlers = [];
    private array $afterHandlers = [];

    // <<<PAYLOAD:access_guard>>>
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
    // <<<END-PAYLOAD>>>

    public function onBeforeAuthorize(callable $handler): void
    {
        $this->beforeHandlers[] = $handler;
    }

    public function onAfterAuthorize(callable $handler): void
    {
        $this->afterHandlers[] = $handler;
    }

    private function dispatch(string $event, array $context): void
    {
        // Conceptual event dispatch implementation
        // In a full implementation, would iterate registered handlers
    }
}