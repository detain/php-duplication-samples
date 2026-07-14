<?php

declare(strict_types=1);

namespace Acme\Mass;

final class CleanB
{
    private array $data = [];
    private bool $locked = false;

    public function __construct() { }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function delete(string $key): void
    {
        unset($this->data[$key]);
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function lock(): void
    {
        $this->locked = true;
    }

    public function unlock(): void
    {
        $this->locked = false;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function fromArray(array $data): void
    {
        $this->data = $data;
    }

    public function label(): string
    {
        return strtolower(str_replace('\\', '.', static::class));
    }
}
