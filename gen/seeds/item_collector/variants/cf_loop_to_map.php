<?php

declare(strict_types=1);

namespace Acme\Seed\ItemCollector;

/**
 * CF-06 variant: the same item collection and transformation
 * re-expressed using array_map instead of a foreach loop.
 * Behaviorally identical to the payload.
 */
final class ItemCollectorLoopToMapVariant
{
    // <<<PAYLOAD:item_collector>>>
    public function collect(array $users): array
    {
        return array_values(
            array_map(
                fn(array $user): array => [
                    'id' => $user['id'],
                    'label' => strtoupper($user['name'] ?? ''),
                    'role' => $user['role'] ?? 'guest',
                ],
                array_filter($users, fn(array $u) => ($u['active'] ?? false) === true)
            )
        );
    }
    // <<<END-PAYLOAD>>>
}
