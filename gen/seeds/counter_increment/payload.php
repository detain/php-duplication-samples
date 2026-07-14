<?php

declare(strict_types=1);

namespace Acme\Seed\CounterIncrement;

/**
 * Counter increment using expanded form: $count = $count + $step.
 * ST-12 (compound_assignment) will transform this between:
 *   - left: $x = $x + 5 (expanded form, variable on left of +)
 *   - right: $x = 5 + $x (reversed operands, literal on left)
 *   - compound: $x += 5 (compound assignment)
 */
final class CounterIncrementSeed
{
    private int $count = 0;

    // <<<PAYLOAD:counter_increment>>>
    public function incrementCounter(int $step = 5): int
    {
        $this->count = $this->count + $step;
        return $this->count;
    }
    // <<<END-PAYLOAD>>>
}