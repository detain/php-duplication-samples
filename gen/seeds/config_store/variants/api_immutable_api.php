<?php

declare(strict_types=1);

namespace Acme\Seed\ConfigStore;

/**
 * API-06 variant: immutable configuration store using wither pattern.
 * Each wither returns a new instance; original is never modified.
 * Behaviorally equivalent to the mutable payload but no internal state changes.
 */
final class ConfigStoreImmutableVariant
{
    // <<<PAYLOAD:config_store>>>
    private array $config = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function with(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->config[$key] = $value;
        return $clone;
    }

    public function all(): array
    {
        return $this->config;
    }
    // <<<END-PAYLOAD>>>
}
