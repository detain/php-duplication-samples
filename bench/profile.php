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

function buildToolProfile(string $name, string $bin, string $type): array
{
    $version = trim((string)@shell_exec($type === 'phar'
        ? "php " . escapeshellarg($bin) . " --version 2>/dev/null"
        : escapeshellarg($bin) . " --version 2>/dev/null"));
    $version = strtok($version, "\n");

    return [
        'name'    => $name,
        'version' => $version,
        'type'    => $type,

        // What clone types this tool can detect
        'detects' => [
            'type-1 exact duplication',
            'type-1 with whitespace variation',
            'type-1 with comment variation',
            'type-2 with identifier rename',
            'type-2 with literal change',
            'type-3 with statement edit',
        ],

        // What this tool typically misses
        'misses' => [
            'type-4 semantic/architectural',
            'cross-file scattered clones',
            'partial duplication with unique regions',
            'semantic-equivalent variants (CF/API selection)',
            'behaviorally-equivalent refactored code',
        ],

        // Detection thresholds
        'thresholds' => [
            'min_lines'  => 5,
            'min_tokens' => 50,
        ],

        // False-positive patterns
        'fp_profile' => [
            'shared_boilerplate' => 'high',
            'getter_setter_pairs' => 'medium',
            'template_like_code' => 'medium',
            'class_header_blocks' => 'medium',
        ],

        // Accuracy characteristics
        'region_accuracy' => 'line-based (approximate)',
        'token_accuracy' => 'token-based (precise for type-1/2)',

        // Scaling behavior
        'scaling' => 'O(n^2) token comparison; degrades on >10k token files',
    ];
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
