<?php

declare(strict_types=1);

/**
 * Behavioral-equivalence proof for the access_guard seed (§12.4).
 *
 *   php gen/seeds/access_guard/equivalence_test.php
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
loadAs($dir . '/payload.php', 'auth_pristine');

// Every behaviorally-equivalent control-flow variant is checked against the
// pristine payload over the same input matrix. Add a row here when a new
// variant file is introduced under variants/.
$variants = [
    'auth_nested'        => $dir . '/variants/cf_guard_nested.php',
    'auth_match'         => $dir . '/variants/cf_match_guard.php',
    'auth_if_ternary'    => $dir . '/variants/cf_if_ternary.php',
    'auth_loop_forms'    => $dir . '/variants/cf_loop_forms.php',
    'auth_early_return'  => $dir . '/variants/cf_early_return.php',
    'auth_demorgan'      => $dir . '/variants/bl_demorgan.php',
    'auth_split_combined'=> $dir . '/variants/bl_split_combined.php',
    'auth_commutative'  => $dir . '/variants/bl_commutative.php',
    'auth_arith'         => $dir . '/variants/ex_arith.php',
    'auth_null_styles'  => $dir . '/variants/nu_null_styles.php',
];
foreach ($variants as $fn => $file) {
    loadAs($file, $fn);
}

// Exhaustive-ish matrix over the decision inputs.
$users = [
    [],
    ['id' => 1],
    ['id' => 1, 'status' => 'active'],
    ['id' => 1, 'status' => 'suspended', 'roles' => ['admin']],
    ['id' => 2, 'status' => 'active', 'roles' => ['admin']],
    ['id' => 3, 'status' => 'active', 'roles' => ['viewer']],
    ['id' => 4, 'status' => 'active', 'roles' => ['viewer'], 'grants' => ['r-9']],
];
$resources = [
    [],
    ['id' => 'r-1'],
    ['id' => 'r-9', 'ownerId' => 3],
    ['id' => 'r-9', 'ownerId' => 99],
];

$failures = 0;
$count = 0;
foreach ($users as $ui => $user) {
    foreach ($resources as $ri => $resource) {
        $expected = auth_pristine($user, $resource);
        foreach (array_keys($variants) as $fn) {
            $count++;
            $actual = @$fn($user, $resource);
            if ($actual !== $expected) {
                $failures++;
                fwrite(STDERR, "[FAIL] {$fn} user {$ui} resource {$ri}: expected "
                    . var_export($expected, true) . " got " . var_export($actual, true) . "\n");
            }
        }
    }
}

if ($failures > 0) {
    fwrite(STDERR, "[equivalence] access_guard: {$failures}/{$count} divergence(s)\n");
    exit(1);
}
echo "[equivalence] access_guard: OK ({$count} input combinations across "
    . count($variants) . " variant(s))\n";
exit(0);
