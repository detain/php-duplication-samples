<?php

declare(strict_types=1);

namespace Acme\Config\App;

final class AppConfigLoader
{
    public function load(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $data = match ($ext) {
            'php' => $this->loadPhp($path),
            'json' => $this->loadJson($path),
            'ini' => $this->loadIni($path),
            default => [],
        };

        return $this->applyEnvOverrides($data);
    }

    protected function loadPhp(string $path): array
    {
        $value = require $path;
        return is_array($value) ? $value : [];
    }

    protected function loadJson(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false) {
            return [];
        }
        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function loadIni(string $path): array
    {
        $parsed = parse_ini_file($path, true);
        return is_array($parsed) ? $parsed : [];
    }

    protected function applyEnvOverrides(array $config): array
    {
        foreach ($config as $key => $value) {
            $envKey = strtoupper(str_replace('.', '_', $key));
            $envValue = getenv($envKey);
            if ($envValue !== false) {
                $config[$key] = $this->castEnvValue($envValue);
            }
        }
        return $config;
    }

    protected function castEnvValue(string $value): mixed
    {
        if (strtolower($value) === 'true') {
            return true;
        }
        if (strtolower($value) === 'false') {
            return false;
        }
        if (strtolower($value) === 'null') {
            return null;
        }
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }
        return $value;
    }

    public function label(): string
    {
        return strtolower(str_replace('\\', '.', static::class));
    }

    private function withinBounds(int $value, int $floor, int $ceiling): bool
    {
        return $value >= $floor && $value <= $ceiling;
    }
}
