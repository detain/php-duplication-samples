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
    11 => 'Cross-file scatter & inheritance chains',
    12 => 'Deep refactoring & partial duplication',
    13 => 'Cross-seed & multi-domain clones',
    14 => 'Semantic equivalence variants',
    15 => 'Composed selectors & API hybrids',
    16 => 'Behavioral equivalence & proof obligations',
    17 => 'Genealogical drift chains',
    18 => 'Fragment-level partial duplication',
    19 => 'Budget-constrained edit synthesis',
    20 => 'Adversarial anti-detection patterns',
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

// ---------------------------------------------------------------------------
// F-14: Build the capability-progression matrix and write progression.json.
// ---------------------------------------------------------------------------

$profilerFile = $root . '/bench/results/tool-profiles.json';
$profiles = is_file($profilerFile)
    ? json_decode((string)file_get_contents($profilerFile), true)['profiles'] ?? []
    : [];

// Map level → clone-type description (matches bench/profile.php detects list).
$levelCloneTypes = [
    0 => null,                                                          // no clones
    1 => 'type-1 exact duplication',
    2 => 'type-1 with whitespace variation',
    3 => 'type-1 with comment variation',
    4 => 'type-2 with identifier rename',
    5 => 'type-2 with literal change',
    6 => 'type-3 with statement edit',
    7 => 'type-3 with statement edit',                                  // control-flow rewrites ≈ type-3
    8 => 'type-4 semantic/architectural',
    9 => 'type-4 semantic/architectural',                               // compound ≈ type-4
    10 => 'type-4 semantic/architectural',                              // adversarial ≈ type-4
    11 => 'cross-file scattered clones',
    12 => 'cross-file scattered clones',                                // deep refactoring ≈ scattered
    13 => 'cross-file scattered clones',                               // cross-seed ≈ scattered
    14 => 'semantic-equivalent variants (CF/API selection)',
    15 => 'semantic-equivalent variants (CF/API selection)',            // API hybrids ≈ semantic equiv
    16 => 'behaviorally-equivalent refactored code',
    17 => 'behaviorally-equivalent refactored code',                    // genealogical drift ≈ behavioral
    18 => 'partial duplication with unique regions',
    19 => 'partial duplication with unique regions',                    // budget-constrained ≈ partial
    20 => 'type-4 semantic/architectural',                              // adversarial anti-detection
];

// Skill name per level (used in the walk array).
$levelSkills = [
    0  => 'detection_absent',
    1  => 'exact_clone',
    2  => 'ws_clone',
    3  => 'cm_clone',
    4  => 'rn_clone',
    5  => 'lt_clone',
    6  => 'st_edit',
    7  => 'cf_rewrite',
    8  => 'api_substitution',
    9  => 'compound_interference',
    10 => 'adversarial_edge',
    11 => 'cross_file_scatter',
    12 => 'deep_refactor_partial',
    13 => 'cross_seed',
    14 => 'semantic_equiv',
    15 => 'api_hybrid',
    16 => 'behavioral_equiv',
    17 => 'genealogical_drift',
    18 => 'partial_fragment',
    19 => 'budget_constrained',
    20 => 'anti_detection',
];

// Build level entries with tool expectations.
$progressionLevels = [];
foreach ($levelEntries as $entry) {
    $lvl = (int)$entry['level'];
    $cloneType = $levelCloneTypes[$lvl] ?? null;

    $toolExpectations = [];
    foreach ($profiles as $tool => $profile) {
        if ($cloneType === null) {
            // Level 0 — no clones present.
            $toolExpectations[$tool] = ['detects' => false, 'reason' => 'no clones'];
        } elseif (in_array($cloneType, $profile['detects'] ?? [], true)) {
            $toolExpectations[$tool] = ['detects' => true, 'confidence' => 'high'];
        } elseif (in_array($cloneType, $profile['misses'] ?? [], true)) {
            $toolExpectations[$tool] = ['detects' => false, 'reason' => 'tool_miss'];
        } else {
            // Clone type not in either list — treat as unknown.
            $toolExpectations[$tool] = ['detects' => null, 'reason' => 'unknown'];
        }
    }

    $progressionLevels[] = [
        'level'             => $lvl,
        'name'              => $entry['title'],
        'description'       => $entry['title'],
        'clone_type'        => $cloneType,
        'tool_expectations' => $toolExpectations,
    ];
}

// Build the walk array.
$walkEntries = [];
foreach ($levelEntries as $entry) {
    $lvl = (int)$entry['level'];
    $walkEntries[] = [
        'level' => $lvl,
        'skill' => $levelSkills[$lvl] ?? "level_{$lvl}",
    ];
}

$progression = [
    'schema_version'  => 1,
    'generated_at'   => gitRefOrDate($root),
    'levels'         => $progressionLevels,
    'walk'           => $walkEntries,
    'tools'          => array_keys($profiles),
    'capability_map' => [
        'detects'   => 'what the tool can find',
        'misses'    => 'what the tool typically fails to detect',
        'thresholds' => 'min_lines / min_tokens for reporting',
        'fp_profile' => 'false-positive prone patterns',
    ],
];

$progressionFile = $root . '/bench/results/progression.json';
@mkdir(dirname($progressionFile), 0o775, true);
file_put_contents(
    $progressionFile,
    json_encode($progression, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
);

echo "[manifest] wrote bench/results/progression.json (" . count($progressionLevels) . " levels)\n";

// ---------------------------------------------------------------------------
// Validate before writing.
// ---------------------------------------------------------------------------
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
