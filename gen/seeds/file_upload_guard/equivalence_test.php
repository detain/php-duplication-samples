<?php
/**
 * Equivalence test for file_upload_guard.
 * Verifies file upload validation.
 *
 * Run: php gen/seeds/file_upload_guard/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

$classes = get_declared_classes();
$className = end($classes);
$guard = new $className();

$testCases = [
    [
        'file' => ['error' => UPLOAD_ERR_OK, 'size' => 1000, 'name' => 'doc.pdf', 'type' => 'application/pdf'],
        'rules' => ['max_size' => 5000, 'allowed_extensions' => ['pdf'], 'allowed_types' => ['application/pdf']],
        'expectValid' => true,
    ],
    [
        'file' => ['error' => UPLOAD_ERR_OK, 'size' => 100000, 'name' => 'doc.pdf', 'type' => 'application/pdf'],
        'rules' => ['max_size' => 5000],
        'expectValid' => false,
        'expectError' => 'too_large',
    ],
    [
        'file' => ['error' => UPLOAD_ERR_OK, 'size' => 1000, 'name' => 'doc.exe', 'type' => 'application/octet-stream'],
        'rules' => ['allowed_extensions' => ['pdf', 'doc']],
        'expectValid' => false,
        'expectError' => 'invalid_extension',
    ],
    [
        'file' => ['error' => UPLOAD_ERR_INI_SIZE, 'size' => 0, 'name' => '', 'type' => ''],
        'rules' => [],
        'expectValid' => false,
        'expectError' => 'upload_error',
    ],
];

foreach ($testCases as $i => $case) {
    $result = $guard->validateUpload($case['file'], $case['rules']);

    if ($result['valid'] !== $case['expectValid']) {
        $pass = false;
        $errors[] = "file_upload_guard case {$i} valid mismatch";
    }

    if (isset($case['expectError']) && !in_array($case['expectError'], $result['errors'])) {
        $pass = false;
        $errors[] = "file_upload_guard case {$i} missing error: {$case['expectError']}";
    }
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}