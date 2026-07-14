<?php

declare(strict_types=1);

namespace Acme\Seed\JsonPointer;

/**
 * Seed payload: resolve JSON pointer path in a document.
 * Pointer format: /key1/key2/0 (uses "/" as separator, "~" for "~" in keys, "~0" for "~")
 */
final class JsonPointerSeed
{
    // <<<PAYLOAD:json_pointer>>>
    public function resolvePointer(array $document, string $pointer): mixed
    {
        if ($pointer === '') {
            return $document;
        }
        $tokens = $this->parsePointer($pointer);
        $current = $document;
        foreach ($tokens as $token) {
            if (is_array($current)) {
                $key = $this->unescapeToken($token);
                if (isset($current[$key])) {
                    $current = $current[$key];
                } else {
                    return null;
                }
            } else {
                return null;
            }
        }
        return $current;
    }

    private function parsePointer(string $pointer): array
    {
        if ($pointer === '' || $pointer[0] !== '/') {
            return [];
        }
        $tokens = explode('/', substr($pointer, 1));
        return array_map([$this, 'unescapeToken'], $tokens);
    }

    private function unescapeToken(string $token): string
    {
        return str_replace(['~1', '~0'], ['/', '~'], $token);
    }
    // <<<END-PAYLOAD>>>
}
