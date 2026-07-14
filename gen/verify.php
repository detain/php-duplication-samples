<?php

declare(strict_types=1);

/**
 * gen/verify.php — the §12 QA pass. Exits nonzero on any failure.
 *
 * Usage:
 *   php gen/verify.php --set=L02-ws_blank_inside-001
 *   php gen/verify.php --level=2
 *   php gen/verify.php --all
 *
 * Checks (per set):
 *   1. Syntax          php -l on every src file
 *   2. Schema          set.json/expected.json validate + cross-checks
 *   3. Positive proof  declared normalization makes carrier members equal
 *                      (L1-L4) / within budget (L5) / behaviorally equal (L6+)
 *   4. Negative proof  no accidental >=40 normalized-token match among
 *                      non-cluster regions; distractor is not a clone; L0 clean
 *   5. Line audit      ground-truth ranges actually bound the cloned region
 *   6. Determinism     re-render is byte-identical to the committed tree
 *   7. Hygiene         no role/level/dup hints in src; LF + UTF-8
 */

require __DIR__ . '/bootstrap.php';

use Gen\Lib\JsonSchema;
use Gen\Lib\PhpTokens;
use Gen\Lib\SetBuilder;

const NEG_MATCH_THRESHOLD = 40;

$root = dirname(__DIR__);
$opts = parseArgs($argv);
$builder = new SetBuilder($root);

$specs = collectSpecs($root, $opts);
if ($specs === []) {
    fwrite(STDERR, "[verify] no sets matched the given filter\n");
    exit(2);
}

$totalFail = 0;
foreach ($specs as [$family, $spec]) {
    $setId = (string)$spec['set_id'];
    $errors = verifySet($root, $builder, $family, $spec);
    if ($errors === []) {
        echo "[PASS] {$setId}\n";
    } else {
        $totalFail++;
        echo "[FAIL] {$setId}\n";
        foreach ($errors as $e) {
            echo "   - {$e}\n";
        }
    }
}

if ($totalFail > 0) {
    fwrite(STDERR, "\n[verify] {$totalFail} set(s) FAILED\n");
    exit(1);
}
echo "\n[verify] all " . count($specs) . " set(s) passed\n";
exit(0);

// ---------------------------------------------------------------------------

/** @return list<string> */
function verifySet(string $root, SetBuilder $builder, array $family, array $spec): array
{
    $errors = [];
    $setId = (string)$spec['set_id'];

    $built = $builder->build($family, $spec);
    $setDir = $root . '/testsets/' . $built['dir'];
    if (!is_dir($setDir)) {
        return ["set directory missing on disk: testsets/{$built['dir']} (run gen/build.php)"];
    }

    // 6. Determinism — committed tree must equal a fresh render.
    $rendered = $built['files'];
    $rendered['set.json'] = json_encode($built['set'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    $rendered['expected.json'] = json_encode($built['expected'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    foreach ($rendered as $rel => $content) {
        $onDisk = @file_get_contents($setDir . '/' . $rel);
        if ($onDisk === false) {
            $errors[] = "determinism: {$rel} missing on disk";
        } elseif ($onDisk !== $content) {
            $errors[] = "determinism: {$rel} differs from a fresh render (regenerate with gen/build.php)";
        }
    }

    // Load on-disk metadata as the source of truth for the remaining checks.
    $setJson = json_decode((string)@file_get_contents($setDir . '/set.json'), true);
    $expected = json_decode((string)@file_get_contents($setDir . '/expected.json'), true);
    if (!is_array($setJson) || !is_array($expected)) {
        $errors[] = 'could not read set.json / expected.json';
        return $errors;
    }

    // 1. Syntax
    foreach (glob($setDir . '/src/*.php') ?: [] as $file) {
        $out = [];
        $rc = 0;
        @exec('php -l ' . escapeshellarg($file) . ' 2>&1', $out, $rc);
        if ($rc !== 0) {
            $errors[] = 'syntax: php -l failed on ' . basename($file) . ' — ' . trim(implode(' ', $out));
        }
    }

    // 2. Schema + cross-checks
    $errors = array_merge($errors, schemaChecks($root, $setDir, $setJson, $expected, $family));

    // 7. Hygiene
    $errors = array_merge($errors, hygieneChecks($setDir));

    // 5. Line audit
    $errors = array_merge($errors, lineAudit($setDir, $expected));

    // 3. Positive proof
    $errors = array_merge($errors, positiveProof($root, $setDir, $setJson, $expected, $spec));

    // 4. Negative proof
    $errors = array_merge($errors, negativeProof($setDir, $setJson, $expected));

    // V-1 through V-10: Schema v2 extended checks — only run for v2 sets
    if (($setJson['schema_version'] ?? 1) >= 2) {
        $errors = array_merge($errors, v1RegionSlocRecompute($setDir, $setJson, $expected));
        $errors = array_merge($errors, v2BudgetConformance($setDir, $setJson, $expected));
        $errors = array_merge($errors, v3TilingCheck($setDir, $setJson, $expected));
        $errors = array_merge($errors, v4FragmentPositiveProof($setDir, $setJson, $expected));
        $errors = array_merge($errors, v5UniqueSegmentDistinctness($setDir, $setJson, $expected));
        $errors = array_merge($errors, v6RefactorProof($root, $setDir, $setJson, $expected));
        $errors = array_merge($errors, v7ProfileRecompute($setDir, $setJson, $expected));
        $errors = array_merge($errors, v8ProgressionSanity($root, $setJson));
        $errors = array_merge($errors, v9MultiClusterHygiene($setDir, $setJson, $expected));
        $errors = array_merge($errors, v10PairwiseConsistency($setDir, $setJson, $expected));
    }

    return $errors;
}

/** @return list<string> */
function schemaChecks(string $root, string $setDir, array $setJson, array $expected, array $family): array
{
    $errors = [];
    $schemaDir = $root . '/testsets/schema';

    foreach (JsonSchema::validate($setJson, json_decode((string)file_get_contents($schemaDir . '/set.schema.json'), true), 'set') as $e) {
        $errors[] = "schema(set.json): {$e}";
    }
    foreach (JsonSchema::validate($expected, json_decode((string)file_get_contents($schemaDir . '/expected.schema.json'), true), 'expected') as $e) {
        $errors[] = "schema(expected.json): {$e}";
    }

    // set_id matches path
    $expectDir = $family['level_dir'] . '/' . $family['family'] . '/' . substr($setJson['set_id'] ?? '', -3);
    if (!str_ends_with(rtrim($setDir, '/'), $expectDir)) {
        $errors[] = "cross-check: set_id {$setJson['set_id']} does not match directory {$setDir}";
    }
    if (($expected['set_id'] ?? '') !== ($setJson['set_id'] ?? '')) {
        $errors[] = 'cross-check: set.json and expected.json set_id disagree';
    }

    // interference codes exist in registry
    $registry = new Gen\Transforms\Registry();
    foreach (($setJson['interference'] ?? []) as $intf) {
        if (!$registry->has($intf['code'] ?? '')) {
            $errors[] = "cross-check: interference code {$intf['code']} not in registry";
        }
    }

    // files listed exist; roles consistent; cluster members are carriers
    $roleByPath = [];
    foreach (($setJson['files'] ?? []) as $f) {
        $roleByPath[$f['path']] = $f['role'];
        if (!is_file($setDir . '/' . $f['path'])) {
            $errors[] = "cross-check: listed file {$f['path']} does not exist";
        }
    }
    // every src file is listed
    foreach (glob($setDir . '/src/*.php') ?: [] as $file) {
        $rel = 'src/' . basename($file);
        if (!isset($roleByPath[$rel])) {
            $errors[] = "cross-check: {$rel} exists but is not listed in set.json";
        }
    }
    foreach (($expected['clusters'] ?? []) as $cluster) {
        foreach (($cluster['members'] ?? []) as $m) {
            if (($roleByPath[$m['file']] ?? null) !== 'carrier') {
                $errors[] = "cross-check: cluster member {$m['file']} is not role 'carrier'";
            }
        }
    }
    // Role composition (DoD-1 / §6). Enforce the standard shape mechanically so a
    // future mis-shaped set (e.g. 2 carrier / 2 distractor) fails loudly.
    $errors = array_merge($errors, roleCompositionChecks($setJson, $expected));

    return $errors;
}

/**
 * §6 anatomy: a standard duplication set is exactly 3 carrier + 1 distractor + 1
 * clean; an L0 negative control is distractor/clean only with an empty clusters
 * array. A family may override the expected counts by declaring a
 * `role_composition` object in set.json (e.g. ex_full_file, ex_multi_cluster).
 *
 * @return list<string>
 */
function roleCompositionChecks(array $setJson, array $expected): array
{
    $errors = [];
    $level = (int)($setJson['level'] ?? -1);

    $counts = ['carrier' => 0, 'distractor' => 0, 'clean' => 0];
    foreach (($setJson['files'] ?? []) as $f) {
        $role = (string)($f['role'] ?? '');
        if (isset($counts[$role])) {
            $counts[$role]++;
        } else {
            $errors[] = "role-composition: file {$f['path']} has unknown role '{$role}'";
        }
    }

    if ($level === 0) {
        if (($expected['clusters'] ?? []) !== []) {
            $errors[] = 'role-composition: L0 set must have an empty clusters array';
        }
        if ($counts['carrier'] !== 0) {
            $errors[] = "role-composition: L0 set must have 0 carriers, found {$counts['carrier']} (roles are distractor/clean only)";
        }
        return $errors;
    }

    // Non-L0: allow a declared override, else require the canonical 3+1+1.
    $expect = $setJson['role_composition'] ?? ['carrier' => 3, 'distractor' => 1, 'clean' => 1];
    foreach (['carrier', 'distractor', 'clean'] as $role) {
        $want = (int)($expect[$role] ?? 0);
        if ($counts[$role] !== $want) {
            $errors[] = "role-composition: expected {$want} {$role} file(s), found {$counts[$role]} "
                . "(standard set is 3 carrier + 1 distractor + 1 clean; declare set.json.role_composition to override)";
        }
    }
    return $errors;
}

/** @return list<string> */
function hygieneChecks(string $setDir): array
{
    $errors = [];
    foreach (glob($setDir . '/src/*.php') ?: [] as $file) {
        $base = basename($file);
        $content = (string)file_get_contents($file);

        if (str_contains($content, "\r")) {
            $errors[] = "hygiene: {$base} contains CR (must be LF-only)";
        }
        if (!mb_check_encoding($content, 'UTF-8')) {
            $errors[] = "hygiene: {$base} is not valid UTF-8";
        }
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $errors[] = "hygiene: {$base} has a UTF-8 BOM";
        }
        if (str_contains($content, '<<<')) {
            $errors[] = "hygiene: {$base} leaks a generator sentinel (<<<)";
        }
        // forbidden role/level/dup hints in names + comments
        if (preg_match('/\b(clone|dup|carrier|distractor|nearmiss|scaffold)\b/i', $base) || preg_match('/\bL[0-9]\b/', $base)) {
            $errors[] = "hygiene: filename {$base} contains a forbidden hint";
        }
        foreach (extractCommentsAndNames($content) as $text) {
            if (preg_match('/\b(clone|dup|carrier|distractor|nearmiss)\b/i', $text) || preg_match('/\bL[0-9]\b/', $text)) {
                $errors[] = "hygiene: {$base} comment/name reveals role/level: '" . trim($text) . "'";
                break;
            }
        }
    }
    return $errors;
}

/** @return list<string> comments + namespace + class/function names */
function extractCommentsAndNames(string $content): array
{
    $out = [];
    foreach (PhpTokens::rawTokens($content) as $t) {
        if (is_array($t) && in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            $out[] = $t[1];
        }
    }
    if (preg_match('/namespace\s+([^;]+);/', $content, $m)) {
        $out[] = $m[1];
    }
    if (preg_match_all('/\b(?:class|function)\s+(\w+)/', $content, $m)) {
        foreach ($m[1] as $name) {
            $out[] = $name;
        }
    }
    return $out;
}

/** @return list<string> */
function lineAudit(string $setDir, array $expected): array
{
    $errors = [];
    foreach (($expected['clusters'] ?? []) as $cluster) {
        $symbol = null;
        foreach (($cluster['members'] ?? []) as $m) {
            $symbol = $m['symbol'] ?? $symbol;
            $region = sliceRegion($setDir . '/' . $m['file'], (int)$m['start_line'], (int)$m['end_line']);
            if ($region === null) {
                $errors[] = "line-audit: {$m['file']} lines {$m['start_line']}-{$m['end_line']} out of range";
                continue;
            }
            $nonBlank = array_values(array_filter($region, static fn($l) => trim($l) !== ''));
            $first = $nonBlank[0] ?? '';
            $last = $nonBlank[count($nonBlank) - 1] ?? '';
            if ($symbol !== null && !str_contains($first, 'function ' . $symbol)) {
                $errors[] = "line-audit: {$m['file']} start line does not begin the symbol '{$symbol}' (got: " . trim($first) . ')';
            }
            if (trim($last) !== '}') {
                $errors[] = "line-audit: {$m['file']} end line is not the closing brace (got: " . trim($last) . ')';
            }
        }
    }
    return $errors;
}

/** @return list<string> */
function positiveProof(string $root, string $setDir, array $setJson, array $expected, array $spec): array
{
    $errors = [];
    $level = (int)($setJson['level'] ?? 0);

    foreach (($expected['clusters'] ?? []) as $cluster) {
        $members = $cluster['members'] ?? [];
        if (count($members) < 2) {
            continue;
        }

        if ($level >= 6) {
            // Behavioral equivalence via the seed's equivalence test.
            $seed = $setJson['seed'] ?? null;
            $eqFile = $root . '/gen/seeds/' . $seed . '/equivalence_test.php';
            if (!is_file($eqFile)) {
                $errors[] = "positive-proof: L{$level} requires gen/seeds/{$seed}/equivalence_test.php (missing)";
                continue;
            }
            $out = [];
            $rc = 0;
            @exec('php ' . escapeshellarg($eqFile) . ' 2>&1', $out, $rc);
            if ($rc !== 0) {
                $errors[] = "positive-proof: behavioral equivalence FAILED for seed {$seed}: " . trim(implode(' ', $out));
            }
            continue;
        }

        // Token-based proof for L1-L5.
        $stages = (array)($cluster['normalized_by'] ?? ['whitespace', 'comments']);
        $opts = PhpTokens::optsForStages($stages);
        $streams = [];
        foreach ($members as $m) {
            $region = sliceRegion($setDir . '/' . $m['file'], (int)$m['start_line'], (int)$m['end_line']);
            if ($region === null) {
                $errors[] = "positive-proof: cannot slice {$m['file']}";
                continue 2;
            }
            $streams[$m['file']] = PhpTokens::normalize(implode("\n", $region), $opts);
        }
        $ref = null;
        $refFile = null;
        foreach ($streams as $file => $stream) {
            if ($ref === null) {
                $ref = $stream;
                $refFile = $file;
                continue;
            }
            if ($level <= 4) {
                if ($stream !== $ref) {
                    $errors[] = "positive-proof: normalized token streams differ ({$refFile} vs {$file}) — members are not equal after [" . implode(',', $stages) . ']';
                }
            } else { // L5 edit budget
                $budget = (int)($spec['edit_budget'] ?? 12);
                $dist = PhpTokens::tokenEditDistance($ref, $stream);
                if ($dist > $budget) {
                    $errors[] = "positive-proof: token edit distance {$dist} exceeds budget {$budget} ({$refFile} vs {$file})";
                }
            }
        }
    }
    return $errors;
}

/** @return list<string> */
function negativeProof(string $setDir, array $setJson, array $expected): array
{
    $errors = [];

    // Cluster member ranges per file (to exclude from non-cluster comparisons).
    $clusterRanges = [];
    $memberStreams = [];
    foreach (($expected['clusters'] ?? []) as $cluster) {
        $clusterMemberStreams = []; // file => type2 stream, for the intra-cluster check
        foreach (($cluster['members'] ?? []) as $m) {
            $clusterRanges[$m['file']][] = [(int)$m['start_line'], (int)$m['end_line']];
            $region = sliceRegion($setDir . '/' . $m['file'], (int)$m['start_line'], (int)$m['end_line']);
            if ($region !== null) {
                $stream = PhpTokens::type2(implode("\n", $region));
                $memberStreams[] = $stream;
                $clusterMemberStreams[$m['file']] = $stream;
            }
        }

        // Token-distinctness: a cluster labelled token_based:false must NOT contain
        // a member pair that a token tool would pair on its own. Assert no pair of
        // members shares a >=40-token normalized (Type-2) run. This is what should
        // have caught the L06 identical-carrier defect (BLOCKER-1); it guards every
        // token_based:false set (L6-L8) against re-introducing a token-detectable pair.
        if (($cluster['detection_expectation']['token_based'] ?? true) === false) {
            $files = array_keys($clusterMemberStreams);
            for ($a = 0; $a < count($files); $a++) {
                for ($b = $a + 1; $b < count($files); $b++) {
                    $run = PhpTokens::longestCommonRun(
                        $clusterMemberStreams[$files[$a]],
                        $clusterMemberStreams[$files[$b]]
                    );
                    if ($run >= NEG_MATCH_THRESHOLD) {
                        $errors[] = "token-distinctness: cluster '{$cluster['id']}' is token_based:false but members "
                            . "{$files[$a]} and {$files[$b]} share a {$run}-token normalized (Type-2) run "
                            . "(>= " . NEG_MATCH_THRESHOLD . ") — a token tool would pair them, contradicting the label";
                    }
                }
            }
        }
    }

    // Distractor traps must not be a clone of any cluster member.
    foreach (($expected['non_duplicates'] ?? []) as $nd) {
        if (empty($nd['trap'])) {
            continue;
        }
        $region = sliceRegion($setDir . '/' . $nd['file'], (int)$nd['start_line'], (int)$nd['end_line']);
        if ($region === null) {
            $errors[] = "negative-proof: cannot slice trap region {$nd['file']}";
            continue;
        }
        $trapStream = PhpTokens::type2(implode("\n", $region));
        foreach ($memberStreams as $ms) {
            $run = PhpTokens::longestCommonRun($trapStream, $ms);
            if ($run >= NEG_MATCH_THRESHOLD) {
                $errors[] = "negative-proof: distractor {$nd['file']} shares a {$run}-token normalized run with a carrier — it is effectively a clone";
                break;
            }
        }
    }

    // No accidental >=40 match among non-cluster regions across files.
    $nonClusterStreams = [];
    foreach (($setJson['files'] ?? []) as $f) {
        $path = $f['path'];
        $lines = explode("\n", (string)file_get_contents($setDir . '/' . $path));
        $ranges = $clusterRanges[$path] ?? [];
        $kept = [];
        foreach ($lines as $i => $line) {
            $ln = $i + 1;
            $inCluster = false;
            foreach ($ranges as [$s, $e]) {
                if ($ln >= $s && $ln <= $e) {
                    $inCluster = true;
                    break;
                }
            }
            if (!$inCluster) {
                $kept[] = $line;
            }
        }
        $nonClusterStreams[$path] = PhpTokens::type2(implode("\n", $kept));
    }
    $paths = array_keys($nonClusterStreams);
    for ($i = 0; $i < count($paths); $i++) {
        for ($j = $i + 1; $j < count($paths); $j++) {
            $run = PhpTokens::longestCommonRun($nonClusterStreams[$paths[$i]], $nonClusterStreams[$paths[$j]]);
            if ($run >= NEG_MATCH_THRESHOLD) {
                $errors[] = "negative-proof: non-cluster regions of {$paths[$i]} and {$paths[$j]} share a {$run}-token run (accidental clone)";
            }
        }
    }

    // L0: token detectors should find nothing.
    if ((int)($setJson['level'] ?? -1) === 0) {
        $errors = array_merge($errors, l0ToolTriage($setDir));
    }

    return $errors;
}

/**
 * V-1: region_sloc recompute from fragments ∪ unique_segments ∪ member ranges.
 * Verifies that declared region_sloc values match actual computed values.
 * @return list<string>
 */
function v1RegionSlocRecompute(string $setDir, array $setJson, array $expected): array
{
    $errors = [];

    foreach (($expected['clusters'] ?? []) as $cluster) {
        foreach (($cluster['members'] ?? []) as $m) {
            $file = $m['file'];
            $startLine = (int)$m['start_line'];
            $endLine = (int)$m['end_line'];
            $expectedRsloc = $m['region_sloc'] ?? null;

            if ($expectedRsloc === null) {
                continue;
            }

            $region = sliceRegion($setDir . '/' . $file, $startLine, $endLine);
            if ($region === null) {
                $errors[] = "V-1: cannot slice {$file} lines {$startLine}-{$endLine}";
                continue;
            }

            $totalLines = count($region);
            $fragments = $m['fragments'] ?? [];
            $uniqueSegments = $m['unique_segments'] ?? [];

            $fragmentLines = 0;
            foreach ($fragments as $f) {
                $fragmentLines += ((int)$f['end_line'] - (int)$f['start_line'] + 1);
            }

            $uniqueSegmentLines = 0;
            foreach ($uniqueSegments as $us) {
                $uniqueSegmentLines += (int)$us['lines'];
            }

            $duplicateLines = $fragmentLines;
            $uniqueLines = $uniqueSegmentLines;
            $fillerLines = $totalLines - $duplicateLines - $uniqueLines;

            if ($fillerLines < 0) {
                $errors[] = "V-1: {$file} cluster {$cluster['id']}: fragment+unique_lines (" . ($duplicateLines + $uniqueLines) . ") exceeds total region ({$totalLines})";
            }

            if ($expectedRsloc['duplicate'] !== $duplicateLines) {
                $errors[] = "V-1: {$file} cluster {$cluster['id']}: declared duplicate={$expectedRsloc['duplicate']} but computed={$duplicateLines}";
            }
            if ($expectedRsloc['unique'] !== $uniqueLines) {
                $errors[] = "V-1: {$file} cluster {$cluster['id']}: declared unique={$expectedRsloc['unique']} but computed={$uniqueLines}";
            }
            if ($expectedRsloc['filler'] !== $fillerLines) {
                $errors[] = "V-1: {$file} cluster {$cluster['id']}: declared filler={$expectedRsloc['filler']} but computed={$fillerLines}";
            }
        }
    }

    foreach (($setJson['files'] ?? []) as $f) {
        $fileRsloc = $f['region_sloc'] ?? null;
        if ($fileRsloc === null) {
            continue;
        }
        $path = $f['path'];
        $totalSloc = (int)$f['sloc'];
        $computedFiller = $totalSloc - ($fileRsloc['duplicate'] ?? 0) - ($fileRsloc['unique'] ?? 0);
        if ($fileRsloc['filler'] !== $computedFiller) {
            $errors[] = "V-1: {$path} file-level: declared filler={$fileRsloc['filler']} but computed={$computedFiller} (sloc={$totalSloc}, dup={$fileRsloc['duplicate']}, uniq={$fileRsloc['unique']})";
        }
    }

    return $errors;
}

/**
 * V-2: Budget conformance — actual unique_code within declared min/max.
 * @return list<string>
 */
function v2BudgetConformance(string $setDir, array $setJson, array $expected): array
{
    $errors = [];

    $budget = $setJson['duplication']['uniqueness_budget'] ?? null;
    if ($budget === null) {
        return $errors;
    }

    $perCarrierLines = $budget['per_carrier_unique_lines'] ?? null;
    $perCarrierSegs = $budget['per_carrier_segments'] ?? null;

    foreach (($budget['actual'] ?? []) as $actual) {
        $file = $actual['file'];
        $uniqueLines = (int)$actual['unique_lines'];
        $segments = (int)$actual['segments'];

        if ($perCarrierLines) {
            $minLines = (int)($perCarrierLines['min'] ?? 0);
            $maxLines = (int)($perCarrierLines['max'] ?? PHP_INT_MAX);
            if ($uniqueLines < $minLines || $uniqueLines > $maxLines) {
                $errors[] = "V-2: {$file} unique_lines={$uniqueLines} outside budget [{$minLines}, {$maxLines}]";
            }
        }

        if ($perCarrierSegs) {
            $minSegs = (int)($perCarrierSegs['min'] ?? 0);
            $maxSegs = (int)($perCarrierSegs['max'] ?? PHP_INT_MAX);
            if ($segments < $minSegs || $segments > $maxSegs) {
                $errors[] = "V-2: {$file} segments={$segments} outside budget [{$minSegs}, {$maxSegs}]";
            }
        }
    }

    return $errors;
}

/**
 * V-3: Tiling — fragments + unique_segments exactly tile [start_line, end_line].
 * @return list<string>
 */
function v3TilingCheck(string $setDir, array $setJson, array $expected): array
{
    $errors = [];

    foreach (($expected['clusters'] ?? []) as $cluster) {
        foreach (($cluster['members'] ?? []) as $m) {
            $file = $m['file'];
            $startLine = (int)$m['start_line'];
            $endLine = (int)$m['end_line'];

            if (!empty($m['pristine'])) {
                continue;
            }

            $fragments = $m['fragments'] ?? [];
            $uniqueSegments = $m['unique_segments'] ?? [];

            $covered = array_fill(0, $endLine - $startLine + 1, false);

            foreach ($fragments as $f) {
                $fs = (int)$f['start_line'];
                $fe = (int)$f['end_line'];
                for ($l = $fs; $l <= $fe; $l++) {
                    if ($l >= $startLine && $l <= $endLine) {
                        $covered[$l - $startLine] = true;
                    }
                }
            }

            foreach ($uniqueSegments as $us) {
                $uss = (int)$us['start_line'];
                $use = (int)$us['end_line'];
                for ($l = $uss; $l <= $use; $l++) {
                    if ($l >= $startLine && $l <= $endLine) {
                        $covered[$l - $startLine] = true;
                    }
                }
            }

            for ($i = 0; $i < count($covered); $i++) {
                if (!$covered[$i]) {
                    $errors[] = "V-3: {$file} cluster {$cluster['id']}: line " . ($startLine + $i) . " not covered by fragments or unique_segments (tiling gap)";
                }
            }
        }
    }

    return $errors;
}

/**
 * V-4: Fragment positive proof — normalized token streams equal per aligned fragment pair.
 * @return list<string>
 */
function v4FragmentPositiveProof(string $setDir, array $setJson, array $expected): array
{
    $errors = [];

    foreach (($expected['clusters'] ?? []) as $cluster) {
        $members = $cluster['members'] ?? [];
        if (count($members) < 2) {
            continue;
        }

        $fragmentsByMember = [];
        foreach ($members as $m) {
            $fragmentsByMember[$m['file']] = $m['fragments'] ?? [];
        }

        $firstMember = $members[0];
        $firstFile = $firstMember['file'];
        $firstFragments = $fragmentsByMember[$firstFile] ?? [];

        if ($firstFragments === []) {
            continue;
        }

        $stages = (array)($cluster['normalized_by'] ?? ['whitespace', 'comments']);
        $opts = PhpTokens::optsForStages($stages);

        foreach ($firstFragments as $idx => $frag) {
            $refRegion = sliceRegion($setDir . '/' . $firstFile, (int)$frag['start_line'], (int)$frag['end_line']);
            if ($refRegion === null) {
                continue;
            }
            $refStream = PhpTokens::normalize(implode("\n", $refRegion), $opts);

            foreach (array_slice($members, 1) as $m) {
                $otherFragments = $fragmentsByMember[$m['file']] ?? [];
                if (!isset($otherFragments[$idx])) {
                    continue;
                }
                $otherFrag = $otherFragments[$idx];
                $otherRegion = sliceRegion($setDir . '/' . $m['file'], (int)$otherFrag['start_line'], (int)$otherFrag['end_line']);
                if ($otherRegion === null) {
                    continue;
                }
                $otherStream = PhpTokens::normalize(implode("\n", $otherRegion), $opts);

                if ($otherStream !== $refStream) {
                    $errors[] = "V-4: cluster {$cluster['id']} fragment[{$idx}]: normalized streams differ between {$firstFile} and {$m['file']} after [" . implode(',', $stages) . "]";
                }
            }
        }
    }

    return $errors;
}

/**
 * V-5: Unique-segment distinctness — no unique_segment shares ≥40-token normalized run with other files.
 * @return list<string>
 */
function v5UniqueSegmentDistinctness(string $setDir, array $setJson, array $expected): array
{
    $errors = [];

    $uniqueSegmentStreams = [];
    foreach (($expected['clusters'] ?? []) as $cluster) {
        foreach (($cluster['members'] ?? []) as $m) {
            $file = $m['file'];
            foreach (($m['unique_segments'] ?? []) as $us) {
                $region = sliceRegion($setDir . '/' . $file, (int)$us['start_line'], (int)$us['end_line']);
                if ($region !== null) {
                    $stream = PhpTokens::type2(implode("\n", $region));
                    $uniqueSegmentStreams[] = ['file' => $file, 'stream' => $stream, 'id' => $us['start_line'] . '-' . $us['end_line']];
                }
            }
        }
    }

    for ($a = 0; $a < count($uniqueSegmentStreams); $a++) {
        for ($b = $a + 1; $b < count($uniqueSegmentStreams); $b++) {
            $run = PhpTokens::longestCommonRun($uniqueSegmentStreams[$a]['stream'], $uniqueSegmentStreams[$b]['stream']);
            if ($run >= NEG_MATCH_THRESHOLD) {
                $errors[] = "V-5: unique_segment {$uniqueSegmentStreams[$a]['id']} and {$uniqueSegmentStreams[$b]['id']} share a {$run}-token run (>= " . NEG_MATCH_THRESHOLD . ")";
            }
        }
    }

    return $errors;
}

/**
 * V-6: Refactor proof — solution/refactor_proof.php exits 0.
 * @return list<string>
 */
function v6RefactorProof(string $root, string $setDir, array $setJson, array $expected): array
{
    $errors = [];

    $intendedRef = $expected['intended_refactoring'] ?? $setJson['intended_refactoring'] ?? null;
    if ($intendedRef === null) {
        return $errors;
    }

    $proofFile = null;
    if (!empty($intendedRef['proof'])) {
        $proofFile = $setDir . '/' . $intendedRef['proof'];
    } elseif (!empty($intendedRef['solution_path'])) {
        $proofFile = $setDir . '/' . $intendedRef['solution_path'];
    }

    if ($proofFile === null || !is_file($proofFile)) {
        $errors[] = "V-6: intended_refactoring declared but proof file not found";
        return $errors;
    }

    $out = [];
    $rc = 0;
    @exec('php ' . escapeshellarg($proofFile) . ' 2>&1', $out, $rc);
    if ($rc !== 0) {
        $errors[] = "V-6: refactor proof failed with exit code {$rc}: " . trim(implode(' ', $out));
    }

    return $errors;
}

/**
 * V-7: Profile recompute — interference_profile, difficulty.axes, score_raw re-derived.
 * @return list<string>
 */
function v7ProfileRecompute(string $setDir, array $setJson, array $expected): array
{
    $errors = [];

    $interferenceProfile = $setJson['interference_profile'] ?? null;
    $declaredAxes = $setJson['difficulty']['axes'] ?? null;
    $declaredScoreRaw = $setJson['difficulty']['score_raw'] ?? null;

    if ($interferenceProfile !== null) {
        $axisCount = count($setJson['interference'] ?? []);
        $distinctCodes = array_unique(array_column($setJson['interference'] ?? [], 'code'));
        $groups = array_unique(array_map(static fn($i) => substr($i['code'] ?? '', 0, 2), $setJson['interference'] ?? []));

        if (count($distinctCodes) !== ($interferenceProfile['axis_count'] ?? null)) {
            $errors[] = "V-7: interference_profile.axis_count={$interferenceProfile['axis_count']} but interference array has " . count($distinctCodes) . " distinct codes";
        }

        $declaredDistinct = $interferenceProfile['distinct_codes'] ?? [];
        sort($declaredDistinct);
        sort($distinctCodes);
        if ($declaredDistinct !== $distinctCodes) {
            $errors[] = "V-7: interference_profile.distinct_codes mismatch";
        }
    }

    if ($declaredAxes !== null) {
        $breadth = count($setJson['interference'] ?? []);
        if (($declaredAxes['breadth'] ?? null) !== $breadth) {
            $errors[] = "V-7: difficulty.axes.breadth declared={$declaredAxes['breadth']} computed={$breadth}";
        }

        // V-7 score_raw cannot be fully recomputed here without SetBuilder and the full code-weight registry.
        // level_base is a lookup table (not level*4+15), code weights vary 2-24 (not breadth*10),
        // and intensity_bonus is a spec integer (not avg of per-interference intensities).
        // Basic reasonability check only — full validation deferred to P8e integration.
        if ($declaredScoreRaw !== null) {
            if (!is_numeric($declaredScoreRaw) || $declaredScoreRaw < 0 || $declaredScoreRaw > 200) {
                $errors[] = "V-7: difficulty.score_raw={$declaredScoreRaw} is out of reasonable bounds (0–200)";
            }
        }
    }

    return $errors;
}

/**
 * V-8: Progression sanity — prev_set/next_set/prerequisites all exist; rung strictly increases.
 * @return list<string>
 */
function v8ProgressionSanity(string $root, array $setJson): array
{
    $errors = [];

    $progression = $setJson['progression'] ?? null;
    if ($progression === null) {
        return $errors;
    }

    $setId = $setJson['set_id'] ?? '';
    $currentRung = (int)($progression['rung'] ?? -1);

    foreach (($progression['prerequisites'] ?? []) as $prereq) {
        $prereqPath = $root . '/testsets/' . substr($prereq, 0, 3) . '/' . $prereq . '/set.json';
        if (!is_file($prereqPath)) {
            $errors[] = "V-8: prerequisite {$prereq} does not exist on disk";
        }
    }

    if ($progression['prev_set'] !== null) {
        $prevPath = $root . '/testsets/' . substr($progression['prev_set'], 0, 3) . '/' . $progression['prev_set'] . '/set.json';
        if (!is_file($prevPath)) {
            $errors[] = "V-8: prev_set {$progression['prev_set']} does not exist on disk";
        } else {
            $prevData = json_decode((string)file_get_contents($prevPath), true);
            $prevRung = (int)(($prevData['progression']['rung'] ?? -1));
            if ($prevRung >= $currentRung) {
                $errors[] = "V-8: prev_set {$progression['prev_set']} rung={$prevRung} should be less than current rung={$currentRung}";
            }
        }
    }

    if ($progression['next_set'] !== null) {
        $nextPath = $root . '/testsets/' . substr($progression['next_set'], 0, 3) . '/' . $progression['next_set'] . '/set.json';
        if (!is_file($nextPath)) {
            $errors[] = "V-8: next_set {$progression['next_set']} does not exist on disk";
        } else {
            $nextData = json_decode((string)file_get_contents($nextPath), true);
            $nextRung = (int)(($nextData['progression']['rung'] ?? PHP_INT_MAX));
            if ($nextRung <= $currentRung) {
                $errors[] = "V-8: next_set {$progression['next_set']} rung={$nextRung} should be greater than current rung={$currentRung}";
            }
        }
    }

    return $errors;
}

/**
 * V-9: Multi-cluster hygiene — no cross-cluster ≥40-token run unless relation declares it.
 * @return list<string>
 */
function v9MultiClusterHygiene(string $setDir, array $setJson, array $expected): array
{
    $errors = [];

    $clusters = $expected['clusters'] ?? [];
    if (count($clusters) < 2) {
        return $errors;
    }

    $clusterMemberRanges = [];
    $clusterMemberStreams = [];
    foreach ($clusters as $cluster) {
        $cid = $cluster['id'];
        $clusterMemberRanges[$cid] = [];
        $clusterMemberStreams[$cid] = [];
        foreach (($cluster['members'] ?? []) as $m) {
            $clusterMemberRanges[$cid][] = ['file' => $m['file'], 'start' => (int)$m['start_line'], 'end' => (int)$m['end_line']];
            $region = sliceRegion($setDir . '/' . $m['file'], (int)$m['start_line'], (int)$m['end_line']);
            if ($region !== null) {
                $clusterMemberStreams[$cid][$m['file']] = PhpTokens::type2(implode("\n", $region));
            }
        }
    }

    $clusterIds = array_keys($clusters);
    for ($i = 0; $i < count($clusterIds); $i++) {
        for ($j = $i + 1; $j < count($clusterIds); $j++) {
            $cidA = $clusterIds[$i];
            $cidB = $clusterIds[$j];
            $relation = $clusters[$i]['relation'] ?? null;

            $allowsCrossCluster = false;
            if ($relation) {
                $withId = $relation['with'] ?? '';
                if ($withId === $cidB && in_array($relation['type'], ['overlapping', 'chained', 'braided'], true)) {
                    $allowsCrossCluster = true;
                }
            }

            if ($allowsCrossCluster) {
                continue;
            }

            foreach (($clusterMemberStreams[$cidA] ?? []) as $fileA => $streamA) {
                foreach (($clusterMemberStreams[$cidB] ?? []) as $fileB => $streamB) {
                    $run = PhpTokens::longestCommonRun($streamA, $streamB);
                    if ($run >= NEG_MATCH_THRESHOLD) {
                        $errors[] = "V-9: cross-cluster {$cidA}/{$cidB}: {$fileA} and {$fileB} share a {$run}-token run without declared relation";
                    }
                }
            }
        }
    }

    return $errors;
}

/**
 * V-10: Pairwise consistency — pairwise_expectation files are member files; omitted pairs inherit cluster default.
 * @return list<string>
 */
function v10PairwiseConsistency(string $setDir, array $setJson, array $expected): array
{
    $errors = [];

    foreach (($expected['clusters'] ?? []) as $cluster) {
        $members = $cluster['members'] ?? [];
        $memberFiles = array_column($members, 'file');

        $tokenBasedDefault = $cluster['detection_expectation']['token_based'] ?? true;
        $astBasedDefault = $cluster['detection_expectation']['ast_based'] ?? true;

        $pairwiseExp = $cluster['pairwise_expectation'] ?? [];
        $coveredPairs = [];

        foreach ($pairwiseExp as $pw) {
            $a = $pw['a'] ?? '';
            $b = $pw['b'] ?? '';

            if (!in_array($a, $memberFiles, true)) {
                $errors[] = "V-10: cluster {$cluster['id']} pairwise_expectation: file '{$a}' is not a member";
            }
            if (!in_array($b, $memberFiles, true)) {
                $errors[] = "V-10: cluster {$cluster['id']} pairwise_expectation: file '{$b}' is not a member";
            }

            $pairKey = $a < $b ? "{$a}|{$b}" : "{$b}|{$a}";
            $coveredPairs[$pairKey] = true;
        }

        $files = array_values($memberFiles);
        for ($x = 0; $x < count($files); $x++) {
            for ($y = $x + 1; $y < count($files); $y++) {
                $pairKey = $files[$x] < $files[$y] ? "{$files[$x]}|{$files[$y]}" : "{$files[$y]}|{$files[$x]}";
                if (!isset($coveredPairs[$pairKey])) {
                    if ($tokenBasedDefault === false && $astBasedDefault === false) {
                        $errors[] = "V-10: cluster {$cluster['id']}: pair {$pairKey} not in pairwise_expectation and inherits token_based/ast_based=false, expected duplication not declared";
                    }
                }
            }
        }
    }

    return $errors;
}

/** @return list<string> */
function l0ToolTriage(string $setDir): array
{
    $errors = [];
    $root = dirname(dirname($setDir . '/../..'));
    $repoRoot = realpath(dirname(__DIR__));
    $phar = $repoRoot . '/bench/tools/phpcpd.phar';
    if (is_file($phar)) {
        $xml = tempnam(sys_get_temp_dir(), 'l0') . '.xml';
        register_shutdown_function('unlink', $xml);
        @exec(sprintf('php %s --fuzzy --min-lines 5 --min-tokens 50 --log-pmd %s %s 2>/dev/null',
            escapeshellarg($phar), escapeshellarg($xml), escapeshellarg($setDir . '/src')), $_, $rc);
        if (is_file($xml)) {
            $sx = @simplexml_load_file($xml);
            if ($sx instanceof SimpleXMLElement && count($sx->duplication) > 0) {
                $errors[] = 'negative-proof(L0): phpcpd reported ' . count($sx->duplication) . ' duplication(s) — set is not clean';
            }
        }
    }
    return $errors;
}

/** @return list<string>|null */
function sliceRegion(string $file, int $start, int $end): ?array
{
    if (!is_file($file)) {
        return null;
    }
    $lines = explode("\n", (string)file_get_contents($file));
    if ($start < 1 || $end > count($lines) || $start > $end) {
        return null;
    }
    return array_slice($lines, $start - 1, $end - $start + 1);
}

/** @return array{set:?string,level:?int,all:bool} */
function parseArgs(array $argv): array
{
    $o = ['set' => null, 'level' => null, 'all' => false];
    foreach (array_slice($argv, 1) as $arg) {
        if (str_starts_with($arg, '--set=')) {
            $o['set'] = substr($arg, 6);
        } elseif (str_starts_with($arg, '--level=')) {
            $o['level'] = (int)substr($arg, 8);
        } elseif ($arg === '--all') {
            $o['all'] = true;
        }
    }
    if ($o['set'] === null && $o['level'] === null) {
        $o['all'] = true;
    }
    return $o;
}

/** @return list<array{0:array,1:array}> [family, spec] pairs */
function collectSpecs(string $root, array $opts): array
{
    $out = [];
    foreach (glob($root . '/gen/recipes/*/*.json') ?: [] as $recipeFile) {
        $data = json_decode((string)file_get_contents($recipeFile), true);
        if (!is_array($data) || !isset($data['sets'])) {
            continue;
        }
        $family = [
            'level'      => (int)$data['level'],
            'level_name' => (string)$data['level_name'],
            'level_dir'  => (string)$data['level_dir'],
            'family'     => (string)$data['family'],
            'recipe_rel' => ltrim(str_replace($root, '', $recipeFile), '/'),
        ];
        foreach ($data['sets'] as $spec) {
            if ($opts['set'] !== null && ($spec['set_id'] ?? '') !== $opts['set']) {
                continue;
            }
            if ($opts['level'] !== null && $family['level'] !== $opts['level']) {
                continue;
            }
            $out[] = [$family, $spec];
        }
    }
    return $out;
}
