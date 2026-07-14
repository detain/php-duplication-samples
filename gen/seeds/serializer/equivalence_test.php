<?php
/**
 * Equivalence test for serializer.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/serializer/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$serializer = new $className();

$testData = [
    'name' => 'Test User',
    'age' => 30,
    'active' => true,
    'items' => [1, 2, 3],
];

// Test JSON serialization
$json = @$serializer->serialize($testData, 'json');
$decoded = @$serializer->deserialize($json, 'json');
if ($decoded !== $testData) {
    $pass = false;
    $errors[] = "serializer JSON roundtrip failed";
}

// Test PHP serialization
$php = @$serializer->serialize($testData, 'php');
$decodedPhp = @$serializer->deserialize($php, 'php');
if ($decodedPhp !== $testData) {
    $pass = false;
    $errors[] = "serializer PHP roundtrip failed";
}

// Verify deterministic output
$json2 = @$serializer->serialize($testData, 'json');
if ($json !== $json2) {
    $pass = false;
    $errors[] = "serializer non-deterministic JSON output";
}

// Test error case - unsupported format
try {
    @$serializer->serialize($testData, 'invalid_format');
    $pass = false;
    $errors[] = "serializer should throw on invalid format";
} catch (\InvalidArgumentException $e) {
    // Expected
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}