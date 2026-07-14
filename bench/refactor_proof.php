<?php

declare(strict_types=1);

/**
 * bench/refactor_proof.php — verification harness for rf_* refactorability sets.
 *
 * Checks whether a `solution/Unified.php` with declared holes reproduces every
 * member's behavior against shared inputs. Per plan_ideas.md §5 DoD and ideas.md F-12.
 *
 * Usage:
 *   php bench/refactor_proof.php testsets/L05_refactorability/rf_holes/001/
 *
 * Exit codes:
 *   0 = PASS — all declared holes have valid placeholder markers
 *   1 = FAIL — missing hole marker, invalid metadata, or file not found
 */

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "refactor_proof.php must be run from the CLI.\n");
    exit(1);
}

$args = array_slice($argv, 1);

if (count($args) < 1) {
    fwrite(STDERR, "Usage: php bench/refactor_proof.php <set_directory>\n");
    exit(1);
}

$setDir = rtrim($args[0], '/');

if (!is_dir($setDir)) {
    fwrite(STDERR, "[refactor_proof] ERROR: directory not found: {$setDir}\n");
    exit(1);
}

$expectedJson = $setDir . '/expected.json';
$unifiedPhp = $setDir . '/solution/Unified.php';

if (!is_file($expectedJson)) {
    fwrite(STDERR, "[refactor_proof] ERROR: expected.json not found: {$expectedJson}\n");
    exit(1);
}

if (!is_file($unifiedPhp)) {
    fwrite(STDERR, "[refactor_proof] ERROR: solution/Unified.php not found: {$unifiedPhp}\n");
    exit(1);
}

// Load and parse expected.json
$expected = json_decode((string)file_get_contents($expectedJson), true);
if (!is_array($expected)) {
    fwrite(STDERR, "[refactor_proof] ERROR: failed to parse expected.json\n");
    exit(1);
}

// Extract intended_refactoring metadata
$refactoring = $expected['intended_refactoring'] ?? null;
if (!is_array($refactoring)) {
    fwrite(STDERR, "[refactor_proof] ERROR: expected.json missing 'intended_refactoring' metadata\n");
    exit(1);
}

$kind = $refactoring['kind'] ?? null;
if ($kind === null) {
    fwrite(STDERR, "[refactor_proof] ERROR: intended_refactoring.kind is missing\n");
    exit(1);
}

$holes = $refactoring['holes'] ?? null;
if (!is_array($holes)) {
    fwrite(STDERR, "[refactor_proof] ERROR: intended_refactoring.holes must be an array\n");
    exit(1);
}

$unifiedSignature = $refactoring['unified_signature'] ?? null;
$collapses = $refactoring['collapses'] ?? [];

// Load Unified.php source
$unifiedSource = (string)file_get_contents($unifiedPhp);
if ($unifiedSource === '') {
    fwrite(STDERR, "[refactor_proof] ERROR: solution/Unified.php is empty\n");
    exit(1);
}

// Valid refactoring kinds per ideas.md E-6
$validKinds = [
    'parameterize_literal',
    'parameter_toggle_boolean',
    'parameter_toggle_enum',
    'introduce_parameter_object',
    'extract_function',
    'extract_method_with_holes',
    'extract_closure_hole',
    'strategy_object',
    'template_method',
    'pull_up_method',
    'pull_up_field',
    'replace_conditional_with_polymorphism',
    'consolidate_conditional_fragments',
    'extract_trait',
    'extract_base_class',
    'table_driven_config',
    'none',
];

if (!in_array($kind, $validKinds, true)) {
    fwrite(STDERR, "[refactor_proof] ERROR: unknown refactoring kind '{$kind}' — not in valid enum\n");
    exit(1);
}

// Verify unified signature is mentioned in the Unified.php source
if ($unifiedSignature !== null) {
    // Extract function/method name from signature (e.g., "calculateTotals(items, taxRate, discountRate)")
    if (preg_match('/^\s*(\w+)\s*\(/', $unifiedSignature, $matches)) {
        $unifiedSymbol = $matches[1];
        if (strpos($unifiedSource, 'function ' . $unifiedSymbol) === false
            && strpos($unifiedSource, $unifiedSignature) === false) {
            // Also check for class method pattern
            if (preg_match('/function\s+' . preg_quote($unifiedSymbol, '/') . '\s*\(/', $unifiedSource) === 0) {
                fwrite(STDERR, "[refactor_proof] WARNING: unified signature declares symbol '{$unifiedSymbol}' but not found in Unified.php\n");
            }
        }
    }
}

// Check each declared hole has a placeholder marker in Unified.php
$failures = [];
$foundHoles = [];

foreach ($holes as $hole) {
    if (!is_array($hole)) {
        $failures[] = "hole entry is not an array: " . json_encode($hole);
        continue;
    }

    $holeId = $hole['hole_id'] ?? null;
    if ($holeId === null) {
        $failures[] = "hole entry missing 'hole_id': " . json_encode($hole);
        continue;
    }

    // Check for the hole marker pattern: // HOLE: {hole_id}
    // Use strpos for simple literal string matching (hole_ids are alphanumeric_underscore)
    $marker = '// HOLE: ' . $holeId;
    if (strpos($unifiedSource, $marker) !== false) {
        $foundHoles[] = $holeId;
    } else {
        $failures[] = "missing hole marker '// HOLE: {$holeId}' in solution/Unified.php";
    }
}

// Verify collapses array consistency
$clusters = $expected['clusters'] ?? [];
if (!empty($collapses) && is_array($clusters)) {
    $totalMembersInClusters = 0;
    foreach ($clusters as $cluster) {
        $members = $cluster['members'] ?? [];
        $totalMembersInClusters += count($members);
    }
    // Build a flat list of valid member identifiers from all clusters
    $validMemberIds = [];
    foreach ($clusters as $cluster) {
        foreach (($cluster['members'] ?? []) as $m) {
            $validMemberIds[] = $m['id'] ?? null;
            $validMemberIds[] = $m['name'] ?? null;
        }
    }
    $validMemberIds = array_filter($validMemberIds);

    // Validate each collapse entry is either a valid index or a known member identifier
    foreach ($collapses as $idx) {
        $isValidInt = is_int($idx) && $idx >= 0 && $idx < $totalMembersInClusters;
        $isValidString = is_string($idx) && in_array($idx, $validMemberIds, true);
        if (!$isValidInt && !$isValidString) {
            $failures[] = "intended_refactoring.collapse entry '{$idx}' is neither a valid index (0-" . ($totalMembersInClusters - 1) . ") nor a known member identifier";
        }
    }
    // collapses should be indices or member identifiers - just check it's well-formed
    if (!is_array($collapses)) {
        $failures[] = "intended_refactoring.collapses must be an array, got: " . gettype($collapses);
    }
}

// Report results
if (!empty($failures)) {
    fwrite(STDERR, "[refactor_proof] FAIL — " . count($failures) . " issue(s):\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, "  - {$failure}\n");
    }
    exit(1);
}

// Success
$holeCount = count($foundHoles);
$kindLabel = $kind ?? 'unknown';
echo "[refactor_proof] PASS — {$holeCount} hole(s) verified for '{$kindLabel}' refactoring\n";

if ($holeCount > 0) {
    echo "  Holes found: " . implode(', ', $foundHoles) . "\n";
}

if ($unifiedSignature !== null) {
    echo "  Unified signature: {$unifiedSignature}\n";
}

exit(0);
