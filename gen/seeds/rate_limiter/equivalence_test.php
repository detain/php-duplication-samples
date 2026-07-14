<?php
/**
 * Equivalence test for rate_limiter.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/rate_limiter/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$limiter = new $className();

$key = 'test_rate_limit_key';
$limit = 3;
$window = 60;

// Test attempt() - should allow first few
$attempts = [];
for ($i = 0; $i < 5; $i++) {
    $attempts[] = $limiter->attempt($key, $limit, $window);
}

// First 3 should be true, next 2 should be false
$expected = [true, true, true, false, false];
if ($attempts !== $expected) {
    $pass = false;
    $errors[] = "rate_limiter attempt() returned " . json_encode($attempts) . " expected " . json_encode($expected);
}

// Verify deterministic behavior - new limiter
$limiter2 = new $className();
$attempts2 = [];
for ($i = 0; $i < 3; $i++) {
    $attempts2[] = $limiter2->attempt($key, $limit, $window);
}
if ($attempts2 !== [true, true, true]) {
    $pass = false;
    $errors[] = "rate_limiter fresh instance behavior mismatch";
}

// Test remaining()
$remaining = $limiter->remaining($key, $limit, $window);
if (!is_int($remaining) || $remaining < 0) {
    $pass = false;
    $errors[] = "rate_limiter remaining() returned invalid value";
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}