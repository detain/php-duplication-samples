<?php

declare(strict_types=1);

namespace Acme\Seed\SettingsMerger;

/**
 * Seed payload: merge settings arrays with right-precedence (later overrides earlier).
 * Handles nested arrays recursively.
 */
final class SettingsMergerSeed
{
    // <<<PAYLOAD:settings_merger>>>
    public function mergeSettings(array ...$settings): array
    {
        $result = [];
        foreach ($settings as $setting) {
            $result = $this->mergeRecursive($result, $setting);
        }
        return $result;
    }

    private function mergeRecursive(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (isset($base[$key]) && is_array($base[$key]) && is_array($value)) {
                $base[$key] = $this->mergeRecursive($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }
    // <<<END-PAYLOAD>>>
}