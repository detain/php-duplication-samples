<?php
/**
 * Equivalence test for stopwatch_metrics.
 * Verifies elapsed time and rate calculation.
 *
 * Run: php gen/seeds/stopwatch_metrics/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$stopwatch = new $className();

$testCases = [
    ['start' => 1000000, 'end' => 1001000, 'expectSeconds' => 0.001, 'expectRate' => 1000.0],
    ['start' => 1000000, 'end' => 1010000, 'expectSeconds' => 0.01, 'expectRate' => 100.0],
    ['start' => 1000000, 'end' => 1100000, 'expectSeconds' => 0.1, 'expectRate' => 10.0],
    ['start' => 1000000, 'end' => 2000000, 'expectSeconds' => 1.0, 'expectRate' => 1.0],
    ['start' => 1000000, 'end' => 1000000, 'expectSeconds' => 0.0, 'expectRate' => 0.0],
];

foreach ($testCases as $i => $case) {
    $result = $stopwatch->trackElapsed($case['start'], $case['end']);

    if (abs($result['seconds'] - $case['expectSeconds']) > 0.0001) {
        $pass = false;
        $errors[] = "stopwatch_metrics case {$i} seconds mismatch";
    }

    if (abs($result['rate'] - $case['expectRate']) > 0.01) {
        $pass = false;
        $errors[] = "stopwatch_metrics case {$i} rate mismatch";
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}