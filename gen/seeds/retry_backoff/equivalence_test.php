<?php
/**
 * Equivalence test for retry_backoff.
 * Verifies exponential backoff delay calculation.
 *
 * Run: php gen/seeds/retry_backoff/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$backoff = new $className();

// Test base delays
$testCases = [
    ['attempt' => 1, 'baseDelay' => 1.0, 'maxDelay' => 30.0, 'jitterFactor' => 0.0],
    ['attempt' => 2, 'baseDelay' => 1.0, 'maxDelay' => 30.0, 'jitterFactor' => 0.0],
    ['attempt' => 3, 'baseDelay' => 1.0, 'maxDelay' => 30.0, 'jitterFactor' => 0.0],
    ['attempt' => 5, 'baseDelay' => 1.0, 'maxDelay' => 30.0, 'jitterFactor' => 0.0],
    ['attempt' => 1, 'baseDelay' => 0.5, 'maxDelay' => 10.0, 'jitterFactor' => 0.0],
];

foreach ($testCases as $i => $case) {
    $result = $backoff->retryWithBackoff(
        $case['attempt'],
        $case['baseDelay'],
        $case['maxDelay'],
        $case['jitterFactor']
    );

    if (!is_float($result)) {
        $pass = false;
        $errors[] = "retry_backoff case {$i} returned non-float";
        continue;
    }

    // Calculate expected
    $expected = $case['baseDelay'] * pow(2.0, $case['attempt'] - 1);
    $expected = min($expected, $case['maxDelay']);

    if (abs($result - $expected) > 0.001) {
        $pass = false;
        $errors[] = "retry_backoff case {$i} delay mismatch: expected $expected, got $result";
    }
}

// Test zero attempt
$zeroResult = $backoff->retryWithBackoff(0, 1.0, 30.0, 0.0);
if ($zeroResult !== 0.0) {
    $pass = false;
    $errors[] = "retry_backoff zero attempt should return 0";
}

// Test max delay cap
$cappedResult = $backoff->retryWithBackoff(10, 1.0, 30.0, 0.0);
if ($cappedResult > 30.0) {
    $pass = false;
    $errors[] = "retry_backoff should cap at maxDelay";
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}