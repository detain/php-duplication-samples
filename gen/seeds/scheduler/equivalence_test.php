<?php
/**
 * Equivalence test for scheduler.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/scheduler/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$scheduler = new $className();

// Test validate()
$validCron = '30 14 * * 1'; // 2:30 PM every Monday
if (!$scheduler->validate($validCron)) {
    $pass = false;
    $errors[] = "scheduler validate() returned false for valid expression";
}

$invalidCron = '60 14 * * 1'; // invalid minute
if ($scheduler->validate($invalidCron)) {
    $pass = false;
    $errors[] = "scheduler validate() returned true for invalid expression";
}

$invalidCron2 = '30 25 * * 1'; // invalid hour
if ($scheduler->validate($invalidCron2)) {
    $pass = false;
    $errors[] = "scheduler validate() returned true for invalid hour";
}

// Test nextRun()
$nextRun = $scheduler->nextRun($validCron, '2024-01-01 00:00:00');
if ($nextRun === null) {
    $pass = false;
    $errors[] = "scheduler nextRun() returned null for valid expression";
}

// Test deterministic nextRun
$nextRun2 = $scheduler->nextRun($validCron, '2024-01-01 00:00:00');
if ($nextRun !== $nextRun2) {
    $pass = false;
    $errors[] = "scheduler nextRun() non-deterministic";
}

// Test isDue()
$isDue = $scheduler->isDue($validCron, null);
if (!is_bool($isDue)) {
    $pass = false;
    $errors[] = "scheduler isDue() should return boolean";
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}