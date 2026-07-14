<?php
/**
 * Equivalence test for password_policy.
 * Verifies password validation rules work correctly.
 *
 * Run: php gen/seeds/password_policy/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$validator = new $className();

$testCases = [
    [
        'password' => 'Weak1!',
        'rules' => ['min_length' => 8],
        'expectValid' => false,
        'expectError' => 'too_short'
    ],
    [
        'password' => 'NoDigits!',
        'rules' => ['require_digit' => true],
        'expectValid' => false,
        'expectError' => 'missing_digit'
    ],
    [
        'password' => 'noupercase1!',
        'rules' => ['require_uppercase' => true],
        'expectValid' => false,
        'expectError' => 'missing_uppercase'
    ],
    [
        'password' => 'NOLOWERCASE1!',
        'rules' => ['require_lowercase' => true],
        'expectValid' => false,
        'expectError' => 'missing_lowercase'
    ],
    [
        'password' => 'NoSpecial123',
        'rules' => ['require_special' => true],
        'expectValid' => false,
        'expectError' => 'missing_special'
    ],
    [
        'password' => 'ValidPass1!',
        'rules' => [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_digit' => true,
            'require_special' => true,
        ],
        'expectValid' => true,
        'expectError' => null
    ],
];

foreach ($testCases as $i => $case) {
    $result = @$validator->validatePassword($case['password'], $case['rules']);

    if (!is_array($result)) {
        $pass = false;
        $errors[] = "password_policy case {$i} returned non-array";
        continue;
    }

    if ($result['valid'] !== $case['expectValid']) {
        $pass = false;
        $errors[] = "password_policy case {$i} valid mismatch";
    }

    if ($case['expectError'] !== null && !in_array($case['expectError'], $result['errors'])) {
        $pass = false;
        $errors[] = "password_policy case {$i} missing error: {$case['expectError']}";
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}