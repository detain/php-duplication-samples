#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Corpus Data Generator
 *
 * Walks testsets/ directory structure, parses set.json files,
 * and generates combined corpus-data.json for the corpus explorer.
 */

namespace CorpusExplorer;

const TESTSETS_DIR = __DIR__ . '/../testsets';
const OUTPUT_FILE = __DIR__ . '/../public_html/corpus-data.json';

/**
 * Find all set.json files under testsets/ directory.
 *
 * @return array<string> Array of absolute paths to set.json files
 */
function findSetJsonFiles(string $rootDir): array
{
    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($rootDir, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::LEAVES_ONLY
    );

    $setJsonFiles = [];

    foreach ($iterator as $file) {
        if ($file->isDir()) {
            continue;
        }

        if ($file->getFilename() === 'set.json') {
            $setJsonFiles[] = $file->getPathname();
        }
    }

    return $setJsonFiles;
}

/**
 * Parse a set.json file and extract key fields.
 *
 * @param string $setJsonPath Absolute path to set.json
 * @return array<string, mixed>|null Parsed data or null on failure
 */
function parseSetJson(string $setJsonPath): ?array
{
    $jsonContent = file_get_contents($setJsonPath);

    if ($jsonContent === false) {
        fwrite(STDERR, "WARNING: Could not read file: {$setJsonPath}\n");
        return null;
    }

    $data = json_decode($jsonContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        fwrite(STDERR, "WARNING: Invalid JSON in {$setJsonPath}: " . json_last_error_msg() . "\n");
        return null;
    }

    return $data;
}

/**
 * Count files by role in a set.
 *
 * @param array<int, array<string, mixed>> $files
 * @return array<string, int>
 */
function countFilesByRole(array $files): array
{
    $counts = [
        'num_carriers' => 0,
        'num_distractors' => 0,
        'num_clean' => 0,
    ];

    foreach ($files as $file) {
        $role = $file['role'] ?? 'unknown';
        match ($role) {
            'carrier' => $counts['num_carriers']++,
            'distractor' => $counts['num_distractors']++,
            'clean' => $counts['num_clean']++,
            default => null,
        };
    }

    return $counts;
}

/**
 * Extract interference codes from set data.
 *
 * @param array<int, array<string, mixed>> $interference
 * @return array<string>
 */
function extractInterferenceCodes(array $interference): array
{
    $codes = [];

    foreach ($interference as $item) {
        if (isset($item['code'])) {
            $codes[] = $item['code'];
        }
    }

    return array_values(array_unique($codes));
}

/**
 * Transform a parsed set.json into corpus-data entry format.
 *
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function transformSetData(array $data): array
{
    $files = $data['files'] ?? [];
    $roleCounts = countFilesByRole($files);
    $interference = $data['interference'] ?? [];
    $duplication = $data['duplication'] ?? [];

    // Find the first carrier file for snippet reference
    $snippetFile = '';
    $snippetStart = 0;
    $snippetEnd = 0;

    foreach ($files as $file) {
        if (($file['role'] ?? '') === 'carrier') {
            $snippetFile = $file['path'] ?? '';
            break;
        }
    }

    // Build files array for corpus-data (simplified)
    $corpusFiles = [];
    foreach ($files as $file) {
        $corpusFiles[] = [
            'path' => $file['path'] ?? '',
            'role' => $file['role'] ?? 'unknown',
            'duplicate_group' => $file['duplicate_group'] ?? null,
            'sloc' => $file['sloc'] ?? 0,
        ];
    }

    return [
        'set_id' => $data['set_id'] ?? 'unknown',
        'level' => $data['level'] ?? 0,
        'level_name' => $data['level_name'] ?? 'unknown',
        'family' => $data['family'] ?? 'unknown',
        'title' => $data['title'] ?? '',
        'description' => $data['description'] ?? '',
        'seed' => $data['seed'] ?? '',
        'difficulty_band' => $data['difficulty_band'] ?? 'unknown',
        'difficulty_score' => $data['difficulty']['score'] ?? 0,
        'clone_type' => $duplication['clone_type'] ?? 'unknown',
        'granularity' => $duplication['granularity'] ?? 'unknown',
        'num_carriers' => $roleCounts['num_carriers'],
        'num_distractors' => $roleCounts['num_distractors'],
        'num_clean' => $roleCounts['num_clean'],
        'interference' => extractInterferenceCodes($interference),
        'requires' => $data['difficulty']['requires'] ?? [],
        'files' => $corpusFiles,
        'snippet_file' => $snippetFile,
        'snippet_start' => $snippetStart,
        'snippet_end' => $snippetEnd,
    ];
}

/**
 * Extract unique values for a given key across all sets.
 *
 * @param array<int, array<string, mixed>> $sets
 * @param string $key
 * @return array<int, mixed>
 */
function extractUniqueValues(array $sets, string $key): array
{
    $values = [];

    foreach ($sets as $set) {
        if (isset($set[$key])) {
            $values[] = $set[$key];
        }
    }

    $unique = array_unique($values);
    sort($unique);

    return array_values($unique);
}

/**
 * Extract unique levels from sets.
 *
 * @param array<int, array<string, mixed>> $sets
 * @return array<int>
 */
function extractLevels(array $sets): array
{
    $levels = [];

    foreach ($sets as $set) {
        if (isset($set['level'])) {
            $levels[] = (int) $set['level'];
        }
    }

    $unique = array_unique($levels);
    sort($unique);

    return array_values($unique);
}

/**
 * Generate the corpus-data.json file.
 *
 * @param array<int, array<string, mixed>> $sets
 * @param string $outputPath
 * @return void
 */
function generateOutput(array $sets, string $outputPath): void
{
    $output = [
        'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        'total_sets' => count($sets),
        'sets' => $sets,
        'levels' => extractLevels($sets),
        'families' => extractUniqueValues($sets, 'family'),
        'seeds' => extractUniqueValues($sets, 'seed'),
        'clone_types' => extractUniqueValues($sets, 'clone_type'),
    ];

    $json = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($json === false) {
        fwrite(STDERR, "ERROR: Failed to encode JSON: " . json_last_error_msg() . "\n");
        exit(1);
    }

    $dir = dirname($outputPath);
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            fwrite(STDERR, "ERROR: Failed to create directory: {$dir}\n");
            exit(1);
        }
    }

    if (file_put_contents($outputPath, $json) === false) {
        fwrite(STDERR, "ERROR: Failed to write output file: {$outputPath}\n");
        exit(1);
    }

    echo "Generated corpus-data.json with {$output['total_sets']} sets.\n";
    echo "Output: {$outputPath}\n";
}

/**
 * Main entry point.
 */
function main(): void
{
    echo "Scanning for set.json files in: " . TESTSETS_DIR . "\n";

    if (!is_dir(TESTSETS_DIR)) {
        fwrite(STDERR, "ERROR: testsets directory not found: " . TESTSETS_DIR . "\n");
        exit(1);
    }

    $setJsonFiles = findSetJsonFiles(TESTSETS_DIR);

    if (empty($setJsonFiles)) {
        fwrite(STDERR, "ERROR: No set.json files found\n");
        exit(1);
    }

    echo "Found " . count($setJsonFiles) . " set.json files\n";

    $sets = [];
    $errors = 0;

    foreach ($setJsonFiles as $setJsonPath) {
        $data = parseSetJson($setJsonPath);

        if ($data === null) {
            $errors++;
            continue;
        }

        $corpusEntry = transformSetData($data);
        $sets[] = $corpusEntry;
    }

    if ($errors > 0) {
        echo "Processed with {$errors} errors\n";
    }

    // Sort sets by set_id for consistent output
    usort($sets, fn($a, $b) => strcmp($a['set_id'], $b['set_id']));

    generateOutput($sets, OUTPUT_FILE);

    echo "Done.\n";
}

main();
