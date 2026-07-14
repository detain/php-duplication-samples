<?php
/**
 * Equivalence test for tax_bracket.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/tax_bracket/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$calculator = new $className();

$brackets = [
    ['floor' => 0, 'ceiling' => 10000, 'rate' => 0.10],
    ['floor' => 10000, 'ceiling' => 50000, 'rate' => 0.20],
    ['floor' => 50000, 'ceiling' => 100000, 'rate' => 0.30],
    ['floor' => 100000, 'ceiling' => PHP_FLOAT_MAX, 'rate' => 0.40],
];

$testCases = [5000, 15000, 75000, 150000, 0];

foreach ($testCases as $income) {
    $result = @$calculator->calculateTax((float) $income, $brackets);

    if (!isset($result['tax'], $result['effective_rate'], $result['gross'], $result['net'])) {
        $pass = false;
        $errors[] = "tax_bracket income={$income} missing required keys";
        continue;
    }

    // Verify net = gross - tax (within floating point tolerance)
    $expectedNet = $income - $result['tax'];
    if (abs($result['net'] - $expectedNet) > 0.02) {
        $pass = false;
        $errors[] = "tax_bracket income={$income}: net={$result['net']} expected {$expectedNet}";
    }

    // Verify effective_rate = tax / gross (for non-zero income)
    if ($income > 0) {
        $expectedRate = $result['tax'] / $income;
        if (abs($result['effective_rate'] - $expectedRate) > 0.0001) {
            $pass = false;
            $errors[] = "tax_bracket income={$income}: effective_rate={$result['effective_rate']} expected {$expectedRate}";
        }
    }

    // Verify tax is non-negative
    if ($result['tax'] < 0) {
        $pass = false;
        $errors[] = "tax_bracket income={$income}: tax should be non-negative";
    }

    // Verify deterministic
    $result2 = @$calculator->calculateTax((float) $income, $brackets);
    if ($result !== $result2) {
        $pass = false;
        $errors[] = "tax_bracket income={$income} non-deterministic";
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}