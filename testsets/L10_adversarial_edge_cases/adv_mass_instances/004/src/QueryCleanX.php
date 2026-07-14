<?php

declare(strict_types=1);

namespace Acme\Query\Clean;

final class QueryCleanX
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
