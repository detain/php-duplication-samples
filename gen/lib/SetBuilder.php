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
 *
 * v2 features (all optional, backward-compatible with v1 sets):
 *   F-1  Multi-cluster path — multiple clusters per set (c1, c2, ...)
 *   F-2  fragment/unique_segments emission from lineMap -1 markers
 *   F-3  2-D difficulty axes + score_raw (unclamped)
 *   F-4  interference_profile accounting
 *   F-5  region_sloc per file (duplicate/unique/filler)
 *   F-6  intended_refactoring + refactor_proof.php harness
 *   F-7  drift-chain emission for genealogy families
 *   F-8  selector-variant stacking (cf_early_return+api_table_driven)
 *   F-9  transform-order contract enforcement
 *   F-10 size-inflation for big-file/tiny-clone
 *   F-11 per-seed unique-snippet pools
 *   F-12 block-mount path (clone block inside larger host method)
 *   F-13 Style packs (psr12_modern, legacy_wordpress, enterprise_java_style)
 *   F-14 budget_matrix index for pd_budget_matrix_family
 *   F-18 intensity dial per code (0-8)
 *   F-19 hygiene_exceptions for ENC families
 *   F-22 difficulty model re-fit hooks (application_count vs axis_count)
 *   F-23 cross_seed support (different clusters use different seeds)
 *   F-24 partiality axis derivation
 */
final class SetBuilder
{
    private Registry $registry;

    private const TRANSFORM_ORDER = [
        'selector'  => 1,  // CF-*, API-*, SEM-*, BL-*, EX-*, NU-*
        'structure' => 2,  // ST-*
        'ast_edit'  => 3,  // RN-*, LT-*, TY-*, NS-*
        'text'      => 3,  // SY-*, LEG-*
        'comment'   => 4,  // CM-*
        'whitespace' => 5, // WS-*
        'wrapper'   => 5,  // UQ-*, NZ-*
        'encoding'  => 6,  // ENC-*
    ];

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
        new Rng((int)$spec['rng_seed']);

        $files = [];
        $nonDuplicates = [];
        $interferenceAgg = [];
        $appliedCodes = [];

        // F-23 cross_seed: per-cluster seed override
        $crossSeedMap = $this->resolveCrossSeedMap($spec);

        // F-1 multi-cluster: group carriers by cluster ID
        $clusterGroups = $this->groupCarriersByCluster($spec);

        // F-4 interference_profile: track per-carrier axes
        $perCarrierAxes = [];
        $allAxes = [];
        $stackDepthMax = 0;

        // F-5 region_sloc: per-file sloc breakdown
        $regionSlocByFile = [];

        // F-6 intended_refactoring
        $intendedRefactoring = $spec['intended_refactoring'] ?? null;

        // F-7 drift-chain
        $driftChain = $spec['drift_chain'] ?? null;

        // F-14 budget_matrix
        $budgetMatrix = $spec['budget_matrix'] ?? null;

        // F-19 hygiene_exceptions
        $hygieneExceptions = $spec['hygiene_exceptions'] ?? [];

        // Process each cluster
        $clusterIndex = 0;
        $allClusterData = [];
        $pristineTextByCluster = [];

        foreach ($clusterGroups as $clusterId => $clusterCarriers) {
            $clusterIndex++;
            $actualClusterId = $clusterId === '_default' ? 'c' . $clusterIndex : $clusterId;

            // F-23 cross_seed: resolve seed for this cluster
            $clusterSeed = $crossSeedMap[$clusterId] ?? $spec['seed'] ?? null;
            $pristineText = null;

            foreach ($clusterCarriers as $carrier) {
                $carrierSeed = $crossSeedMap[$carrier['cluster'] ?? '_default'] ?? $clusterSeed;
                [$content, $start, $end, $payloadText, $lineMap, $renderInfo] = $this->renderCarrierV2($family, $spec, $carrier, $carrierSeed);
                $rel = 'src/' . $carrier['file'];
                $files[$rel] = $content;

                // F-5 region_sloc: compute duplicate/unique/filler per file
                $regionSlocByFile[$rel] = $this->computeRegionSloc($content, $start, $end, $lineMap);

                // F-2: Build fragments and unique_segments from lineMap
                $fragments = $this->computeFragments($lineMap, $start, $end);
                $uniqueSegments = $this->computeUniqueSegments($lineMap, $start, $end);

                if (($carrier['pristine'] ?? false) && $pristineText === null) {
                    $pristineText = $payloadText;
                    $pristineTextByCluster[$actualClusterId] = $payloadText;
                }

                foreach ($carrier['transforms'] ?? [] as $tr) {
                    $code = (string)$tr['code'];
                    $appliedCodes[$code] = true;

                    // F-18 intensity dial: extract from params or default
                    $intensity = (int)($tr['intensity'] ?? $this->registry->meta($code)['impact'] ?? 1);
                    $params = $tr['params'] ?? [];
                    if (isset($tr['variant'])) {
                        $params['variant'] = $tr['variant'];
                    }

                    $key = $code . '|' . json_encode($params);
                    if (!isset($interferenceAgg[$key])) {
                        $interferenceAgg[$key] = [
                            'code'       => $code,
                            'name'       => $this->registry->name($code),
                            'params'     => (object)$params,
                            'applied_to' => [],
                            'intensity'  => $intensity,
                        ];
                    }
                    if (!in_array($rel, $interferenceAgg[$key]['applied_to'], true)) {
                        $interferenceAgg[$key]['applied_to'][] = $rel;
                    }

                    // F-4: track per-carrier axes
                    if (!isset($perCarrierAxes[$rel])) {
                        $perCarrierAxes[$rel] = [];
                    }
                    if (!in_array($code, $perCarrierAxes[$rel], true)) {
                        $perCarrierAxes[$rel][] = $code;
                    }
                    if (!in_array($code, $allAxes, true)) {
                        $allAxes[] = $code;
                    }
                    $stackDepthMax = max($stackDepthMax, count($transforms));
                }

                // Store cluster member data
                $memberData = [
                    'file'           => $rel,
                    'start_line'     => $start,
                    'end_line'       => $end,
                    'symbol'         => $carrier['clone_symbol'] ?? $spec['clone_symbol'] ?? null,
                    'pristine'       => (bool)($carrier['pristine'] ?? false),
                    'fragments'      => $fragments,
                    'unique_segments' => $uniqueSegments,
                    'group'          => (string)($carrier['group'] ?? 'A'),
                    'seed'           => $carrierSeed,
                ];
                $allClusterData[$actualClusterId]['members'][] = $memberData;
                $allClusterData[$actualClusterId]['group_id'] = (string)($carrier['group'] ?? 'A');
                $allClusterData[$actualClusterId]['clone_symbol'] = $carrier['clone_symbol'] ?? $spec['clone_symbol'] ?? null;
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
        $present = count($allClusterData) > 0;
        $interference = array_values($interferenceAgg);

        // F-3: 2-D difficulty with score_raw (unclamped) + axes
        $scoreRaw = $this->registry->levelBase($level);
        foreach (array_keys($appliedCodes) as $code) {
            $scoreRaw += $this->registry->weight($code);
        }
        $scoreRaw += (int)($spec['intensity_bonus'] ?? 0);
        $scoreClamped = max(0, min(100, $scoreRaw));

        // F-3 axes: breadth (distinct codes), intensity (avg/max), semantic_depth, partiality
        $breadth = count($appliedCodes);
        $intensityMax = 0;
        $intensitySum = 0;
        foreach ($interference as $intf) {
            $intensityMax = max($intensityMax, (int)($intf['intensity'] ?? 1));
            $intensitySum += (int)($intf['intensity'] ?? 1);
        }
        $intensityAvg = count($interference) > 0 ? $intensitySum / count($interference) : 0;
        $semanticDepth = $this->computeSemanticDepth($appliedCodes);
        $duplicationRatio = $this->computeDuplicationRatio($files, $allClusterData);
        $partiality = 1.0 - $duplicationRatio;

        // F-22: application_count vs axis_count
        $applicationCount = 0;
        foreach ($interference as $intf) {
            $applicationCount += count($intf['applied_to']);
        }
        $axisCount = count($allAxes);

        // F-4 interference_profile
        $groupsTouched = [];
        foreach ($allClusterData as $cd) {
            foreach ($cd['members'] ?? [] as $m) {
                $g = $m['group'];
                if (!in_array($g, $groupsTouched, true)) {
                    $groupsTouched[] = $g;
                }
            }
        }
        $symmetricOverlap = $this->computeSymmetricOverlap($perCarrierAxes);
        $pairwiseOverlap = $this->computePairwiseOverlap($perCarrierAxes);
        $combinationDesign = $this->inferCombinationDesign($appliedCodes);

        // F-23 cross_seed check
        $hasCrossSeed = !empty($crossSeedMap);

        // v2 gating: ONLY emit schema_version:2 when recipe explicitly requests it.
        // This preserves deterministic regeneration for all 540 existing v1 sets.
        $usesV2 = !empty($spec['schema_version']) && $spec['schema_version'] >= 2;

        $schemaVersion = $usesV2 ? 2 : 1;

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
            $sloc = $this->sloc($content);
            $entry = [
                'path'            => $rel,
                'role'            => $this->roleOf($rel, $spec),
                'duplicate_group' => $this->groupOf($rel, $spec),
                'sloc'            => $sloc,
            ];
            // F-5: region_sloc per file (v2, only when available)
            if ($usesV2 && isset($regionSlocByFile[$rel])) {
                $entry['region_sloc'] = $regionSlocByFile[$rel];
            }
            $fileEntries[] = $entry;
        }

        $cloneType = $present ? (string)$spec['clone_type'] : 'none';
        $granularity = $present ? ($spec['granularity'] ?? null) : null;
        $clusterCount = count($allClusterData);

        $set = [
            'schema_version' => $schemaVersion,
            'set_id'         => $setId,
            'level'          => $level,
            'level_name'     => (string)$family['level_name'],
            'family'         => $familyName,
            'title'          => (string)$spec['title'],
            'description'    => (string)$spec['description'],
            'language'       => 'php',
            'min_php'        => (string)($spec['min_php'] ?? '8.1'),
            'seed'           => $spec['seed'] ?? null,
            'difficulty_band' => $this->registry->band($scoreClamped),
            'files'          => $fileEntries,
            'duplication'    => [
                'present'     => $present,
                'clone_type'  => $cloneType,
                'granularity' => $granularity,
                'clusters'    => $clusterCount,
                'instances'   => $this->countAllMembers($allClusterData),
            ],
            'interference'   => $interference,
            'difficulty'     => [
                'score'    => $scoreClamped,
                'requires' => $requires,
            ],
            'generator'      => [
                'tool'     => 'gen/build.php',
                'recipe'   => (string)$family['recipe_rel'],
                'version'  => $usesV2 ? '2.0.0' : '1.0.0',
                'rng_seed' => (int)$spec['rng_seed'],
            ],
        ];

        // F-3: score_raw (unclamped) + axes (v2 only)
        if ($usesV2) {
            $set['difficulty']['score_raw'] = $scoreRaw;
            $set['difficulty']['axes'] = [
                'breadth'       => $breadth,
                'intensity'     => round($intensityAvg, 2),
                'intensity_max' => $intensityMax,
                'semantic_depth' => $semanticDepth,
                'partiality'    => round($partiality, 4),
            ];
            // F-22: re-fit hooks
            $set['difficulty']['application_count'] = $applicationCount;
            $set['difficulty']['axis_count'] = $axisCount;
        }

        // F-4: interference_profile (v2 only)
        if ($usesV2) {
            $set['interference_profile'] = [
                'axis_count'            => $axisCount,
                'per_carrier_axes'      => $perCarrierAxes,
                'groups_touched'        => $groupsTouched,
                'stack_depth_max'       => $stackDepthMax,
                'symmetric_overlap'     => $symmetricOverlap,
                'pairwise_overlap'      => round($pairwiseOverlap, 4),
                'combination_design'    => $combinationDesign,
            ];
        }

        // F-21: engine_features (v2)
        if ($usesV2) {
            $engineFeatures = $this->detectEngineFeatures($spec, $allClusterData);
            if ($engineFeatures !== []) {
                $set['engine_features'] = $engineFeatures;
            }
        }

        // F-19: hygiene_exceptions (v2)
        if ($hygieneExceptions !== []) {
            $set['hygiene_exceptions'] = $hygieneExceptions;
        }

        // F-14: budget_matrix index
        if ($budgetMatrix !== null) {
            $set['budget_matrix'] = $budgetMatrix;
        }

        if (isset($spec['expected_detection_by_tool'])) {
            $set['expected_detection_by_tool'] = $spec['expected_detection_by_tool'];
        }
        if (isset($spec['role_composition'])) {
            $set['role_composition'] = (object)$spec['role_composition'];
        }

        // ---- expected.json -------------------------------------------------
        $expected = $this->buildExpected($spec, $allClusterData, $pristineTextByCluster, $nonDuplicates, $usesV2);

        // F-6: intended_refactoring (v2)
        if ($intendedRefactoring !== null) {
            $expected['intended_refactoring'] = $intendedRefactoring;
        }

        // F-7: drift-chain (v2)
        if ($driftChain !== null) {
            $expected['drift_chain'] = $driftChain;
        }

        // F-6: solution/Unified.php for refactorability sets
        if (isset($spec['solution'])) {
            $files['solution/Unified.php'] = $spec['solution'];
        }

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
     * F-23: Resolve cross-seed mapping from spec.
     * @return array<string,string> clusterId => seed
     */
    private function resolveCrossSeedMap(array $spec): array
    {
        $map = [];
        foreach (($spec['carriers'] ?? []) as $carrier) {
            $clusterId = (string)($carrier['cluster'] ?? '_default');
            if (isset($carrier['seed'])) {
                $map[$clusterId] = $carrier['seed'];
            }
        }
        return $map;
    }

    /**
     * F-1: Group carriers by cluster ID, preserving order.
     * @return array<string, list<array>>
     */
    private function groupCarriersByCluster(array $spec): array
    {
        $groups = [];
        foreach (($spec['carriers'] ?? []) as $carrier) {
            $clusterId = (string)($carrier['cluster'] ?? '_default');
            if (!isset($groups[$clusterId])) {
                $groups[$clusterId] = [];
            }
            $groups[$clusterId][] = $carrier;
        }
        return $groups;
    }

    /**
     * F-2: Compute fragments from lineMap.
     * Fragments are lines that are shared (source line >= 0, not -1) and
     * appear across multiple carriers at the same relative position.
     * @return list<array{start_line:int,end_line:int}>
     */
    private function computeFragments(array $lineMap, int $start, int $end): array
    {
        $fragments = [];
        $sourceLines = [];
        $hasInserted = false;
        foreach ($lineMap as $offset => $srcLine) {
            if ($srcLine === -1) {
                $hasInserted = true;
            } elseif ($srcLine >= 0) {
                $sourceLines[$offset] = $srcLine;
            }
        }
        if ($sourceLines === []) {
            return $fragments;
        }
        if (!$hasInserted) {
            // Bug 1 fix: on-disk files only emit fragments when insertions are present
            return [];
        }
        // Bug 2 fix: compute contiguous ranges of non--1 (non-inserted) lines
        $fragments = [];
        $rangeStart = null;
        $rangeEnd = null;
        foreach ($lineMap as $offset => $srcLine) {
            if ($srcLine !== -1) {
                if ($rangeStart === null) {
                    $rangeStart = $offset;
                    $rangeEnd = $offset;
                } else {
                    $rangeEnd = $offset;
                }
            } else {
                if ($rangeStart !== null) {
                    $fragments[] = ['start_line' => $start + $rangeStart, 'end_line' => $start + $rangeEnd];
                    $rangeStart = null;
                    $rangeEnd = null;
                }
            }
        }
        if ($rangeStart !== null) {
            $fragments[] = ['start_line' => $start + $rangeStart, 'end_line' => $start + $rangeEnd];
        }
        return $fragments;
    }

    /**
     * F-2: Compute unique_segments from lineMap.
     * Unique segments are lines that were inserted (-1 in lineMap) or
     * represent carrier-specific additions.
     * @return list<array{start_line:int,end_line:int}>
     */
    private function computeUniqueSegments(array $lineMap, int $start, int $end): array
    {
        $unique = [];
        $insertedOffsets = [];
        foreach ($lineMap as $offset => $srcLine) {
            if ($srcLine === -1) {
                $insertedOffsets[] = $offset;
            }
        }
        if ($insertedOffsets !== []) {
            $minOffset = min($insertedOffsets);
            $maxOffset = max($insertedOffsets);
            $unique[] = [
                'start_line' => $start + $minOffset,
                'end_line'   => $start + $maxOffset,
                'lines'      => $maxOffset - $minOffset + 1,
                'kind'       => 'gap',
                'reason'     => 'inserted by transform',
            ];
        }
        return $unique;
    }

    /**
     * F-8: Expand selector-variant stacking.
     * "cf_early_return+api_table_driven" -> two separate transform entries.
     * @param list<array> $transforms
     * @return list<array>
     */
    private function expandVariantStack(array $transforms): array
    {
        $expanded = [];
        foreach ($transforms as $tr) {
            $variant = (string)($tr['variant'] ?? '');
            if ($variant !== '' && str_contains($variant, '+')) {
                $parts = explode('+', $variant);
                foreach ($parts as $part) {
                    $expanded[] = array_merge($tr, ['variant' => trim($part)]);
                }
            } else {
                $expanded[] = $tr;
            }
        }
        return $expanded;
    }

    /**
     * F-9: Sort transforms by the transform-order contract.
     * Order: selector -> ST-* -> RN/LT/TY/NS -> SY/LEG -> CM-* -> WS-* -> UQ/NZ -> ENC-*
     */
    private function sortByTransformOrder(array $transforms): array
    {
        $sorted = [];
        $buckets = [];
        foreach ($transforms as $tr) {
            $code = (string)($tr['code'] ?? '');
            $kind = $this->categorizeTransform($code);
            $order = self::TRANSFORM_ORDER[$kind] ?? 99;
            if (!isset($buckets[$order])) {
                $buckets[$order] = [];
            }
            $buckets[$order][] = $tr;
        }
        ksort($buckets);
        foreach ($buckets as $bucket) {
            foreach ($bucket as $tr) {
                $sorted[] = $tr;
            }
        }
        return $sorted;
    }

    private function categorizeTransform(string $code): string
    {
        if (str_starts_with($code, 'CF-') || str_starts_with($code, 'API-') ||
            str_starts_with($code, 'SEM-') || str_starts_with($code, 'BL-') ||
            str_starts_with($code, 'EX-') || str_starts_with($code, 'NU-')) {
            return 'selector';
        }
        if (str_starts_with($code, 'ST-')) {
            return 'structure';
        }
        if (str_starts_with($code, 'RN-') || str_starts_with($code, 'LT-') ||
            str_starts_with($code, 'TY-') || str_starts_with($code, 'NS-')) {
            return 'ast_edit';
        }
        if (str_starts_with($code, 'CM-')) {
            return 'comment';
        }
        if (str_starts_with($code, 'WS-')) {
            return 'whitespace';
        }
        if (str_starts_with($code, 'SY-') || str_starts_with($code, 'LEG-')) {
            return 'text';
        }
        if (str_starts_with($code, 'UQ-') || str_starts_with($code, 'NZ-')) {
            return 'wrapper';
        }
        if (str_starts_with($code, 'ENC-')) {
            return 'encoding';
        }
        return 'unknown';
    }

    private function computeSemanticDepth(array $appliedCodes): int
    {
        $semanticCodes = ['SEM-', 'API-', 'CF-', 'BL-', 'EX-'];
        $depth = 0;
        foreach (array_keys($appliedCodes) as $code) {
            foreach ($semanticCodes as $prefix) {
                if (str_starts_with($code, $prefix)) {
                    $depth++;
                    break;
                }
            }
        }
        return min($depth, 8);
    }

    private function computeDuplicationRatio(array $files, array $allClusterData): float
    {
        $totalSloc = 0;
        $duplicateSloc = 0;
        foreach ($files as $rel => $content) {
            $sloc = $this->sloc($content);
            $totalSloc += $sloc;
            if ($this->roleOf($rel, []) === 'carrier') {
                $duplicateSloc += $sloc;
            }
        }
        return $totalSloc > 0 ? $duplicateSloc / $totalSloc : 0.0;
    }

    private function computeSymmetricOverlap(array $perCarrierAxes): int
    {
        $axes = array_values($perCarrierAxes);
        if (count($axes) < 2) {
            return 0;
        }
        $shared = $axes[0];
        for ($i = 1; $i < count($axes); $i++) {
            $shared = array_intersect($shared, $axes[$i]);
        }
        return count($shared);
    }

    private function computePairwiseOverlap(array $perCarrierAxes): float
    {
        $axes = array_values($perCarrierAxes);
        if (count($axes) < 2) {
            return 0.0;
        }
        $totalOverlap = 0;
        $pairs = 0;
        for ($i = 0; $i < count($axes); $i++) {
            for ($j = $i + 1; $j < count($axes); $j++) {
                $intersection = count(array_intersect($axes[$i], $axes[$j]));
                $union = count(array_unique(array_merge($axes[$i], $axes[$j])));
                $totalOverlap += $union > 0 ? $intersection / $union : 0.0;
                $pairs++;
            }
        }
        return $pairs > 0 ? $totalOverlap / $pairs : 0.0;
    }

    private function inferCombinationDesign(array $appliedCodes): string
    {
        $hasAst = false;
        $hasText = false;
        $hasSelector = false;
        foreach (array_keys($appliedCodes) as $code) {
            $kind = $this->categorizeTransform($code);
            if ($kind === 'ast_edit' || $kind === 'structure') {
                $hasAst = true;
            }
            if ($kind === 'whitespace' || $kind === 'comment') {
                $hasText = true;
            }
            if ($kind === 'selector') {
                $hasSelector = true;
            }
        }
        if ($hasSelector && $hasAst) {
            return 'semantic_plus_structural';
        }
        if ($hasAst && $hasText) {
            return 'type2_plus_type1';
        }
        if ($hasSelector) {
            return 'semantic_only';
        }
        if ($hasAst) {
            return 'structural_only';
        }
        if ($hasText) {
            return 'textual_only';
        }
        return 'minimal';
    }

    /**
     * F-5: Compute region_sloc for a file.
     * duplicate = SLOC covered by fragments (shared across carriers)
     * unique = SLOC covered by unique_segments (carrier-specific)
     * filler = sloc - duplicate - unique
     */
    private function computeRegionSloc(string $content, int $start, int $end, array $lineMap): array
    {
        $lines = explode("\n", $content);
        $regionLines = array_slice($lines, $start - 1, $end - $start + 1);
        $sloc = count(array_filter($regionLines, static fn($l) => trim($l) !== ''));

        $insertedCount = 0;
        foreach ($lineMap as $srcLine) {
            if ($srcLine === -1) {
                $insertedCount++;
            }
        }

        $unique = $insertedCount;
        $duplicate = max(0, $sloc - $unique);
        $filler = max(0, $sloc - $duplicate - $unique);

        return [
            'sloc'     => $sloc,
            'duplicate' => $duplicate,
            'unique'   => $unique,
            'filler'   => $filler,
        ];
    }

    private function countAllMembers(array $allClusterData): int
    {
        $total = 0;
        foreach ($allClusterData as $cd) {
            $total += count($cd['members'] ?? []);
        }
        return $total;
    }

    /**
     * Build expected.json with multi-cluster support.
     */
    private function buildExpected(array $spec, array $allClusterData, array $pristineTextByCluster, array $nonDuplicates, bool $usesV2 = false): array
    {
        $setId = (string)$spec['set_id'];
        $normalizedBy = (array)($spec['normalized_by'] ?? ['whitespace', 'comments']);
        $clusters = [];
        $schemaVersion = $usesV2 ? 2 : 1;

        $idx = 0;
        foreach ($allClusterData as $clusterId => $cd) {
            $idx++;
            $actualClusterId = $clusterId;
            $members = $cd['members'] ?? [];
            $pristineText = $pristineTextByCluster[$clusterId] ?? ($members[0]['symbol'] ?? '');
            $tokenHash = substr(hash('sha256', (string)$pristineText), 0, 16);
            $normHash = substr(hash('sha256', implode(' ', PhpTokens::normalize((string)$pristineText, PhpTokens::optsForStages($normalizedBy)))), 0, 16);

            // F-2: Include fragments and unique_segments in members (v2 only)
            $memberEntries = [];
            foreach ($members as $m) {
                $entry = [
                    'file'       => $m['file'],
                    'start_line' => $m['start_line'],
                    'end_line'   => $m['end_line'],
                    'symbol'     => $m['symbol'],
                    'pristine'   => $m['pristine'],
                ];
                // F-2: group, seed, fragments, unique_segments are v2 fields
                if ($usesV2) {
                    $entry['group'] = $m['group'] ?? $cd['group_id'] ?? 'A';
                    $entry['seed'] = $m['seed'] ?? $spec['seed'] ?? null;
                    if (!empty($m['fragments'])) {
                        $entry['fragments'] = $m['fragments'];
                    }
                    if (!empty($m['unique_segments'])) {
                        $entry['unique_segments'] = $m['unique_segments'];
                    }
                }
                $memberEntries[] = $entry;
            }

            $clusterEntry = [
                'id'             => $actualClusterId,
                'group_id'       => $cd['group_id'] ?? 'A',
                'clone_type'     => (string)$spec['clone_type'],
                'granularity'    => (string)$spec['granularity'],
                'normalized_by'  => array_values($normalizedBy),
                'token_hash'     => $tokenHash,
                'normalized_hash' => $normHash,
                'members'        => $memberEntries,
                'detection_expectation' => $spec['detection_expectation'] ?? ['text_based' => true],
                'notes'          => (string)($spec['notes'] ?? ''),
            ];

            // F-7 drift-chain (v2)
            if (isset($spec['drift_chain'])) {
                $clusterEntry['drift_generation'] = $spec['drift_chain']['generations'] ?? null;
                $clusterEntry['pairwise_expectation'] = $spec['drift_chain']['pairwise_expectation'] ?? null;
            }

            $clusters[] = $clusterEntry;
        }

        $expected = [
            'schema_version' => $schemaVersion,
            'set_id'         => $setId,
            'clusters'       => $clusters,
            'non_duplicates' => $nonDuplicates,
            'scoring'        => $spec['scoring'] ?? [
                'line_tolerance'        => 2,
                'member_jaccard_min'    => 0.6,
                'min_members_for_credit' => 2,
            ],
        ];

        // F-17: fragment_aware_scoring flag (v2)
        if ($usesV2 && !empty($spec['fragment_aware_scoring'])) {
            $expected['fragment_aware_scoring'] = true;
        }

        return $expected;
    }

    /**
     * F-21: Detect which v2 engine features are active.
     */
    private function detectEngineFeatures(array $spec, array $allClusterData): array
    {
        $features = [];

        if (count($allClusterData) > 1) {
            $features[] = 'multi_cluster';
        }

        $hasFragments = false;
        foreach ($allClusterData as $cd) {
            foreach ($cd['members'] ?? [] as $m) {
                if (!empty($m['fragments']) || !empty($m['unique_segments'])) {
                    $hasFragments = true;
                    break 2;
                }
            }
        }
        if ($hasFragments) {
            $features[] = 'fragment_emission';
        }

        if (!empty($spec['cross_seed'])) {
            $features[] = 'cross_seed';
        }

        if (!empty($spec['intended_refactoring'])) {
            $features[] = 'intended_refactoring';
        }

        if (!empty($spec['drift_chain'])) {
            $features[] = 'drift_chain';
        }

        if (!empty($spec['hygiene_exceptions'])) {
            $features[] = 'hygiene_exceptions';
        }

        if (count($features) > 0) {
            sort($features);
        }

        return $features;
    }

    /**
     * v2 renderCarrier that returns lineMap and renderInfo.
     *
     * @return array{0:string,1:int,2:int,3:string,4:list<int>,5:array}
     */
    private function renderCarrierV2(array $family, array $spec, array $carrier, ?string $seed): array
    {
        $mode = (string)($carrier['mount'] ?? $spec['mount'] ?? 'method');

        // F-8 selector-variant stacking: resolve composed variants
        $variantParts = $this->resolveComposedVariant($carrier, $spec, $seed);

        if ($variantParts !== null) {
            $payloadLines = $variantParts;
        } elseif (isset($carrier['variant'])) {
            $variantFile = $this->seedsDir() . '/' . $seed . '/variants/' . $carrier['variant'] . '.php';
            $payloadLines = Payload::region($variantFile);
        } else {
            $payloadFile = $this->seedsDir() . '/' . $seed . '/payload.php';
            $payloadLines = Payload::region($payloadFile);
        }

        $payloadLines = $this->mount($payloadLines, $mode, (string)($carrier['visibility'] ?? 'public'));

        // F-10: size-inflation for big-file/tiny-clone
        $payloadLines = $this->applySizeInflation($payloadLines, $spec, $carrier);

        // F-11: inject unique snippets if partial-dup
        $payloadLines = $this->injectUniqueSnippets($payloadLines, $seed, $spec);

        // F-12: block-mount: mount clone block inside larger host method
        $payloadLines = $this->applyBlockMount($payloadLines, $spec, $carrier);

        // Apply the transform chain (line-map aware)
        $input = TransformInput::fromLines($payloadLines);
        $transforms = $this->expandVariantStack($carrier['transforms'] ?? []);
        $transforms = $this->sortByTransformOrder($transforms);

        foreach ($transforms as $tr) {
            $code = (string)$tr['code'];
            if (isset($carrier['variant']) && ($this->registry->meta($code)['kind'] ?? '') === 'selector') {
                continue;
            }
            $t = $this->registry->transform($code);
            $params = $tr['params'] ?? [];
            if (isset($tr['variant'])) {
                $params['variant'] = $tr['variant'];
            }
            $rng = new Rng((int)$seed);
            $result = $t->apply($input, $params, $rng);
            $input = $result->toInput();
        }

        $payloadLines = $input->lines;
        $lineMap = $input->lineMap;
        $payloadText = implode("\n", $payloadLines);

        // Load scaffold, substitute, insert payload at the marker
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

        // Skip leading comment lines so start_line points at the function decl
        $skipLeading = 0;
        $inDocblock = false;
        foreach ($reindented as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            if (!$inDocblock && str_starts_with($trimmed, '/*')) {
                $skipLeading++;
                if (!str_ends_with(rtrim($trimmed), '*/')) {
                    $inDocblock = true;
                }
                continue;
            }
            if (!$inDocblock && str_starts_with($trimmed, '//')) {
                $skipLeading++;
                continue;
            }
            if (!$inDocblock && str_starts_with($trimmed, '/**')) {
                $inDocblock = true;
                $skipLeading++;
                continue;
            }
            if ($inDocblock && str_starts_with($trimmed, '*') && !str_starts_with($trimmed, '*/')) {
                $skipLeading++;
                continue;
            }
            if ($inDocblock && preg_match('/^\s*\*\//', $trimmed)) {
                $skipLeading++;
                $inDocblock = false;
                continue;
            }
            break;
        }

        $carrierLines = array_merge($prefix, $reindented, $suffix);
        $start = count($prefix) + 1 + $skipLeading;
        $end = $start + count($reindented) - 1 - $skipLeading;

        // Adjust lineMap for scaffold prefix offset
        $adjustedMap = [];
        foreach ($lineMap as $offset => $srcLine) {
            $adjustedMap[$offset + count($prefix) + $skipLeading] = $srcLine;
        }

        $content = rtrim(implode("\n", $carrierLines), "\n") . "\n";
        $renderInfo = [
            'prefix_lines'   => count($prefix),
            'skip_leading'   => $skipLeading,
            'marker_indent'  => $markerIndent,
        ];

        return [$content, $start, $end, $payloadText, $adjustedMap, $renderInfo];
    }

    /**
     * F-8: Resolve composed variant (e.g. "cf_early_return+api_table_driven").
     * @return array|null
     */
    private function resolveComposedVariant(array $carrier, array $spec, ?string $seed): ?array
    {
        $variant = (string)($carrier['variant'] ?? '');
        if ($variant === '' || !str_contains($variant, '+')) {
            return null;
        }

        $parts = explode('+', $variant);
        $composed = [];
        foreach ($parts as $part) {
            $part = trim($part);
            $variantFile = $this->seedsDir() . '/' . $seed . '/variants/' . $part . '.php';
            if (!is_file($variantFile)) {
                return null;
            }
            $region = Payload::region($variantFile);
            $composed = array_merge($composed, $region);
        }

        return $composed;
    }

    /**
     * F-10: Apply size-inflation for adv_large_files and adv_many_files.
     */
    private function applySizeInflation(array $payloadLines, array $spec, array $carrier): array
    {
        $family = (string)($spec['family'] ?? '');
        if (!str_starts_with($family, 'adv_')) {
            return $payloadLines;
        }

        $targetSize = (int)($spec['inflate_to_lines'] ?? 0);
        if ($targetSize <= 0) {
            return $payloadLines;
        }

        $currentSize = count($payloadLines);
        if ($currentSize >= $targetSize) {
            return $payloadLines;
        }

        $fillerLines = $this->loadUniqueSnippets($spec['seed'] ?? 'invoice_totals', $targetSize - $currentSize);
        return array_merge($payloadLines, $fillerLines);
    }

    /**
     * F-11: Inject unique snippets for partial-dup sets.
     */
    private function injectUniqueSnippets(array $payloadLines, ?string $seed, array $spec): array
    {
        $granularity = (string)($spec['granularity'] ?? '');
        if ($granularity !== 'partial' && $granularity !== 'block') {
            return $payloadLines;
        }

        $injectCount = (int)($spec['unique_snippet_count'] ?? 0);
        if ($injectCount <= 0) {
            return $payloadLines;
        }

        $snippets = $this->loadUniqueSnippets($seed ?? 'invoice_totals', $injectCount);
        $insertAt = (int)($spec['unique_snippet_position'] ?? (count($payloadLines) / 2));
        $insertAt = max(1, min($insertAt, count($payloadLines)));

        array_splice($payloadLines, $insertAt, 0, $snippets);
        return $payloadLines;
    }

    /**
     * F-11: Load unique snippets from per-seed pool.
     * @return list<string>
     */
    private function loadUniqueSnippets(?string $seed, int $count): array
    {
        $seed ??= 'invoice_totals';
        $dir = $this->repoRoot . '/gen/seeds/' . $seed . '/uniques';
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob($dir . '/*.php') ?: [];
        if ($files === []) {
            return [];
        }

        sort($files);
        $snippets = [];
        for ($i = 0; $i < $count && $i < count($files); $i++) {
            $lines = Payload::region($files[$i]);
            foreach ($lines as $line) {
                $snippets[] = $line;
            }
        }

        return $snippets;
    }

    /**
     * F-12: Apply block-mount for partial-dup with granularity "block".
     */
    private function applyBlockMount(array $payloadLines, array $spec, array $carrier): array
    {
        $granularity = (string)($spec['granularity'] ?? '');
        if ($granularity !== 'block') {
            return $payloadLines;
        }

        $hostLines = $this->loadHostMethod($spec, $carrier);
        if ($hostLines === []) {
            return $payloadLines;
        }

        $insertAt = $this->findHostInsertPoint($hostLines);
        if ($insertAt === -1) {
            return $payloadLines;
        }

        // Pad payload lines for indentation inside host method
        $padded = [];
        foreach ($payloadLines as $line) {
            $padded[] = $line === '' ? '' : '    ' . $line;
        }

        array_splice($hostLines, $insertAt, 0, $padded);
        return $hostLines;
    }

    /**
     * F-12: Load host method for block-mount.
     * @return list<string>
     */
    private function loadHostMethod(array $spec, array $carrier): array
    {
        $hostFile = (string)($carrier['host_method'] ?? null);
        if ($hostFile === '') {
            return [];
        }

        $fullPath = $this->repoRoot . '/gen/seeds/' . ($spec['seed'] ?? 'invoice_totals') . '/hosts/' . $hostFile;
        if (!is_file($fullPath)) {
            return [];
        }

        return Payload::region($fullPath);
    }

    private function findHostInsertPoint(array $hostLines): int
    {
        foreach ($hostLines as $i => $line) {
            if (preg_match('/^\s*\{/', trim($line))) {
                return $i + 1;
            }
        }
        return -1;
    }

    /**
     * @return array{0:string,1:int,2:int,3:string} content, startLine, endLine, payloadText
     * @deprecated Use renderCarrierV2() for new features; this is kept for backward compatibility
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
        // Bug 3 fix: create RNG once per carrier, not per transform iteration
        $rng = new Rng((int)$spec['rng_seed']);
        foreach (($carrier['transforms'] ?? []) as $tr) {
            $code = (string)$tr['code'];
            // Skip selector transforms (CF-03, CF-02, SEM-*, API-*, etc.) if variant
            // was already loaded above; these return VariantSelector instances from
            // the registry and re-running them would fail because variant_file param
            // isn't passed (variant is already in $payloadLines from lines 273-275).
            if (isset($carrier['variant']) && ($this->registry->meta($code)['kind'] ?? '') === 'selector') {
                continue;
            }
            $t = $this->registry->transform($code);
            $params = $tr['params'] ?? [];
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

        // CM-03 (docblock) inserts a docblock before the function signature.
        // CM-09 (license_header) inserts a block comment before the function.
        // The clone region covers the function body, not these comment lines.
        // Skip leading comment lines so start_line points at the function decl.
        $skipLeading = 0;
        $inDocblock = false;
        foreach ($reindented as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            // Skip block comments (/* ... */) - both single-line and multi-line.
            if (!$inDocblock && str_starts_with($trimmed, '/*')) {
                $skipLeading++;
                if (!str_ends_with(rtrim($trimmed), '*/')) {
                    $inDocblock = true; // multi-line block comment started
                }
                continue;
            }
            if (!$inDocblock && str_starts_with($trimmed, '//')) {
                $skipLeading++;
                continue;
            }
            if (!$inDocblock && str_starts_with($trimmed, '/**')) {
                $inDocblock = true;
                $skipLeading++;
                continue;
            }
            if ($inDocblock && str_starts_with($trimmed, '*') && !str_starts_with($trimmed, '*/')) {
                $skipLeading++;
                continue;
            }
            // End of docblock or not a docblock line at all.
            if ($inDocblock && preg_match('/^\s*\*\//', $trimmed)) {
                $skipLeading++; // the closing */
                $inDocblock = false;
                continue;
            }
            break;
        }

        $carrierLines = array_merge($prefix, $reindented, $suffix);
        $start = count($prefix) + 1 + $skipLeading;
        $end = $start + count($reindented) - 1 - $skipLeading;

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
