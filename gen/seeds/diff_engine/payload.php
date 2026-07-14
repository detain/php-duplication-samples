<?php

declare(strict_types=1);

namespace Acme\Seed\DiffEngine;

final class DiffEngineSeed
{
    // <<<PAYLOAD:diff_engine>>>
    public function diff(array $from, array $to): array
    {
        $changes = [];
        $fromKeys = array_keys($from);
        $toKeys = array_keys($to);
        $allKeys = array_unique(array_merge($fromKeys, $toKeys));

        foreach ($allKeys as $key) {
            $inFrom = array_key_exists($key, $from);
            $inTo = array_key_exists($key, $to);

            if ($inFrom && !$inTo) {
                $changes[] = ['op' => 'remove', 'key' => $key, 'value' => $from[$key]];
            } elseif (!$inFrom && $inTo) {
                $changes[] = ['op' => 'add', 'key' => $key, 'value' => $to[$key]];
            } elseif ($from[$key] !== $to[$key]) {
                if (is_array($from[$key]) && is_array($to[$key])) {
                    $nested = $this->diff($from[$key], $to[$key]);
                    foreach ($nested as $change) {
                        $change['key'] = $key . '.' . $change['key'];
                        $changes[] = $change;
                    }
                } else {
                    $changes[] = [
                        'op' => 'change',
                        'key' => $key,
                        'from' => $from[$key],
                        'to' => $to[$key],
                    ];
                }
            }
        }

        return $changes;
    }

    public function apply(array $base, array $changes): array
    {
        $result = $base;
        foreach ($changes as $change) {
            $key = $change['key'];
            $op = $change['op'];

            if (str_contains($key, '.')) {
                $keys = explode('.', $key);
                $target = &$result;
                for ($i = 0; $i < count($keys) - 1; $i++) {
                    if (!isset($target[$keys[$i]])) {
                        $target[$keys[$i]] = [];
                    }
                    $target = &$target[$keys[$i]];
                }
                $finalKey = end($keys);
            } else {
                $target = &$result;
                $finalKey = $key;
            }

            match ($op) {
                'add', 'change' => $target[$finalKey] = $change['value'],
                'remove' => null,
                default => null,
            };
            if ($op === 'remove') {
                unset($target[$finalKey]);
            }
        }
        return $result;
    }

    public function render(array $changes, string $format = 'unified'): string
    {
        return match ($format) {
            'unified' => $this->renderUnified($changes),
            'json' => json_encode($changes, JSON_PRETTY_PRINT),
            'text' => $this->renderText($changes),
            default => '',
        };
    }

    protected function renderUnified(array $changes): string
    {
        $lines = [];
        foreach ($changes as $change) {
            $key = $change['key'];
            $op = $change['op'];
            $line = match ($op) {
                'add' => "+ {$key}: " . json_encode($change['value']),
                'remove' => "- {$key}: " . json_encode($change['value']),
                'change' => "~ {$key}: " . json_encode($change['from']) . " => " . json_encode($change['to']),
                default => "  {$key}: " . json_encode($change['value']),
            };
            $lines[] = $line;
        }
        return implode("\n", $lines);
    }

    protected function renderText(array $changes): string
    {
        $lines = [];
        foreach ($changes as $change) {
            $op = $change['op'];
            $key = $change['key'];
            $lines[] = match ($op) {
                'add' => "Added {$key}",
                'remove' => "Removed {$key}",
                'change' => "Changed {$key}",
                default => "{$key}",
            };
        }
        return implode("\n", $lines);
    }
    // <<<END-PAYLOAD>>>
}
