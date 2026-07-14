<?php
/**
 * Equivalence test for event_dispatcher.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/event_dispatcher/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$dispatcher = new $className();

// Test on and dispatch
$called = false;
$dispatcher->on('test.event', function ($context) use (&$called) {
    $called = true;
    return $context;
});

$result = $dispatcher->dispatch('test.event', ['key' => 'value']);
if (!$called) {
    $pass = false;
    $errors[] = "event_dispatcher listener not called";
}

// Test context passed correctly
$contextReceived = null;
$dispatcher2 = new $className();
$dispatcher2->on('data.event', function ($context) use (&$contextReceived) {
    $contextReceived = $context;
    return $context;
});
$dispatcher2->dispatch('data.event', ['test' => 'data']);
if ($contextReceived !== ['test' => 'data']) {
    $pass = false;
    $errors[] = "event_dispatcher context not passed correctly";
}

// Test multiple listeners with priority
$callOrder = [];
$dispatcher3 = new $className();
$dispatcher3->on('multi.event', function ($ctx) use (&$callOrder) {
    $callOrder[] = 1;
}, 10); // higher priority
$dispatcher3->on('multi.event', function ($ctx) use (&$callOrder) {
    $callOrder[] = 2;
}, 5); // lower priority
$dispatcher3->dispatch('multi.event', []);
if ($callOrder !== [1, 2]) {
    $pass = false;
    $errors[] = "event_dispatcher priority order incorrect: " . json_encode($callOrder);
}

// Test off
$listener = function ($ctx) { return $ctx; };
$dispatcher4 = new $className();
$dispatcher4->on('remove.event', $listener);
$dispatcher4->off('remove.event', $listener);
$results = $dispatcher4->dispatch('remove.event', []);
if (count($results) !== 0) {
    $pass = false;
    $errors[] = "event_dispatcher off() failed";
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}