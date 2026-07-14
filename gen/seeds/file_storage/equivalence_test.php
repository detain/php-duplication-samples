<?php
/**
 * Equivalence test for file_storage.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/file_storage/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$storage = new $className();

// Create a temp file for testing
$tempDir = sys_get_temp_dir();
$tempFile = $tempDir . '/test_file_' . uniqid() . '.txt';
file_put_contents($tempFile, 'test content for file storage');

// Test upload
$result = @$storage->upload($tempFile, 'uploads/test.txt');
if (!is_array($result)) {
    $pass = false;
    $errors[] = "file_storage upload() returned non-array";
} elseif (!isset($result['success'])) {
    $pass = false;
    $errors[] = "file_storage upload() missing success key";
}

// Test upload with non-existent file
$result2 = @$storage->upload('/non/existent/file.txt', 'uploads/test.txt');
if (!isset($result2['success']) || $result2['success'] !== false) {
    $pass = false;
    $errors[] = "file_storage should fail for non-existent source";
}

// Test download
$result3 = @$storage->download('uploads/test.txt');
if (!is_array($result3)) {
    $pass = false;
    $errors[] = "file_storage download() should return array";
}

// Cleanup
@unlink($tempFile);

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}