<?php
/**
 * Equivalence test for config_loader.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/config_loader/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$loader = new $className();

// Create a temp file for testing
$tempDir = sys_get_temp_dir();
$tempPhp = $tempDir . '/test_config_' . uniqid() . '.php';
$tempJson = $tempDir . '/test_config_' . uniqid() . '.json';

file_put_contents($tempPhp, '<?php return ["key1" => "value1", "nested" => ["key2" => "value2"]];');
file_put_contents($tempJson, '{"key1": "value1", "nested": {"key2": "value2"}}');

// Test PHP file loading
$phpConfig = $loader->load($tempPhp);
if (!isset($phpConfig['key1']) || $phpConfig['key1'] !== 'value1') {
    $pass = false;
    $errors[] = "config_loader PHP loading returned " . json_encode($phpConfig);
}

// Test JSON file loading
$jsonConfig = $loader->load($tempJson);
if (!isset($jsonConfig['key1']) || $jsonConfig['key1'] !== 'value1') {
    $pass = false;
    $errors[] = "config_loader JSON loading returned " . json_encode($jsonConfig);
}

// Test non-existent file
$missingConfig = $loader->load('/non/existent/path.json');
if (!is_array($missingConfig) || !empty($missingConfig)) {
    $pass = false;
    $errors[] = "config_loader should return empty array for missing file";
}

// Cleanup
unlink($tempPhp);
unlink($tempJson);

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}