<?php

declare(strict_types=1);

/**
 * Behavioral-equivalence proof for the query_builder seed.
 *
 *   php gen/seeds/query_builder/equivalence_test.php
 *
 * Exit 0 = both imperative and fluent builders produce the same SQL.
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
loadAs($dir . '/payload.php', 'build_imperative');
loadAs($dir . '/variants/api_fluent_api.php', 'build_fluent');

$cases = [
    ['users', ['id', 'name'], ['status = active'], 'name'],
    ['orders', ['*'], [], 'created_at'],
    ['products', ['sku', 'price'], ['price > 10'], 'sku'],
];

$failures = 0;
$count = 0;

foreach ($cases as $ci => $case) {
    [$table, $cols, $wheres, $order] = $case;

    // Imperative
    $imp = new class {
        public function select(string $t, array $c): void {}
        public function where(string $w): void {}
        public function orderBy(string $o): void {}
        public function build(): string { return ''; }
    };
    $code = implode("\n", Payload::region($dir . '/payload.php'));
    $code = str_replace('class ' . __COMPILER_HALT_OFFSET__ . 'Seed', 'class ImpBuilder', $code);
    eval($code);
    $bImp = new \ImpBuilder();
    $bImp->select($table, $cols);
    foreach ($wheres as $w) { $bImp->where($w); }
    if ($order) { $bImp->orderBy($order); }
    $expected = $bImp->build();

    // Fluent
    $code2 = implode("\n", Payload::region($dir . '/variants/api_fluent_api.php'));
    $code2 = str_replace('class QueryBuilderFluentVariant', 'class FluentBuilder', $code2);
    eval($code2);
    $bFl = new \FluentBuilder();
    $bFl->select($table, $cols);
    foreach ($wheres as $w) { $bFl->where($w); }
    if ($order) { $bFl->orderBy($order); }
    $actual = $bFl->build();

    $count++;
    if ($actual !== $expected) {
        $failures++;
        fwrite(STDERR, "[FAIL] case {$ci}: expected {$expected} got {$actual}\n");
    }
}

if ($failures > 0) {
    fwrite(STDERR, "[equivalence] query_builder: {$failures}/{$count} divergence(s)\n");
    exit(1);
}
echo "[equivalence] query_builder: OK ({$count} cases)\n";
exit(0);
