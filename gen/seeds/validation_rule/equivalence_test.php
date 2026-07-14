<?php
/**
 * Equivalence test for validation_rule.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/validation_rule/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$validator = new $className();

// Test required rule
$errors1 = $validator->validate(null, [['name' => 'required']]);
if (count($errors1) === 0) {
    $pass = false;
    $errors[] = "validation_rule required should fail for null";
}

$errors2 = $validator->validate('value', [['name' => 'required']]);
if (count($errors2) !== 0) {
    $pass = false;
    $errors[] = "validation_rule required should pass for 'value'";
}

// Test min_length rule
$errors3 = $validator->validate('ab', [['name' => 'min_length', 'param' => 3]]);
if (count($errors3) === 0) {
    $pass = false;
    $errors[] = "validation_rule min_length should fail for 'ab' (min 3)";
}

$errors4 = $validator->validate('abcd', [['name' => 'min_length', 'param' => 3]]);
if (count($errors4) !== 0) {
    $pass = false;
    $errors[] = "validation_rule min_length should pass for 'abcd' (min 3)";
}

// Test max_length rule
$errors5 = $validator->validate('abcdef', [['name' => 'max_length', 'param' => 3]]);
if (count($errors5) === 0) {
    $pass = false;
    $errors[] = "validation_rule max_length should fail for 'abcdef' (max 3)";
}

// Test min rule
$errors6 = $validator->validate(5, [['name' => 'min', 'param' => 10]]);
if (count($errors6) === 0) {
    $pass = false;
    $errors[] = "validation_rule min should fail for 5 (min 10)";
}

// Test max rule
$errors7 = $validator->validate(15, [['name' => 'max', 'param' => 10]]);
if (count($errors7) === 0) {
    $pass = false;
    $errors[] = "validation_rule max should fail for 15 (max 10)";
}

// Test in rule
$errors8 = $validator->validate('red', [['name' => 'in', 'param' => ['red', 'green', 'blue']]]);
if (count($errors8) !== 0) {
    $pass = false;
    $errors[] = "validation_rule in should pass for 'red'";
}

$errors9 = $validator->validate('yellow', [['name' => 'in', 'param' => ['red', 'green', 'blue']]]);
if (count($errors9) === 0) {
    $pass = false;
    $errors[] = "validation_rule in should fail for 'yellow'";
}

// Test pattern rule
$errors10 = $validator->validate('abc123', [['name' => 'pattern', 'param' => '/^[a-z]+$/']]);
if (count($errors10) === 0) {
    $pass = false;
    $errors[] = "validation_rule pattern should fail for 'abc123' (needs letters only)";
}

// Verify deterministic
$errors11 = $validator->validate('test', [['name' => 'min_length', 'param' => 3]]);
$errors12 = $validator->validate('test', [['name' => 'min_length', 'param' => 3]]);
if ($errors11 !== $errors12) {
    $pass = false;
    $errors[] = "validation_rule non-deterministic";
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}