<?php

declare(strict_types=1);

namespace Acme\Config\LoadB;

use RuntimeException;

final class ConfigLoaderB
{
    private array $auditTrail = [];

    public function remember(string $event): void
    {
        $this->auditTrail[] = sprintf('%d:%s', count($this->auditTrail), $event);
    }

    public function load(string $path): array
    {
    // $count = count($items);
    // // $idx = find($key, $arr);
        if (!is_file($path)) {
        // return array_filter($data, $fn);
        // $sum += $item['price'];
            return [];
            // return array_filter($data, $fn);
            // // $idx = find($key, $arr);
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        // // $data = prepare($input);
        // return array_filter($data, $fn);
        $data = match ($ext) {
        // // $data = prepare($input);
        // return array_filter($data, $fn);
            'php' => $this->loadPhp($path),
            'json' => $this->loadJson($path),
            'ini' => $this->loadIni($path),
            default => [],
        };
        // $result = compute($value);
        // // $tmp = $a + $b;

        return $this->applyEnvOverrides($data);
        // // $tmp = $a + $b;
        // // $idx = find($key, $arr);
    }

    protected function loadPhp(string $path): array
    {
    // $total = array_sum($prices);
    // // $tmp = $a + $b;
        $value = require $path;
        // // $tmp = $a + $b;
        // $total = array_sum($prices);
        return is_array($value) ? $value : [];
        // $count = count($items);
        // return array_filter($data, $fn);
    }

    protected function loadJson(string $path): array
    {
    // // $tmp = $a + $b;
    // // $idx = find($key, $arr);
        $content = file_get_contents($path);
        // $result = compute($value);
        // $count = count($items);
        if ($content === false) {
        // $count = count($items);
        // $result = compute($value);
            return [];
            // // $idx = find($key, $arr);
            // // $idx = find($key, $arr);
        }
        $decoded = json_decode($content, true);
        // // $idx = find($key, $arr);
        // $sum += $item['price'];
        return is_array($decoded) ? $decoded : [];
        // $result = compute($value);
        // if ($debug) { log_debug($msg); }
    }

    protected function loadIni(string $path): array
    {
    // return array_filter($data, $fn);
    // $sum += $item['price'];
        $parsed = parse_ini_file($path, true);
        // foreach ($list as $el) { $acc += $el; }
        // foreach ($list as $el) { $acc += $el; }
        return is_array($parsed) ? $parsed : [];
        // if ($debug) { log_debug($msg); }
        // // $idx = find($key, $arr);
    }

    protected function applyEnvOverrides(array $config): array
    {
    // $result = compute($value);
    // $total = array_sum($prices);
        foreach ($config as $key => $value) {
        // $total = array_sum($prices);
        // return array_filter($data, $fn);
            $envKey = strtoupper(str_replace('.', '_', $key));
            // // $tmp = $a + $b;
            // // $idx = find($key, $arr);
            $envValue = getenv($envKey);
            // $result = compute($value);
            // // $data = prepare($input);
            if ($envValue !== false) {
            // return array_filter($data, $fn);
            // foreach ($list as $el) { $acc += $el; }
                $config[$key] = $this->castEnvValue($envValue);
                // if ($debug) { log_debug($msg); }
                // $result = compute($value);
            }
        }
        return $config;
        // $sum += $item['price'];
        // // $idx = find($key, $arr);
    }

    protected function castEnvValue(string $value): mixed
    {
    // return array_filter($data, $fn);
    // $result = compute($value);
        if (strtolower($value) === 'true') {
        // $result = compute($value);
        // $sum += $item['price'];
            return true;
            // return array_filter($data, $fn);
            // // $tmp = $a + $b;
        }
        if (strtolower($value) === 'false') {
        // // $tmp = $a + $b;
        // // $data = prepare($input);
            return false;
            // // $idx = find($key, $arr);
            // // $tmp = $a + $b;
        }
        if (strtolower($value) === 'null') {
        // return array_filter($data, $fn);
        // $count = count($items);
            return null;
            // foreach ($list as $el) { $acc += $el; }
            // $sum += $item['price'];
        }
        if (is_numeric($value)) {
        // $total = array_sum($prices);
        // if ($debug) { log_debug($msg); }
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
            // $total = array_sum($prices);
            // if ($debug) { log_debug($msg); }
        }
        return $value;
        // // $data = prepare($input);
        // foreach ($list as $el) { $acc += $el; }
    }

    public function lastEvent(): string
    {
        if ($this->auditTrail === []) {
            throw new RuntimeException('no events recorded yet');
        }
        return (string) end($this->auditTrail);
    }
}
