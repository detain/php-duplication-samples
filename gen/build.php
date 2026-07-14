<?php

declare(strict_types=1);

/**
 * gen/build.php — assemble sets from recipes.
 *
 * Usage:
 *   php gen/build.php --set=L01-ex_function-001
 *   php gen/build.php --family=ex_function
 *   php gen/build.php --level=2
 *   php gen/build.php --all
 *   php gen/build.php --check            # regenerate in memory, byte-diff vs the
 *                                        # committed tree; nonzero exit on drift
 *
 * Ground-truth line numbers come from the renderer's line map — never hand-typed.
 * Deterministic: mt_srand(rng_seed) per set.
 */

require __DIR__ . '/bootstrap.php';

use Gen\Lib\SetBuilder;

$root = dirname(__DIR__);
$opts = parseArgs($argv);

$builder = new SetBuilder($root);
$recipes = discoverRecipes($root);

$matched = 0;
$written = 0;
$drift = [];

foreach ($recipes as $recipeFile) {
    $data = json_decode((string)file_get_contents($recipeFile), true);
    if (!is_array($data) || !isset($data['sets'])) {
        fwrite(STDERR, "[warn] skipping malformed recipe {$recipeFile}\n");
        continue;
    }
    $family = [
        'level'       => (int)$data['level'],
        'level_name'  => (string)$data['level_name'],
        'level_dir'   => (string)$data['level_dir'],
        'family'      => (string)$data['family'],
        'recipe_rel'  => ltrim(str_replace($root, '', $recipeFile), '/'),
    ];

    foreach ($data['sets'] as $spec) {
        if (!setMatches($spec, $family, $opts)) {
            continue;
        }
        $matched++;
        $built = $builder->build($family, $spec);
        $targetDir = $root . '/testsets/' . $built['dir'];

        $rendered = renderFiles($built);

        if ($opts['check']) {
            foreach ($rendered as $rel => $content) {
                $path = $targetDir . '/' . $rel;
                $existing = is_file($path) ? file_get_contents($path) : null;
                if ($existing !== $content) {
                    $drift[] = $built['set_id'] . ' :: ' . $rel . ($existing === null ? ' (missing on disk)' : ' (content differs)');
                }
            }
        } else {
            foreach ($rendered as $rel => $content) {
                $path = $targetDir . '/' . $rel;
                @mkdir(dirname($path), 0o775, true);
                file_put_contents($path, $content);
                $written++;
            }
            echo "[built] {$built['set_id']} -> testsets/{$built['dir']}\n";
        }
    }
}

if ($opts['check']) {
    if ($drift === []) {
        echo "[check] OK — {$matched} set(s) regenerate byte-identical to the committed tree\n";
        exit(0);
    }
    fwrite(STDERR, "[check] DRIFT DETECTED (" . count($drift) . "):\n");
    foreach ($drift as $d) {
        fwrite(STDERR, "  - {$d}\n");
    }
    exit(1);
}

if ($matched === 0) {
    fwrite(STDERR, "[build] no sets matched the given filter\n");
    exit(2);
}
echo "[build] wrote {$written} file(s) across {$matched} set(s)\n";
exit(0);

/** @return array{set:?string,family:?string,level:?int,all:bool,check:bool} */
function parseArgs(array $argv): array
{
    $o = ['set' => null, 'family' => null, 'level' => null, 'all' => false, 'check' => false];
    foreach (array_slice($argv, 1) as $arg) {
        if (str_starts_with($arg, '--set=')) {
            $o['set'] = substr($arg, 6);
        } elseif (str_starts_with($arg, '--family=')) {
            $o['family'] = substr($arg, 9);
        } elseif (str_starts_with($arg, '--level=')) {
            $o['level'] = (int)substr($arg, 8);
        } elseif ($arg === '--all') {
            $o['all'] = true;
        } elseif ($arg === '--check') {
            $o['check'] = true;
        }
    }
    return $o;
}

/** @return list<string> */
function discoverRecipes(string $root): array
{
    $files = glob($root . '/gen/recipes/*/*.json') ?: [];
    sort($files);
    return $files;
}

function setMatches(array $spec, array $family, array $opts): bool
{
    if ($opts['set'] !== null) {
        return ($spec['set_id'] ?? '') === $opts['set'];
    }
    if ($opts['family'] !== null) {
        return $family['family'] === $opts['family'];
    }
    if ($opts['level'] !== null) {
        return $family['level'] === $opts['level'];
    }
    return $opts['all'];
}

/** @return array<string,string> src-relative path => content (incl. set.json/expected.json) */
function renderFiles(array $built): array
{
    $out = $built['files'];
    $out['set.json'] = jsonPretty($built['set']);
    $out['expected.json'] = jsonPretty($built['expected']);
    return $out;
}

function jsonPretty(array $data): string
{
    return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
}
