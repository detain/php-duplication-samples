<?php
/**
 * Equivalence test for template_renderer.
 * Verifies template placeholder substitution.
 *
 * Run: php gen/seeds/template_renderer/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$renderer = new $className();

$testCases = [
    [
        'template' => 'Hello {{name}}!',
        'data' => ['name' => 'World'],
        'expected' => 'Hello World!'
    ],
    [
        'template' => 'Order #{{order_id}} for {{customer}}',
        'data' => ['order_id' => '12345', 'customer' => 'Alice'],
        'expected' => 'Order #12345 for Alice'
    ],
    [
        'template' => 'Welcome {{name}}! Your code is {{code:NO_CODE}}.',
        'data' => ['name' => 'Bob'],
        'expected' => 'Welcome Bob! Your code is NO_CODE.'
    ],
    [
        'template' => 'Hello {{name}}!',
        'data' => ['other' => 'X'],
        'expected' => 'Hello {{name}}!'
    ],
];

foreach ($testCases as $i => $case) {
    $result = $renderer->renderTemplate($case['template'], $case['data']);

    if ($result !== $case['expected']) {
        $pass = false;
        $errors[] = "template_renderer case {$i}: expected '{$case['expected']}', got '$result'";
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}