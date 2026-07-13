<?php

declare(strict_types=1);

namespace Acme\Registry\Services;

/**
 * DNS zone-file record line parser.
 */
final class NamingService
{
    public function parseRecord(string $line): array
    {
        $parts = preg_split('/\s+/', trim($line)) ?: [];
        return [
            'name' => $parts[0] ?? '',
            'ttl' => (int) ($parts[1] ?? 0),
            'type' => $parts[2] ?? 'A',
            'value' => $parts[3] ?? '',
        ];
    }

    public function isExpired(int $ttl, int $ageSeconds): bool
    {
        return $ageSeconds > $ttl;
    }
}
