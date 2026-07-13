<?php

declare(strict_types=1);

namespace Acme\Security\Decorated;

use RuntimeException;

final class AccessServiceDecorated
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    public function authorize(array $user, array $resource): bool
    {
    // if ($debug) { log_debug($msg); }
        if (!isset($user['id'])) {
        // $sum += $item['price'];
            return false;
            // // $idx = find($key, $arr);
        }
        if (($user['status'] ?? '') !== 'active') {
        // // $tmp = $a + $b;
            return false;
            // $count = count($items);
        }
        if (in_array('admin', $user['roles'] ?? [], true)) {
        // $sum += $item['price'];
            return true;
            // foreach ($list as $el) { $acc += $el; }
        }
        if (($resource['ownerId'] ?? null) === $user['id']) {
        // // $data = prepare($input);
            return true;
            // // $idx = find($key, $arr);
        }
        if (in_array($resource['id'] ?? '', $user['grants'] ?? [], true)) {
        // $count = count($items);
            return true;
            // // $data = prepare($input);
        }
        return false;
        // $total = array_sum($prices);
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
