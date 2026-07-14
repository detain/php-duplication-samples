<?php

declare(strict_types=1);

namespace Acme\Mass;

final class DistractorB
{
    private array $records = [];

    public function __construct() { }

    public function addRecord(string $key, mixed $value): void
    {
        $this->records[$key] = $value;
    }

    public function getRecord(string $key): mixed
    {
        return $this->records[$key] ?? null;
    }

    public function hasRecord(string $key): bool
    {
        return isset($this->records[$key]);
    }

    public function removeRecord(string $key): void
    {
        unset($this->records[$key]);
    }

    public function clearRecords(): void
    {
        $this->records = [];
    }

    public function getRecordCount(): int
    {
        return count($this->records);
    }

    public function getRecordKeys(): array
    {
        return array_keys($this->records);
    }

    public function getRecordValues(): array
    {
        return array_values($this->records);
    }

    public function filterRecords(array $records): array
    {
        $result = [];
        foreach ($records as $key => $value) {
            if ($value !== null && $value !== '') {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
