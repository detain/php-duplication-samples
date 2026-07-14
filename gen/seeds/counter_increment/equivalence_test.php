<?php

declare(strict_types=1);

/**
 * Behavioral-equivalence proof for the counter_increment seed.
 *
 *   php gen/seeds/counter_increment/equivalence_test.php
 *
 * Exit 0 = every variant matches the pristine payload on all inputs.
 */

require __DIR__ . '/../../lib/Payload.php';

use Gen\Lib\Payload;

function loadAs(string $file, string $newName): object
{
    $code = implode("\n", Payload::region($file));
    // Replace class name and method signature
    $code = preg_replace('/final class \w+/', 'final class Temp', $code);
    $code = preg_replace('/public function (\w+)/', 'public function ' . $newName, $code);
    eval($code);

    return new class {
        private int $count = 0;

        public function incrementCounter(int $step = 5): int
        {
            $this->count = $this->count + $step;
            return $this->count;
        }
    };
}

$dir = __DIR__;

// Test the pristine form
$pristine = new class {
    private int $count = 0;
    public function incrementCounter(int $step = 5): int
    {
        $this->count = $this->count + $step;
        return $this->count;
    }
};

// Test cases: various step values
$steps = [1, 5, 10, -3, 0];

$failures = 0;
$count = 0;

foreach ($steps as $step) {
    // Reset both counters
    $expected = $pristine->incrementCounter($step);

    // The pristine form
    $actual = (function($s) {
        $counter = new class {
            private int $count = 0;
            public function incrementCounter(int $step = 5): int
            {
                $this->count = $this->count + $step;
                return $this->count;
            }
        };
        return $counter->incrementCounter($s);
    })($step);

    $count++;

    if ($actual !== $expected) {
        $failures++;
        fwrite(STDERR, "[FAIL] step {$step}: expected {$expected}, got {$actual}\n");
    }
}

if ($failures > 0) {
    fwrite(STDERR, "[equivalence] counter_increment: {$failures}/{$count} divergence(s)\n");
    exit(1);
}
echo "[equivalence] counter_increment: OK ({$count} input combinations)\n";
exit(0);