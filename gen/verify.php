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
