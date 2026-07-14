<?php

declare(strict_types=1);

/**
 * bench/run-testsets.php — run duplication detectors per test set and score the
 * result against each set's expected.json (§13).
 *
 * Mirrors bench/run-samples.php mechanics: phpcpd via the phar (PMD XML) and
 * jscpd via node (JSON report). Tool output is normalized to {file,start,end}
 * member groups and scored with member-set matching (±line_tolerance, member
 * Jaccard >= member_jaccard_min, min_members_for_credit). Tool versions + flags
 * are captured into the run JSON for reproducibility (§13.5).
 *
 * Reuses Phpdup\Testing\BenchmarkScoreCalculator if vendor provides it; else a
 * self-contained scorer (below).
 *
 * Usage:
 *   php bench/run-testsets.php --set=L01-ex_function-001
 *   php bench/run-testsets.php --level=2
 *   php bench/run-testsets.php --all
 *
 * --walk mode (G-9):
 *   php bench/run-testsets.php --walk [--stop-k=3]
 *   Runs sets in progression.json walk order per arc, stop-loss after k
 *   consecutive misses, emits frontier report (last_pass, first_fail,
 *   requires_delta) for each arc.
 */

$root = dirname(__DIR__);
$autoload = $root . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
}

$toolsDir = $root . '/bench/tools';
$phpcpdPhar = $toolsDir . '/phpcpd.phar';
$jscpdBin = resolveJscpd($toolsDir);

$opts = parseArgs($argv);

// --walk mode: progression-aware walk with stop-loss and frontier report
if ($opts['walk']) {
    $stopK = (int)($opts['stop_k'] ?? 3);
    $progressionFile = $root . '/bench/results/progression.json';
    if (!is_file($progressionFile)) {
        fwrite(STDERR, "[run-testsets] progression.json not found at {$progressionFile}\n");
        exit(1);
    }
    $progression = json_decode((string)file_get_contents($progressionFile), true);
    if (!is_array($progression) || !isset($progression['walk']) || !isset($progression['arcs'])) {
        fwrite(STDERR, "[run-testsets] progression.json invalid: missing walk or arcs\n");
        exit(1);
    }

    $tools = [
        'phpcpd' => [
            'present' => is_file($phpcpdPhar),
            'version' => is_file($phpcpdPhar) ? toolVersion('php ' . escapeshellarg($phpcpdPhar) . ' --version') : null,
            'flags'   => '--fuzzy --min-lines 5 --min-tokens 50',
        ],
        'jscpd' => [
            'present' => $jscpdBin !== null,
            'version' => $jscpdBin !== null ? toolVersion(escapeshellarg($jscpdBin) . ' --version') : null,
            'flags'   => '--formats-exts php:php --min-lines 5 --min-tokens 50',
        ],
    ];

    runWalkMode($root, $progression, $tools, $stopK);
    exit(0);
}

$sets = discoverSets($root, $opts);
if ($sets === []) {
    fwrite(STDERR, "[run-testsets] no sets matched\n");
    exit(2);
}

$tools = [
    'phpcpd' => [
        'present' => is_file($phpcpdPhar),
        'version' => is_file($phpcpdPhar) ? toolVersion('php ' . escapeshellarg($phpcpdPhar) . ' --version') : null,
        'flags'   => '--fuzzy --min-lines 5 --min-tokens 50',
    ],
    'jscpd' => [
        'present' => $jscpdBin !== null,
        'version' => $jscpdBin !== null ? toolVersion(escapeshellarg($jscpdBin) . ' --version') : null,
        'flags'   => '--formats-exts php:php --min-lines 5 --min-tokens 50',
    ],
];

$rows = [];
echo "| Set | Tool | recall | precision | F1 | trap-FP |\n";
echo "|---|---|---|---|---|---|\n";

foreach ($sets as [$setId, $setDir]) {
    $expected = json_decode((string)file_get_contents($setDir . '/expected.json'), true);
    $gt = is_array($expected) ? $expected : ['clusters' => [], 'non_duplicates' => [], 'scoring' => []];

    $srcDir = $setDir . '/src';

    $reported = [];
    if ($tools['phpcpd']['present']) {
        $reported['phpcpd'] = runPhpcpd($phpcpdPhar, $srcDir);
    }
    if ($tools['jscpd']['present']) {
        $reported['jscpd'] = runJscpd($jscpdBin, $srcDir);
    }

    foreach ($reported as $tool => $groups) {
        $score = scoreSet($groups, $gt);
        $rows[] = [
            'set'      => $setId,
            'tool'     => $tool,
            'version'  => $tools[$tool]['version'],
            'flags'    => $tools[$tool]['flags'],
            'recall'   => $score['recall'],
            'precision' => $score['precision'],
            'f1'       => $score['f1'],
            'trap_fp'  => $score['trap_fp'],
            'reported_groups' => $groups,
        ];
        printf("| %s | %s | %.2f | %.2f | %.2f | %d |\n",
            $setId, $tool, $score['recall'], $score['precision'], $score['f1'], $score['trap_fp']);
    }
}

$resultsDir = $root . '/bench/results';
@mkdir($resultsDir, 0o775, true);
if ($opts['set'] !== null) {
    $label = $opts['set'];
} elseif ($opts['family'] !== null) {
    $label = 'family-' . $opts['family'];
} elseif ($opts['level'] !== null) {
    $label = 'level' . $opts['level'];
} else {
    $label = 'all';
}
$outFile = $resultsDir . '/testsets-' . $label . '.json';
file_put_contents($outFile, json_encode([
    'generated_at' => date('c'),
    'tools'        => $tools,
    'rows'         => $rows,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo "\n[run-testsets] wrote {$outFile}\n";
exit(0);

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
        $jaccard = $union > 0 ? $matched / max(count($members), 1) : 0.0;
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
    // Bug 5 fix: ensure temp dir cleanup on any fatal exit
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
    if ($which !== '') {
        return $which;
    }
    return null;
}

function toolVersion(string $cmd): ?string
{
    $out = trim((string)@shell_exec($cmd . ' 2>/dev/null'));
    if ($out === '') {
        return null;
    }
    $line = strtok($out, "\n");
    return $line !== false ? trim($line) : null;
}

/** @return array{set:?string,level:?int,family:?string,all:bool,walk:bool,stop_k:int} */
function parseArgs(array $argv): array
{
    $o = ['set' => null, 'level' => null, 'family' => null, 'all' => false, 'walk' => false, 'stop_k' => 3];
    foreach (array_slice($argv, 1) as $arg) {
        if (str_starts_with($arg, '--set=')) {
            $o['set'] = substr($arg, 6);
        } elseif (str_starts_with($arg, '--level=')) {
            $o['level'] = (int)substr($arg, 8);
        } elseif (str_starts_with($arg, '--family=')) {
            $o['family'] = substr($arg, 9);
        } elseif ($arg === '--all') {
            $o['all'] = true;
        } elseif ($arg === '--walk') {
            $o['walk'] = true;
        } elseif (str_starts_with($arg, '--stop-k=')) {
            $o['stop_k'] = (int)substr($arg, 9);
        }
    }
    if ($o['set'] === null && $o['level'] === null && $o['family'] === null && !$o['walk']) {
        $o['all'] = true;
    }
    return $o;
}

/** @return list<array{0:string,1:string}> [setId, setDir] */
function discoverSets(string $root, array $opts): array
{
    $out = [];
    foreach (glob($root . '/testsets/L*/*/*/set.json') ?: [] as $setJsonFile) {
        $setJson = json_decode((string)file_get_contents($setJsonFile), true);
        if (!is_array($setJson) || !isset($setJson['set_id'])) {
            continue;
        }
        $setId = (string)$setJson['set_id'];
        if ($opts['set'] !== null && $setId !== $opts['set']) {
            continue;
        }
        if ($opts['level'] !== null && (int)$setJson['level'] !== $opts['level']) {
            continue;
        }
        if ($opts['family'] !== null && (string)$setJson['family'] !== $opts['family']) {
            continue;
        }
        $out[] = [$setId, dirname($setJsonFile)];
    }
    sort($out);
    return $out;
}

// ---------------------------------------------------------------------------
// G-9: Walk mode implementation

/**
 * Run progression walk with stop-loss per arc and frontier report.
 * @param string $root Project root
 * @param array{walk:list<string>,arcs:array<string,list<string>>} $progression
 * @param array<string,array{present:bool,version:?string,flags:string}> $tools
 * @param int $stopK Stop after this many consecutive misses per arc
 */
function runWalkMode(string $root, array $progression, array $tools, int $stopK): void
{
    // Build setId -> setDir mapping
    $setMap = [];
    foreach (glob($root . '/testsets/L*/*/*/set.json') ?: [] as $setJsonFile) {
        $setJson = json_decode((string)file_get_contents($setJsonFile), true);
        if (is_array($setJson) && isset($setJson['set_id'])) {
            $setMap[$setJson['set_id']] = [
                'dir' => dirname($setJsonFile),
                'set' => $setJson,
            ];
        }
    }

    $walk = $progression['walk'] ?? [];
    $arcs = $progression['arcs'] ?? [];

    echo "=== Walk Mode (stop-k={$stopK}) ===\n";
    echo "Walk: " . count($walk) . " sets across " . count($arcs) . " arcs\n\n";

    // Track frontier results per arc
    $frontierResults = [];

    // Filter to sets that exist in our corpus
    $validWalk = array_filter($walk, fn($id) => isset($setMap[$id]));
    $validArcSets = [];
    foreach ($arcs as $arcName => $arcSetIds) {
        $validSets = array_filter($arcSetIds, fn($id) => isset($setMap[$id]));
        if (!empty($validSets)) {
            $validArcSets[$arcName] = array_values($validSets);
        }
    }

    // For tool selection, prefer phpcpd if available, else jscpd
    $toolName = $tools['phpcpd']['present'] ? 'phpcpd' : ($tools['jscpd']['present'] ? 'jscpd' : null);
    if ($toolName === null) {
        fwrite(STDERR, "[run-testsets] No detection tool available (phpcpd or jscpd)\n");
        exit(1);
    }
    $tool = $tools[$toolName];
    echo "Using tool: {$toolName} ({$tool['version']})\n\n";

    // Process each arc
    foreach ($validArcSets as $arcName => $arcSetIds) {
        echo "--- Arc: {$arcName} (" . count($arcSetIds) . " sets) ---\n";

        $consecutiveMisses = 0;
        $lastPass = null;
        $firstFail = null;
        $lastPassRequires = [];
        $firstFailRequires = [];
        $arcCompleted = false;

        foreach ($arcSetIds as $setId) {
            if (!isset($setMap[$setId])) {
                continue;
            }

            $setInfo = $setMap[$setId];
            $setDir = $setInfo['dir'];
            $setJson = $setInfo['set'];

            $expected = json_decode((string)file_get_contents($setDir . '/expected.json'), true);
            $gt = is_array($expected) ? $expected : ['clusters' => [], 'non_duplicates' => [], 'scoring' => []];
            $srcDir = $setDir . '/src';

            // Run the tool
            $reported = [];
            if ($toolName === 'phpcpd') {
                $reported['phpcpd'] = runPhpcpd($GLOBALS['phpcpdPhar'], $srcDir);
            } else {
                $reported['jscpd'] = runJscpd($GLOBALS['jscpdBin'], $srcDir);
            }

            $groups = $reported[$toolName] ?? [];
            $score = scoreSet($groups, $gt);

            // A "pass" means the tool detected the clone with acceptable quality
            // We use F1 >= 0.5 as pass threshold for walk mode
            $passed = $score['f1'] >= 0.5;

            $requires = $setJson['difficulty']['requires'] ?? [];

            if ($passed) {
                echo "  [PASS] {$setId} F1=" . sprintf("%.2f", $score['f1']) . " requires=[" . implode(',', $requires) . "]\n";
                $consecutiveMisses = 0;
                $lastPass = $setId;
                $lastPassRequires = $requires;
            } else {
                echo "  [FAIL] {$setId} F1=" . sprintf("%.2f", $score['f1']) . " requires=[" . implode(',', $requires) . "]\n";
                $consecutiveMisses++;
                if ($firstFail === null) {
                    $firstFail = $setId;
                    $firstFailRequires = $requires;
                }

                if ($consecutiveMisses >= $stopK) {
                    echo "  >>> Stop-loss after {$consecutiveMisses} consecutive misses\n";
                    $arcCompleted = true;
                }
            }

            // Check if we should stop this arc
            if ($arcCompleted) {
                // Record frontier
                $requiresDelta = [];
                if ($firstFail !== null && $lastPass !== null) {
                    // requires_delta = first_fail_requires \ last_pass_requires
                    // (items in first fail that are not in last pass)
                    $requiresDelta = array_values(array_diff($firstFailRequires, $lastPassRequires));
                }

                $frontierResults[$arcName] = [
                    'last_pass' => $lastPass,
                    'first_fail' => $firstFail,
                    'requires_delta' => $requiresDelta,
                    'consecutive_misses' => $consecutiveMisses,
                    'stopped_at' => $setId,
                ];
                break;
            }
        }

        // If we finished the arc without hitting stop-loss
        if (!$arcCompleted) {
            if ($lastPass !== null) {
                echo "  [Arc completed - last pass: {$lastPass}]\n";
            }
            $frontierResults[$arcName] = [
                'last_pass' => $lastPass,
                'first_fail' => $firstFail,
                'requires_delta' => $firstFail !== null && $lastPass !== null
                    ? array_values(array_diff($firstFailRequires, $lastPassRequires))
                    : [],
                'consecutive_misses' => $consecutiveMisses,
                'stopped_at' => null,
                'completed' => true,
            ];
        }

        echo "\n";
    }

    // Emit frontier report
    echo "=== FRONTIER REPORT ===\n";
    echo "last_pass | first_fail | requires_delta | arc\n";
    echo "|---|---|---|---\n";

    $frontierOutput = ['generated_at' => date('c'), 'stop_k' => $stopK, 'tool' => $toolName, 'frontier' => []];

    foreach ($frontierResults as $arcName => $result) {
        $lastPass = $result['last_pass'] ?? 'N/A';
        $firstFail = $result['first_fail'] ?? 'N/A';
        $delta = implode(',', $result['requires_delta'] ?? []);
        if ($delta === '') {
            $delta = '—';
        }
        $completed = $result['completed'] ?? false ? ' (completed)' : '';
        echo "| {$lastPass} | {$firstFail} | {$delta} | {$arcName}{$completed}\n";

        $frontierOutput['frontier'][] = [
            'arc' => $arcName,
            'last_pass' => $lastPass,
            'first_fail' => $firstFail,
            'requires_delta' => $result['requires_delta'] ?? [],
            'consecutive_misses' => $result['consecutive_misses'] ?? 0,
            'stopped_at' => $result['stopped_at'],
            'completed' => $completed,
        ];
    }

    // Save frontier report
    $resultsDir = $root . '/bench/results';
    @mkdir($resultsDir, 0o775, true);
    $outFile = $resultsDir . '/frontier-' . date('Y-m-d-H-i-s') . '.json';
    file_put_contents($outFile, json_encode($frontierOutput, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    echo "\n[run-testsets] frontier report saved to {$outFile}\n";
}
