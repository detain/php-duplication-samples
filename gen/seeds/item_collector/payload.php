<?php

declare(strict_types=1);

namespace Acme\Seed\ItemCollector;

final class ItemCollectorSeed
{
    // <<<PAYLOAD:item_collector>>>
    /**
     * Collect and transform active user IDs from a list.
     * Payload: foreach loop collecting processed items.
     */
    public function collect(array $users): array
    {
        $result = [];
        foreach ($users as $user) {
            if (($user['active'] ?? false) === true) {
                $result[] = [
                    'id' => $user['id'],
                    'label' => strtoupper($user['name'] ?? ''),
                    'role' => $user['role'] ?? 'guest',
                ];
            }
        }
        return $result;
    }
    // <<<END-PAYLOAD>>>
}
