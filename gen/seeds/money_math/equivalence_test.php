<?php
/**
 * Equivalence test for money_math.
 * Verifies money calculations produce correct results.
 *
 * Run: php gen/seeds/money_math/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$calculator = new $className();

$testCases = [
    ['amount' => 10.00, 'taxRate' => 0.08, 'quantity' => 1],
    ['amount' => 19.99, 'taxRate' => 0.07, 'quantity' => 3],
    ['amount' => 100.00, 'taxRate' => 0.10, 'quantity' => 2],
    ['amount' => 0.01, 'taxRate' => 0.00, 'quantity' => 1],
    ['amount' => 5.50, 'taxRate' => 0.0825, 'quantity' => 4],
];

foreach ($testCases as $i => $case) {
    $result = @$calculator->computeMoney($case['amount'], $case['taxRate'], $case['quantity']);

    if (!is_array($result)) {
        $pass = false;
        $errors[] = "money_math case {$i} returned non-array";
        continue;
    }

    // Verify unit price
    $expectedUnit = round($case['amount'], 2);
    if (abs($result['unit_price'] - $expectedUnit) > 0.001) {
        $pass = false;
        $errors[] = "money_math case {$i} unit_price mismatch";
    }

    // Verify subtotal
    $expectedSubtotal = round($case['amount'] * $case['quantity'], 2);
    if (abs($result['subtotal'] - $expectedSubtotal) > 0.001) {
        $pass = false;
        $errors[] = "money_math case {$i} subtotal mismatch";
    }

    // Verify tax
    $expectedTax = round($expectedSubtotal * $case['taxRate'], 2);
    if (abs($result['tax'] - $expectedTax) > 0.001) {
        $pass = false;
        $errors[] = "money_math case {$i} tax mismatch";
    }

    // Verify total = subtotal + tax
    $expectedTotal = round($expectedSubtotal + $expectedTax, 2);
    if (abs($result['total'] - $expectedTotal) > 0.001) {
        $pass = false;
        $errors[] = "money_math case {$i} total mismatch";
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}