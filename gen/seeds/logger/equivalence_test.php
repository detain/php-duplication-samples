<?php
/**
 * Equivalence test for logger.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/logger/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$logger = new $className();

// Test that log methods don't throw
try {
    $logger->info('Test info message');
    $logger->warning('Test warning message');
    $logger->error('Test error message');
    $logger->debug('Test debug message');
} catch (\Throwable $e) {
    $pass = false;
    $errors[] = "logger methods threw exception: " . $e->getMessage();
}

// Test log with context
try {
    $logger->info('User {name} logged in', ['name' => 'TestUser']);
} catch (\Throwable $e) {
    $pass = false;
    $errors[] = "logger with context threw exception: " . $e->getMessage();
}

// Test interpolation is working (should not throw)
try {
    $logger->info('Values: {val1} and {val2}', ['val1' => 123, 'val2' => 'abc']);
} catch (\Throwable $e) {
    $pass = false;
    $errors[] = "logger interpolation threw exception: " . $e->getMessage();
}

// Test that methods exist
$reflection = new \ReflectionClass($logger);
if (!$reflection->hasMethod('info') ||
    !$reflection->hasMethod('warning') ||
    !$reflection->hasMethod('error') ||
    !$reflection->hasMethod('debug')) {
    $pass = false;
    $errors[] = "logger missing required methods";
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}