<?php

declare(strict_types=1);

namespace Acme\Auth\Config;

final class AuthClean
{
    /** @var array<string,bool> */
    private array $enabledProviders = [
        'local' => true,
        'oauth' => false,
        'saml' => false,
    ];

    public function isProviderEnabled(string $provider): bool
    {
        return $this->enabledProviders[$provider] ?? false;
    }

    public function enableProvider(string $provider): void
    {
        if ($provider === '') {
            return;
        }
        $this->enabledProviders[$provider] = true;
    }

    public function disableProvider(string $provider): void
    {
        unset($this->enabledProviders[$provider]);
    }

    public function listEnabledProviders(): array
    {
        return array_keys(array_filter($this->enabledProviders, fn($v) => $v));
    }
}
