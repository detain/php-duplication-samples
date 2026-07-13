<?php

declare(strict_types=1);

namespace Gen\Lib;

use Gen\Transforms\Registry;
use Gen\Transforms\TransformInput;

/**
 * Assembles one benchmark set from a family recipe entry: renders the src/
 * files and builds set.json + expected.json with line numbers taken from the
 * render (never hand-typed).
 *
 * A BuiltSet is returned in memory; build.php writes it (or diffs it for
 * --check). Everything is deterministic given the set's rng_seed.
 */
final class SetBuilder
{
    private Registry $registry;

    public function __construct(
        private string $repoRoot,
        ?Registry $registry = null,
    ) {
        $this->registry = $registry ?? new Registry();
    }

    public function seedsDir(): string
    {
        return $this->repoRoot . '/gen/seeds';
    }

    /**
     * @param array<string,mixed> $family recipe header (level, level_dir, family, recipe_rel)
     * @param array<string,mixed> $spec   one set entry
     * @return array{files: array<string,string>, set: array<string,mixed>, expected: array<string,mixed>, set_id: string, dir: string}
     */
    public function build(array $family, array $spec): array
    {
        $setId = (string)$spec['set_id'];
        $level = (int)$family['level'];
        $levelDir = (string)$family['level_dir'];
        $familyName = (string)$family['family'];
        new Rng((int)$spec['rng_seed']); // seed mt_rand deterministically for this set

        $seed = $spec['seed'] ?? null;
        $files = [];            // src-relative path => content
        $members = [];          // cluster members
        $nonDuplicates = [];    // trap/near-miss regions
        $interferenceAgg = [];  // "code|paramsJson" => [code,name,params,applied_to[]]
        $appliedCodes = [];     // code => true
        $pristineText = null;

        // ---- carriers ------------------------------------------------------
        foreach (($spec['carriers'] ?? []) as $carrier) {
            [$content, $start, $end, $payloadText] = $this->renderCarrier($family, $spec, $carrier);
            $rel = 'src/' . $carrier['file'];
            $files[$rel] = $content;

            $members[] = [
                'file'       => $rel,
                'start_line' => $start,
                'end_line'   => $end,
                'symbol'     => $spec['clone_symbol'] ?? null,
                'pristine'   => (bool)($carrier['pristine'] ?? false),
            ];
            if (($carrier['pristine'] ?? false) && $pristineText === null) {
                $pristineText = $payloadText;
            }

            foreach (($carrier['transforms'] ?? []) as $tr) {
                $code = (string)$tr['code'];
                $appliedCodes[$code] = true;
                $params = $tr['params'] ?? [];
                if (isset($tr['variant'])) {
                    $params = ['variant' => $tr['variant']];
                }
                // One interference entry per (code, params); applied_to holds the
                // distinct files it was applied to. A file is recorded at most once
                // per (code, params) so a carrier that lists the same transform twice
                // never double-lists itself (MINOR-3: one entry per code+file+params).
                $key = $code . '|' . json_encode($params);
                if (!isset($interferenceAgg[$key])) {
                    $interferenceAgg[$key] = [
                        'code'       => $code,
                        'name'       => $this->registry->name($code),
                        'params'     => (object)$params,
                        'applied_to' => [],
                    ];
                }
                if (!in_array($rel, $interferenceAgg[$key]['applied_to'], true)) {
                    $interferenceAgg[$key]['applied_to'][] = $rel;
                }
            }
        }

        // ---- distractors + clean fillers -----------------------------------
        foreach (($spec['distractors'] ?? []) as $d) {
            [$content, $region] = $this->renderAsset('distractors', $d);
            $rel = 'src/' . $d['file'];
            $files[$rel] = $content;
            if ($region !== null) {
                $nonDuplicates[] = [
                    'file'       => $rel,
                    'start_line' => $region[0],
                    'end_line'   => $region[1],
                    'reason'     => (string)($d['reason'] ?? 'near-miss: shares vocabulary/shape with the clone but computes a different result'),
                    'trap'       => (bool)($d['trap'] ?? true),
                ];
            }
        }
        foreach (($spec['cleans'] ?? []) as $c) {
            [$content] = $this->renderAsset('distractors', $c);
            $rel = 'src/' . $c['file'];
            $files[$rel] = $content;
        }

        // ---- explicit bait/trap regions (L0 negative controls) -------------
        // §6-rule-4 / §7.2: a set with no cluster may still mark the deliberate
        // false-positive bait — the most-tempting shared-shape region(s) — in
        // non_duplicates. Line numbers are resolved from the already-rendered
        // file (never hand-typed), keeping the ground truth generator-emitted.
        foreach (($spec['traps'] ?? []) as $trap) {
            $rel = 'src/' . $trap['file'];
            if (!isset($files[$rel])) {
                throw new \RuntimeException("trap references unrendered file {$rel}");
            }
            [$s, $e] = $this->resolveTrapRegion($files[$rel], (string)($trap['region'] ?? 'class_header'));
            $nd = [
                'file'       => $rel,
                'start_line' => $s,
                'end_line'   => $e,
                'reason'     => (string)($trap['reason'] ?? 'deliberate false-positive bait: shares shape with other files but is not a duplicate'),
                'trap'       => true,
            ];
            if (array_key_exists('known_tool_fp', $trap)) {
                $nd['known_tool_fp'] = (bool)$trap['known_tool_fp'];
            }
            $nonDuplicates[] = $nd;
        }

        // ---- set.json ------------------------------------------------------
        $present = $members !== [];
        $interference = array_values($interferenceAgg);

        // Difficulty = level_base + sum of each DISTINCT applied code's weight
        // (§7.1). $appliedCodes is keyed by code, so a code applied to several
        // carriers (asymmetric interference) contributes its weight exactly once.
        $score = $this->registry->levelBase($level);
        foreach (array_keys($appliedCodes) as $code) {
            $score += $this->registry->weight($code);
        }
        $score += (int)($spec['intensity_bonus'] ?? 0);
        $score = max(0, min(100, $score));

        $requires = [];
        if (isset($spec['requires'])) {
            $requires = (array)$spec['requires'];
        } else {
            foreach (array_keys($appliedCodes) as $code) {
                $requires = array_merge($requires, $this->registry->requires($code));
            }
            $requires = array_values(array_unique($requires));
        }

        $fileEntries = [];
        foreach ($files as $rel => $content) {
            $fileEntries[] = [
                'path'            => $rel,
                'role'            => $this->roleOf($rel, $spec),
                'duplicate_group' => $this->groupOf($rel, $spec),
                'sloc'            => $this->sloc($content),
            ];
        }

        $cloneType = $present ? (string)$spec['clone_type'] : 'none';
        $granularity = $present ? ($spec['granularity'] ?? null) : null;

        $set = [
            'schema_version' => 1,
            'set_id'         => $setId,
            'level'          => $level,
            'level_name'     => (string)$family['level_name'],
            'family'         => $familyName,
            'title'          => (string)$spec['title'],
            'description'    => (string)$spec['description'],
            'language'       => 'php',
            'min_php'        => (string)($spec['min_php'] ?? '8.1'),
            'seed'           => $seed,
            'difficulty_band' => $this->registry->band($score),
            'files'          => $fileEntries,
            'duplication'    => [
                'present'     => $present,
                'clone_type'  => $cloneType,
                'granularity' => $granularity,
                'clusters'    => $present ? 1 : 0,
                'instances'   => count($members),
            ],
            'interference'   => $interference,
            'difficulty'     => [
                'score'    => $score,
                'requires' => $requires,
            ],
            'generator'      => [
                'tool'     => 'gen/build.php',
                'recipe'   => (string)$family['recipe_rel'],
                'version'  => '1.0.0',
                'rng_seed' => (int)$spec['rng_seed'],
            ],
        ];
        if (isset($spec['expected_detection_by_tool'])) {
            $set['expected_detection_by_tool'] = $spec['expected_detection_by_tool'];
        }

        // ---- expected.json -------------------------------------------------
        $clusters = [];
        if ($present) {
            $normalizedBy = (array)($spec['normalized_by'] ?? ['whitespace', 'comments']);
            $pristineText ??= $members[0]['symbol'] ?? '';
            $tokenHash = substr(hash('sha256', (string)$pristineText), 0, 16);
            $normHash = substr(hash('sha256', implode(' ', PhpTokens::normalize((string)$pristineText, PhpTokens::optsForStages($normalizedBy)))), 0, 16);

            $clusters[] = [
                'id'          => 'c1',
                'group_id'    => $this->firstGroup($spec),
                'clone_type'  => (string)$spec['clone_type'],
                'granularity' => (string)$spec['granularity'],
                'normalized_by' => array_values($normalizedBy),
                'token_hash'  => $tokenHash,
                'normalized_hash' => $normHash,
                'members'     => $members,
                'detection_expectation' => $spec['detection_expectation'],
                'notes'       => (string)($spec['notes'] ?? ''),
            ];
        }

        $expected = [
            'schema_version' => 1,
            'set_id'         => $setId,
            'clusters'       => $clusters,
            'non_duplicates' => $nonDuplicates,
            'scoring'        => $spec['scoring'] ?? [
                'line_tolerance'        => 2,
                'member_jaccard_min'    => 0.6,
                'min_members_for_credit' => 2,
            ],
        ];

        $dir = $levelDir . '/' . $familyName . '/' . $this->setNum($setId);

        return [
            'files'    => $files,
            'set'      => $set,
            'expected' => $expected,
            'set_id'   => $setId,
            'dir'      => $dir,
        ];
    }

    /**
     * @return array{0:string,1:int,2:int,3:string} content, startLine, endLine, payloadText
     */
    private function renderCarrier(array $family, array $spec, array $carrier): array
    {
        $mode = (string)($carrier['mount'] ?? $spec['mount'] ?? 'method');

        // Resolve the payload region (pristine seed or a hand-written variant).
        if (isset($carrier['variant'])) {
            $variantFile = $this->seedsDir() . '/' . $spec['seed'] . '/variants/' . $carrier['variant'] . '.php';
            $payloadLines = Payload::region($variantFile);
        } else {
            $payloadFile = $this->seedsDir() . '/' . $spec['seed'] . '/payload.php';
            $payloadLines = Payload::region($payloadFile);
        }
        $payloadLines = $this->mount($payloadLines, $mode, (string)($carrier['visibility'] ?? 'public'));

        // Apply the transform chain (line-map aware).
        $input = TransformInput::fromLines($payloadLines);
        foreach (($carrier['transforms'] ?? []) as $tr) {
            $code = (string)$tr['code'];
            $t = $this->registry->transform($code);
            $params = $tr['params'] ?? [];
            if (isset($tr['variant'])) {
                // A selector carrier already loaded its variant above; the
                // transform entry is metadata-only, skip re-applying.
                continue;
            }
            $rng = new Rng((int)$spec['rng_seed']);
            $result = $t->apply($input, $params, $rng);
            $input = $result->toInput();
        }
        $payloadLines = $input->lines;
        $payloadText = implode("\n", $payloadLines);

        // Load scaffold, substitute, insert payload at the marker.
        $scaffoldFile = $this->repoRoot . '/gen/scaffolds/' . $carrier['scaffold'] . '.php';
        $scaffold = $this->readTemplate($scaffoldFile, [
            '__NAMESPACE__' => (string)$carrier['namespace'],
            '__CLASS__'     => (string)$carrier['class'],
        ]);
        $scaffoldLines = explode("\n", $scaffold);

        $markerIdx = null;
        $markerIndent = '';
        foreach ($scaffoldLines as $i => $line) {
            if (str_contains($line, '<<<INSERT>>>')) {
                $markerIdx = $i;
                preg_match('/^(\s*)/', $line, $m);
                $markerIndent = $m[1];
                break;
            }
        }
        if ($markerIdx === null) {
            throw new \RuntimeException("scaffold {$carrier['scaffold']} has no <<<INSERT>>> marker");
        }

        $reindented = [];
        foreach ($payloadLines as $line) {
            $reindented[] = $line === '' ? '' : $markerIndent . $line;
        }

        $prefix = array_slice($scaffoldLines, 0, $markerIdx);
        $suffix = array_slice($scaffoldLines, $markerIdx + 1);

        $carrierLines = array_merge($prefix, $reindented, $suffix);
        $start = count($prefix) + 1;
        $end = $start + count($reindented) - 1;

        $content = rtrim(implode("\n", $carrierLines), "\n") . "\n";
        return [$content, $start, $end, $payloadText];
    }

    /**
     * Render a distractor/clean asset; strip any <<<NEARMISS>>> markers and
     * report their final-file line range.
     *
     * @return array{0:string,1:?array{0:int,1:int}}
     */
    private function renderAsset(string $kind, array $asset): array
    {
        $file = $this->repoRoot . '/gen/' . $kind . '/' . $asset['source'] . '.php';
        $text = $this->readTemplate($file, [
            '__NAMESPACE__' => (string)$asset['namespace'],
            '__CLASS__'     => (string)$asset['class'],
        ]);
        $lines = explode("\n", $text);

        $region = null;
        $out = [];
        $startLine = null;
        foreach ($lines as $line) {
            if (str_contains($line, '<<<NEARMISS>>>')) {
                $startLine = count($out) + 1; // region begins on the next emitted line
                continue;
            }
            if (str_contains($line, '<<<END-NEARMISS>>>')) {
                $region = [$startLine, count($out)];
                continue;
            }
            $out[] = $line;
        }
        $content = rtrim(implode("\n", $out), "\n") . "\n";
        return [$content, $region];
    }

    /**
     * Resolve a named trap region to 1-based [start, end] lines in rendered
     * content. Only the shape actually present in the file is marked, so the
     * ground truth stays generator-emitted.
     *
     * Supported regions:
     *   class_header — line 1 through the class body's opening brace (the
     *                  declare/namespace/final-class scaffold shared verbatim by
     *                  every file; a low-threshold token tool may pair it).
     *
     * @return array{0:int,1:int}
     */
    private function resolveTrapRegion(string $content, string $region): array
    {
        $lines = explode("\n", rtrim($content, "\n"));
        switch ($region) {
            case 'class_header':
                foreach ($lines as $i => $line) {
                    if (preg_match('/^\s*(?:abstract\s+|final\s+)?(?:class|interface|trait|enum)\s/', $line)) {
                        // Extend through the opening brace (same line or the next
                        // non-blank line that begins with it).
                        $end = $i + 1; // 1-based, class-declaration line
                        if (!str_contains($line, '{')) {
                            for ($j = $i + 1; $j < count($lines); $j++) {
                                if (trim($lines[$j]) !== '') {
                                    $end = $j + 1;
                                    if (str_contains($lines[$j], '{')) {
                                        break;
                                    }
                                }
                            }
                        }
                        return [1, $end];
                    }
                }
                throw new \RuntimeException("resolveTrapRegion: no class declaration found for region 'class_header'");
            default:
                throw new \RuntimeException("resolveTrapRegion: unknown region '{$region}'");
        }
    }

    /** @param list<string> $lines */
    private function mount(array $lines, string $mode, string $visibility): array
    {
        $lines = Payload::dedent($lines);
        // Find first non-blank line (the signature).
        foreach ($lines as $i => $line) {
            if (trim($line) === '') {
                continue;
            }
            if ($mode === 'function') {
                $lines[$i] = preg_replace('/^\s*(public|protected|private)\s+function\b/', 'function', $line, 1);
            } else { // method
                if (preg_match('/^\s*function\b/', $line)) {
                    $lines[$i] = preg_replace('/^(\s*)function\b/', '$1' . $visibility . ' function', $line, 1);
                }
            }
            break;
        }
        return array_values($lines);
    }

    private function readTemplate(string $file, array $subs): string
    {
        $raw = file_get_contents($file);
        if ($raw === false) {
            throw new \RuntimeException("template not found: {$file}");
        }
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        return strtr($raw, $subs);
    }

    private function roleOf(string $rel, array $spec): string
    {
        foreach (($spec['carriers'] ?? []) as $c) {
            if ('src/' . $c['file'] === $rel) {
                return 'carrier';
            }
        }
        foreach (($spec['distractors'] ?? []) as $d) {
            if ('src/' . $d['file'] === $rel) {
                return 'distractor';
            }
        }
        return 'clean';
    }

    private function groupOf(string $rel, array $spec): ?string
    {
        foreach (($spec['carriers'] ?? []) as $c) {
            if ('src/' . $c['file'] === $rel) {
                return (string)($c['group'] ?? 'A');
            }
        }
        return null;
    }

    private function firstGroup(array $spec): string
    {
        foreach (($spec['carriers'] ?? []) as $c) {
            return (string)($c['group'] ?? 'A');
        }
        return 'A';
    }

    private function sloc(string $content): int
    {
        $n = 0;
        foreach (explode("\n", $content) as $line) {
            if (trim($line) !== '') {
                $n++;
            }
        }
        return $n;
    }

    private function setNum(string $setId): string
    {
        $parts = explode('-', $setId);
        return end($parts);
    }
}
