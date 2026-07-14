<?php
/**
 * Equivalence test for state_machine.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/state_machine/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$sm = new $className();

// Use Reflection to set the private transitions property
$reflectionClass = new ReflectionClass($className);
$transitionsProp = $reflectionClass->getProperty('transitions');
$transitionsProp->setAccessible(true);
$transitionsProp->setValue($sm, [
    'draft' => ['submit' => ['to' => 'pending', 'guard' => fn() => true]],
    'pending' => ['approve' => ['to' => 'approved', 'guard' => fn() => true]],
    'approved' => ['publish' => ['to' => 'published', 'guard' => fn() => true]],
]);

// Test transition()
$result = $sm->transition('draft', 'submit');
if ($result !== 'pending') {
    $pass = false;
    $errors[] = "state_machine transition() returned " . json_encode($result) . " expected 'pending'";
}

// Test can()
$can = $sm->can('draft', 'submit');
if ($can !== true) {
    $pass = false;
    $errors[] = "state_machine can() returned " . json_encode($can) . " expected true";
}

// Test invalid transition
$result2 = $sm->transition('draft', 'approve');
if ($result2 !== null) {
    $pass = false;
    $errors[] = "state_machine invalid transition should return null";
}

// Test events()
$events = $sm->events('draft');
if ($events !== ['submit']) {
    $pass = false;
    $errors[] = "state_machine events() returned " . json_encode($events);
}

// Test metadata()
$meta = $sm->metadata('draft');
if (!isset($meta['is_initial']) || !isset($meta['is_final'])) {
    $pass = false;
    $errors[] = "state_machine metadata() missing required keys";
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}