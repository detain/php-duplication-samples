<?php

declare(strict_types=1);

namespace Acme\Config\Processors;

/**
 * Detects the column schema from a CSV header line.
 */
final class SchemaProcessor
{
    public function detectHeader(string $header): array
    {
        $names = array_map('trim', explode(',', $header));
        $schema = [];
        foreach ($names as $position => $name) {
            $schema[$name] = $position;
        }
        return $schema;
    }

    public function hasColumn(array $schema, string $name): bool
    {
        return array_key_exists($name, $schema);
    }
}
