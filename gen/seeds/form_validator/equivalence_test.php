<?php

declare(strict_types=1);

/**
 * Behavioral-equivalence proof for the form_validator seed.
 *
 *   php gen/seeds/form_validator/equivalence_test.php
 *
 * Exit 0 = every variant matches the pristine payload on all inputs.
 */

require __DIR__ . '/../../lib/Payload.php';

use Gen\Lib\Payload;

function loadAs(string $file, string $newName): void
{
    $code = implode("\n", Payload::region($file));
    $code = preg_replace('/^\s*(?:public|protected|private)\s+function\s+\w+/', 'function ' . $newName, $code, 1);
    eval($code);
}

$dir = __DIR__;
loadAs($dir . '/payload.php', 'validate_pristine');

$variants = [
    'validate_guard_chain' => $dir . '/variants/cf_validation_chains.php',
];
foreach ($variants as $fn => $file) {
    loadAs($file, $fn);
}

$submissions = [
    [['email' => '', 'password' => '', 'username' => '']],
    [['email' => 'not-an-email', 'password' => 'pass123', 'username' => 'bob']],
    [['email' => 'a@b.com', 'password' => 'short', 'username' => 'bob']],
    [['email' => 'a@b.com', 'password' => 'longEnoughPass', 'username' => 'bo']],
    [['email' => 'a@b.com', 'password' => 'longEnoughPass', 'username' => 'validuser']],
    [['email' => 'valid@test.com', 'password' => 'supersecretword', 'username' => 'alice']],
];

$failures = 0;
$count = 0;
foreach ($submissions as $si => $data) {
    $expected = validate_pristine($data);
    foreach (array_keys($variants) as $fn) {
        $count++;
        $actual = @$fn($data);
        if ($actual !== $expected) {
            $failures++;
            fwrite(STDERR, "[FAIL] {$fn} submission {$si}: expected "
                . var_export($expected, true) . " got " . var_export($actual, true) . "\n");
        }
    }
}

if ($failures > 0) {
    fwrite(STDERR, "[equivalence] form_validator: {$failures}/{$count} divergence(s)\n");
    exit(1);
}
echo "[equivalence] form_validator: OK ({$count} input combinations across "
    . count($variants) . " variant(s))\n";
exit(0);
