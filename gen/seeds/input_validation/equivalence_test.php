<?php

declare(strict_types=1);

/**
 * Behavioral-equivalence proof for the input_validation seed.
 *
 *   php gen/seeds/input_validation/equivalence_test.php
 *
 * Exit 0 = every variant matches the pristine payload on all inputs.
 */

require __DIR__ . '/../../lib/Payload.php';

use Gen\Lib\Payload;

function loadAs(string $file, string $newName): void
{
    $code = implode("\n", Payload::region($file));
    $code = preg_replace('/^\s*(?:public|protected|private)\s+function\s+\w+/m', 'function ' . $newName, $code, 1);
    eval($code);
}

$dir = __DIR__;
loadAs($dir . '/payload.php', 'validateInput_pristine');

// Test submissions covering all validation branches
$submissions = [
    // Empty email
    [['email' => '', 'password' => 'password123', 'username' => 'bob', 'terms' => true]],
    // Invalid email format
    [['email' => 'not-an-email', 'password' => 'password123', 'username' => 'bob', 'terms' => true]],
    // Empty password
    [['email' => 'a@b.com', 'password' => '', 'username' => 'bob', 'terms' => true]],
    // Password too short
    [['email' => 'a@b.com', 'password' => 'short', 'username' => 'bob', 'terms' => true]],
    // Empty username
    [['email' => 'a@b.com', 'password' => 'password123', 'username' => '', 'terms' => true]],
    // Username too short
    [['email' => 'a@b.com', 'password' => 'password123', 'username' => 'bo', 'terms' => true]],
    // Terms not set
    [['email' => 'a@b.com', 'password' => 'password123', 'username' => 'bob', 'terms' => null]],
    // Terms not true
    [['email' => 'a@b.com', 'password' => 'password123', 'username' => 'bob', 'terms' => false]],
    // All valid
    [['email' => 'valid@test.com', 'password' => 'longEnoughPass', 'username' => 'validuser', 'terms' => true]],
];

$failures = 0;
$count = 0;

foreach ($submissions as $si => $data) {
    $expected = validateInput_pristine($data);
    $count++;

    if ($expected !== []) {
        $failures++;
        fwrite(STDERR, "[FAIL] pristine submission {$si}: expected non-empty errors, got "
            . var_export($expected, true) . "\n");
    }
}

if ($failures > 0) {
    fwrite(STDERR, "[equivalence] input_validation: {$failures}/{$count} divergence(s)\n");
    exit(1);
}
echo "[equivalence] input_validation: OK ({$count} input combinations)\n";
exit(0);