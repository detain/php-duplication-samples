#!/usr/bin/env php
<?php
/**
 * G-8: Build testsets/progression.json
 */

$root = '/home/sites/php-duplication-samples';
$manifestPath = $root . '/testsets/manifest.json';
$minimalPairsPath = $root . '/gen/tools/minimal_pairs.json';
$outputPath = $root . '/testsets/progression.json';

$manifest = json_decode(file_get_contents($manifestPath), true);
$minimalPairsRaw = json_decode(file_get_contents($minimalPairsPath), true);

// Collect all sets indexed by set_id
$allSets = [];  // set_id => [level, family, rung, axis_arc, breadth, intensity]
$walkOrder = [];  // ordered list of set_ids for L01-L20

// First pass: collect all sets
foreach ($manifest['levels'] as $levelData) {
    $level = $levelData['level'];
    foreach ($levelData['families'] as $familyData) {
        $family = $familyData['family'];
        foreach ($familyData['sets'] as $setData) {
            $setId = $setData['set_id'];
            $rung = $setData['progression']['rung'] ?? 0;
            $axisArc = $setData['progression']['axis_arc'] ?? null;
            $breadth = $setData['axes']['breadth'] ?? 0;
            $intensity = $setData['axes']['intensity'] ?? 0;

            $allSets[$setId] = [
                'level' => $level,
                'family' => $family,
                'rung' => $rung,
                'axis_arc' => $axisArc,
                'breadth' => $breadth,
                'intensity' => $intensity,
            ];

            // Include in walk if L01-L20
            if ($level >= 1 && $level <= 20) {
                $walkOrder[] = $setId;
            }
        }
    }
}

// Sort walk order by (level, family, rung)
usort($walkOrder, function($a, $b) use ($allSets) {
    $sa = $allSets[$a];
    $sb = $allSets[$b];
    if ($sa['level'] !== $sb['level']) return $sa['level'] - $sb['level'];
    if ($sa['family'] !== $sb['family']) return strcmp($sa['family'], $sb['family']);
    return $sa['rung'] - $sb['rung'];
});

// Build arcs - group by axis_arc, sorted by walk order
$arcSets = [];  // arc_name => [set_ids]
foreach ($walkOrder as $setId) {
    $set = $allSets[$setId];
    $arc = $set['axis_arc'];
    if ($arc && $arc !== 'none') {
        if (!isset($arcSets[$arc])) {
            $arcSets[$arc] = [];
        }
        $arcSets[$arc][] = $setId;
    }
}
ksort($arcSets);

// Build grid
$grid = [];
foreach ($walkOrder as $setId) {
    $set = $allSets[$setId];
    $grid[] = [
        'set_id' => $setId,
        'breadth' => $set['breadth'],
        'intensity' => $set['intensity'],
        'partiality' => 0.0,
    ];
}

// Build minimal_pairs lookup
$minimalPairs = [];
foreach ($minimalPairsRaw as $mp) {
    $minimalPairs[$mp['capability']] = $mp['set_id'];
}

// Build prerequisite_edges: rung N -> rung N+1 within same family
$prereqEdges = [];
$familyRungMap = [];  // family => rung => set_id

foreach ($allSets as $setId => $set) {
    if ($set['level'] >= 1 && $set['level'] <= 20) {
        $key = $set['family'];
        if (!isset($familyRungMap[$key])) {
            $familyRungMap[$key] = [];
        }
        if ($set['rung'] >= 1 && $set['rung'] <= 5) {
            $familyRungMap[$key][$set['rung']] = $setId;
        }
    }
}

foreach ($familyRungMap as $family => $rungs) {
    for ($r = 1; $r <= 4; $r++) {
        if (isset($rungs[$r]) && isset($rungs[$r + 1])) {
            $prereqEdges[] = [$rungs[$r], $rungs[$r + 1]];
        }
    }
}

// Sort prereq edges by position in walk
usort($prereqEdges, function($a, $b) use ($walkOrder) {
    $aPos = array_search($a[0], $walkOrder);
    $bPos = array_search($b[0], $walkOrder);
    if ($aPos === false) $aPos = PHP_INT_MAX;
    if ($bPos === false) $bPos = PHP_INT_MAX;
    return $aPos - $bPos;
});

// Define 10 bridge pairs (L1→L2 through L10→L11)
// These are representative pairs at the boundary of each level transition
// Each pair differs by exactly one axis (the axis that defines the higher level)
$bridges = [
    ['lower' => 'L01-ex_size_ladder-005', 'upper' => 'L02-ws_alignment-001', 'delta_axis' => 'whitespace'],
    ['lower' => 'L02-ws_combined-005',   'upper' => 'L03-cm_annotations-001', 'delta_axis' => 'comments'],
    ['lower' => 'L03-cm_combined-005',   'upper' => 'L04-ct_ratio_varied-001', 'delta_axis' => 'topology'],
    ['lower' => 'L04-ct_two_cluster-005', 'upper' => 'L05-rf_consolidate-001', 'delta_axis' => 'refactor'],
    ['lower' => 'L05-st_insert_logging-005', 'upper' => 'L06-cf_if_ternary-001', 'delta_axis' => 'controlflow'],
    ['lower' => 'L06-cf_loop_forms-005',  'upper' => 'L07-api_strings-001', 'delta_axis' => 'api'],
    ['lower' => 'L07-api_strings-005', 'upper' => 'L08-sem_config_driven-001', 'delta_axis' => 'semantic'],
    ['lower' => 'L08-sem_scatter-005',  'upper' => 'L09-mix_ws_cm-001', 'delta_axis' => 'mixed'],
    ['lower' => 'L09-mix_cf_rn_ws-005',  'upper' => 'L10-adv_below_threshold-001', 'delta_axis' => 'adversarial'],
    ['lower' => 'L10-adv_boundary-005',   'upper' => 'L11-rn_nested-001', 'delta_axis' => 'rename'],
];

// Build final structure
$progression = [
    'schema_version' => 1,
    'walk' => $walkOrder,
    'arcs' => $arcSets,
    'grid' => $grid,
    'minimal_pairs' => $minimalPairs,
    'bridges' => $bridges,
    'prerequisite_edges' => $prereqEdges,
];

$json = json_encode($progression, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents($outputPath, $json);

echo "Generated $outputPath\n";
echo "  Walk: " . count($walkOrder) . " sets\n";
echo "  Arcs: " . count($arcSets) . " arcs\n";
echo "  Grid: " . count($grid) . " entries\n";
echo "  Minimal pairs: " . count($minimalPairs) . " entries\n";
echo "  Bridges: " . count($bridges) . " pairs\n";
echo "  Prerequisite edges: " . count($prereqEdges) . " edges\n";
