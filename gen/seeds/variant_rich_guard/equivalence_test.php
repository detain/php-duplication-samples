<?php
/**
 * Equivalence test for variant_rich_guard.
 * Verifies all variants produce equivalent authorization decisions.
 *
 * Run: php gen/seeds/variant_rich_guard/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/../../lib/Payload.php';
use Gen\Lib\Payload;

function loadAs(string $file, string $newName): void
{
    $code = implode("\n", Payload::region($file));
    $code = preg_replace('/^\s*(?:public|protected|private)\s+function\s+\w+/m', 'function ' . $newName, $code, 1);
    eval($code);
}

$dir = __DIR__;
loadAs($dir . '/payload.php', 'guard_pristine');
loadAs($dir . '/variants/cf_nested_conditions.php', 'guard_nested');
loadAs($dir . '/variants/bl_split_conditions.php', 'guard_split');

$variants = ['guard_nested', 'guard_split'];

$testCases = [
    [['id' => 1, 'status' => 'active', 'roles' => ['admin']], ['id' => 'r-1'], []],
    [['id' => 1, 'status' => 'active', 'roles' => []], ['owner_id' => 1], []],
    [['id' => 1, 'status' => 'active', 'grants' => ['r-5']], ['id' => 'r-5'], []],
    [['id' => 1, 'status' => 'inactive'], ['id' => 'r-1'], []],
    [['id' => 1, 'status' => 'active'], ['id' => 'r-1'], ['allowed_origins' => ['domain.com'], 'request_origin' => 'other.com']],
    [['id' => '', 'status' => 'active'], ['id' => 'r-1'], []],
    [['id' => 1, 'status' => 'active', 'roles' => ['viewer']], ['id' => 'r-1'], []],
    [['id' => 1, 'status' => 'verified', 'roles' => ['user']], ['owner_id' => 99], []],
];

$failures = 0;
foreach ($testCases as $i => [$user, $resource, $context]) {
    $expected = guard_pristine($user, $resource, $context);
    foreach ($variants as $fn) {
        $actual = @$fn($user, $resource, $context);
        if ($actual !== $expected) {
            $failures++;
            fwrite(STDERR, "[FAIL] case {$i} variant {$fn}: expected " . json_encode($expected) . " got " . json_encode($actual) . "\n");
        }
    }
}

if ($failures > 0) {
    fwrite(STDERR, "[equivalence] variant_rich_guard: {$failures} divergence(s)\n");
    exit(1);
}
echo "[equivalence] variant_rich_guard: OK (" . count($testCases) . " cases x " . count($variants) . " variant(s))\n";
exit(0);
