<?php

declare(strict_types=1);

namespace __NAMESPACE__;

final class __CLASS__
{
    public function fetch(string $url): string
    {
        return file_get_contents($url);
    }

    public function post(string $url, array $data): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'content' => http_build_query($data),
            ]
        ]);
        return file_get_contents($url, false, $context);
    }
}
