<?php
declare(strict_types=1);

namespace Acme\Events\Unified;

class EventBuilder
{
    public function buildEvent(string $type, array $payload, array $metadata = []): array
    {
        return [
            'type' => $type,
            'payload' => $payload,
            'metadata' => $metadata,
        ];
    }
}
