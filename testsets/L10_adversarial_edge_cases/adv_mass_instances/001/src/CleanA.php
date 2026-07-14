<?php

declare(strict_types=1);

namespace Acme\Mass;

final class CleanA
{
    private string $id = '';
    private string $name = '';
    private int $value = 0;

    public function __construct(string $id = '', string $name = '') { }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'value' => $this->value];
    }

    public function fromArray(array $data): void
    {
        $this->id = $data['id'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->value = $data['value'] ?? 0;
    }

    public function label(): string
    {
        return strtolower(str_replace('\\', '.', static::class));
    }
}
