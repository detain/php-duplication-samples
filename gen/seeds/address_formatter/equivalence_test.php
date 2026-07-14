<?php
/**
 * Equivalence test for address_formatter.
 * Verifies address formatting into lines.
 *
 * Run: php gen/seeds/address_formatter/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$formatter = new $className();

$testCases = [
    [
        'address' => [
            'name' => 'John Doe',
            'street1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal' => '10001',
            'country' => 'USA',
        ],
        'expectLines' => ['John Doe', '123 Main St', 'New York, NY 10001', 'USA'],
    ],
    [
        'address' => [
            'company' => 'Acme Corp',
            'street1' => '456 Oak Ave',
            'street2' => 'Suite 100',
            'city' => 'Los Angeles',
            'postal' => '90001',
        ],
        'expectLines' => ['Acme Corp', '456 Oak Ave Suite 100', 'Los Angeles 90001'],
    ],
    [
        'address' => [
            'name' => 'Jane',
            'city' => 'Chicago',
            'state' => 'IL',
        ],
        'expectLines' => ['Jane', 'Chicago, IL'],
    ],
];

foreach ($testCases as $i => $case) {
    $result = $formatter->formatAddress($case['address']);

    if (!isset($result['lines']) || $result['lines'] !== $case['expectLines']) {
        $pass = false;
        $errors[] = "address_formatter case {$i}: expected " . json_encode($case['expectLines'])
            . " got " . json_encode($result['lines'] ?? null);
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}