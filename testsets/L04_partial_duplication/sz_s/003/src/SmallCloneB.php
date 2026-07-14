<?php

declare(strict_types=1);

namespace Acme\Import\Small;

use RuntimeException;

final class SmallCloneB
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

        $columns = explode($delimiter, $line);
        $index = 0;
        $filled = 0;
        foreach ($columns as $column) {
            $value = trim($column);
            if ($value === '') {
                $record['col_' . $index] = null;
                $index++;
                continue;
            }
            $normalized = preg_replace('/\s+/', ' ', $value);
            $record['col_' . $index] = strtolower($normalized);
            $filled++;
            $index++;
        }
        $record['__count'] = $index;
        $record['__filled'] = $filled;

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
