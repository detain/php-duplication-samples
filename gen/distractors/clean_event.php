<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function emit(string $name, mixed $data): void
    {
        $this->events[] = ['name' => $name, 'data' => $data, 'time' => time()];
    }

    public function all(): array
    {
        return $this->events;
    }
}
