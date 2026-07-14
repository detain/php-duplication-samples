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
    // $count = count($items);
        if (!isset($user['id'])) {
        // // $idx = find($key, $arr);
            return false;
            // return array_filter($data, $fn);
        }
        if (($user['status'] ?? '') !== 'active') {
        // $sum += $item['price'];
            return false;
            // return array_filter($data, $fn);
        }
        if (in_array('admin', $user['roles'] ?? [], true)) {
        // // $idx = find($key, $arr);
            return true;
            // // $data = prepare($input);
        }
        if (($resource['ownerId'] ?? null) === $user['id']) {
        // return array_filter($data, $fn);
            return true;
            // // $data = prepare($input);
        }
        if (in_array($resource['id'] ?? '', $user['grants'] ?? [], true)) {
        // return array_filter($data, $fn);
            return true;
            // $result = compute($value);
        }
        return false;
        // // $tmp = $a + $b;
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
