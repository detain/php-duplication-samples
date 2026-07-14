<?php
/**
 * Equivalence test for phone_normalizer.
 * Verifies phone number normalization to E.164.
 *
 * Run: php gen/seeds/phone_normalizer/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$normalizer = new $className();

$testCases = [
    ['phone' => '(555) 123-4567', 'countryCode' => '1', 'expected' => '+15551234567'],
    ['phone' => '555-123-4567', 'countryCode' => '1', 'expected' => '+15551234567'],
    ['phone' => '+1 555 123 4567', 'countryCode' => '1', 'expected' => '+15551234567'],
    ['phone' => '15551234567', 'countryCode' => '1', 'expected' => '+15551234567'],
    ['phone' => '5551234567', 'countryCode' => '1', 'expected' => '+15551234567'],
    ['phone' => 'abc', 'countryCode' => '1', 'expected' => null],
    ['phone' => '', 'countryCode' => '1', 'expected' => null],
    ['phone' => '+44 20 7123 4567', 'countryCode' => '44', 'expected' => '+442071234567'], // 12 digits already with country code
];

foreach ($testCases as $i => $case) {
    $result = $normalizer->normalizePhone($case['phone'], $case['countryCode']);

    if ($result !== $case['expected']) {
        $pass = false;
        $errors[] = "phone_normalizer case {$i}: expected " . var_export($case['expected'], true)
            . " got " . var_export($result, true);
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}