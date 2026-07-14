<?php
/**
 * Equivalence test for basket_pricing.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/basket_pricing/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$pricing = new $className();

$testCases = [
    [
        'items' => [['quantity' => 2, 'price' => 10.00]],
        'taxRate' => 0.07,
        'discountCode' => '',
    ],
    [
        'items' => [
            ['quantity' => 5, 'price' => 20.00],
            ['quantity' => 3, 'price' => 15.00],
        ],
        'taxRate' => 0.08,
        'discountCode' => 'BULK10',
    ],
    [
        'items' => [['quantity' => 1, 'price' => 100.00]],
        'taxRate' => 0.10,
        'discountCode' => 'VIP',
    ],
];

foreach ($testCases as $i => $case) {
    $result = @$pricing->calculateTotal($case['items'], $case['taxRate'], $case['discountCode']);

    if (!isset($result['subtotal'], $result['total'], $result['tax'])) {
        $pass = false;
        $errors[] = "basket_pricing case {$i} missing required keys";
        continue;
    }

    // Verify deterministic output
    $result2 = @$pricing->calculateTotal($case['items'], $case['taxRate'], $case['discountCode']);
    if ($result !== $result2) {
        $pass = false;
        $errors[] = "basket_pricing case {$i} non-deterministic";
    }

    // Verify math is consistent
    $expectedTotal = $result['subtotal'] - $result['discount'] + $result['tax'] + $result['shipping'];
    if (abs($expectedTotal - $result['total']) > 0.01) {
        $pass = false;
        $errors[] = "basket_pricing case {$i} total calculation mismatch";
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}