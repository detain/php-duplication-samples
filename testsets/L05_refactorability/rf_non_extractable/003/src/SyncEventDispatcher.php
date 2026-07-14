<?php

declare(strict_types=1);

namespace Acme\Events\Sync;

final class SyncEventDispatcher
{
    public function on(string $event, callable $listener, int $priority = 0): void
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }
        $this->listeners[$event][] = [
            'listener' => $listener,
            'priority' => $priority,
        ];
        usort($this->listeners[$event], static fn($a, $b) => $b['priority'] - $a['priority']);
    }

    public function dispatch(string $event, array $context = []): array
    {
        $results = [];
        if (!isset($this->listeners[$event])) {
            return $results;
        }
        foreach ($this->listeners[$event] as $entry) {
            $listener = $entry['listener'];
            $result = $listener($context);
            $results[] = $result;
            if ($result === false) {
                break;
            }
        }
        return $results;
    }

    public function once(string $event, callable $listener, int $priority = 0): void
    {
        $wrapper = function (array $context) use ($event, $listener, &$wrapper) {
            $this->off($event, $wrapper);
            return $listener($context);
        };
        $this->on($event, $wrapper, $priority);
    }

    public function off(string $event, ?callable $listener = null): void
    {
        if ($listener === null) {
            unset($this->listeners[$event]);
            return;
        }
        if (!isset($this->listeners[$event])) {
            return;
        }
        $this->listeners[$event] = array_values(array_filter(
            $this->listeners[$event],
            static fn($entry) => $entry['listener'] !== $listener
        ));
    }

    private array $listeners = [];

    public function label(): string
    {
        return strtolower(str_replace('\\', '.', static::class));
    }

    private function withinBounds(int $value, int $floor, int $ceiling): bool
    {
        return $value >= $floor && $value <= $ceiling;
    }
}
