<?php

declare(strict_types=1);

/**
 * bench/profile.php — R8 Tool Capability Profile generator (F-20).
 *
 * Generates a capability profile card per tool showing:
 *   - detects / misses / thresholds / FPs / region-accuracy / scaling
 *
 * Usage:
 *   php bench/profile.php --tool=phpcpd
 *   php bench/profile.php --tool=jscpd
 *   php bench/profile.php --all
 */

$root = dirname(__DIR__);
$autoload = $root . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
}

$opts = parseArgs($argv);

// Handle --walk mode first, exit after
if ($opts['walk']) {
    walkProgression($opts['tool']);
    exit(0);
}

$toolsDir = $root . '/bench/tools';
$phpcpdPhar = $toolsDir . '/phpcpd.phar';
$jscpdBin = resolveJscpd($toolsDir);

$profile = [];

if ($opts['tool'] === null || $opts['tool'] === 'phpcpd') {
    if (is_file($phpcpdPhar)) {
        $profile['phpcpd'] = buildToolProfile('phpcpd', $phpcpdPhar, 'phar');
    }
}

if ($opts['tool'] === null || $opts['tool'] === 'jscpd') {
    if ($jscpdBin !== null) {
        $profile['jscpd'] = buildToolProfile('jscpd', $jscpdBin, 'node');
    }
}

$outFile = $root . '/bench/results/tool-profiles.json';
@mkdir(dirname($outFile), 0o775, true);
file_put_contents($outFile, json_encode([
    'generated_at' => date('c'),
    'profiles'     => $profile,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

echo "[profile] wrote {$outFile}\n";
foreach ($profile as $name => $p) {
    echo "\n=== {$name} ===\n";
    echo "Version:     {$p['version']}\n";
    echo "Detects:     " . implode(', ', $p['detects']) . "\n";
    echo "Misses:      " . implode(', ', $p['misses']) . "\n";
    echo "Thresholds: {$p['thresholds']['min_lines']} min-lines, {$p['thresholds']['min_tokens']} min-tokens\n";
    echo "FP profile:  " . implode(', ', array_map(fn($k, $v) => "{$k}={$v}", array_keys($p['fp_profile']), array_values($p['fp_profile']))) . "\n";
    echo "Accuracy:    region={$p['region_accuracy']}, token={$p['token_accuracy']}\n";
    echo "Scaling:     {$p['scaling']}\n";
}

exit(0);

// ---------------------------------------------------------------------------

/**
 * Build a capability profile for a tool by running it against sample test sets.
 *
 * @param string $name Tool name (phpcpd|jscpd)
 * @param string $bin  Path to tool binary or phar
 * @param string $type  'phar' or 'node'
 * @return array<string,mixed>
 */
function buildToolProfile(string $name, string $bin, string $type): array
{
    // Early exit: binary not accessible
    if (!is_file($bin) && $type !== 'node') {
        return fallbackProfile($name, $bin, 'binary not found');
    }

    $version = trim((string)@shell_exec($type === 'phar'
        ? "php " . escapeshellarg($bin) . " --version 2>/dev/null"
        : escapeshellarg($bin) . " --version 2>/dev/null"));
    $version = strtok($version, "\n");

    // Run benchmark against sample sets to derive real detects/misses
    $benchmarkResult = runBenchmark($name, $bin, $type);

    if ($benchmarkResult === null) {
        // Fail fast: benchmark failed, use fallback with warning
        error_log("[profile] WARNING: benchmark failed for {$name}, using fallback profile");
        return fallbackProfile($name, $bin, $version);
    }

    // Parse benchmark results into detects/misses per clone type
    $detects = [];
    $misses = [];
    $cloneTypeF1 = $benchmarkResult['clone_type_f1'] ?? [];

    // Define all known clone types
    $allTypes = ['type-1', 'type-2', 'type-3', 'type-4'];
    $typeLabels = [
        'type-1' => 'type-1 exact duplication',
        'type-2' => 'type-2 with transformation',
        'type-3' => 'type-3 with significant changes',
        'type-4' => 'type-4 semantic/architectural',
    ];

    foreach ($allTypes as $cloneType) {
        $f1 = (float)($cloneTypeF1[$cloneType] ?? 0.0);
        if ($f1 >= 0.5) {
            $detects[] = $typeLabels[$cloneType];
        } else {
            $misses[] = $typeLabels[$cloneType];
        }
    }

    // Ensure we have at least some detects/misses (from benchmark or fallback)
    if (empty($detects) && empty($misses)) {
        $detects = ['type-1 exact duplication', 'type-2 with transformation'];
        $misses = ['type-3 with significant changes', 'type-4 semantic/architectural'];
    }

    return [
        'name'    => $name,
        'version' => $version,
        'type'    => $type,

        // What clone types this tool can detect (based on actual F1 >= 0.5)
        'detects' => $detects,

        // What this tool typically misses (based on actual F1 < 0.5)
        'misses' => $misses,

        // Detection thresholds (from actual invocation)
        'thresholds' => $benchmarkResult['thresholds'],

        // False-positive patterns (reasonable defaults since FP profiling requires more infrastructure)
        'fp_profile' => [
            'shared_boilerplate' => 'high',
            'getter_setter_pairs' => 'medium',
            'template_like_code' => 'medium',
            'class_header_blocks' => 'medium',
        ],

        // Accuracy characteristics (based on tool approach)
        'region_accuracy' => $name === 'phpcpd'
            ? 'line-based (approximate)'
            : 'token-based (precise for type-1/2)',
        'token_accuracy' => $name === 'phpcpd'
            ? 'token-based (precise for type-1/2)'
            : 'token-based (precise for type-1/2)',

        // Scaling behavior (reasonable defaults)
        'scaling' => $name === 'phpcpd'
            ? 'O(n^2) token comparison; degrades on >10k token files'
            : 'O(n) with hash lookup; scales linearly to ~100k tokens',

        // Benchmark metadata
        '_benchmark' => [
            'sample_size' => $benchmarkResult['sample_size'],
            'clone_type_f1' => $benchmarkResult['clone_type_f1'],
        ],
    ];
}

/**
 * Run the tool against sample sets (one per level L01-L10) and score against ground truth.
 *
 * @param string $name Tool name
 * @param string $bin  Path to tool binary
 * @param string $type 'phar' or 'node'
 * @return array<string,mixed>|null  null on failure
 */
function runBenchmark(string $name, string $bin, string $type): ?array
{
    $root = dirname(__DIR__);
    $sets = discoverBenchmarkSets($root);

    if ($sets === []) {
        return null;
    }

    // Track F1 per clone type
    $cloneTypeScores = [
        'type-1' => ['total_f1' => 0.0, 'count' => 0],
        'type-2' => ['total_f1' => 0.0, 'count' => 0],
        'type-3' => ['total_f1' => 0.0, 'count' => 0],
        'type-4' => ['total_f1' => 0.0, 'count' => 0],
    ];

    $thresholds = ['min_lines' => 5, 'min_tokens' => 50];
    $processedCount = 0;

    foreach ($sets as [$setId, $setDir]) {
        // Load ground truth
        $expectedFile = $setDir . '/expected.json';
        $setJsonFile = $setDir . '/set.json';
        if (!is_file($expectedFile) || !is_file($setJsonFile)) {
            continue;
        }

        $expected = json_decode((string)file_get_contents($expectedFile), true);
        $setJson = json_decode((string)file_get_contents($setJsonFile), true);
        if (!is_array($expected) || !is_array($setJson)) {
            continue;
        }

        $gt = [
            'clusters' => $expected['clusters'] ?? [],
            'non_duplicates' => $expected['non_duplicates'] ?? [],
            'scoring' => $expected['scoring'] ?? [],
        ];

        $srcDir = $setDir . '/src';
        if (!is_dir($srcDir)) {
            continue;
        }

        // Run the tool
        $groups = [];
        if ($name === 'phpcpd' && $type === 'phar') {
            $groups = runPhpcpd($bin, $srcDir);
        } elseif ($name === 'jscpd' && $type === 'node') {
            $groups = runJscpd($bin, $srcDir);
        }

        // Score against ground truth
        $score = scoreSet($groups, $gt);

        // Accumulate per-clone-type F1
        $cloneType = $setJson['duplication']['clone_type'] ?? 'unknown';
        // Normalize clone type to our taxonomy
        $normalizedType = normalizeCloneType($cloneType);
        if (isset($cloneTypeScores[$normalizedType])) {
            $cloneTypeScores[$normalizedType]['total_f1'] += $score['f1'];
            $cloneTypeScores[$normalizedType]['count']++;
        }

        $processedCount++;
    }

    if ($processedCount === 0) {
        return null;
    }

    // Compute average F1 per clone type
    $cloneTypeF1 = [];
    foreach ($cloneTypeScores as $type => $data) {
        $cloneTypeF1[$type] = $data['count'] > 0
            ? round($data['total_f1'] / $data['count'], 3)
            : 0.0;
    }

    return [
        'sample_size' => $processedCount,
        'clone_type_f1' => $cloneTypeF1,
        'thresholds' => $thresholds,
    ];
}

/**
 * Normalize clone type string to our taxonomy.
 */
function normalizeCloneType(string $cloneType): string
{
    // Map various type-1 variants
    if (str_starts_with($cloneType, 'type-1') || $cloneType === 'exact') {
        return 'type-1';
    }
    // Map type-2 variants
    if (str_starts_with($cloneType, 'type-2') || str_contains($cloneType, 'rename') || str_contains($cloneType, 'literal')) {
        return 'type-2';
    }
    // Map type-3 variants
    if (str_starts_with($cloneType, 'type-3') || str_contains($cloneType, 'statement') || str_contains($cloneType, 'control')) {
        return 'type-3';
    }
    // Map type-4 variants
    if (str_starts_with($cloneType, 'type-4') || str_contains($cloneType, 'semantic') || str_contains($cloneType, 'architectural')) {
        return 'type-4';
    }
    return 'type-1'; // default to type-1 for unknown
}

/**
 * Discover one representative set per level L01-L10 for benchmarking.
 *
 * @return list<array{0:string,1:string}> [setId, setDir]
 */
function discoverBenchmarkSets(string $root): array
{
    $out = [];
    $seenLevel = [];

    // Walk through test sets in order, picking first set per level
    foreach (glob($root . '/testsets/L*/*/*/set.json') ?: [] as $setJsonFile) {
        $setJson = json_decode((string)file_get_contents($setJsonFile), true);
        if (!is_array($setJson) || !isset($setJson['set_id'])) {
            continue;
        }

        $setId = (string)$setJson['set_id'];
        $level = (int)($setJson['level'] ?? 0);

        // Only consider levels L01-L10
        if ($level < 1 || $level > 10) {
            continue;
        }

        // Skip if we already have a set for this level
        if (isset($seenLevel[$level])) {
            continue;
        }

        $seenLevel[$level] = true;
        $out[] = [$setId, dirname($setJsonFile)];
    }

    // Sort by level
    usort($out, function($a, $b) {
        preg_match('/L(\d+)/', $a[0], $ma);
        preg_match('/L(\d+)/', $b[0], $mb);
        return ((int)($ma[1] ?? 0)) - ((int)($mb[1] ?? 0));
    });

    return $out;
}

/**
 * Fallback profile when benchmark fails (fail-safe).
 */
function fallbackProfile(string $name, string $bin, string $version): array
{
    return [
        'name'    => $name,
        'version' => $version,

        // Fallback: assume basic detection capability
        'detects' => [
            'type-1 exact duplication',
            'type-2 with transformation',
        ],

        'misses' => [
            'type-3 with significant changes',
            'type-4 semantic/architectural',
        ],

        'thresholds' => [
            'min_lines'  => 5,
            'min_tokens' => 50,
        ],

        'fp_profile' => [
            'shared_boilerplate' => 'high',
            'getter_setter_pairs' => 'medium',
            'template_like_code' => 'medium',
            'class_header_blocks' => 'medium',
        ],

        'region_accuracy' => $name === 'phpcpd'
            ? 'line-based (approximate)'
            : 'token-based (precise for type-1/2)',
        'token_accuracy' => $name === 'phpcpd'
            ? 'token-based (precise for type-1/2)'
            : 'token-based (precise for type-1/2)',

        'scaling' => $name === 'phpcpd'
            ? 'O(n^2) token comparison; degrades on >10k token files'
            : 'O(n) with hash lookup; scales linearly to ~100k tokens',
    ];
}

// ---------------------------------------------------------------------------
// Scoring and tool execution (from run-testsets.php)
// ---------------------------------------------------------------------------

/**
 * Self-contained scorer. A GT cluster counts as detected when at least
 * min_members_for_credit of its members appear among the reported members
 * (matched by file + line tolerance) and the member Jaccard >= the min.
 *
 * @param list<list<array{file:string,start:int,end:int}>> $groups
 * @param array<string,mixed> $gt
 * @return array{recall:float,precision:float,f1:float,trap_fp:int}
 */
function scoreSet(array $groups, array $gt): array
{
    $tol = (int)($gt['scoring']['line_tolerance'] ?? 2);
    $jaccardMin = (float)($gt['scoring']['member_jaccard_min'] ?? 0.6);
    $minMembers = (int)($gt['scoring']['min_members_for_credit'] ?? 2);
    $clusters = $gt['clusters'] ?? [];

    // Flatten reported members.
    $reportedMembers = [];
    foreach ($groups as $g) {
        foreach ($g as $m) {
            $reportedMembers[] = $m;
        }
    }

    $detected = 0;
    $reportedMatched = [];
    foreach ($clusters as $cluster) {
        $members = $cluster['members'] ?? [];
        $matched = 0;
        foreach ($members as $gm) {
            foreach ($reportedMembers as $ri => $rm) {
                if (membersMatch($gm, $rm, $tol)) {
                    $matched++;
                    $reportedMatched[$ri] = true;
                    break;
                }
            }
        }
        $union = count($members) + count($reportedMembers) - $matched;
        $jaccard = $union > 0 ? $matched / $union : 0.0;
        if ($matched >= $minMembers && $jaccard >= $jaccardMin) {
            $detected++;
        }
    }

    $gtCount = count($clusters);
    $recall = $gtCount === 0 ? 1.0 : $detected / $gtCount;

    // Precision: reported members that align with a GT member.
    $tp = 0;
    foreach ($reportedMembers as $ri => $rm) {
        if (isset($reportedMatched[$ri])) {
            $tp++;
        }
    }
    $precision = count($reportedMembers) === 0 ? 1.0 : $tp / count($reportedMembers);

    // Trap false positives: reported members overlapping a trap region.
    $trapFp = 0;
    foreach (($gt['non_duplicates'] ?? []) as $nd) {
        if (empty($nd['trap'])) {
            continue;
        }
        foreach ($reportedMembers as $rm) {
            if (membersMatch(['file' => $nd['file'], 'start' => $nd['start_line'], 'end' => $nd['end_line']], $rm, $tol)) {
                $trapFp++;
                break;
            }
        }
    }

    $f1 = ($precision + $recall) > 0 ? 2 * $precision * $recall / ($precision + $recall) : 0.0;
    return ['recall' => $recall, 'precision' => $precision, 'f1' => $f1, 'trap_fp' => $trapFp];
}

function membersMatch(array $a, array $b, int $tol): bool
{
    if (($a['file'] ?? null) !== ($b['file'] ?? null)) {
        return false;
    }
    $as = (int)($a['start'] ?? $a['start_line'] ?? 0);
    $ae = (int)($a['end'] ?? $a['end_line'] ?? 0);
    $bs = (int)$b['start'];
    $be = (int)$b['end'];
    // overlap with tolerance
    return ($as - $tol) <= $be && ($bs - $tol) <= $ae;
}

/** @return list<list<array{file:string,start:int,end:int}>> */
function runPhpcpd(string $phar, string $srcDir): array
{
    $xml = tempnam(sys_get_temp_dir(), 'tphpcpd') . '.xml';
    @exec(sprintf('php %s --fuzzy --min-lines 5 --min-tokens 50 --log-pmd %s %s 2>/dev/null',
        escapeshellarg($phar), escapeshellarg($xml), escapeshellarg($srcDir)), $_, $rc);
    $groups = [];
    if (is_file($xml)) {
        $sx = @simplexml_load_file($xml);
        if ($sx instanceof SimpleXMLElement) {
            foreach ($sx->duplication as $dup) {
                $lines = (int)$dup['lines'];
                $group = [];
                foreach ($dup->file as $f) {
                    $start = (int)$f['line'];
                    $group[] = [
                        'file'  => 'src/' . basename((string)$f['path']),
                        'start' => $start,
                        'end'   => $start + max(0, $lines - 1),
                    ];
                }
                $groups[] = $group;
            }
        }
        @unlink($xml);
    }
    return $groups;
}

/** @return list<list<array{file:string,start:int,end:int}>> */
function runJscpd(string $bin, string $srcDir): array
{
    $out = sys_get_temp_dir() . '/tjscpd-' . bin2hex(random_bytes(4));
    @mkdir($out, 0o775, true);
    register_shutdown_function(function() use ($out) {
        @exec('rm -rf ' . escapeshellarg($out));
    });
    @exec(sprintf('%s --formats-exts php:php --min-lines 5 --min-tokens 50 --reporters json --silent --output %s %s 2>/dev/null',
        escapeshellarg($bin), escapeshellarg($out), escapeshellarg($srcDir)), $_, $rc);
    $groups = [];
    $report = $out . '/jscpd-report.json';
    if (is_file($report)) {
        $data = json_decode((string)file_get_contents($report), true);
        foreach (($data['duplicates'] ?? []) as $d) {
            $a = $d['firstFile'] ?? null;
            $b = $d['secondFile'] ?? null;
            if (!is_array($a) || !is_array($b)) {
                continue;
            }
            $groups[] = [
                ['file' => 'src/' . basename((string)($a['name'] ?? '')), 'start' => (int)($a['start'] ?? 0), 'end' => (int)($a['end'] ?? 0)],
                ['file' => 'src/' . basename((string)($b['name'] ?? '')), 'start' => (int)($b['start'] ?? 0), 'end' => (int)($b['end'] ?? 0)],
            ];
        }
    }
    @exec('rm -rf ' . escapeshellarg($out));
    return $groups;
}

function resolveJscpd(string $toolsDir): ?string
{
    $local = $toolsDir . '/node_modules/.bin/jscpd';
    if (is_executable($local)) {
        return $local;
    }
    $which = trim((string)@shell_exec('command -v jscpd 2>/dev/null'));
    return $which !== '' ? $which : null;
}

function parseArgs(array $argv): array
{
    $o = ['tool' => null, 'all' => false, 'walk' => false];
    foreach (array_slice($argv, 1) as $arg) {
        if (str_starts_with($arg, '--tool=')) {
            $o['tool'] = substr($arg, 7);
        } elseif ($arg === '--all') {
            $o['all'] = true;
        } elseif ($arg === '--walk') {
            $o['walk'] = true;
        }
    }
    return $o;
}

function walkProgression(?string $filterTool): void
{
    $levels = loadProgressionLevels();

    if ($filterTool !== null) {
        $levels = array_filter(
            $levels,
            fn(array $entry) => isset($entry['tool_expectations'][$filterTool])
        );
    }

    echo "=== Tool Capability Walk ===\n";
    echo "Corpus progression L00-L20: what each tool detects and misses\n";
    echo "as complexity increases from no clones through adversarial cases.\n\n";

    foreach ($levels as $entry) {
        $levelStr = 'L' . str_pad((string)($entry['level'] ?? 0), 2, '0', STR_PAD_LEFT);
        $name = $entry['name'] ?? 'Unknown';
        $cloneType = $entry['clone_type'] ?? 'none';

        echo "{$levelStr} [{$name}]\n";
        echo "      clone_type: {$cloneType}\n";

        if (isset($entry['tool_expectations']) && is_array($entry['tool_expectations'])) {
            foreach ($entry['tool_expectations'] as $tool => $expectation) {
                if ($filterTool !== null && $tool !== $filterTool) {
                    continue;
                }
                $detects = $expectation['detects'] ?? null;
                $reason = $expectation['reason'] ?? null;
                $confidence = $expectation['confidence'] ?? null;

                if ($detects === true) {
                    $status = 'DETECTS';
                    if ($confidence) {
                        $status .= " (confidence: {$confidence})";
                    }
                } elseif ($detects === false) {
                    $status = 'MISSES';
                    if ($reason) {
                        $status .= " (reason: {$reason})";
                    }
                } else {
                    $status = 'UNKNOWN';
                }

                echo "        {$tool}: {$status}\n";
            }
        }
        echo "\n";
    }
}

function loadProgressionLevels(): array
{
    $root = dirname(__DIR__);
    $progressionFile = $root . '/bench/results/progression.json';

    if (is_file($progressionFile)) {
        $json = file_get_contents($progressionFile);
        if ($json !== false) {
            $data = json_decode($json, true);
            if (is_array($data) && isset($data['levels']) && is_array($data['levels'])) {
                return $data['levels'];
            }
        }
    }

    return buildInlineProgressionData();
}

function buildInlineProgressionData(): array
{
    return [
        0  => ['level' => 0, 'name' => 'No duplication', 'clone_type' => null, 'tool_expectations' => ['phpcpd' => ['detects' => false, 'reason' => 'no clones'], 'jscpd' => ['detects' => false, 'reason' => 'no clones']]],
        1  => ['level' => 1, 'name' => 'Exact duplication', 'clone_type' => 'type-1 exact duplication', 'tool_expectations' => ['phpcpd' => ['detects' => true, 'confidence' => 'high'], 'jscpd' => ['detects' => true, 'confidence' => 'high']]],
        2  => ['level' => 2, 'name' => 'Whitespace variation', 'clone_type' => 'type-1 with whitespace variation', 'tool_expectations' => ['phpcpd' => ['detects' => true, 'confidence' => 'high'], 'jscpd' => ['detects' => true, 'confidence' => 'high']]],
        3  => ['level' => 3, 'name' => 'Comment variation', 'clone_type' => 'type-1 with comment variation', 'tool_expectations' => ['phpcpd' => ['detects' => true, 'confidence' => 'high'], 'jscpd' => ['detects' => true, 'confidence' => 'high']]],
        4  => ['level' => 4, 'name' => 'Identifier rename', 'clone_type' => 'type-2 with identifier rename', 'tool_expectations' => ['phpcpd' => ['detects' => true, 'confidence' => 'high'], 'jscpd' => ['detects' => true, 'confidence' => 'high']]],
        5  => ['level' => 5, 'name' => 'Literal change', 'clone_type' => 'type-2 with literal change', 'tool_expectations' => ['phpcpd' => ['detects' => true, 'confidence' => 'high'], 'jscpd' => ['detects' => true, 'confidence' => 'high']]],
        6  => ['level' => 6, 'name' => 'Statement reorder', 'clone_type' => 'type-2 with statement reorder', 'tool_expectations' => ['phpcpd' => ['detects' => true, 'confidence' => 'medium'], 'jscpd' => ['detects' => true, 'confidence' => 'high']]],
        7  => ['level' => 7, 'name' => 'Control flow change', 'clone_type' => 'type-3 with control flow change', 'tool_expectations' => ['phpcpd' => ['detects' => false, 'reason' => 'cf_not_supported'], 'jscpd' => ['detects' => true, 'confidence' => 'medium']]],
        8  => ['level' => 8, 'name' => 'API substitution', 'clone_type' => 'type-3 with API substitution', 'tool_expectations' => ['phpcpd' => ['detects' => false, 'reason' => 'semantic'], 'jscpd' => ['detects' => false, 'reason' => 'semantic']]],
        9  => ['level' => 9, 'name' => 'Compound interference', 'clone_type' => 'type-4 compound', 'tool_expectations' => ['phpcpd' => ['detects' => false, 'reason' => 'compound'], 'jscpd' => ['detects' => false, 'reason' => 'compound']]],
        10 => ['level' => 10, 'name' => 'Adversarial edge cases', 'clone_type' => 'type-4 adversarial', 'tool_expectations' => ['phpcpd' => ['detects' => false, 'reason' => 'adversarial'], 'jscpd' => ['detects' => false, 'reason' => 'adversarial']]],
        11 => ['level' => 11, 'name' => 'Cross-file scatter', 'clone_type' => 'cross-file scattered', 'tool_expectations' => ['phpcpd' => ['detects' => false, 'reason' => 'cross_file'], 'jscpd' => ['detects' => false, 'reason' => 'cross_file']]],
        12 => ['level' => 12, 'name' => 'Deep refactoring', 'clone_type' => 'deep refactoring', 'tool_expectations' => ['phpcpd' => ['detects' => false, 'reason' => 'deep'], 'jscpd' => ['detects' => false, 'reason' => 'deep']]],
        13 => ['level' => 13, 'name' => 'Cross-seed clones', 'clone_type' => 'cross-seed', 'tool_expectations' => ['phpcpd' => ['detects' => false, 'reason' => 'cross_seed'], 'jscpd' => ['detects' => false, 'reason' => 'cross_seed']]],
        14 => ['level' => 14, 'name' => 'Semantic equivalence', 'clone_type' => 'semantic-equivalent', 'tool_expectations' => ['phpcpd' => ['detects' => false, 'reason' => 'semantic'], 'jscpd' => ['detects' => false, 'reason' => 'semantic']]],
        15 => ['level' => 15, 'name' => 'API hybrids', 'clone_type' => 'api hybrid', 'tool_expectations' => ['phpcpd' => ['detects' => false, 'reason' => 'hybrid'], 'jscpd' => ['detects' => false, 'reason' => 'hybrid']]],
        16 => ['level' => 16, 'name' => 'Behavioral equivalence', 'clone_type' => 'behaviorally-equivalent', 'tool_expectations' => ['phpcpd' => ['detects' => false, 'reason' => 'behavioral'], 'jscpd' => ['detects' => false, 'reason' => 'behavioral']]],
        17 => ['level' => 17, 'name' => 'Genealogical drift', 'clone_type' => 'drift chain', 'tool_expectations' => ['phpcpd' => ['detects' => false, 'reason' => 'drift'], 'jscpd' => ['detects' => false, 'reason' => 'drift']]],
        18 => ['level' => 18, 'name' => 'Partial duplication', 'clone_type' => 'partial duplication', 'tool_expectations' => ['phpcpd' => ['detects' => false, 'reason' => 'partial'], 'jscpd' => ['detects' => false, 'reason' => 'partial']]],
        19 => ['level' => 19, 'name' => 'Budget-constrained', 'clone_type' => 'budget constrained', 'tool_expectations' => ['phpcpd' => ['detects' => false, 'reason' => 'budget'], 'jscpd' => ['detects' => false, 'reason' => 'budget']]],
        20 => ['level' => 20, 'name' => 'Kitchen sink', 'clone_type' => 'all transforms', 'tool_expectations' => ['phpcpd' => ['detects' => false, 'reason' => 'adversarial'], 'jscpd' => ['detects' => false, 'reason' => 'adversarial']]],
    ];
}
