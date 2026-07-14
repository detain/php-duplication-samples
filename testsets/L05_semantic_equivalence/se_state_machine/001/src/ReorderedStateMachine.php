<?php

declare(strict_types=1);

namespace Acme\Workflow\Reordered;

final class ReorderedStateMachine
{
    public function __construct(private readonly string $region = 'default')
    {
    }

    public function region(): string
    {
        return $this->region;
    }

    public function fingerprint(array $payload): string
    {
        ksort($payload);
        return substr(hash('crc32b', json_encode($payload) ?: ''), 0, 8);
    }

        $transition = $this->transitions[$from][$event] ?? null;
    {
        if (!isset($this->transitions[$from])) {
            return null;
        }
    public function transition(string $from, string $event): ?string
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
}
