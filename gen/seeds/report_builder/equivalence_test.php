<?php

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
loadAs($dir . '/payload.php', 'report_pristine');

$variants = [
    'report_table_method' => $dir . '/variants/table_method.php',
    'report_array_map' => $dir . '/variants/array_map.php',
];
foreach ($variants as $fn => $file) {
    loadAs($file, $fn);
}

$testData = [
    [
        ['name' => 'Alice', 'score' => 95, 'grade' => 'A'],
        ['name' => 'Bob', 'score' => 78, 'grade' => 'C'],
        ['name' => 'Charlie', 'score' => 85, 'grade' => 'B'],
    ],
    [],
    [['id' => 1, 'value' => 'test']],
];

$testOptions = [
    ['title' => 'Grades', 'format' => 'text'],
    ['title' => 'Report', 'format' => 'html', 'include_header' => true, 'include_footer' => true],
    ['title' => 'Empty', 'format' => 'text', 'include_header' => false],
];

$failures = 0;
$count = 0;

foreach ($testData as $di => $data) {
    foreach ($testOptions as $oi => $options) {
        $expected = @report_pristine($data, $options);
        foreach (array_keys($variants) as $fn) {
            $count++;
            $actual = @$fn($data, $options);
            if ($actual !== $expected) {
                $failures++;
                fwrite(STDERR, "[FAIL] {$fn} data={$di} opts={$oi}\n");
            }
        }
    }
}

if ($failures > 0) {
    fwrite(STDERR, "[equivalence] user_repository: {$failures}/{$count} divergence(s)\n");
    exit(1);
}
echo "[equivalence] user_repository: OK ({$count} input combinations across " . count($variants) . " variant(s))\n";
exit(0);
