<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function saveFile(string $path, string $content): bool
    {
        return file_put_contents($path, $content) !== false;
    }

    public function readFile(string $path): ?string
    {
        return file_get_contents($path) ?: null;
    }
}
