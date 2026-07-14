<?php
/**
 * Equivalence test for date_calculator.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/date_calculator/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$calculator = new $className();

// Test diff()
$diff = $calculator->diff('2024-01-01', '2024-01-02', 'days');
if ($diff !== 1) {
    $pass = false;
    $errors[] = "date_calculator diff() returned {$diff} expected 1";
}

// Test diff with different units
$diffHours = $calculator->diff('2024-01-01 00:00:00', '2024-01-01 12:00:00', 'hours');
if ($diffHours !== 12) {
    $pass = false;
    $errors[] = "date_calculator diff(hours) returned {$diffHours} expected 12";
}

// Test add()
$newDate = $calculator->add('2024-01-01', 5, 'days');
if (strpos($newDate, '2024-01-06') === false) {
    $pass = false;
    $errors[] = "date_calculator add(5 days) returned {$newDate}";
}

// Test add months
$newDate2 = $calculator->add('2024-01-15', 1, 'months');
if (strpos($newDate2, '2024-02-') === false) {
    $pass = false;
    $errors[] = "date_calculator add(1 month) returned {$newDate2}";
}

// Verify deterministic
$diff2 = $calculator->diff('2024-01-01', '2024-01-02', 'days');
if ($diff !== $diff2) {
    $pass = false;
    $errors[] = "date_calculator non-deterministic";
}

// Test invalid date handling
$invalidDiff = $calculator->diff('not-a-date', '2024-01-01', 'days');
if ($invalidDiff !== 0) {
    $pass = false;
    $errors[] = "date_calculator should return 0 for invalid dates";
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}