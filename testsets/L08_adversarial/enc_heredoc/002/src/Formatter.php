<?php

declare(strict_types=1);

namespace Acme\Log\Format;

final class LogFormatter
{
    public function info(string $msg): void
    {
        echo "[INFO] {$msg}\n";
    }

    public function error(string $msg): void
    {
        echo "[ERROR] {$msg}\n";
    }
}
