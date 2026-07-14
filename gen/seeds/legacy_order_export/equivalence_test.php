<?php
/**
 * Equivalence test for legacy_order_export.
 * Verifies pipe-delimited order export format.
 *
 * Run: php gen/seeds/legacy_order_export/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$exporter = new $className();

$testCases = [
    [
        'order' => [
            'id' => 'ORD-001',
            'customer' => 'John Doe',
            'created_at' => mktime(0, 0, 0, 7, 14, 2026),
            'total' => 99.99,
            'status' => 'completed',
            'items' => [['sku' => 'A', 'qty' => 2]],
        ],
        'expectPattern' => '/^ORD-001\|John Doe\|2026-07-14\|99.99\|COMPLETED\|1$/',
    ],
    [
        'order' => [
            'id' => 'ORD-002',
            'customer' => 'Jane Smith',
            'created_at' => time(),
            'total' => 150.00,
            'status' => 'pending',
            'items' => [],
        ],
        'expectPattern' => '/^ORD-002\|Jane Smith\|\d{4}-\d{2}-\d{2}\|150.00\|PENDING\|0$/',
    ],
    [
        'order' => [
            'id' => 'ORD-003',
            'customer' => 'Test|User',
            'total' => 10.00,
        ],
        'expectPattern' => '/".*"\|Test\|User.*"/', // Quoted because of pipe
    ],
];

foreach ($testCases as $i => $case) {
    $result = $exporter->exportLegacyFormat($case['order']);

    if (!is_string($result)) {
        $pass = false;
        $errors[] = "legacy_order_export case {$i} returned non-string";
        continue;
    }

    $parts = explode('|', $result);
    if (count($parts) !== 6) {
        $pass = false;
        $errors[] = "legacy_order_export case {$i} wrong part count: $result";
    }

    // Check ID is first part
    if ($parts[0] !== $case['order']['id']) {
        $pass = false;
        $errors[] = "legacy_order_export case {$i} ID mismatch";
    }

    // Check total format
    if (!preg_match('/^\d+\.\d{2}$/', $parts[3])) {
        $pass = false;
        $errors[] = "legacy_order_export case {$i} total format wrong: {$parts[3]}";
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}