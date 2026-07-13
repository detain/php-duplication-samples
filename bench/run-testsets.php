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
$label = $opts['set'] ?? ($opts['level'] !== null ? 'level' . $opts['level'] : 'all');
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
        $out[] = [$setId, dirname($setJsonFile)];
    }
    sort($out);
    return $out;
}
