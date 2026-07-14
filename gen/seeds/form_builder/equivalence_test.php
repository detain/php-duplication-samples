<?php

declare(strict_types=1);

require __DIR__ . '/../../lib/Payload.php';

use Gen\Lib\Payload;

function loadAs(string $file, string $newName): void
{
    $code = implode("\n", Payload::region($file));
    $code = preg_replace('/^\s*(?:public|protected|private)\s+function\s+\w+/', 'function ' . $newName, $code, 1);
    eval($code);
}

$dir = __DIR__;
loadAs($dir . '/payload.php', 'form_pristine');

$variants = [
    'form_data_driven' => $dir . '/variants/data_driven.php',
];
foreach ($variants as $fn => $file) {
    loadAs($file, $fn);
}

$testSchemas = [
    [
        'id' => 'login',
        'method' => 'POST',
        'action' => '/login',
        'fields' => [
            ['name' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true],
            ['name' => 'password', 'type' => 'password', 'label' => 'Password', 'required' => true],
        ],
    ],
    [
        'id' => 'profile',
        'fields' => [
            ['name' => 'name', 'type' => 'text', 'label' => 'Full Name'],
            ['name' => 'country', 'type' => 'select', 'options' => ['us' => 'United States', 'uk' => 'United Kingdom'], 'value' => 'us'],
        ],
    ],
];

$failures = 0;
$count = 0;

foreach ($testSchemas as $si => $schema) {
    $expected = @form_pristine($schema);
    foreach (array_keys($variants) as $fn) {
        $count++;
        $actual = @$fn($schema);
        if ($actual !== $expected) {
            $failures++;
            fwrite(STDERR, "[FAIL] {$fn} schema={$si}\n");
        }
    }
}

if ($failures > 0) {
    fwrite(STDERR, "[equivalence] form_builder: {$failures}/{$count} divergence(s)\n");
    exit(1);
}
echo "[equivalence] form_builder: OK ({$count} input combinations across " . count($variants) . " variant(s))\n";
exit(0);
