<?php

declare(strict_types=1);

namespace Gen\Transforms\Selector;

use Gen\Lib\Payload;
use Gen\Lib\Rng;
use Gen\Transforms\Transform;
use Gen\Transforms\TransformInput;
use Gen\Transforms\TransformResult;

/**
 * Variant SELECTOR (CF / API / SEM families) — does NOT compute a rewrite. It replaces
 * the payload region with a pre-written, behaviorally-equivalent variant body
 * from gen/seeds/<seed>/variants/<variant>.php (§11.2). The variant's line
 * count differs from the pristine payload, so the renderer computes the
 * ground-truth end line from the returned lines. Behavioral equivalence is
 * enforced separately by the seed's equivalence_test.php (§12.4).
 *
 * params:
 *   variant_file (string)  absolute path to the variant payload file
 *   code         (string)  the interference code being realized (e.g. CF-03)
 *   name         (string)  the interference name (e.g. guard_nested)
 */
final class VariantSelector implements Transform
{
    public function __construct(
        private string $code = 'CF-03',
        private string $name = 'guard_nested',
    ) {
    }

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult
    {
        $file = (string)($params['variant_file'] ?? '');
        if ($file === '' || !is_file($file)) {
            throw new \RuntimeException("VariantSelector: variant_file missing or not found: {$file}");
        }
        if (isset($params['code'])) {
            $this->code = (string)$params['code'];
        }
        if (isset($params['name'])) {
            $this->name = (string)$params['name'];
        }

        $lines = Payload::region($file);
        return new TransformResult(array_values($lines), range(0, max(0, count($lines) - 1)));
    }
}
