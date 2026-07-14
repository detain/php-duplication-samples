<?php
/**
 * Equivalence test for duration_formatter.
 * Verifies duration formatting into human-readable strings.
 *
 * Run: php gen/seeds/duration_formatter/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$formatter = new $className();

$testCases = [
    ['seconds' => 0, 'expected' => '0s'],
    ['seconds' => 1, 'expected' => '1s'],
    ['seconds' => 60, 'expected' => '1m'],
    ['seconds' => 61, 'expected' => '1m 1s'],
    ['seconds' => 3600, 'expected' => '1h 0m 0s'],
    ['seconds' => 3661, 'expected' => '1h 1m 1s'],
    ['seconds' => 86400, 'expected' => '1d 0h 0m 0s'],
    ['seconds' => 90061, 'expected' => '1d 1h 1m 1s'],
    ['seconds' => 60, 'precision' => 1, 'expected' => '1m'],
];

foreach ($testCases as $i => $case) {
    $precision = $case['precision'] ?? 4;
    $result = $formatter->formatDuration($case['seconds'], $precision);

    if ($result !== $case['expected']) {
        $pass = false;
        $errors[] = "duration_formatter case {$i}: expected '{$case['expected']}', got '$result'";
    }
}

// Test negative
$negResult = $formatter->formatDuration(-100, 2);
if ($negResult !== '-1m 40s') {
    $pass = false;
    $errors[] = "duration_formatter negative case failed: got $negResult";
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}