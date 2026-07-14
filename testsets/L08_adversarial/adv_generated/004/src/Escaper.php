<?php

declare(strict_types=1);

namespace Acme\Db\Esc;

final class QueryEscaper
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
