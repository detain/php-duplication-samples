<?php

declare(strict_types=1);

namespace Acme\Db\Comp;

final class SqlCompiler
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
