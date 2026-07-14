<?php
/**
 * Equivalence test for weekday_scheduler.
 * Verifies next weekday calculation.
 *
 * Run: php gen/seeds/weekday_scheduler/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$scheduler = new $className();

// Test with a known date: July 14, 2026 is a Tuesday (day 2)
$knownTimestamp = mktime(12, 0, 0, 7, 14, 2026); // Day 2 (Tuesday)

$testCases = [
    ['targetWeekday' => 1, 'fromTimestamp' => $knownTimestamp, 'expectDay' => 1], // Next Monday
    ['targetWeekday' => 3, 'fromTimestamp' => $knownTimestamp, 'expectDay' => 3], // Next Wednesday
    ['targetWeekday' => 2, 'fromTimestamp' => $knownTimestamp, 'expectDay' => 2, 'skipSameDay' => true], // Thursday -> same weekday next week
    ['targetWeekday' => 0, 'fromTimestamp' => $knownTimestamp, 'expectDay' => 0], // Next Sunday
    ['targetWeekday' => 6, 'fromTimestamp' => $knownTimestamp, 'expectDay' => 6], // Next Saturday
];

foreach ($testCases as $i => $case) {
    $result = $scheduler->nextWeekday($case['targetWeekday'], $case['fromTimestamp']);
    $resultDay = (int) date('w', $result);

    if ($case['targetWeekday'] === 2 && isset($case['skipSameDay'])) {
        if ($resultDay !== 2 || $result <= $case['fromTimestamp']) {
            $pass = false;
            $errors[] = "weekday_scheduler case {$i}: same day should be next week";
        }
    } elseif ($resultDay !== $case['expectDay']) {
        $pass = false;
        $errors[] = "weekday_scheduler case {$i}: expected day {$case['expectDay']}, got $resultDay";
    }

    if ($result <= $case['fromTimestamp']) {
        $pass = false;
        $errors[] = "weekday_scheduler case {$i}: result should be after fromTimestamp";
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}