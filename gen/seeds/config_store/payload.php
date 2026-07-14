<?php

declare(strict_types=1);

namespace Acme\Seed\ConfigStore;

final class ConfigStoreSeed
{
    // <<<PAYLOAD:config_store>>>
    /**
     * Mutable configuration store: set() modifies internal state.
     * Payload: mutable API with setters.
     */
    private array $config = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }

    public function all(): array
    {
        return $this->config;
    }
    // <<<END-PAYLOAD>>>
}
