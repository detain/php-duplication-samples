<?php
/**
 * Equivalence test for cache_manager.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/cache_manager/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$cache = new $className();

// Test set and get
$cache->set('test_key', 'test_value');
$value = $cache->get('test_key');
if ($value !== 'test_value') {
    $pass = false;
    $errors[] = "cache_manager get/set returned " . json_encode($value) . " expected 'test_value'";
}

// Test overwrite
$cache->set('test_key', 'new_value');
$value2 = $cache->get('test_key');
if ($value2 !== 'new_value') {
    $pass = false;
    $errors[] = "cache_manager overwrite failed";
}

// Test non-existent key
$missing = $cache->get('non_existent_key');
if ($missing !== null) {
    $pass = false;
    $errors[] = "cache_manager non-existent key should return null";
}

// Test invalidate
$cache->invalidate('test_key');
$value3 = $cache->get('test_key');
if ($value3 !== null) {
    $pass = false;
    $errors[] = "cache_manager invalidate failed";
}

// Test clear
$cache->set('key1', 'value1');
$cache->set('key2', 'value2');
$cache->clear();
$cleared1 = $cache->get('key1');
$cleared2 = $cache->get('key2');
if ($cleared1 !== null || $cleared2 !== null) {
    $pass = false;
    $errors[] = "cache_manager clear failed";
}

// Test TTL (if implemented)
$cache->set('ttl_key', 'ttl_value', 1);
$ttlValue = $cache->get('ttl_key');
if ($ttlValue !== 'ttl_value') {
    $pass = false;
    $errors[] = "cache_manager TTL get failed";
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}