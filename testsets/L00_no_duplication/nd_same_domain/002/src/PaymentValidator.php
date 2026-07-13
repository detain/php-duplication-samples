<?php

declare(strict_types=1);

namespace Acme\Billing\Payments;

/**
 * Resolves the inherited role set for a role.
 */
final class PaymentValidator
{
    /** @var array<string,list<string>> */
    private array $inherits = [
        'admin' => ['editor', 'viewer'],
        'editor' => ['viewer'],
        'viewer' => [],
    ];

    public function expand(string $role): array
    {
        $seen = [$role => true];
        foreach ($this->inherits[$role] ?? [] as $child) {
            $seen[$child] = true;
        }
        return array_keys($seen);
    }

    public function isKnown(string $role): bool
    {
        return isset($this->inherits[$role]);
    }
}
