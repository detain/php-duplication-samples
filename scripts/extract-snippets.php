#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Snippet Extractor for Corpus Explorer
 *
 * Reads corpus-data.json, finds clone regions from expected.json files,
 * and extracts code snippets (15-25 lines) centered on clone regions.
 */

namespace CorpusExplorer;

const CORPUS_DATA_FILE = __DIR__ . '/../public_html/corpus-data.json';
const TESTSETS_DIR = __DIR__ . '/../testsets';

/**
 * Read and parse corpus-data.json.
 *
 * @return array<string, mixed>
 */
function readCorpusData(): array
{
    if (!file_exists(CORPUS_DATA_FILE)) {
        fwrite(STDERR, "ERROR: corpus-data.json not found. Run generate-corpus-data.php first.\n");
        exit(1);
    }

    $jsonContent = file_get_contents(CORPUS_DATA_FILE);

    if ($jsonContent === false) {
        fwrite(STDERR, "ERROR: Could not read corpus-data.json\n");
        exit(1);
    }

    $data = json_decode($jsonContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        fwrite(STDERR, "ERROR: Invalid JSON in corpus-data.json: " . json_last_error_msg() . "\n");
        exit(1);
    }

    return $data;
}

/**
 * Find the expected.json path for a given set.
 *
 * @param string $setId e.g., "L04-pd_head_unique-001"
 * @return string|null Absolute path to expected.json or null
 */
function findExpectedJsonPath(string $setId): ?string
{
    // Pattern: testsets/L*_*/{family}/{number}/expected.json
    // We need to search for the directory that contains this set

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator(TESTSETS_DIR, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if ($file->isDir()) {
            continue;
        }

        if ($file->getFilename() !== 'expected.json') {
            continue;
        }

        // Read the expected.json to check if it matches our set_id
        $content = file_get_contents($file->getPathname());
        if ($content === false) {
            continue;
        }

        $expected = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            continue;
        }

        if (($expected['set_id'] ?? '') === $setId) {
            return $file->getPathname();
        }
    }

    return null;
}

/**
 * Parse expected.json to find clone region for a carrier.
 *
 * @param string $expectedJsonPath
 * @param string $carrierFile Path relative to src/ directory
 * @return array{start_line: int, end_line: int}|null
 */
function findCloneRegion(string $expectedJsonPath, string $carrierFile): ?array
{
    $content = file_get_contents($expectedJsonPath);

    if ($content === false) {
        return null;
    }

    $expected = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }

    $clusters = $expected['clusters'] ?? [];

    foreach ($clusters as $cluster) {
        $members = $cluster['members'] ?? [];

        foreach ($members as $member) {
            // Match by file path (both should reference same file like "src/InvoiceServiceA.php")
            if (($member['file'] ?? '') === $carrierFile) {
                return [
                    'start_line' => (int) ($member['start_line'] ?? 0),
                    'end_line' => (int) ($member['end_line'] ?? 0),
                ];
            }
        }
    }

    return null;
}

/**
 * Extract a snippet from a PHP file, centered on clone region.
 *
 * @param string $filePath Absolute path to PHP file
 * @param int $centerLine Line to center the snippet on
 * @param int $regionStart Clone region start line
 * @param int $regionEnd Clone region end line
 * @return string|null Extracted snippet or null on failure
 */
function extractSnippet(string $filePath, int $centerLine, int $regionStart, int $regionEnd): ?string
{
    if (!file_exists($filePath)) {
        return null;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
        return null;
    }

    $totalLines = count($lines);

    if ($totalLines === 0) {
        return null;
    }

    // Calculate snippet bounds: 15-25 lines centered on clone region
    $cloneLength = $regionEnd - $regionStart + 1;
    $targetLength = min(25, max(15, $cloneLength + 10)); // At least 15, at most 25, prefer clone+buffer

    $halfWindow = (int) floor(($targetLength - $cloneLength) / 2);

    $snippetStart = max(0, $regionStart - 1 - $halfWindow); // -1 for 0-indexed array
    $snippetEnd = min($totalLines, $regionEnd + $halfWindow);

    // Ensure we get at least 15 lines if possible
    while (($snippetEnd - $snippetStart) < 15 && $snippetStart > 0) {
        $snippetStart--;
    }

    while (($snippetEnd - $snippetStart) < 15 && $snippetEnd < $totalLines) {
        $snippetEnd++;
    }

    $snippetLines = array_slice($lines, $snippetStart, $snippetEnd - $snippetStart);

    // Add line numbers for reference
    $numberedLines = [];
    foreach ($snippetLines as $index => $line) {
        $lineNumber = $snippetStart + $index + 1; // 1-indexed
        $numberedLines[] = sprintf('%4d: %s', $lineNumber, $line);
    }

    return implode("\n", $numberedLines);
}

/**
 * Find the absolute path to a carrier file for a given set.
 *
 * @param string $setDir Directory containing the set
 * @param string $carrierPath Relative path from set.json's files array (e.g., "src/InvoiceServiceA.php")
 * @return string|null Absolute path to carrier file
 */
function findCarrierFilePath(string $setDir, string $carrierPath): ?string
{
    $fullPath = $setDir . '/' . $carrierPath;

    if (file_exists($fullPath)) {
        return $fullPath;
    }

    return null;
}

/**
 * Find the set directory for a given set_id.
 *
 * @param string $setId
 * @return string|null
 */
function findSetDirectory(string $setId): ?string
{
    // Pattern: testsets/L*_*/{family}/{number}/
    // We search for a directory whose set.json has this set_id

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator(TESTSETS_DIR, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if ($file->isDir()) {
            continue;
        }

        if ($file->getFilename() !== 'set.json') {
            continue;
        }

        $content = file_get_contents($file->getPathname());
        if ($content === false) {
            continue;
        }

        $setData = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            continue;
        }

        if (($setData['set_id'] ?? '') === $setId) {
            // Return the parent directory (the set directory)
            return dirname($file->getPathname());
        }
    }

    return null;
}

/**
 * Process a single set and extract its snippet.
 *
 * @param array<string, mixed> $setEntry
 * @return array<string, mixed> Updated set entry with snippet info
 */
function processSet(array $setEntry): array
{
    $setId = $setEntry['set_id'] ?? '';

    if ($setId === '') {
        return $setEntry;
    }

    // Find the set directory
    $setDir = findSetDirectory($setId);

    if ($setDir === null) {
        fwrite(STDERR, "WARNING: Could not find directory for set {$setId}\n");
        return $setEntry;
    }

    // Find expected.json
    $expectedJsonPath = findExpectedJsonPath($setId);

    if ($expectedJsonPath === null) {
        fwrite(STDERR, "WARNING: Could not find expected.json for set {$setId}\n");
        return $setEntry;
    }

    // Find the carrier file (first carrier in the set)
    $snippetFile = $setEntry['snippet_file'] ?? '';

    if ($snippetFile === '') {
        fwrite(STDERR, "WARNING: No snippet_file for set {$setId}\n");
        return $setEntry;
    }

    $carrierFilePath = findCarrierFilePath($setDir, $snippetFile);

    if ($carrierFilePath === null) {
        fwrite(STDERR, "WARNING: Could not find carrier file {$snippetFile} for set {$setId}\n");
        return $setEntry;
    }

    // Find clone region from expected.json
    $region = findCloneRegion($expectedJsonPath, $snippetFile);

    if ($region === null) {
        fwrite(STDERR, "WARNING: Could not find clone region for {$snippetFile} in set {$setId}\n");
        return $setEntry;
    }

    // Extract snippet
    $snippet = extractSnippet(
        $carrierFilePath,
        (int) (($region['start_line'] + $region['end_line']) / 2),
        $region['start_line'],
        $region['end_line']
    );

    if ($snippet === null) {
        fwrite(STDERR, "WARNING: Failed to extract snippet for set {$setId}\n");
        return $setEntry;
    }

    $setEntry['snippet'] = $snippet;
    $setEntry['snippet_start'] = $region['start_line'];
    $setEntry['snippet_end'] = $region['end_line'];
    $setEntry['snippet_file'] = $snippetFile;

    return $setEntry;
}

/**
 * Save updated corpus data.
 *
 * @param array<string, mixed> $data
 * @return void
 */
function saveCorpusData(array $data): void
{
    $data['generated_at'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($json === false) {
        fwrite(STDERR, "ERROR: Failed to encode JSON: " . json_last_error_msg() . "\n");
        exit(1);
    }

    if (file_put_contents(CORPUS_DATA_FILE, $json) === false) {
        fwrite(STDERR, "ERROR: Failed to write corpus-data.json\n");
        exit(1);
    }
}

/**
 * Main entry point.
 */
function main(): void
{
    echo "Reading corpus-data.json...\n";

    $data = readCorpusData();

    $sets = $data['sets'] ?? [];

    if (empty($sets)) {
        fwrite(STDERR, "ERROR: No sets found in corpus-data.json\n");
        exit(1);
    }

    echo "Processing " . count($sets) . " sets to extract snippets...\n";

    $processed = 0;
    $errors = 0;

    foreach ($sets as $index => $setEntry) {
        $setId = $setEntry['set_id'] ?? "unknown-{$index}";

        echo "  Processing {$setId}...\n";

        $updatedSet = processSet($setEntry);

        if (isset($updatedSet['snippet'])) {
            $processed++;
        } else {
            $errors++;
        }

        $sets[$index] = $updatedSet;
    }

    $data['sets'] = $sets;

    echo "Saving updated corpus-data.json...\n";

    saveCorpusData($data);

    echo "Done. Processed: {$processed}, Errors: {$errors}\n";
    echo "Output: " . CORPUS_DATA_FILE . "\n";
}

main();
