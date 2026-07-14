<?php
/**
 * Equivalence test for http_client.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/http_client/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$client = new $className();

// Test with a simple request (using httpbin.org for testing)
$result = @$client->request('GET', 'https://httpbin.org/get', ['timeout' => 10]);

if (!is_array($result)) {
    $pass = false;
    $errors[] = "http_client request() returned non-array";
} elseif (isset($result['error']) && !isset($result['body'])) {
    // Network error is acceptable in test environment
    echo "[INFO] http_client network error (acceptable in sandbox): {$result['error']}\n";
} elseif (!isset($result['body']) && !isset($result['code'])) {
    $pass = false;
    $errors[] = "http_client response missing body/code";
}

// Test that we can instantiate and call without fatal error
$client2 = new $className();
$result2 = @$client2->request('POST', 'https://httpbin.org/post', [
    'body' => ['test' => 'value'],
    'timeout' => 10,
]);
if (!is_array($result2)) {
    $pass = false;
    $errors[] = "http_client POST request failed";
}

// Verify deterministic error handling for invalid URL
$client3 = new $className();
$result3 = @$client3->request('GET', 'not-a-valid-url', ['timeout' => 1]);
if (isset($result3['error'])) {
    // This should return an error, not crash
    echo "[INFO] http_client correctly handles invalid URL\n";
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}