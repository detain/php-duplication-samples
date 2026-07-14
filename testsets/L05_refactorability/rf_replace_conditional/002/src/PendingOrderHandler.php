<?php

declare(strict_types=1);

namespace Acme\Order\Pending;

final class PendingOrderHandler
{
    public function transition(string $from, string $event): ?string
    {
        if (!isset($this->transitions[$from])) {
            return null;
        }
        $transition = $this->transitions[$from][$event] ?? null;
        if ($transition === null) {
            return null;
        }
        if (is_callable($transition['guard'] ?? null)) {
            if (!$transition['guard']()) {
                return null;
            }
        }
        return $transition['to'];
    }

    public function can(string $from, string $event): bool
    {
        return $this->transition($from, $event) !== null;
    }

    public function events(string $state): array
    {
        return array_keys($this->transitions[$state] ?? []);
    }

    public function metadata(string $state): array
    {
        return [
            'is_initial' => $state === $this->initialState,
            'is_final' => !isset($this->transitions[$state]),
            'events' => $this->events($state),
        ];
    }

    /** @var array<string,array<string,array{to:string,guard?:callable}>> */
    private array $transitions = [];

    private string $initialState = 'draft';

    public function label(): string
    {
        return strtolower(str_replace('\\', '.', static::class));
    }

    private function withinBounds(int $value, int $floor, int $ceiling): bool
    {
        return $value >= $floor && $value <= $ceiling;
    }
}
