<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
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
