<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function encode(mixed $data): string
    {
        return serialize($data);
    }

    public function decode(string $data): mixed
    {
        return unserialize($data);
    }
}
