<?php

declare(strict_types=1);

namespace Gen\Transforms;

use Gen\Transforms\Selector\VariantSelector;

/**
 * Loads gen/transforms/registry.json and instantiates the implemented
 * transform for a code. Also exposes the difficulty inputs (level_base,
 * per-code weights, band cutoffs) used by the generator to compute
 * set.json.difficulty.score MECHANICALLY (§7.1).
 */
final class Registry
{
    /** @var array<string,mixed> */
    private array $data;

    public function __construct(?string $file = null)
    {
        $file ??= __DIR__ . '/registry.json';
        $raw = file_get_contents($file);
        if ($raw === false) {
            throw new \RuntimeException("cannot read registry: {$file}");
        }
        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['codes'])) {
            throw new \RuntimeException("malformed registry: {$file}");
        }
        $this->data = $data;
    }

    public function has(string $code): bool
    {
        return isset($this->data['codes'][$code]);
    }

    /** @return array<string,mixed> */
    public function meta(string $code): array
    {
        if (!$this->has($code)) {
            throw new \RuntimeException("unknown interference code: {$code}");
        }
        return $this->data['codes'][$code];
    }

    /** @return list<string> */
    public function allCodes(): array
    {
        return array_keys($this->data['codes']);
    }

    public function isImplemented(string $code): bool
    {
        return (bool)($this->meta($code)['implemented'] ?? false);
    }

    public function name(string $code): string
    {
        return (string)($this->meta($code)['name'] ?? $code);
    }

    public function weight(string $code): int
    {
        return (int)($this->meta($code)['weight'] ?? 0);
    }

    /** @return list<string> */
    public function requires(string $code): array
    {
        return array_values((array)($this->meta($code)['requires'] ?? []));
    }

    public function levelBase(int $level): int
    {
        return (int)($this->data['level_base'][(string)$level] ?? 0);
    }

    /** Map a mechanical score to its coarse difficulty band. */
    public function band(int $score): string
    {
        $cutoffs = $this->data['band_cutoffs'] ?? [];
        $band = 'baseline';
        foreach ($cutoffs as $name => $min) {
            if ($score >= (int)$min) {
                $band = (string)$name;
            }
        }
        return $band;
    }

    /** Instantiate the implemented transform for a code. */
    public function transform(string $code): Transform
    {
        $meta = $this->meta($code);
        if (empty($meta['implemented'])) {
            throw new \RuntimeException("interference code {$code} is declared but not implemented in this build");
        }
        if (($meta['kind'] ?? '') === 'selector') {
            return new VariantSelector($code, (string)$meta['name']);
        }
        $class = (string)($meta['class'] ?? '');
        if ($class === '' || !class_exists($class)) {
            throw new \RuntimeException("transform class missing for {$code}: {$class}");
        }
        /** @var Transform $t */
        $t = new $class();
        return $t;
    }
}
