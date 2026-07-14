<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function diffArrays(array $from, array $to): array
    {
        $changes = [];
        foreach ($from as $key => $value) {
            if (!array_key_exists($key, $to)) {
                $changes[] = ['op' => 'remove', 'key' => $key];
            } elseif ($value !== $to[$key]) {
                $changes[] = ['op' => 'change', 'key' => $key, 'from' => $value, 'to' => $to[$key]];
            }
        }
        foreach ($to as $key => $value) {
            if (!array_key_exists($key, $from)) {
                $changes[] = ['op' => 'add', 'key' => $key, 'value' => $value];
            }
        }
        return $changes;
    }
}
