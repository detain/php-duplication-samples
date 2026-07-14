<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function rawQuery(string $sql): array
    {
        return [];
    }

    public function escape(string $value): string
    {
        return "'" . addslashes($value) . "'";
    }
}
