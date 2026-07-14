<?php
/**
 * Equivalence test for discount_tiers.
 * Verifies discount calculation based on quantity.
 *
 * Run: php gen/seeds/discount_tiers/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$calculator = new $className();

$testCases = [
    ['quantity' => 5, 'pricePerUnit' => ['unit' => 10.00], 'expectRate' => 0.0, 'expectDiscount' => 0.0],
    ['quantity' => 10, 'pricePerUnit' => ['unit' => 10.00], 'expectRate' => 0.05, 'expectDiscount' => 5.0],
    ['quantity' => 20, 'pricePerUnit' => ['unit' => 10.00], 'expectRate' => 0.10, 'expectDiscount' => 20.0],
    ['quantity' => 50, 'pricePerUnit' => ['unit' => 10.00], 'expectRate' => 0.15, 'expectDiscount' => 75.0],
    ['quantity' => 100, 'pricePerUnit' => ['unit' => 10.00], 'expectRate' => 0.20, 'expectDiscount' => 200.0],
    ['quantity' => 150, 'pricePerUnit' => ['unit' => 10.00], 'expectRate' => 0.20, 'expectDiscount' => 300.0],
];

foreach ($testCases as $i => $case) {
    $result = $calculator->computeDiscount($case['quantity'], $case['pricePerUnit']);

    if ($result['discount_rate'] !== $case['expectRate']) {
        $pass = false;
        $errors[] = "discount_tiers case {$i} rate mismatch: expected {$case['expectRate']}, got {$result['discount_rate']}";
    }

    if (abs($result['discount_amount'] - $case['expectDiscount']) > 0.01) {
        $pass = false;
        $errors[] = "discount_tiers case {$i} discount mismatch: expected {$case['expectDiscount']}, got {$result['discount_amount']}";
    }

    $expectedTotal = ($case['quantity'] * $case['pricePerUnit']['unit']) - $case['expectDiscount'];
    if (abs($result['total'] - $expectedTotal) > 0.01) {
        $pass = false;
        $errors[] = "discount_tiers case {$i} total mismatch";
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}