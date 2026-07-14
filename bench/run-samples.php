<?php
/**
 * Run duplication detection tools against sample files.
 *
 * Reads samples.json to get the list of duplication types and their sample files,
 * then runs phpcpd and jscpd against each type's sample files.
 *
 * Usage:
 *   php bench/run-samples.php              # all types
 *   php bench/run-samples.php --type=clone_type_1  # specific type
 *
 * Tools:
 *   phpcpd — bench/tools/phpcpd.phar
 *   jscpd  — bench/tools/node_modules/.bin/jscpd
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$toolsDir = $root . '/bench/tools';
$nodeBin = $toolsDir . '/node_modules/.bin';
$jscpdBin = $nodeBin . '/jscpd';
$phpcpdPhar = $toolsDir . '/phpcpd.phar';

// Detect available tools
$hasPhpCpd = is_file($phpcpdPhar);
$hasJsCpd = is_executable($jscpdBin);

// Parse command-line arguments
$filterType = null;
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--type=')) {
        $filterType = substr($arg, 7);
    }
}

// Load samples metadata
$metaFile = $root . '/samples.json';
if (!is_file($metaFile)) {
    fwrite(STDERR, "[error] samples.json not found at {$metaFile}\n");
    exit(1);
}

$meta = json_decode((string)file_get_contents($metaFile), true);
if (!is_array($meta) || !isset($meta['types'])) {
    fwrite(STDERR, "[error] samples.json has unexpected structure\n");
    exit(1);
}

// Filter types if requested
$types = $meta['types'];
if ($filterType !== null) {
    $types = array_filter($types, static fn(array $t): bool => $t['name'] === $filterType);
    if ($types === []) {
        fwrite(STDERR, "[error] unknown type: {$filterType}\n");
        exit(1);
    }
}

// Header
echo "| Type | phpcpd | jscpd |\n";
echo "|---|---|---|\n";

// Process each type
foreach ($types as $type) {
    $typeName = $type['name'];
    $samples = $type['samples'] ?? [];

    if ($samples === []) {
        echo "| {$typeName} | 0 | 0 |\n";
        continue;
    }

    // Collect all PHP files for this type
    $files = [];
    foreach ($samples as $sample) {
        $sampleId = $sample['id'];
        $sampleDir = $root . '/samples/' . $typeName . '/' . $sampleId;
        if (!is_dir($sampleDir)) {
            continue;
        }
        $dirFiles = glob($sampleDir . '/*.php') ?: [];
        foreach ($dirFiles as $f) {
            if (!in_array($f, $files, true)) {
                $files[] = $f;
            }
        }
    }

    if ($files === []) {
        echo "| {$typeName} | 0 | 0 |\n";
        continue;
    }

    // Run phpcpd
    $phpcpdCount = '—';
    if ($hasPhpCpd) {
        $phpcpdCount = runPhpCpd($phpcpdPhar, $files);
    }

    // Run jscpd
    $jscpdCount = '—';
    if ($hasJsCpd) {
        $jscpdCount = runJsCpd($jscpdBin, $files);
    }

    echo "| {$typeName} | {$phpcpdCount} | {$jscpdCount} |\n";
}

/**
 * Run phpcpd against a set of PHP files.
 * Returns the number of duplications found, or '—' on error.
 */
function runPhpCpd(string $phar, array $files): string
{
    if ($files === []) {
        return '0';
    }

    $tmpDir = sys_get_temp_dir() . '/phpcpd-run-' . bin2hex(random_bytes(4));
    @mkdir($tmpDir, 0755, true);

    $xmlOut = $tmpDir . '/phpcpd.xml';
    $fileList = $tmpDir . '/files.txt';
    file_put_contents($fileList, implode("\n", $files));

    $cmd = sprintf(
        'php %s --fuzzy --min-lines 5 --min-tokens 50 --log-pmd %s @%s 2>/dev/null',
        escapeshellarg($phar),
        escapeshellarg($xmlOut),
        escapeshellarg($fileList)
    );

    @exec($cmd, $_, $rc);

    $count = 0;
    if (is_file($xmlOut)) {
        $xml = @simplexml_load_file($xmlOut);
        if ($xml instanceof SimpleXMLElement) {
            $count = count($xml->duplication);
        }
    }

    @passthru('rm -rf ' . escapeshellarg($tmpDir));

    return (string)$count;
}

/**
 * Run jscpd against a set of PHP files.
 * Returns the number of duplications found, or '—' on error.
 */
function runJsCpd(string $jscpdBin, array $files): string
{
    if ($files === []) {
        return '0';
    }

    $tmpDir = sys_get_temp_dir() . '/jscpd-run-' . bin2hex(random_bytes(4));
    @mkdir($tmpDir, 0755, true);
    // Bug 8 fix: ensure temp dir cleanup on any fatal exit
    register_shutdown_function(function() use ($tmpDir) {
        @exec('rm -rf ' . escapeshellarg($tmpDir));
    });
    @mkdir($tmpDir . '/report', 0755, true);

    // Create a temporary directory with symlinks to files
    $workDir = $tmpDir . '/work';
    @mkdir($workDir, 0755, true);
    foreach ($files as $f) {
        @symlink($f, $workDir . '/' . basename($f));
    }

    $cmd = sprintf(
        '%s --formats-exts php:php --reporters json --silent --output %s %s 2>/dev/null',
        escapeshellarg($jscpdBin),
        escapeshellarg($tmpDir . '/report'),
        escapeshellarg($workDir)
    );

    @exec($cmd, $_, $rc);

    $jsonReport = $tmpDir . '/report/jscpd-report.json';
    $count = 0;
    if (is_file($jsonReport)) {
        $report = json_decode((string)file_get_contents($jsonReport), true);
        if (is_array($report) && isset($report['duplicates']) && is_array($report['duplicates'])) {
            $count = count($report['duplicates']);
        }
    }

    @passthru('rm -rf ' . escapeshellarg($tmpDir));

    return (string)$count;
}
