<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function get(string $key): mixed
    {
        return $this->memory[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->memory[$key] = $value;
    }
}
