<?php

declare(strict_types=1);

/**
 * gen/manifest.php — rebuild the generated global index and the aggregate bench
 * ground truth (§7.3, §13).
 *
 * Writes:
 *   testsets/manifest.json                          global index (one entry per set)
 *   bench/corpora/testsets.ground-truth.json        every cluster, paths prefixed
 *                                                    by set dir, ready for the scorer
 *
 * Usage: php gen/manifest.php
 */

require __DIR__ . '/bootstrap.php';

use Gen\Lib\JsonSchema;
use Gen\Transforms\Registry;

$root = dirname(__DIR__);
$registry = new Registry();

$levelTitles = [
    0 => 'No duplication (negative controls)',
    1 => 'Exact duplication (Type-1)',
    2 => 'Whitespace & layout variation (Type-1)',
    3 => 'Comment variation (Type-1)',
    4 => 'Renames, literals, types, namespaces (Type-2)',
    5 => 'Statement edits (Type-3)',
    6 => 'Control-flow & expression rewrites',
    7 => 'API & idiom substitution (Type-4)',
    8 => 'Semantic / architectural duplication',
    9 => 'Compound interference (mixed)',
    10 => 'Adversarial edge cases & topology',
];

$setJsonFiles = glob($root . '/testsets/L*/*/*/set.json') ?: [];
sort($setJsonFiles);

$levels = [];             // level => ['dir'=>, 'title'=>, 'families'=>[fam=>['codes'=>[],'sets'=>[]]]]
$aggClusters = [];        // bench ground truth clusters
$totalFiles = 0;
$totalSloc = 0;
$totalClusters = 0;
$distractorFiles = 0;
$cleanFiles = 0;
$totalDupRegions = 0;
$familyKeys = [];

foreach ($setJsonFiles as $file) {
    $set = json_decode((string)file_get_contents($file), true);
    $expected = json_decode((string)file_get_contents(dirname($file) . '/expected.json'), true);
    if (!is_array($set) || !is_array($expected)) {
        continue;
    }

    $level = (int)$set['level'];
    $family = (string)$set['family'];
    $setDirRel = ltrim(str_replace($root . '/testsets', '', dirname($file)), '/');
    $levelDir = explode('/', $setDirRel)[0];

    $levels[$level] ??= ['dir' => $levelDir, 'title' => $levelTitles[$level] ?? "Level {$level}", 'families' => []];
    $levels[$level]['families'][$family] ??= ['codes' => [], 'sets' => []];
    $familyKeys[$levelDir . '/' . $family] = true;

    foreach (($set['interference'] ?? []) as $intf) {
        $levels[$level]['families'][$family]['codes'][$intf['code']] = true;
    }

    $clusterCount = count($expected['clusters'] ?? []);
    $totalClusters += $clusterCount;

    $levels[$level]['families'][$family]['sets'][] = [
        'set_id'     => (string)$set['set_id'],
        'dir'        => $setDirRel,
        'clusters'   => $clusterCount,
        'files'      => count($set['files'] ?? []),
        'difficulty' => (int)($set['difficulty']['score'] ?? 0),
    ];

    foreach (($set['files'] ?? []) as $f) {
        $totalFiles++;
        $totalSloc += (int)($f['sloc'] ?? 0);
        if (($f['role'] ?? '') === 'distractor') {
            $distractorFiles++;
        } elseif (($f['role'] ?? '') === 'clean') {
            $cleanFiles++;
        }
    }

    foreach (($expected['clusters'] ?? []) as $cluster) {
        $totalDupRegions += count($cluster['members'] ?? []);
        $members = [];
        foreach (($cluster['members'] ?? []) as $m) {
            $members[] = [
                'file'  => $setDirRel . '/' . $m['file'],
                'start' => (int)$m['start_line'],
                'end'   => (int)$m['end_line'],
            ];
        }
        $aggClusters[] = [
            'set_id'     => (string)$set['set_id'],
            'id'         => (string)$cluster['id'],
            'clone_type' => (string)$cluster['clone_type'],
            'members'    => $members,
        ];
    }
}

// Assemble manifest.
ksort($levels);
$levelEntries = [];
foreach ($levels as $level => $data) {
    $families = [];
    ksort($data['families']);
    foreach ($data['families'] as $fam => $fdata) {
        $families[] = [
            'family'             => $fam,
            'interference_codes' => array_keys($fdata['codes']),
            'sets'               => $fdata['sets'],
        ];
    }
    $levelEntries[] = [
        'level'    => $level,
        'dir'      => $data['dir'],
        'title'    => $data['title'],
        'families' => $families,
    ];
}

$manifest = [
    'schema_version' => 1,
    'generated_at'   => gitRefOrDate($root),
    'totals'         => [
        'levels'   => count($levels),
        'families' => count($familyKeys),
        'sets'     => count($setJsonFiles),
        'files'    => $totalFiles,
        'clusters' => $totalClusters,
    ],
    'corpus_stats'   => [
        'total_php_files'         => $totalFiles,
        'total_sloc'              => $totalSloc,
        'total_duplicate_regions' => $totalDupRegions,
        'distractor_files'        => $distractorFiles,
        'clean_files'             => $cleanFiles,
    ],
    'levels'         => $levelEntries,
];

// Validate before writing.
$schema = json_decode((string)file_get_contents($root . '/testsets/schema/manifest.schema.json'), true);
$errors = JsonSchema::validate($manifest, $schema, 'manifest');
if ($errors !== []) {
    fwrite(STDERR, "[manifest] schema errors:\n");
    foreach ($errors as $e) {
        fwrite(STDERR, "  - {$e}\n");
    }
    exit(1);
}

file_put_contents(
    $root . '/testsets/manifest.json',
    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
);

@mkdir($root . '/bench/corpora', 0o775, true);
file_put_contents(
    $root . '/bench/corpora/testsets.ground-truth.json',
    json_encode([
        'corpus'       => 'testsets',
        'generated_at' => gitRefOrDate($root),
        'clusters'     => $aggClusters,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
);

echo "[manifest] wrote testsets/manifest.json ({$manifest['totals']['sets']} sets, {$manifest['totals']['clusters']} clusters)\n";
echo "[manifest] wrote bench/corpora/testsets.ground-truth.json (" . count($aggClusters) . " clusters)\n";
exit(0);

function gitRefOrDate(string $root): string
{
    $head = $root . '/.git/HEAD';
    if (is_file($head)) {
        $ref = trim((string)file_get_contents($head));
        if (str_starts_with($ref, 'ref: ')) {
            $refPath = $root . '/.git/' . substr($ref, 5);
            if (is_file($refPath)) {
                return 'git:' . substr(trim((string)file_get_contents($refPath)), 0, 12);
            }
        }
        return 'git:' . substr($ref, 0, 12);
    }
    return 'uncommitted:' . date('c');
}
