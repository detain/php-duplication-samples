<?php
/**
 * Equivalence test for inventory_reservation.
 * Verifies inventory reservation logic.
 *
 * Run: php gen/seeds/inventory_reservation/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$reservation = new $className();

$inventory = [
    'SKU-A' => ['quantity' => 100, 'reserved' => 0],
    'SKU-B' => ['quantity' => 50, 'reserved' => 10],
    'SKU-C' => ['quantity' => 5, 'reserved' => 0],
];

$testCases = [
    [
        'items' => [['sku' => 'SKU-A', 'quantity' => 10]],
        'expectSuccess' => true,
        'expectReserved' => 1,
    ],
    [
        'items' => [['sku' => 'SKU-A', 'quantity' => 10], ['sku' => 'SKU-B', 'quantity' => 5]],
        'expectSuccess' => true,
        'expectReserved' => 2,
    ],
    [
        'items' => [['sku' => 'SKU-B', 'quantity' => 50]], // Only 40 available (50-10)
        'expectSuccess' => false,
        'expectFailed' => 1,
    ],
    [
        'items' => [['sku' => 'SKU-X', 'quantity' => 1]], // Not found
        'expectSuccess' => false,
        'expectFailed' => 1,
    ],
    [
        'items' => [['sku' => 'SKU-C', 'quantity' => 10]], // Only 5 available
        'expectSuccess' => false,
        'expectFailed' => 1,
    ],
];

foreach ($testCases as $i => $case) {
    $result = $reservation->reserveInventory($case['items'], $inventory, 300);

    if ($result['success'] !== $case['expectSuccess']) {
        $pass = false;
        $errors[] = "inventory_reservation case {$i} success mismatch";
    }

    if (isset($case['expectReserved']) && count($result['reserved']) !== $case['expectReserved']) {
        $pass = false;
        $errors[] = "inventory_reservation case {$i} reserved count mismatch";
    }

    if (isset($case['expectFailed']) && count($result['failed']) !== $case['expectFailed']) {
        $pass = false;
        $errors[] = "inventory_reservation case {$i} failed count mismatch";
    }

    if ($result['success'] && !empty($result['reserved'])) {
        foreach ($result['reserved'] as $r) {
            if (!isset($r['expires_at']) || $r['expires_at'] <= time()) {
                $pass = false;
                $errors[] = "inventory_reservation case {$i} invalid expiry";
            }
        }
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}