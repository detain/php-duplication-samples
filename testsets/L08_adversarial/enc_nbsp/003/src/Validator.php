<?php

declare(strict_types=1);

namespace Acme\Util\Valid;

final class DataValidator
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
