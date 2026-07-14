<?php
/**
 * Equivalence test for permission_checker.
 * Verifies behavioral equivalence across different implementations.
 *
 * Run: php gen/seeds/permission_checker/equivalence_test.php
 */

declare(strict_types=1);

require __DIR__ . '/payload.php';

$pass = true;
$errors = [];

// Get the class name from declared classes
$classes = get_declared_classes();
$className = end($classes);
$checker = new $className();

// Use Reflection to set the private permissionMap property
$reflectionClass = new ReflectionClass($className);
$permMapProp = $reflectionClass->getProperty('permissionMap');
$permMapProp->setAccessible(true);
$permMapProp->setValue($checker, [
    'admin' => ['*'],
    'editor' => ['articles.edit', 'articles.delete', 'comments.edit'],
    'viewer' => ['articles.view'],
]);

// Test admin with owner context (admin with * requires owner context to pass)
$adminUser = ['id' => 1, 'roles' => ['admin']];
$adminContext = ['user_id' => 1, 'owner_id' => 1];
if (!$checker->check($adminUser, 'anything.here', $adminContext)) {
    $pass = false;
    $errors[] = "permission_checker admin with matching owner context should pass";
}

// Test admin without matching context fails
if ($checker->check($adminUser, 'anything.here', [])) {
    $pass = false;
    $errors[] = "permission_checker admin without owner context should fail";
}

// Test editor with specific permissions
$editorUser = ['id' => 2, 'roles' => ['editor']];
$editorContext = ['user_id' => 2, 'owner_id' => 2];
if (!$checker->check($editorUser, 'articles.edit', $editorContext)) {
    $pass = false;
    $errors[] = "permission_checker editor should have articles.edit";
}
if (!$checker->check($editorUser, 'articles.delete', $editorContext)) {
    $pass = false;
    $errors[] = "permission_checker editor should have articles.delete";
}

// Test viewer cannot edit
$viewerUser = ['id' => 3, 'roles' => ['viewer']];
if ($checker->check($viewerUser, 'articles.edit', ['user_id' => 3, 'owner_id' => 3])) {
    $pass = false;
    $errors[] = "permission_checker viewer should NOT have articles.edit";
}

// Test user with no roles
$noRolesUser = ['id' => 4, 'roles' => []];
if ($checker->check($noRolesUser, 'anything.at.all', ['user_id' => 4, 'owner_id' => 4])) {
    $pass = false;
    $errors[] = "permission_checker user with no roles should fail all checks";
}

if ($pass) {
    echo "[PASS] {$argv[0]}\n";
    exit(0);
} else {
    echo "[FAIL] " . implode(", ", $errors) . "\n";
    exit(1);
}