<?php
/**
 * Retrofit Progression Tool
 * 
 * Reads all recipe files and assigns progression.rung values (1-5) to each set
 * based on the standard rung semantics:
 *   rung 1 (minimal):  ONE code, ONE occurrence, smallest legal param
 *   rung 2 (light):    Same code(s), 2-3 occurrences or one param step up
 *   rung 3 (moderate): All natural occurrences, mid params
 *   rung 4 (heavy):    Maximum intensity of the family's own axes (intensity_dial 6-8)
 *   rung 5 (combined): Family's axes + ONE adjacent-axis guest code
 *
 * The set_id suffix is the authoritative source for rung assignment (e.g., L01-ex_function-003 -> rung 3)
 * because families may span multiple recipe files but set_ids maintain correct ordering.
 *
 * Usage: php gen/tools/retrofit_progression.php [--dry-run] [--update-manifest] [--verbose]
 */

declare(strict_types=1);

const RUNG_LABELS = [
    1 => 'minimal',
    2 => 'light',
    3 => 'moderate',
    4 => 'heavy',
    5 => 'combined',
];

const RUNG_DESCRIPTIONS = [
    1 => 'ONE code, ONE occurrence, smallest legal param (the minimal pair)',
    2 => 'Same code(s), 2-3 occurrences or one param step up',
    3 => 'All natural occurrences, mid params; or the family\'s second code joins',
    4 => 'Maximum intensity of the family\'s own axes (intensity_dial 6-8)',
    5 => 'Family\'s axes + ONE adjacent-axis guest code (on-ramp to mixing)',
];

// CLI options
$dryRun = in_array('--dry-run', $argv);
$updateManifest = in_array('--update-manifest', $argv);
$verbose = in_array('--verbose', $argv);

echo "=== Retrofit Progression Tool ===\n";
echo "Mode: " . ($dryRun ? 'DRY RUN' : ($updateManifest ? 'UPDATE MANIFEST' : 'PLAN ONLY')) . "\n\n";

$recipesDir = __DIR__ . '/../recipes';
$manifestPath = __DIR__ . '/../../testsets/manifest.json';

/**
 * Extract rung from set_id suffix
 * e.g., L04-pd_head_unique-004 -> 4 (or null if invalid)
 */
function extractRungFromSetId(string $setId): ?int
{
    if (preg_match('/-(\d+)$/', $setId, $matches)) {
        $num = (int) $matches[1];
        return $num;
    }
    return null;
}

// Find all recipe JSON files recursively
$recipeFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($recipesDir, RecursiveDirectoryIterator::SKIP_DOTS)
);
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'json') {
        $recipeFiles[] = $file->getPathname();
    }
}

sort($recipeFiles);
echo "Found " . count($recipeFiles) . " recipe files\n\n";

// Process all recipes and build progression map
$progressionMap = [];  // set_id => progression data
$familyStats = [];    // level => family count
$totalSets = 0;
$rungValidationErrors = [];

foreach ($recipeFiles as $recipePath) {
    $content = file_get_contents($recipePath);
    $recipe = json_decode($content, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "ERROR: Failed to parse {$recipePath}: " . json_last_error_msg() . "\n";
        continue;
    }
    
    $level = $recipe['level'] ?? 0;
    $levelName = $recipe['level_name'] ?? 'unknown';
    $levelDir = $recipe['level_dir'] ?? 'unknown';
    $family = $recipe['family'] ?? 'unknown';
    $sets = $recipe['sets'] ?? [];
    
    if (empty($sets)) {
        continue;
    }
    
    if (!isset($familyStats[$level])) {
        $familyStats[$level] = 0;
    }
    $familyStats[$level]++;
    
    $setCount = count($sets);
    
    foreach ($sets as $index => $set) {
        $setId = $set['set_id'] ?? null;
        
        if ($setId === null) {
            echo "WARNING: Recipe {$recipePath} has set without set_id at index {$index}\n";
            continue;
        }
        
        // Use set_id suffix as authoritative rung source
        $rung = extractRungFromSetId($setId);
        
        if ($rung === null || $rung < 1 || $rung > 5) {
            $rungValidationErrors[] = "Set {$setId} has invalid rung {$rung} (should be 1-5)";
            $rung = min(max($rung ?? 1, 1), 5); // clamp to 1-5
        }
        
        $progressionMap[$setId] = [
            'rung' => $rung,
            'rung_label' => RUNG_LABELS[$rung],
            'rung_description' => RUNG_DESCRIPTIONS[$rung],
            'level' => $level,
            'level_name' => $levelName,
            'family' => $family,
            'set_id' => $setId,
            'title' => $set['title'] ?? '',
            'recipe_path' => $recipePath,
        ];
        
        $totalSets++;
    }
}

if (!empty($rungValidationErrors)) {
    echo "=== Rung Validation Errors ===\n";
    foreach ($rungValidationErrors as $error) {
        echo "  ERROR: {$error}\n";
    }
    echo "\n";
}

echo "Processed {$totalSets} sets across " . count($familyStats) . " levels\n\n";

// Summary by level
echo "=== Summary by Level ===\n";
ksort($familyStats);
foreach ($familyStats as $level => $count) {
    echo "  Level {$level}: {$count} families\n";
}
echo "\n";

// If just doing a dry run or plan, output the JSON
if ($dryRun || !$updateManifest) {
    $outputPath = __DIR__ . '/progression_retrofit_plan.json';
    $planData = [
        'generated_at' => date('c'),
        'tool' => 'gen/tools/retrofit_progression.php',
        'description' => 'Retrofit plan for adding progression.rung to all sets',
        'rung_semantics' => [
            1 => RUNG_LABELS[1] . ': ' . RUNG_DESCRIPTIONS[1],
            2 => RUNG_LABELS[2] . ': ' . RUNG_DESCRIPTIONS[2],
            3 => RUNG_LABELS[3] . ': ' . RUNG_DESCRIPTIONS[3],
            4 => RUNG_LABELS[4] . ': ' . RUNG_DESCRIPTIONS[4],
            5 => RUNG_LABELS[5] . ': ' . RUNG_DESCRIPTIONS[5],
        ],
        'totals' => [
            'sets' => $totalSets,
            'families' => array_sum($familyStats),
            'levels' => count($familyStats),
        ],
        'sets' => $progressionMap,
    ];
    
    file_put_contents($outputPath, json_encode($planData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "Plan written to: {$outputPath}\n";
}

// If updating manifest, read it and add progression data
if ($updateManifest && !$dryRun) {
    echo "Loading manifest from: {$manifestPath}\n";
    
    $manifestContent = file_get_contents($manifestPath);
    $manifest = json_decode($manifestContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("ERROR: Failed to parse manifest: " . json_last_error_msg() . "\n");
    }
    
    $updatedCount = 0;
    $fromRecipeCount = 0;
    $fromFallbackCount = 0;
    
    // Walk through manifest and add progression to each set
    foreach ($manifest['levels'] as $levelIdx => $levelData) {
        foreach ($levelData['families'] as $familyIdx => $familyData) {
            foreach ($familyData['sets'] as $setIdx => $setData) {
                $setId = $setData['set_id'];
                
                if (isset($progressionMap[$setId])) {
                    // Found in recipe - use recipe data
                    $manifest['levels'][$levelIdx]['families'][$familyIdx]['sets'][$setIdx]['progression'] = [
                        'rung' => $progressionMap[$setId]['rung'],
                        'rung_label' => $progressionMap[$setId]['rung_label'],
                    ];
                    $fromRecipeCount++;
                } else {
                    // Fallback: infer from set_id suffix
                    $inferredRung = extractRungFromSetId($setId);
                    if ($inferredRung !== null && $inferredRung >= 1 && $inferredRung <= 5) {
                        $manifest['levels'][$levelIdx]['families'][$familyIdx]['sets'][$setIdx]['progression'] = [
                            'rung' => $inferredRung,
                            'rung_label' => RUNG_LABELS[$inferredRung],
                        ];
                        $fromFallbackCount++;
                        if ($verbose) {
                            echo "INFO: Inferred rung {$inferredRung} for {$setId} from set_id suffix\n";
                        }
                    } else {
                        echo "WARNING: No progression data found for set_id: {$setId} (and no valid set_id suffix)\n";
                    }
                }
                $updatedCount++;
            }
        }
    }
    
    echo "Updated {$updatedCount} sets with progression data\n";
    echo "  - From recipe files: {$fromRecipeCount}\n";
    echo "  - Inferred from set_id: {$fromFallbackCount}\n";
    
    // Write updated manifest
    $backupPath = $manifestPath . '.backup.' . date('Y-m-d-His');
    copy($manifestPath, $backupPath);
    echo "Backup created: {$backupPath}\n";
    
    file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "Updated manifest written to: {$manifestPath}\n";
}

// Output sample progression assignments
echo "\n=== Sample Progression Assignments ===\n";
$sampleCount = 0;
foreach ($progressionMap as $setId => $data) {
    if ($sampleCount >= 20) {
        echo "... (showing first 20 samples)\n";
        break;
    }
    printf("  %-30s rung %d (%s)\n", $setId, $data['rung'], $data['rung_label']);
    $sampleCount++;
}

// Output distribution
echo "\n=== Rung Distribution ===\n";
$rungDist = array_fill(1, 5, 0);
foreach ($progressionMap as $data) {
    $rungDist[$data['rung']]++;
}
foreach ($rungDist as $rung => $count) {
    printf("  rung %d (%-10s): %d sets\n", $rung, RUNG_LABELS[$rung], $count);
}

echo "\n=== Done ===\n";
echo "Next steps:\n";
echo "  1. Review the plan: cat " . __DIR__ . "/progression_retrofit_plan.json\n";
echo "  2. To update manifest: php gen/tools/retrofit_progression.php --update-manifest\n";
echo "  3. To just see the plan (default): php gen/tools/retrofit_progression.php --dry-run\n";
