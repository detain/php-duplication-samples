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
    $o = ['tool' => null, 'all' => false];
    foreach (array_slice($argv, 1) as $arg) {
        if (str_starts_with($arg, '--tool=')) {
            $o['tool'] = substr($arg, 7);
        } elseif ($arg === '--all') {
            $o['all'] = true;
        }
    }
    return $o;
}
