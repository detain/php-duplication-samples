# PHP Duplication Corpus — Combined Expansion Ideas (master `ideas.md`)

**Status:** COMBINED IDEA CATALOG — a union of three independently-generated idea sets, merged into one. **Nothing was dropped.**
**Sources merged (provenance tags used throughout):** **[F]** = `ideas_fable.md` (the most detailed set — reproduced IN FULL as §1–§11 and used as the spine) · **[O]** = `ideas_opencode.md` · **[C]** = `ideas_copilot.md`. A tag such as **[O]** marks the source an idea came from; **[F+O]**, **[O+C]**, etc. mark ideas the sources agreed on (merged, not duplicated).
**How this file was assembled:** fable is the verbatim base (§1–§11, unchanged). Everything unique to opencode/copilot is folded in as **§12–§26**; where opencode/copilot overlap a fable idea, that section **cross-references the fable ID** (e.g. "extends F:C-4 / F:P-5 / F:R-3 / F:E-6") instead of repeating it. The either/or choices are collected — and now **resolved** (D-A = *do both*; D-B = *rich objects + optional flat mirrors*) — in **§0**; the ⚖️ markers throughout are settled by that table.

---
_The remainder of this front-matter and §1–§11 are `ideas_fable.md` reproduced verbatim._

**Status:** IDEA CATALOG (supersedes nothing; extends `CORPUS_EXPANSION_PLAN.md`)
**Date:** 2026-07-13
**Baseline (measured):** 545 sets / 108 (+5 variant) families / 11 levels / 3 seeds / 101 registry codes (75 implemented, 26 declared-only) / one cluster per set / whole-method clones only / `additionalProperties:false` schemas.
**Relationship to the existing plan:** `CORPUS_EXPANSION_PLAN.md` already proposes 5 mixed-variation concepts, 8 seeds, 10 transform families, 5 structural variations, 6 anti-pattern types, and 25 recipes. **Nothing below restates those.** Where an idea extends one of them, the extension is named explicitly (e.g. "extends E.2.6 Recipe 1"). Everything else is new.

**Idea ID conventions used throughout:**
- `E-*` enabling schema/metadata extensions (§1)
- `C-*` combinatorial families (§2) · `P-*` partial-duplication families (§3) · `R-*` refactorability families (§4) · `G-*` progression machinery (§5) · `K-*` capability-probe families (§6)
- `S-*` seeds (§7) · `T-*` transform codes (§8) · `F-*` generator features (§9)

**Per-family template (used for every content-set idea):** (a) slug + level, (b) concept, (c) example composition, (d) metadata (real field names; JSON snippets for new fields), (e) ladder position, (f) capability + `detection_expectation` + `difficulty.requires`, (g) `intended_refactoring` where applicable.

**Hard invariants every idea respects (from the verifier):** `php -l` clean; realistic filenames with no role/level hints; deterministic re-render (`build --check` byte-identical); positive proof (token equality L1–L4, `tokenEditDistance ≤ edit_budget` L5, behavioral `equivalence_test.php` L6+); negative proof (no accidental ≥40-token normalized Type-2 run = `NEG_MATCH_THRESHOLD`, and **every `token_based:false` cluster must keep all member pairs below a 40-token common run**); traps must not share ≥40-token runs with carriers; detector-default calibration phpcpd ≈70 tokens/5 lines, jscpd ≈50 tokens, PMD-CPD ≈100 tokens, Simian ≈6 lines.

---

## 0. Decisions — resolved ✅ (was: open either/or calls)

**Resolved 2026-07-13.** Both options for each fork are written up in full below; these resolutions select or combine them. Every ⚖️ marker elsewhere is settled by this table.

| # | Decision | Resolution |
|---|---|---|
| **D-A** | Level-ladder architecture | ✅ **Do both.** Keep **L00–L10** and add new **families + 2-D `difficulty.axes` + basic→combined rungs** (option-A machinery, applied across *all* levels) **AND** extend the ladder with opencode's new **tiers L11–L20** (option B — §22). Result: the ladder runs **L00–L20**; tiers give coarse thematic progression, families/axes/rungs give the fine intra-level grade. |
| **D-B** | New-metadata field design | ✅ **Rich objects + optional flat mirrors.** fable's nested objects (`region_sloc`, `uniqueness_budget`, `intended_refactoring`, `interference_profile`, 2-D `difficulty.axes`) are **canonical** (generator-emitted + verifier-checked); opencode's flat fields (§26) are optional **derived** mirrors for filtering/sorting. |
| **D-C** | Home for partial-duplication (`pd_*`) | ✅ Follows D-A → **families within L05/L10 + the `partiality` difficulty axis**, *not* a dedicated tier (the adopted L11–L20 scheme already gives L11 to Deep-Rename Stacks). Read fable §3's "L11" tag on `pd_*` as **"L05-extension"**. |
| **D-D** | Keep provenance tags `[F]/[O]/[C]` | ✅ **Keep** — traceability. |

**Consequences applied throughout:** §22's L11–L20 tiers are **adopted** (no longer "alternative"), coexisting with the §5 families/axes/rungs machinery — which now applies within L11–L20 exactly as within L00–L10. fable's §2–§6 families keep their L00–L10 tags; the L11–L20 tiers are added on top and each cross-references its fable equivalent. Evocative set names (§21) keep titles + slugs; scope stays a range (~920 [F] → 1000+ [C], §24); the **old** `samples/` tree stays **out of scope** (expansion targets `testsets/` only, per the master plan's non-goal).

---

## Table of contents

1. [Enabling metadata & schema extensions](#1-enabling-metadata--schema-extensions-the-backbone) — E-1…E-18, V-1…V-10
2. [Combinatorial variation sets (goal 1)](#2-combinatorial-variation-sets-goal-1) — C-1…C-18
3. [Graduated partial-duplication sets (goal 2)](#3-graduated-partial-duplication-sets-goal-2) — P-1…P-14
4. [Refactorability-labeled sets (goal 3)](#4-refactorability-labeled-sets-goal-3) — R-1…R-20
5. [Basic → advanced → combined progression (goal 4)](#5-basic--advanced--combined-progression-goal-4) — G-1…G-9
6. [Detector-capability probe sets (goal 5)](#6-detector-capability-probe-sets-goal-5) — K-1…K-34
7. [New seeds & domains](#7-new-seeds--domains) — S-1…S-26
8. [New transform axes / registry codes](#8-new-transform-axes--registry-codes) — T-groups, ~50 codes
9. [Generator / infrastructure features](#9-generator--infrastructure-features) — F-1…F-24
10. [Organizing principles & suggested build order](#10-organizing-principles--suggested-build-order)
11. [Appendix: idea inventory & counts](#11-appendix-idea-inventory--counts)

**Combined additions — folded in from [O] opencode and [C] copilot (§12–§26):**

12. Cluster-topology, instance-count & structural variations — [O,C]
13. Granularity ladder (expression → file → multi-file) — [O,C]
14. Size-class matrix (XS–XXL) & threshold ladders — [O,C]
15. Clone genealogy & drift taxonomy — [C,O]
16. Domain & cross-semantic equivalence (+ seed/domain union) — [O,C]
17. Interference, noise & distractor sophistication — [O,C]
18. Edge-case & adversarial additions — [O]
19. Hidden-challenge coverage map (60 types) — [C]
20. Per-category family expansion lists (copilot A–N) — [C]
21. Named "flagship" set ideas — [O]
22. Adopted extra tiers L11–L20 — [O] (per §0 D-A: do both → ladder is L00–L20)
23. Combinatorial & refactorability additions — [O,C]
24. Phased build plan, scope & prioritization — [C,O]
25. Negative controls — [O]
26. Metadata additions from [O]/[C] & reconciliation — [O,C] (see §0 D-B)

---

# 1. Enabling metadata & schema extensions (the backbone)

Everything in §2–§6 that tracks budgets, refactorings, stack depth, rungs, fragments, or multi-cluster topology is **rejected today** by `testsets/schema/set.schema.json` / `expected.schema.json` because both use `additionalProperties:false` at every level. This section is the single prerequisite for goals 2, 3, and 4. Design rules:

- **Bump `schema_version` from `const 1` to `enum [1, 2]`.** Every new field is OPTIONAL, so all 545 existing sets stay valid as v1; new sets emit v2. `gen/lib/JsonSchema.php` already supports the needed keywords (type/required/enum/pattern/min-max/additionalProperties) — only the schema JSON files and `SetBuilder` change.
- **Ground truth stays generator-emitted.** Every new numeric field below (line counts, segment counts, hashes, ratios) is computed by `SetBuilder` during render from the `lineMap` (`-1` = inserted line), never hand-typed — same discipline as `start_line`/`end_line` today.
- **Each field gets a verifier check** (V-1…V-10 at the end of this section) so the new metadata is as trustworthy as the old.

## E-1 · `files[].region_sloc` — per-file duplicate/unique/filler line accounting (set.json)

The core enabler for goal 2. Today `files[].sloc` is whole-file only. Add an optional object splitting a carrier's SLOC three ways: lines inside the cluster-member region that carry **shared (duplicate) logic**, lines inside the region that are **unique to this file** (tracked unique segments), and **filler** (scaffold code outside the member region — unique by design).

```json
{
  "path": "src/InvoiceMath.php",
  "role": "carrier",
  "duplicate_group": "A",
  "sloc": 74,
  "region_sloc": { "duplicate": 18, "unique": 6, "filler": 50 }
}
```

Invariants (verifier-enforced): `duplicate + unique` = SLOC of `[start_line, end_line]` of this file's cluster member; `duplicate` = SLOC covered by `members[].fragments[]`; `unique` = SLOC covered by `members[].unique_segments[]` (E-4); `filler = sloc − duplicate − unique`. For pre-v2 sets and pure whole-method clones, `unique` is 0 and `duplicate` equals the member SLOC.

## E-2 · `duplication.uniqueness_budget` — declared MIN/MAX unique-code budget + actuals (set.json)

The user's tracked-budget requirement, verbatim: *"this set: 3 files, each with 4–8 lines of unique code across 2 segments, rest duplicated."* The recipe **declares** the budget; the generator **emits** the actuals; the verifier asserts conformance.

```json
"duplication": {
  "present": true,
  "clone_type": "type-3",
  "granularity": "method",
  "clusters": 1,
  "instances": 3,
  "duplication_ratio": 0.71,
  "uniqueness_budget": {
    "per_carrier_unique_lines": { "min": 4, "max": 8 },
    "per_carrier_segments":     { "min": 2, "max": 2 },
    "carriers_with_unique_code": 3,
    "actual": [
      { "file": "src/InvoiceMath.php", "unique_lines": 6, "segments": 2 },
      { "file": "src/QuoteMath.php",   "unique_lines": 4, "segments": 2 },
      { "file": "src/OrderMath.php",   "unique_lines": 8, "segments": 2 }
    ]
  }
}
```

- `per_carrier_unique_lines.min/max` — the tracked MIN/MAX length budget (in SLOC) of unique code per carrier.
- `per_carrier_segments.min/max` — the tracked count of **distinct varied-code segments** per carrier.
- `carriers_with_unique_code` — the "N-of-M files carry unique code" knob (0 = pure clone set; 1..instances).
- `actual[]` — generator-emitted per-file truth. `unique_lines` = Σ `unique_segments[].lines` for that file; `segments` = `count(unique_segments)`.

## E-3 · `duplication.duplication_ratio` — the graduated-partial scalar (set.json)

`round(Σ duplicate region_sloc ÷ Σ (duplicate+unique) region_sloc, 2)` across carriers — i.e. how much of the *logical region* is shared. `1.0` = whole-region clone (all current sets), `0.0` = no duplication (L0). This single number makes the **no-dup → some-dup → mostly-dup arc** sortable and lets the bench bucket results by partiality. (Shown inline in the E-2 snippet.)

## E-4 · `members[].unique_segments[]` — the inverse of `fragments[]` (expected.json)

`fragments[]` (already in the schema, never emitted) lists the sub-ranges that **match**; `unique_segments[]` lists the sub-ranges inside `[start_line, end_line]` that are **unique to this member**. Both are emitted together by the fragment engine (F-3); they must tile the member region exactly.

```json
{
  "file": "src/InvoiceMath.php",
  "start_line": 21, "end_line": 46, "symbol": "computeTotals", "pristine": false,
  "fragments": [
    { "start_line": 21, "end_line": 29 },
    { "start_line": 34, "end_line": 46 }
  ],
  "unique_segments": [
    { "start_line": 30, "end_line": 33, "lines": 4, "kind": "unique_logic",
      "reason": "per-currency rounding step present only in this carrier" }
  ]
}
```

`kind` enum: `unique_logic` (genuine divergent business code — UQ-* transforms, §8), `noise` (semantically inert insertions — ST-01/ST-02/NZ-*), `gap` (deleted-in-this-member content — ST-06). Distinguishing these lets the bench answer "does the tool skip *noise* but respect *logic* boundaries?" (family P-9).

## E-5 · Cluster rollups: `varied_segment_count`, `region_sloc` (expected.json, per cluster)

```json
"clusters": [{
  "id": "c1",
  "varied_segment_count": 5,
  "region_sloc": { "duplicate": 54, "unique": 18 },
  "...": "existing required fields unchanged"
}]
```

`varied_segment_count` = Σ over members of `count(unique_segments)` — the set-level "how many distinct varied segments interrupt this clone" number the user asked to track and vary. `region_sloc` is the cluster-level rollup of E-1.

## E-6 · `intended_refactoring` — the goal-3 object (expected.json, per cluster; summary echo in set.json)

Records **the refactoring the set was deliberately built to admit**: its kind, the unified signature, the parameters/"holes" that vary per member, which members collapse, and how hard the unification is. Lives on the cluster (it is ground truth *about the cluster*); `set.json` gets a 3-field echo for cheap filtering.

```json
"intended_refactoring": {
  "kind": "parameter_toggle_boolean",
  "advisability": "recommended",
  "unified_symbol": "computeDocumentTotals",
  "unified_signature": "function computeDocumentTotals(array $lineItems, float $taxRate, float $discountRate, bool $includeShipping): array",
  "holes": [
    {
      "hole_id": "h1",
      "kind": "behavior_toggle",
      "name": "includeShipping",
      "php_type": "bool",
      "site": { "file": "src/InvoiceMath.php", "start_line": 33, "end_line": 34 },
      "values_by_member": { "src/InvoiceMath.php": "true", "src/QuoteMath.php": "false", "src/OrderMath.php": "true" }
    }
  ],
  "hole_count": 1,
  "collapses": [
    { "file": "src/InvoiceMath.php", "symbol": "computeTotals" },
    { "file": "src/QuoteMath.php",   "symbol": "computeQuote" },
    { "file": "src/OrderMath.php",   "symbol": "computeOrderTotals" }
  ],
  "residual_unique_lines": 0,
  "refactor_difficulty": "moderate",
  "solution_path": "solution/Unified.php",
  "proof": "solution/refactor_proof.php"
}
```

**`kind` enum (17 values):** `parameterize_literal` · `parameter_toggle_boolean` · `parameter_toggle_enum` · `introduce_parameter_object` · `extract_function` · `extract_method_with_holes` · `extract_closure_hole` (varying part passed as `callable`/`Closure`) · `strategy_object` · `template_method` · `pull_up_method` · `pull_up_field` · `replace_conditional_with_polymorphism` · `consolidate_conditional_fragments` · `extract_trait` · `extract_base_class` · `table_driven_config` · `none` (deliberate anti-refactor label, see R-17).

**`holes[].kind` enum:** `literal` (a differing scalar — pairs with LT-01/LT-02), `identifier` (differing symbol name only), `behavior_toggle` (a block present/absent or A-vs-B — the user's canonical boolean-toggle case), `expression` (a differing sub-expression → callable hole), `strategy` (a whole differing algorithm step), `type` (differing type hint → generics-style hole).

**`advisability` enum:** `recommended` | `neutral` | `inadvisable` — enables *anti-refactor* ground truth (duplication that must NOT be unified because the domains merely coincide; R-17), so refactor-suggesting tools can be scored on restraint as well as recall.

**`refactor_difficulty` enum:** `trivial` (0–1 literal holes) · `easy` (2–3 literal/identifier holes) · `moderate` (any behavior_toggle, or 4–6 holes) · `hard` (expression/strategy holes, cross-file collapse) · `architectural` (class-hierarchy surgery: pull-up, polymorphism, template-method).

`solution_path`/`proof` point at a per-set `solution/` directory **outside `src/`** (tools never see it) holding the worked unified implementation and a runnable proof harness (F-12): the proof instantiates the unified code with each member's `values_by_member` and asserts behavioral equality with each original member — the refactor label is *mechanically verified*, in the same spirit as `equivalence_test.php`.

set.json echo:

```json
"refactoring": { "kind": "parameter_toggle_boolean", "hole_count": 1, "advisability": "recommended" }
```

## E-7 · `interference_profile` — stack-depth/axis-count accounting for goal 1 (set.json)

`interference[]` already lists each applied code, but nothing *counts* the stack. Add a generator-computed summary:

```json
"interference_profile": {
  "axis_count": 14,
  "distinct_codes": ["WS-03","WS-05","WS-06","WS-11","CM-01","CM-03","CM-09","RN-01","RN-02","RN-05","LT-01","NS-01","ST-01","ST-09"],
  "groups_touched": ["WS","CM","RN","LT","NS","ST"],
  "per_carrier_axes": { "src/LegacyCalc.php": 4, "src/PortedCalc.php": 9, "src/DriftedCalc.php": 14 },
  "stack_depth_max": 14,
  "symmetric": false,
  "pack": "legacy_wordpress"
}
```

`axis_count` = |distinct codes| set-wide; `per_carrier_axes` = per-file chain length (asymmetric stacks are first-class); `pack` names the transform-pack macro if one was used (F-7). Verifier recomputes all of it from `interference[]`.

## E-8 · Two-dimensional difficulty — fix score saturation (set.json)

`difficulty.score` clamps at 100 and `L09-mix_4plus-001` already sits there, so an 8-axis and a 20-axis set are indistinguishable. Keep `score` (band derivation unchanged, backward compatible) and add:

```json
"difficulty": {
  "score": 100,
  "score_raw": 231,
  "axes": {
    "breadth": 14,
    "intensity": 27,
    "semantic_depth": 3,
    "partiality": 0.29
  },
  "requires": ["whitespace_normalization", "comment_stripping", "identifier_canonicalization", "literal_abstraction", "ast_canonicalization"]
}
```

- `score_raw` — the **unclamped** `level_base + Σ weights + intensity_bonus`. Preserves total ordering past 100 (a 20-axis stack at raw 231 outranks a 10-axis stack at raw 140 even though both band as `expert`).
- `axes.breadth` — distinct interference codes (== `interference_profile.axis_count`).
- `axes.intensity` — Σ of per-code intensity dials (each transform param set maps to a 0–8 dial; F-18). Separates "many axes, gently" from "few axes, brutally".
- `axes.semantic_depth` — max clone-type distance introduced: 0 = type-1, 1 = type-2, 2 = type-3, 3 = type-4, 4 = semantic-domain.
- `axes.partiality` — `1 − duplication_ratio` (E-3): how much unique code interrupts the clone.

Four orthogonal numbers = the axis-breadth × intensity grid of goal 4, plus the partiality dimension of goal 2, all sortable independently.

## E-9 · `progression` — rung labels and the walkable ladder (set.json)

```json
"progression": {
  "rung": 3,
  "rung_label": "moderate",
  "axis_arc": "rename",
  "grid_position": { "axis": "RN", "rung": 3 },
  "prev_set": "L04-rn_locals-002",
  "next_set": "L04-rn_locals-004",
  "prerequisites": ["L02-ws_blank_inside-001", "L03-cm_line_added-001"],
  "bridge_pair": null
}
```

- `rung` 1–5 with `rung_label` enum `minimal | light | moderate | heavy | combined` — the intra-family sub-difficulty ordering that F.4 flags as missing. Rung 1 is always a **minimal pair** (one code, one occurrence, smallest param).
- `axis_arc` — names the cross-level arc this set belongs to (e.g. `rename` runs L04 rn_* → L09 mix_rn_ws → capstone; §5 defines 16 arcs, one per registry group).
- `prev_set` / `next_set` — the ordered walk, machine-followable; `prerequisites` — DAG edges: "if you fail here but passed these, the *delta* capability is the diagnosis."
- `bridge_pair` — for boundary sets that differ from a set in the adjacent level by exactly one axis (G-6).

## E-10 · Multi-cluster topology (set.json + expected.json)

`expected.json.clusters[]` is already an array and `duplication.clusters` an int — only the builder is single-cluster. Add the descriptive layer:

```json
"duplication": { "clusters": 3, "cluster_topology": "disjoint", "cross_seed": true }
```

`cluster_topology` enum: `single` · `disjoint` (independent regions) · `nested` (one inside another) · `overlapping` (shared lines) · `chained` (genealogy A→B→C→D) · `braided` (two clusters' fragments interleaved in the same files, P-12). Per cluster in expected.json:

```json
{ "id": "c2", "relation": { "type": "nested", "with": "c1" }, "...": "..." }
```

`cross_seed: true` marks clusters drawn from different seed domains living in one set (needs F-2).

## E-11 · Activate `fragments[]`, `drift_generation`, and add `pairwise_expectation` (expected.json)

- `fragments[]` / `drift_generation` exist in the schema but are never emitted (F.0-4). F-3/F-5 make `SetBuilder` emit them; no schema change needed — but add `fragments[].lines` (int, convenience) and legalize `unique_segments` (E-4) beside them.
- **New `pairwise_expectation`** (optional, per cluster): drift chains and gradient stacks have *per-pair* difficulty — A↔B may be token-detectable while A↔D is not. The single `detection_expectation` cannot express that.

```json
"pairwise_expectation": [
  { "a": "src/OriginCalc.php", "b": "src/FirstCopy.php",  "token_based": true,  "ast_based": true },
  { "a": "src/OriginCalc.php", "b": "src/FourthCopy.php", "token_based": false, "ast_based": true }
]
```

Keys mirror the `detection_expectation` booleans; omitted keys inherit the cluster default. The bench's member-pair matcher (D.4) can then score per-pair recall (F-17).

## E-12 · `capability_probe` — probe-set marker (set.json)

For §6's isolation sets: declares which ONE capability the set isolates, its control twin, and what failure means.

```json
"capability_probe": {
  "capability": "commutative_reordering",
  "isolated": true,
  "control_set": "L06-probe_commutative-001",
  "pass_criterion": "cluster c1 detected with member_jaccard >= 0.6",
  "fail_meaning": "tool does not normalize operand order of commutative operators"
}
```

The Tool Capability Profile generator (F-20) turns {control passes, probe fails} into a crisp per-capability verdict.

## E-13 · `scoring` extensions — fragment- and refactor-aware credit (expected.json)

```json
"scoring": {
  "line_tolerance": 2,
  "member_jaccard_min": 0.6,
  "min_members_for_credit": 2,
  "fragment_scoring": "fragments_union",
  "unique_segment_penalty": true,
  "pairwise_credit": false
}
```

- `fragment_scoring` enum `whole_member | fragments_union` — for partial-dup sets, recall is computed against the union of `fragments[]`, and a reported region is *clipped* against `unique_segments` before Jaccard.
- `unique_segment_penalty` — when true, a tool that reports a member's `unique_segments` as part of the clone takes a boundary-accuracy penalty (it claimed non-shared code was shared).
- `pairwise_credit` — score per member-pair (needed with E-11) instead of per-cluster.

## E-14 · `difficulty.requires` vocabulary additions

The 12-value enum maps 1:1 to the challenges doc's normalizations but cannot express goals 2/3/5 needs. Add six values (schema enum + registry `requires`):

| New value | Meaning | First consumers |
|---|---|---|
| `encoding_normalization` | BOM/NBSP/EOL/escape/heredoc canonicalization | ENC-01..07 sets, K-24..K-27 |
| `gap_tolerance` | match clones interrupted by insertions/deletions | ST-06/07, UQ-*, all §3 |
| `boundary_precision` | report region edges within tolerance | K-29/K-30, P-4 |
| `noise_filtering` | discount semantically inert instrumentation | NZ-*, C-12, P-9 |
| `multi_cluster_reporting` | report ALL independent clusters, not the best one | E-10 sets, C-15, P-12 |
| `refactoring_synthesis` | derive a unified abstraction + holes | all §4 (rf_*) |

## E-15 · `files[].role` addition: `support`

Refactor families need hierarchy context files — the common parent class in `pull_up_method`, the interface in `strategy_object`. They are neither `carrier` (no cluster member), `distractor` (not bait), nor `clean` (not unrelated filler — they are *referenced* by carriers). Add enum value `support` with `duplicate_group: null`. `verify.php` role-count check learns the fourth role via `role_composition.support`.

## E-16 · Registry metadata additions (`gen/transforms/registry.json`)

Per-code new optional keys: `min_php` (e.g. `"8.1"` for first-class callable syntax), `max_php` (legacy-only constructs), `probe_family` (the K-* family that unit-tests this code — the registry-coverage gate F-15 reads this), `composable_with` (codes known-safe to stack; interaction-tested by C-16), `pack_memberships` (which style packs include it, F-7).

## E-17 · `generator` additions

`generator.intensity_dial` (int 0–8, the per-set dial that already feeds `intensity_bonus`, now emitted), `generator.packs` (array of pack names expanded), `generator.engine_features` (array like `["fragments","multi_cluster"]` — lets tooling filter sets needing v2-aware scoring).

## E-18 · Set-level provenance links

`set.json.related` (optional): `{ "variant_of": "L08-sem_scatter-001", "old_corpus_ref": "samples/algorithm/1", "mirror_of": null }`. The `old_corpus_ref` finally connects `testsets/` to the 707-scenario hand-written corpus (which already contains worked refactorings the R-* families reuse as `solution/` inspiration).

## V-1…V-10 · New verifier checks accompanying E-1…E-18

| # | Check | Guards |
|---|---|---|
| V-1 | `region_sloc` recompute: duplicate/unique/filler re-derived from fragments ∪ unique_segments ∪ member ranges; must match byte-for-byte | E-1, E-5 |
| V-2 | Budget conformance: every `actual[]` entry within declared `min/max` for lines AND segments; `carriers_with_unique_code` correct | E-2 |
| V-3 | Tiling: fragments + unique_segments exactly tile `[start_line, end_line]`, no overlap, no gap | E-4 |
| V-4 | Fragment positive proof: normalized token streams equal **per aligned fragment pair** (not per whole member) for L1–L4-style axes; `tokenEditDistance` per fragment ≤ per-fragment budget at L5 | E-4, E-13 |
| V-5 | Unique-segment distinctness: no unique_segment shares a ≥40-token normalized run with any other file's region (unique code must not accidentally clone) | E-4 |
| V-6 | Refactor proof: `solution/refactor_proof.php` exits 0 — unified code + per-member hole values reproduce every member's behavior on the seed input matrix | E-6 |
| V-7 | Profile recompute: `interference_profile`, `difficulty.axes`, `score_raw` re-derived from `interference[]` + registry weights | E-7, E-8 |
| V-8 | Progression sanity: `prev_set`/`next_set`/`prerequisites` all exist; rung strictly increases along `next_set` within a family; no DAG cycles | E-9 |
| V-9 | Multi-cluster hygiene: cross-cluster member pairs share no ≥40-token run unless `relation` declares nesting/overlap; `duplication.clusters == count(clusters)` at last | E-10 |
| V-10 | Pairwise consistency: `pairwise_expectation` files are member files; any pair omitted inherits cluster default; `token_based:false` pairs obey the 40-token distinctness guard while `token_based:true` pairs must SHARE a ≥40-token run | E-11 |

---

# 2. Combinatorial variation sets (goal 1)

**The arithmetic that makes 10–20 axes feasible today:** the implemented text+ast codes alone are 45 (WS-01..12 = 12, CM-01..11 = 11, RN-01..05 = 5, LT-01..04 = 4, TY-01..02 = 2, NS-01..02 = 2, ST-01..09 = 9). Text and ast transforms **layer freely** (they edit tokens/lines through the line map), and `SetBuilder` already applies text/ast chains *after* a pre-loaded selector variant. So a 20-axis stack = at most 1 selector variant + 19 text/ast codes — **no new transform is required for the first combinatorial wave.** What IS required: E-7/E-8 metadata (else the stacks are invisible in difficulty), and discipline about the two legality guards:

- **Guard A (positive proof):** stay within `edit_budget` at L5-style proofs, or declare `normalized_by` stages that genuinely equalize (L1–L4-style), or ship behavioral proof via the seed's `equivalence_test.php` (L6+ style). For big stacks the recipe should raise `edit_budget` explicitly (e.g. 24–40) — that number is itself graduated metadata.
- **Guard B (negative proof):** if the cluster declares `token_based:false`, EVERY member pair must stay under a 40-token common normalized run. Deep stacks make this easy to satisfy; shallow asymmetric stacks (pristine carrier present) make it easy to *violate* — so pristine-vs-heavy families usually declare `token_based:true` and let the heavy pair be the stress case via `pairwise_expectation` (E-11).

**Ordering discipline for stacked chains** (prevents transforms trampling each other; encode as a builder assertion, F-6): selector variant → ST-* structure edits → RN/LT/TY/NS ast edits → CM-* comments → WS-* whitespace → ENC-* bytes. Rationale: ast transforms need parseable, structurally final code; comment insertion must precede whitespace so blank-line counts include them; encoding is byte-level last.

---

## C-1 · `mix_t1_blizzard` (L9) — 20 stacked axes that are STILL a Type-1 clone

**(b) Concept.** The purest possible demonstration of "many variations, still duplicate": stack **every whitespace and comment code at once** — WS-01, WS-02, WS-03, WS-04, WS-05, WS-06, WS-07, WS-08, WS-09, WS-11, WS-12 + CM-01, CM-02, CM-03, CM-04, CM-06, CM-07, CM-08, CM-09, CM-10 = **20 distinct codes** — and the members remain *token-identical after whitespace+comment stripping*. A tool with only two normalizations should score 100% here; a tool that panics at visual dissimilarity fails. This is the "maximum interference, minimum required capability" corner of the axis×capability plane, which no current family occupies (mix_ws_cm stacks ~2–4).

**(c) Composition.** Seed `invoice_totals`, 3 carriers: A = 6 WS codes; B = 6 CM codes + 3 WS; C = all 20. Distractor `nearmiss_invoice` (margin math, same vocabulary); clean `clean_billing`. Set 001→005 walk `axis_count` 5/8/12/16/20 (rung = E-9).

**(d) Metadata.** `interference[]` lists all applied codes with real params (`{"code":"WS-05","params":{"width":2}}` …); `interference_profile.axis_count: 20`, `groups_touched: ["WS","CM"]`; `difficulty.axes: {breadth: 20, intensity: 22, semantic_depth: 0, partiality: 0}`; `score_raw` ≈ 78+Σ(2..5)·20 → ~140, `score` clamps 100 — the flagship saturation-fix showcase. `normalized_by: ["whitespace","comments"]`; cluster `clone_type: "type-1"`.

**(e) Ladder.** Rung-5 terminus of BOTH the whitespace and comments arcs (`axis_arc: "whitespace"` + listed in `comments` arc via `prerequisites`).

**(f) Capability.** `requires: ["whitespace_normalization","comment_stripping"]` — deliberately nothing else. `detection_expectation: {text_based: false, token_based: true, ast_based: true, metric_based: true, semantic: true}`.

## C-2 · `mix_t2_saturation` (L9) — full Type-2 stack: 10–20 codes, equal after rename/literal/type/namespace abstraction

**(b)** Every Type-2 axis at once: RN-01+RN-02+RN-03+RN-04+RN-05, LT-01+LT-02+LT-03+LT-04, TY-01+TY-02, NS-01+NS-02 (13 codes) plus 4–7 WS/CM to taste → **15–20 axes** whose fixpoint under identifier/literal/type/namespace canonicalization is exact equality. The set answers: "does your Type-2 normalization pipeline COMPOSE, or does each normalizer assume the others' output?"

**(c)** Seed `csv_import` (`parseRow` — string-literal-rich, exercises LT-02 well). A = renames only (5 codes); B = literals+types+namespaces (8); C = all 13 + WS-03/WS-06/CM-03 (16). Distractor: `nearmiss_csv`.

**(d)** `normalized_by: ["whitespace","comments","identifiers","literals","types","namespaces","case_convention"]` — the first family to use ALL token-canonicalizable stages at once; `difficulty.axes.breadth: 16`, `semantic_depth: 1`.

**(e)** Rung-5 terminus of `rename` + `literals` arcs; prerequisite for C-4.

**(f)** `requires: ["whitespace_normalization","comment_stripping","identifier_canonicalization","literal_abstraction"]`. `detection_expectation: {text_based: false, token_based: true, ast_based: true, metric_based: true, semantic: true}` — token_based stays true because Type-2 normalization is a token-level capability; positive proof via `PhpTokens::optsForStages` equality still works.

## C-3 · `mix_t3_torrent` (L9) — Type-2 stack + all four safe ST insertions, 18–20 axes

**(b)** C-2's stack plus ST-01 (logging), ST-02 (dead code), ST-03 (functional no-op), ST-05 (reorder independent), ST-09 (expr tweak) → 18–20 axes with a **declared, graduated `edit_budget`** (24/32/40 across sets — itself the intensity dial). The clone is Type-3: gap-tolerant token tools (phpcpd `--fuzzy`) and AST matchers should survive; strict tokenizers die. This is the direct 10–20-axis fulfillment of the user's request in its most realistic shape: "years of independent edits."

**(c)** Seed `invoice_totals`. A = 6 axes/budget 8; B = 12 axes/budget 16; C = 20 axes/budget 28. `pairwise_expectation`: A↔B token_based true; A↔C token_based false (kept legal by Guard B: C's insertions break all ≥40 runs vs A); cluster-level token_based false with per-pair overrides.

**(d)** `difficulty.axes: {breadth: 20, intensity: 30+, semantic_depth: 2}`; `edit_budget: 28` surfaced in set.json `generator` echo (new optional `generator.edit_budget`).

**(e)** Rung-5 of the `statement` arc; prerequisite: C-2 and L05 st_combined.

**(f)** `requires: [identifier_canonicalization, literal_abstraction, ast_canonicalization, deadcode_elimination, gap_tolerance]`. `{text_based: false, token_based: false, ast_based: true, metric_based: true, semantic: true}`.

## C-4 · `mix_full_spectrum` (L10 capstone) — the 20-axis, 10-group flagship

**(b)** One code from **every** implemented group plus doubles: 1 selector (CF-05 early_return via variant) + WS-03/05/06/11 + CM-03/07/09 + RN-01/02/05 + LT-01/02 + TY-01 + NS-01 + ST-01/05/09 + ENC-04/06 (once implemented) + NZ-01 (once implemented) = **20 codes across 10 groups**. The "can your whole pipeline run in one pass" capstone. Until ENC/NZ land, a 17-axis variant ships with groups WS/CM/RN/LT/TY/NS/ST/CF.

**(c)** Seed `access_guard` (richest variant pool). Carrier A = pristine guard-clause body + 5 light axes; B = `cf_early_return.php` variant + 10 axes; C = `cf_match_guard.php` variant + 17–20 axes. Behavioral proof via the existing 56-combo `equivalence_test.php`. Guard B honored: variants already differ structurally, stacks push runs far below 40.

**(d)** Everything from E-7/E-8 at maximum: `axis_count: 20`, `groups_touched: 10`, `score_raw ≈ 82+~230 = 312` — the corpus's highest raw difficulty, finally *representable*.

**(e)** Terminal rung of the global walk (G-8's last node). `difficulty_band: expert`.

**(f)** `requires`: 8+ values incl. `controlflow_normalization, encoding_normalization, noise_filtering, gap_tolerance`. `{text_based: false, token_based: false, ast_based: false, metric_based: true, semantic: true}` — an honest "only metric/semantic tools should survive" ceiling marker, with `pairwise_expectation` granting ast_based true for the A↔B pair.

## C-5 · `mix_disjoint_stacks` (L9) — every pair differs on ~12 axes, but NO axis is shared

**(b)** Carriers receive **disjoint** axis sets: A = pure whitespace profile (WS-03/05/06/08/11/12), B = pure rename/literal profile (RN-01/02/04/05 + LT-01/02), C = pure comment/statement profile (CM-01/03/07/09 + ST-01/05). Set-wide 18 distinct codes, but each *pair* of members diverges along the union of two disjoint 6-axis stacks. Detectors whose normalizers are keyed to "what changed vs a canonical form" do fine; pairwise-diff-based matchers must normalize the union. No existing mix family has zero axis overlap between carriers.

**(c)** Seed `csv_import`; A/B/C above; distractor `nearmiss_csv`; clean `clean_parsing`. Sets 001–005 rotate which profile lands on which scaffold and walk profile sizes 4/5/6.

**(d)** `interference_profile.symmetric: false`; new optional `interference_profile.pairwise_overlap: [{"pair":["src/A.php","src/B.php"],"shared_codes":[]}]` — emitted evidence of disjointness.

**(e)** Rung 4 of the `mixed` arc, prerequisite for C-4.

**(f)** `requires: [whitespace_normalization, comment_stripping, identifier_canonicalization, literal_abstraction, ast_canonicalization]`; `{text_based: false, token_based: true, ast_based: true, metric_based: true, semantic: true}`.

## C-6 · `mix_gradient_stack` (L9) — pristine → 7 axes → 14 axes in one cluster

**(b)** Extends E.2.1 `mix_asymmetric_heavy` to double depth *and* per-pair ground truth: A pristine, B = 7 axes, C = 14 axes (B's 7 plus 7 more — a superset chain, so B is "between" A and C in edit space). The pristine↔heavy pair is the real test; the middle member measures whether tools degrade linearly or cliff. **Requires E-11 `pairwise_expectation`** — the reason it can't ship today.

**(c)** Seed `invoice_totals`; A = pristine (`pristine: true`); B = RN-05+WS-03/06+CM-03+LT-01+ST-01+NS-01; C = B + RN-01/02+CM-07/09+WS-05/11+ST-09. Distractor + clean standard.

**(d)** `pairwise_expectation`: A↔B all-true; B↔C token true; A↔C token false (Guard B checked on that pair only — V-10). `per_carrier_axes: {A: 0, B: 7, C: 14}`.

**(e)** Rung 3–5 across sets 001–005 (gradient steepness is the dial: 0/4/8 → 0/7/14 → 0/10/20).

**(f)** `requires: [identifier_canonicalization, literal_abstraction, ast_canonicalization, gap_tolerance]`; cluster `{text_based: false, token_based: false, ast_based: true, metric_based: true, semantic: true}` + per-pair overrides.

## C-7 · `mix_pairwise_cover` (L9) — covering-array design over group interactions

**(b)** Treat the 8 implemented groups (WS, CM, RN, LT, TY, NS, ST, CF) as factors and build a **pairwise covering array**: 5 sets such that every unordered group PAIR (28 pairs) co-occurs on at least one carrier in at least one set. This is classic combinatorial test design (all-pairs) applied to clone-detector normalization: if a tool passes all sets except the ones containing {RN, WS-06}, the *interaction* of rename with line-wrapping is the localized bug. Companion family `mix_triple_cover` (L10) covers all 56 group triples in ~8 sets.

**(c)** Each set's three carriers carry 5–8 codes chosen by the covering-array generator (a tiny deterministic solver in the recipe compiler, F-7). Seeds rotate across all available so domain isn't a confounder.

**(d)** New recipe/emitted field `interference_profile.combination_design: "pairwise-cover"` + `covered_pairs: [["RN","WS"],["RN","CM"],…]` (generator-emitted, verifier-recomputed).

**(e)** Sits between single-axis levels and mix capstones; rung fixed at `combined`.

**(f)** Union `requires` of the groups in each set; `{text_based:false, token_based:true, ast_based:true, metric_based:true, semantic:true}` (interaction sets stay token-provable — the *diagnosis* value is in which set fails, not in depth).

## C-8 · `mix_interaction_probes` (L9) — the four known-nasty transform interactions, isolated

**(b)** Four sets, each engineering ONE interaction that plausibly breaks normalizer pipelines: (1) **RN-05 × WS-12** — case-convention rename changes identifier lengths, destroying column alignment that WS-12 padded (a normalizer that strips alignment BEFORE renaming sees different pad widths than one that renames first — order-of-normalization bug bait); (2) **CM-08 × WS-06** — a mid-statement comment lands inside a wrapped expression: `$tax = round($taxable /* pre-rounding */ \n * $taxRate, 2);` — comment strippers that operate line-wise corrupt the join; (3) **ST-06 × CM-07** — the gap content is a *commented-out copy of the deleted lines* (the deleted statements survive as comments — comment-stripping REOPENS the gap and the clone becomes contiguous again; tools that strip comments before gap analysis see a smaller gap than tools that don't); (4) **LT-04 × NS-01** — a literal is hoisted to a `const` that is then imported via `use const Acme\Rates\DEFAULT_TAX;` — literal abstraction must chase through the import.

**(c)** One interaction per set (001–004), set 005 = all four at once. Seed `invoice_totals`; standard 3+1+1.

**(d)** `interference_profile.interaction_under_test: ["RN-05","WS-12"]` (new optional field); notes spell out the two plausible normalizer orders and which one fails.

**(e)** Rung `heavy`; prerequisite = the two constituent single-axis families.

**(f)** Per-set: (1)+(2) `requires:[whitespace_normalization, identifier_canonicalization, comment_stripping]`, token true; (3) adds `gap_tolerance`, token false; (4) `requires:[literal_abstraction, ast_canonicalization]`, token false, ast true.

## C-9 · `mix_style_war` (L9) — coherent style-profile packs, ~12 axes each, realistic narrative

**(b)** Instead of arbitrary code lists, each carrier is rendered through a named **style pack** (F-7) that models a real PHP subculture: `psr12_modern` (4-space, camelCase, docblocks, `use` imports, short arrays, strict operators), `legacy_wordpress` (tabs, snake_case, Yoda conditions [needs T/SY-06], aligned assignments, block comments, `array()`), `enterprise_java_style` (deep namespaces, Hungarian-ish prefixes, license headers, one-return-per-method [CF-05 inverse], heavy docblocks). A pack expands to 8–13 codes with tuned params. The narrative — "the same function maintained by three teams with different coding standards" — is the single most common real-world Type-1/2 story and currently has no coherent family.

**(c)** Seed `access_guard`. A = psr12_modern (8 codes), B = legacy_wordpress (12 codes incl. SY-06 once built; 11 until then), C = enterprise_java_style (11 codes). Sets 001–005 rotate pack↔scaffold assignments and pack intensity dials.

**(d)** `interference_profile.pack` per carrier (extend to `packs: {"src/A.php": "psr12_modern", …}`); every expanded code still listed individually in `interference[]` (packs are authoring sugar, not metadata opacity).

**(e)** Rung `combined` of the whitespace/rename/comments arcs jointly; a deliberately *memorable* set for demos and docs.

**(f)** `requires: [whitespace_normalization, comment_stripping, identifier_canonicalization, ast_canonicalization]`; `{text_based:false, token_based:true, ast_based:true, metric_based:true, semantic:true}`.

## C-10 · `mix_modernization_gap` (L9) — "PHP 5.6 file vs PHP 8.3 file", 10–14 axes of era drift

**(b)** One carrier written as-if never modernized, one fully modernized — the axes are the LEG/SY codes of §8: `array()`↔`[]` (SY-04), `list()`↔`[$a,$b]` (LEG-01), `pow()`↔`**` (LEG-02), string concat↔interpolation (API-10), `isset($a)?$a:$b`↔`$a ?? $b` (NU-02), positional↔named args (SY-01), anonymous fn↔arrow fn (SY-02), classic ctor props↔promoted (LEG-08), `strpos()!==false`↔`str_contains` (API-16), plus WS/CM incidentals → 10–14 axes with an airtight real-world narrative ("the file that missed every refactor sprint"). Distinct from E.2.3's individual transforms: this family is their *composition* at scale.

**(c)** Seed `invoice_totals` ported to a legacy-styled variant body (one hand-written `variants/legacy_php56_style.php`, behaviorally identical, equivalence-proven). A = legacy variant + 3 era codes; B = modern pristine; C = half-modernized (7 axes). 2 carriers minimum viable via `role_composition` until the variant matures.

**(d)** `interference_profile.pack: "php56_era"`; `min_php` stays 8.1 (legacy *style*, not legacy *syntax* — everything must still parse on 8.1; the pack definition excludes removed syntax).

**(e)** Rung `combined` of a new `modernization` arc (G-2 table).

**(f)** `requires: [ast_canonicalization, api_abstraction, literal_abstraction]`; `{text_based:false, token_based:false, ast_based:true, metric_based:true, semantic:true}` — Guard B satisfied because era rewrites shatter token runs.

## C-11 · `mix_selector_composed` (L9/L10) — TWO semantic variants stacked on one member (needs F-6)

**(b)** Today one carrier can host exactly one selector variant — so "CF rewrite AND API substitution AND still duplicate" is inexpressible, capping semantic stack depth. With composed variant files (`variants/cf_early_return+api_table_driven.php`, proven by the same `equivalence_test.php`), a carrier can sit two selector axes deep, then take text/ast codes on top: e.g. member B = early-return control flow × table-driven rule lookup × RN-05 × WS-06 × CM-03 = 5 axes of which 2 are semantic. Family walks semantic depth: 001 = 1 variant + 4 t/a codes; 003 = 2 variants + 6; 005 = 2 variants + 10.

**(c)** Seed `access_guard` (24 variants on disk → richest composition matrix; start with the 6 pairs that compose naturally: guard×table, match×datashape, early_return×null_styles…). Composition files are hand-written once, reused across sets.

**(d)** `interference[]` cites both selector codes with `params: {"variant": "cf_early_return+api_table_driven"}`; `difficulty.axes.semantic_depth: 3`.

**(e)** The bridge between L9 mixing and L8 semantics — rung `combined` of the `semantic` arc.

**(f)** `requires: [controlflow_normalization, api_abstraction, ast_canonicalization, semantic_reasoning]`; `{text_based:false, token_based:false, ast_based:false, metric_based:false, semantic:true}` with 40-token distinctness verified pairwise (V-10).

## C-12 · `mix_noise_blizzard` (L9) — all ten NZ wrappers at once (post NZ implementation)

**(b)** An exact clone wearing every noise costume simultaneously: NZ-01 logging + NZ-02 metrics + NZ-03 security asserts + NZ-04 framework attributes + NZ-05 i18n wraps + NZ-06 feature-flag guards + NZ-07 debug dumps + NZ-08 env checks + NZ-09 timing + NZ-10 request-context threading = 10 noise axes + 2–4 WS/CM = **12–14 axes**, where the underlying business logic is UNTOUCHED. The single best "noise_filtering" stress test; today `mix_noise_heavy` fakes noise with WS/CM/RN/ST because NZ-* are declared-only.

**(c)** Seed `cache_manager` (planned E.2.2 seed — noise reads naturally around a cache-aside body) or `invoice_totals` until then. A = 3 NZ codes at density 1; B = 6 at density 2; C = all 10 at density 3 (`params.density` per E.2.3-style NZ spec, §8 T-NZ). Every inserted line is `unique_segments[].kind: "noise"` (E-4) — this family is ALSO a partial-duplication family, demonstrating the metadata unification.

**(d)** `duplication.uniqueness_budget` with `kind: noise` actuals; `difficulty.axes.partiality` > 0 despite logic being fully duplicated — `notes` explain the noise/logic distinction.

**(e)** Rung 5 of the `noise` arc (new arc, G-2).

**(f)** `requires: [deadcode_elimination, noise_filtering, gap_tolerance]`; `{text_based:false, token_based:false, ast_based:true, metric_based:true, semantic:true}`.

## C-13 · `mix_encoding_gauntlet` (L10) — every ENC code at once + WS (post ENC implementation)

**(b)** BOM (ENC-01) + NBSP indentation (ENC-02) + Unicode-confusable identifier (ENC-03: `$café` NFC vs NFD — PHP identifiers accept bytes 0x80–0xFF, so both parse) + CRLF/LF mix (ENC-04) + `"\x41"`↔`"A"` escapes (ENC-05) + heredoc↔quoted (ENC-06) + WS-08 tabs = 7 axes that are **invisible in most editors** but byte-storms to naive matchers. The set that separates "normalizes bytes before tokens" tools from the rest.

**(c)** Seed `csv_import` (string-heavy). A = BOM+CRLF; B = NBSP+escapes; C = all + heredoc. Hygiene checks (LF-only, UTF-8, no BOM) get per-set waivers via a new recipe key `hygiene_exceptions: ["bom","crlf","nbsp"]` — the verifier must KNOW these violations are the payload (V-extension).

**(d)** `interference[]` finally cites real ENC codes (today `adv_encoding` cites nothing citable — F.5 weakness (b)); `requires: ["encoding_normalization"]` (E-14).

**(e)** L10 terminus of the `encoding` arc.

**(f)** `{text_based:false, token_based:true, ast_based:true, metric_based:true, semantic:true}` — token tools SHOULD pass once their lexer normalizes encoding; failure is diagnostic.

## C-14 · `mix_budget_walk` (L9) — breadth fixed, token-drift budget graduated

**(b)** Five sets, all with the SAME 12 codes, differing only in `edit_budget`/params intensity: 001 budget 8 (each code at minimal occurrence) → 005 budget 40 (every occurrence, max params). Isolates **intensity** from **breadth** — the two 2-D difficulty axes (E-8) varied independently for the first time. Paired with C-1 (breadth walk at fixed intensity), it calibrates the difficulty model itself: if tool F1 correlates with breadth but not intensity (or vice versa), the scoring weights can be re-fit empirically (F-22).

**(c)** Seed `invoice_totals`; codes: WS-03/05/06 + CM-01/03 + RN-01/05 + LT-01 + ST-01/05/09 + NS-01. Symmetric stacks (all carriers same 12) so pair difficulty is uniform.

**(d)** `difficulty.axes: {breadth: 12, intensity: 6→38}`; `generator.intensity_dial: 0/2/4/6/8`.

**(e)** Rungs 1–5 mapped directly to the five sets — the cleanest rung ladder in the corpus.

**(f)** `requires` constant across sets (that's the point); `token_based` degrades true→false between 003 and 004 — recorded per-set, giving the bench a measured "intensity cliff" per tool.

## C-15 · `mix_multi_cluster_stacked` (L9) — 3 independent clusters × different stacks in ONE set (needs F-2)

**(b)** Extends E.2.4 §4.4 beyond exact clones: cluster c1 = `invoice_totals` under a 6-axis WS/CM stack; c2 = `csv_import` under a 6-axis RN/LT stack; c3 = `access_guard` under a CF variant + 4 axes. Nine carriers + 1 distractor + 1 clean (`role_composition {carrier: 9, distractor: 1, clean: 1}`). Tests `multi_cluster_reporting`: tools that emit only their best cluster lose 2/3 recall; tools that merge clusters across domains lose precision.

**(c)** As above; sets 001–005 permute which stack lands on which seed and add a 4th cluster in 005.

**(d)** `duplication: {clusters: 3, cluster_topology: "disjoint", cross_seed: true}` (E-10); expected.json clusters c1/c2/c3 each with own `normalized_by`/`detection_expectation`; V-9 cross-cluster distinctness.

**(e)** Rung `combined`; prerequisite `ex_multi_cluster` (L1) — the exact→stacked multi-cluster arc.

**(f)** Union of the three stacks' `requires` + `multi_cluster_reporting`; per-cluster expectations differ (c1 token true, c3 semantic-only) — per-cluster scoring already works since D.4 iterates GT clusters.

## C-16 · `mix_commit_history` (L9) — five sets = five commits, stacks accumulate ACROSS sets

**(b)** A cross-SET narrative: set 001's carriers carry 3 axes; each subsequent set re-renders the same seed with the previous set's stack PLUS 3 more codes (deterministic accumulation), so 005 carries 15. `progression.prev_set/next_set` (E-9) chain them; a tool-builder replays the "history" to find exactly which commit (axis triple) broke detection. Unlike `mix_realistic_drift` (intra-set narrative), the drift here is *inter-set* and perfectly controlled.

**(c)** Seed `access_guard`; accumulation order: (WS-03, CM-01, RN-01) → (+WS-06, CM-03, LT-02) → (+RN-05, ST-01, NS-01) → (+ST-05, CM-07, TY-01) → (+ST-09, WS-11, CM-09).

**(d)** `progression` chain + `interference_profile.axis_count` 3/6/9/12/15; `related.variant_of` pointing to the predecessor set.

**(e)** Itself a 5-rung mini-ladder — the canonical demonstration of E-9 mechanics.

**(f)** requires grows monotonically along the chain; `token_based` flips false at set 004 (measured, then asserted).

## C-17 · `mix_asymmetric_partial_stack` (L9/L11) — combinatorial × partial-duplication fusion

**(b)** The two headline goals composed: carriers carry BOTH a deep interference stack (8–12 axes) AND tracked unique segments (4–8 lines × 2 segments, E-2 budget). The clone survives normalization only as a fragments-union. This is the single hardest *realistic* configuration — real drifted copies have both style divergence and logic divergence — and it exercises E-1/E-2/E-4/E-7/E-8 simultaneously.

**(c)** Seed `report_builder` (L-class seed S-11 — big enough to absorb interruptions). A = 8 axes + 1 unique segment; B = 10 axes + 2 segments; C = 12 axes + 2 segments. `fragment_scoring: "fragments_union"`.

**(d)** Full E-2 budget block + `difficulty.axes: {breadth: 12, intensity: 20, semantic_depth: 2, partiality: 0.25}` — all four axes non-zero, unique in the corpus.

**(e)** The final rung of BOTH the mixed arc and the partial arc; L11 candidate (see §10 level placement).

**(f)** `requires: [identifier_canonicalization, ast_canonicalization, gap_tolerance, noise_filtering, boundary_precision]`; `{text_based:false, token_based:false, ast_based:true, metric_based:false, semantic:true}`.

## C-18 · `mix_repeated_axis` (L9) — same CODE applied k times with different params (depth ≠ breadth)

**(b)** Stack the *same* transform repeatedly: WS-06 line-wrap applied at occurrence 1, 2, 3, 4; CM-01 line comments at 4 different anchors; ST-01 logging inserted at 3 boundaries. Axis breadth = 3 codes but application count = 11. Probes whether difficulty is really "distinct codes" (current formula counts each code once — F.0 score rule) or "total perturbation mass". Feeds the empirical re-fit of the difficulty model (F-22) and adds a new metadata distinction: `interference_profile.application_count` vs `axis_count`.

**(c)** Seed `invoice_totals`; 001 = each code ×1 (control) … 005 = each code ×4.

**(d)** New `interference_profile.application_count: 11` (count of `interference[]` entries incl. same-code re-applications; today double-listing is collapsed — MINOR-3 fix made it one entry per code+file+params, which naturally supports this).

**(e)** Rung ladder by application count.

**(f)** `requires` constant `[whitespace_normalization, comment_stripping, deadcode_elimination]`; expectation flips measured per rung.

---

### §2 summary table

| ID | Family | Level | Axes | Distinguishing dimension | Needs |
|---|---|---|---|---|---|
| C-1 | mix_t1_blizzard | L9 | 5→20 | max breadth, min capability (type-1) | E-7/E-8 only |
| C-2 | mix_t2_saturation | L9 | 10→16 | all Type-2 normalizers composed | E-7/E-8 |
| C-3 | mix_t3_torrent | L9 | 18–20 | breadth + graduated edit_budget | E-7/E-8/E-11 |
| C-4 | mix_full_spectrum | L10 | 17–20 | 10 groups at once, capstone | ENC/NZ impls |
| C-5 | mix_disjoint_stacks | L9 | 18 set-wide | zero pairwise axis overlap | E-7 |
| C-6 | mix_gradient_stack | L9 | 0/7/14 | pristine↔heavy pair | E-11 |
| C-7 | mix_pairwise_cover | L9 | 5–8/set | covering array over 28 group pairs | F-7 solver |
| C-8 | mix_interaction_probes | L9 | 2–8 | known-nasty interactions isolated | none |
| C-9 | mix_style_war | L9 | 8–13 | coherent style packs, realism | F-7 packs |
| C-10 | mix_modernization_gap | L9 | 10–14 | PHP-era drift composition | SY/LEG codes |
| C-11 | mix_selector_composed | L9/10 | 2 semantic + N | two variants on one member | F-6 |
| C-12 | mix_noise_blizzard | L9 | 12–14 | all NZ at once, noise vs logic | NZ impls |
| C-13 | mix_encoding_gauntlet | L10 | 7 | byte-level invisibility | ENC impls |
| C-14 | mix_budget_walk | L9 | 12 fixed | intensity isolated from breadth | E-8 |
| C-15 | mix_multi_cluster_stacked | L9 | 3 clusters | multi-cluster reporting | F-2 |
| C-16 | mix_commit_history | L9 | 3→15 | cross-set accumulation | E-9 |
| C-17 | mix_asymmetric_partial_stack | L9/11 | 8–12 + budget | combinatorial × partial fusion | E-1..E-4, F-3 |
| C-18 | mix_repeated_axis | L9 | 3 codes ×11 | application-count vs breadth | E-7 |

---

# 3. Graduated partial-duplication sets (goal 2)

**Mechanism.** Today "partial" exists only as ST-06 (gap_split) / ST-07 (partial_fragment) modeled as whole-method members with no sub-range truth (F.0-4). The §3 families rest on four enablers: the **UQ transform group** (T-UQ in §8 — inserts *tracked, meaningful* unique code, distinct from ST-01/02's inert noise), **fragment emission** (F-3 — `fragments[]` + `unique_segments[]` computed from the lineMap's `-1` markers), **per-seed unique-snippet pools** (`gen/seeds/<seed>/uniques/*.php`, F-11 — so unique code looks like real domain logic, not lorem ipsum), and the **E-2 budget declaration/actual/verify loop**. Proof obligations change: positive proof runs per aligned fragment pair (V-4), negative proof additionally checks unique segments never accidentally clone (V-5).

**The graduated arc in one table** (each row is a rung of `duplication_ratio`, E-3):

| Rung | duplication_ratio | Description | Families |
|---|---|---|---|
| 0 | 0.00 | no duplication at all (existing L0) | nd_* |
| 1 | 0.10–0.25 | a small shared block inside otherwise-unrelated code | P-5, P-6 |
| 2 | 0.40–0.60 | half shared, half unique — the fork-and-diverge zone | P-5, P-7, P-12 |
| 3 | 0.70–0.85 | mostly duplicate, a few tracked unique segments | P-1, P-2, P-3 |
| 4 | 0.90–0.99 | near-total duplicate, 1–2 tiny unique lines | P-1 rung 2 |
| 5 | 1.00 | whole-region clone (all 540 existing sets) | existing corpus |

---

## P-1 · `pd_budget_ladder` (L11 / L05-extension) — the canonical tracked-budget family

**(b) Concept.** The user's specification made literal: five sets that walk the per-carrier unique-code budget while everything else stays fixed. Set 001 = budget 0 (control: pure whole-method clone). Set 002 = each carrier gets **2–4 unique lines in 1 segment**. Set 003 = **4–8 lines across 2 segments** (the user's worked example). Set 004 = **8–16 lines across 3 segments**. Set 005 = **16–32 lines across 4 segments**, duplication_ratio ≈ 0.5. Unique code is drawn from the seed's `uniques/` pool (per-currency rounding, an extra audit write, a bounds clamp — real-looking logic that does not alter the shared computation's outputs for the equivalence matrix, OR is declared output-extending and proven on the *shared* outputs only).

**(c) Composition.** Seed `report_builder` (S-11, L-class, 70+ lines — small seeds can't absorb 32 unique lines). 3 carriers all budgeted identically per set; distractor: a report-summary near-miss; clean: formatting helper. `fragment_scoring: "fragments_union"`, `line_tolerance: 2`.

**(d) Metadata.** Full E-2 block per set, e.g. set 003:

```json
"uniqueness_budget": {
  "per_carrier_unique_lines": {"min": 4, "max": 8},
  "per_carrier_segments": {"min": 2, "max": 2},
  "carriers_with_unique_code": 3,
  "actual": [
    {"file": "src/QuarterlyDigest.php", "unique_lines": 6, "segments": 2},
    {"file": "src/AnnualDigest.php", "unique_lines": 5, "segments": 2},
    {"file": "src/AdHocDigest.php", "unique_lines": 8, "segments": 2}
  ]
}
```

plus `duplication_ratio` 1.00/0.94/0.85/0.72/0.51 across sets; members carry `fragments[]` + `unique_segments[]` (E-4); interference cites UQ-03 with `params: {"segments": 2, "lines_min": 4, "lines_max": 8, "pool": "report_builder"}`.

**(e) Ladder.** THE reference rung ladder for `axes.partiality`; rungs 1–5 = sets 001–005; `axis_arc: "partiality"`.

**(f) Capability.** `requires: ["gap_tolerance", "boundary_precision"]` (+`ast_canonicalization` from rung 3). Expectations degrade honestly: 001–002 `{text_based:false, token_based:true, ast_based:true, metric_based:true, semantic:true}`; 004–005 token_based false (fragments each < 70 tokens for phpcpd), ast_based true, metric_based false.

## P-2 · `pd_asymmetric_budget` (L11) — "N of M files carry unique code"

**(b)** Varies **which** carriers diverge rather than how much: 001 = only 1 of 3 carriers has unique code (the other two remain a perfect pair — an anchor pair exists); 002 = 2 of 3; 003 = 3 of 3 equal budgets; 004 = 3 of 3 with steeply unequal budgets (2 / 8 / 20 lines); 005 = 4 carriers (role_composition 4+1+1), budgets 0/4/12/24 — a divergence fan. Tests whether tools that pair-match can still assemble the full cluster when some pairs are much weaker than others (`min_members_for_credit` interplay).

**(c)** Seed `basket_pricing` (S-10 multi-symbol). Distractor shares the basket vocabulary.

**(d)** `uniqueness_budget.carriers_with_unique_code` = 1/2/3/3/4; `pairwise_expectation` marks the anchor pair token_based true while diverged pairs are false; scoring `min_members_for_credit: 2` (crediting partial cluster assembly) with a companion strict re-score at 3 in the bench.

**(e)** Rung by N-of-M; prerequisite P-1.

**(f)** `requires: ["gap_tolerance", "multi_cluster_reporting"?]` — no: `[gap_tolerance, boundary_precision]`; expectations per-pair via E-11.

## P-3 · `pd_segment_count_walk` (L11) — volume fixed, fragmentation graduated

**(b)** Total unique budget FIXED at 12 lines/carrier; segment count walks 1 / 2 / 3 / 4 / 6 across sets. Rung 1: one 12-line unique block (clone splits into 2 fragments of ~20 tokens+); rung 5: six 2-line interruptions (clone shredded into 7 fragments of 8–10 lines). Same partiality, radically different *fragmentation* — isolates gap-COUNT tolerance from gap-VOLUME tolerance (phpcpd's `--fuzzy` tolerates renamed tokens but not gaps; jscpd tolerates small gaps; AST matchers with statement-level hashing tolerate both until fragments drop under their window).

**(c)** Seed `report_builder`. All carriers identical budget/segments per set (symmetric).

**(d)** `varied_segment_count` (E-5) = 3/6/9/12/18 set-wide; per-fragment token counts cited in `notes` (§8.1 calibration discipline); `uniqueness_budget.per_carrier_segments: {"min": k, "max": k}`.

**(e)** Orthogonal rung ladder to P-1 (volume) — together they span the (volume × fragmentation) plane; grid_position uses axis `UQ`.

**(f)** `requires: [gap_tolerance, boundary_precision]`; token_based true at rung 1 (each fragment ≥ 70 tokens), false by rung 3; `fragment_scoring: fragments_union`.

## P-4 · `pd_position_sweep` (L11) — one 6-line unique segment, five positions

**(b)** A single 6-line unique segment placed at: function prologue (before shared logic), 25%, 50%, 75%, and epilogue (after shared logic) across sets 001–005. Prologue/epilogue positions produce ONE contiguous shared fragment (does the tool clip the boundary correctly?); interior positions produce TWO fragments (does it report both, or swallow the unique code into one bloated region — `unique_segment_penalty` catches the latter). The cheapest possible probe of region-boundary behavior with everything else constant.

**(c)** Seed `invoice_totals` (24-line payload + 6 unique = still compact). Unique snippet: a currency-conversion pre-pass (prologue variant) / an item-count audit (interior) / a totals memo write (epilogue).

**(d)** `unique_segments[].reason` names the position; `scoring.unique_segment_penalty: true`; `capability_probe.capability: "boundary_precision"` — this family doubles as a §6 probe.

**(e)** Rung `light`; feeds K-29.

**(f)** `requires: [boundary_precision, gap_tolerance]`; `{text_based:false, token_based:true, ast_based:true, metric_based:true, semantic:true}` at edge positions; interior sets token_based depends on fragment sizes (cited in notes).

## P-5 · `pd_ratio_arc` (L11) — the NO-dup → some-dup → mostly-dup family, one arc in five sets

**(b)** The user's arc as a single family. 001: `duplication.present: false` — the two "carriers" share only domain vocabulary (ratio 0.00; effectively an L0-style set with carrier-shaped files; clusters `[]`, trap regions marked). 002: an 8-line shared block appears inside otherwise-unrelated 50-line methods (ratio ≈ 0.16, granularity **block** — first realized use of the enum value). 003: half the method shared (ratio 0.5). 004: mostly shared with 2 unique segments (0.8). 005: whole-method clone (1.0, the classic). One family, the entire spectrum — the walkable demonstration that "duplication" is a dial, not a bit.

**(c)** Seed `user_repository` (S-12) whose methods naturally share sub-blocks (hydration loop) while differing elsewhere (query text, cache handling). Block-mount (F-4) hosts the shared block inside per-carrier host methods.

**(d)** 001 emits `duplication: {present:false, clone_type:"none", granularity:null, clusters:0, instances:0, duplication_ratio: 0.0}` + `non_duplicates[]` traps; 002+ emit `granularity: "block"`, fragments = whole block, `region_sloc` per E-1. `progression.next_set` chains 001→005.

**(e)** THE spine of the partiality arc; rungs 0–5 of the §3 rung table in one family.

**(f)** 002's block is deliberately sized at ~45–55 tokens: below PMD-CPD's 100-token floor, at jscpd's 50, below phpcpd's 70 — per-tool divergence by design (notes cite counts). `requires: [gap_tolerance]` from 003; expectations move from n/a (001) → `{text:false, token:false, ast:true, metric:false, semantic:true}` (002) → all-true (005).

## P-6 · `pd_shared_block_only` (L11) — block granularity, hostile hosts

**(b)** The clone is ONLY a 10–14-line block (validation + normalization run), embedded in three host methods that are *actively different* — different loops, different return shapes, different error handling — not just different filler. Harder than P-5-002 because the surrounding code is dense and plausible, maximizing the temptation to over-extend the reported region. The negative-space counterpart: `non_duplicates[]` entries cover the host-method remainders with `trap: false, reason: "host logic unique per carrier"` so over-extension is measurable (boundary penalty) without counting as trap FP.

**(c)** Seed `validation_rule` (planned E.2.2 seed) — its rule-loop block transplants naturally. Hosts: an HTTP controller action, a CLI importer, a queue worker (3 realistic frames from new scaffolds, F-4).

**(d)** `duplication.granularity: "block"`; members' `symbol: null` (no enclosing symbol match — relaxes the line-audit, F-4); `region_sloc.filler` dominates (E-1).

**(e)** Rung `moderate` of partiality arc; pairs with P-5.

**(f)** `requires: [gap_tolerance, boundary_precision]`; `{text:false, token:true(50-token block cited), ast:true, metric:false, semantic:true}`.

## P-7 · `pd_grow_shrink` (L11) — head/tail extensions, shared core intersection

**(b)** All carriers share a core computation, but A extends the TAIL (post-processing appended), B extends the HEAD (pre-validation prepended), C extends both. The member ranges differ in length (A: core+8, B: 6+core, C: 6+core+8); the ground-truth fragments are the pairwise-common CORE. Detectors that anchor on function boundaries will report inflated regions (boundary penalty); detectors that report maximal common substrings get it right. Models the most common real divergence: copies that *accrete* at the edges.

**(c)** Seed `slug_generator` (S-2) with head snippet = input trimming/encoding guard, tail snippet = uniqueness-suffix loop.

**(d)** `unique_segments[].kind: "unique_logic"` at head/tail; `uniqueness_budget` asymmetric actuals; `scoring.unique_segment_penalty: true`.

**(e)** Rung `moderate`; complements P-4 (interior) with edge growth.

**(f)** `requires: [boundary_precision]`; all classes true except metric (length divergence breaks size-metric matching — `metric_based: false`, a rarely-false class today, diagnostic gold).

## P-8 · `pd_replacement_drift` (L11) — unique code REPLACES shared code (net shrink)

**(b)** UQ-05 semantics: in each carrier, one 4–6-line shared step is torn out and *rewritten differently* (not removed — replaced with a behavior-adjacent but textually/structurally unique implementation). Unlike insertion families, total length stays ~constant while the shared set shrinks — the clone erodes from within. Rung walk: 1 replaced step → 3 replaced steps; by rung 5 the members share only 60% and the "clone" is approaching the near-miss frontier — the family ends exactly where `adv_near_miss` begins, and `progression.next_set` of 005 points at an L10 near-miss set (a cross-level bridge, G-6).

**(c)** Seed `tax_bracket` (S-6): the bracket-walk loop stays shared; the rounding step / the edge-clamp step / the currency step get per-carrier rewrites from `uniques/`.

**(d)** `unique_segments[].kind: "unique_logic"` with `replaces: {"start_line": n, "end_line": m}` (new optional sub-field documenting what pristine content the segment displaced — generator knows it from the lineMap); `duplication_ratio` 0.92→0.61.

**(e)** Rung ladder by replaced-step count; the "erosion" arc terminus feeds L10.

**(f)** `requires: [gap_tolerance, ast_canonicalization]`; token flips false at rung 2; `notes` must justify why members still count as one cluster (shared skeleton + majority logic) — the honesty discipline for erosion sets.

## P-9 · `pd_noise_vs_logic` (L11) — same budgets, different SEMANTICS of the interruptions

**(b)** Paired sets with byte-similar budgets where the interruptions are (001/002/003) inert noise — ST-01 logging, NZ-09 timing, debug dumps (`kind: "noise"`) — versus (004/005) genuine divergent business logic (`kind: "unique_logic"`). A noise-aware detector (deadcode/instrumentation filtering) should report the noise sets as *near-full* clones (the noise is ignorable) but the logic sets as *partial* clones. Same geometry, different verdicts — no other family can distinguish a tool that merely tolerates gaps from one that understands WHAT is in the gap.

**(c)** Seed `cache_manager`; budgets fixed at 6 lines × 2 segments for all five sets.

**(d)** The `kind` field (E-4) carries the entire design; `detection_expectation` differs BETWEEN sets despite identical budgets — noise sets expect `ast_based: true, token_based: false`; logic sets expect fragment-level truth with `fragment_scoring: fragments_union`.

**(e)** Rung `moderate`; a discriminator set-pair rather than a ladder.

**(f)** `requires`: noise sets `[noise_filtering, deadcode_elimination]`; logic sets `[gap_tolerance, boundary_precision]` — the same geometry mapping to different capabilities is the lesson.

## P-10 · `pd_threshold_shred` (L10/L11) — every fragment individually sub-threshold, union far above

**(b)** Fragments engineered against §8.1 tool floors: the clone totals ~140 tokens but is shredded into four 30–38-token fragments by three 2-line unique wedges. EVERY fragment is below phpcpd's 70, jscpd's 50, and PMD's 100-token floors — token tools are *structurally blind* to a clone whose union is huge. Graduation: 001 fragments ≈ 90 tokens each (all visible) → 003 ≈ 55–65 (jscpd sees, phpcpd doesn't) → 005 ≈ 30–38 (nobody token-based sees). The sharpest possible demonstration that fragment-assembly is a distinct capability, and the direct partial-dup analogue of `ex_size_ladder`.

**(c)** Seed `report_builder`; wedges from `uniques/`. Token counts PER FRAGMENT cited in `notes` (mandatory per §8.1 discipline).

**(d)** `capability_probe.capability: "gap_tolerance"`; fragments carry `lines` and notes carry tokens; `pairwise_expectation` uniform.

**(e)** L10-adjacent rung `heavy` of the partiality arc.

**(f)** `requires: [gap_tolerance]` alone (deliberate isolation); 005 `{text:false, token:false, ast:true, metric:false, semantic:true}`.

## P-11 · `pd_fork_pair` (L11) — the two-file "forked file" realistic shape

**(b)** Exactly two carriers (role_composition 2+1+2), each ~120 lines, sharing ~70 lines across 4 fragments — a file copied wholesale then maintained by two teams for a year. The set-level story: not a method clone but a FILE-granularity partial clone (granularity `file`, fragments spanning multiple methods — needs F-4 multi-region mount). The most common real-world dedup input (fork-and-diverge) is currently absent from the corpus at file scale.

**(c)** Seed `user_repository` multi-symbol: `findById` (shared verbatim), `findByEmail` (shared modulo RN-01), `insert` (heavily diverged = mostly unique), `update` (half shared). Distractor: a repository for an unrelated entity with the same skeleton (`known_tool_fp` candidate).

**(d)** `duplication.granularity: "file"`; ONE cluster whose 2 members have 4 `fragments[]` each + interleaved `unique_segments[]`; `region_sloc` at file scale; `drift_generation: 0/1` (both hop-1 from a virtual origin — semantics documented in notes).

**(e)** Rung `heavy`; the realism capstone of §3.

**(f)** `requires: [gap_tolerance, boundary_precision, identifier_canonicalization]`; `{text:false, token:true (fragments 1–2 exceed floors), ast:true, metric:false, semantic:true}`.

## P-12 · `pd_braided_clusters` (L11/L10) — two clusters interleaved in the same files (needs F-2 + F-3)

**(b)** The adversarial-topology summit: files A and B each contain fragments of cluster c1 AND cluster c2, interleaved (c1-frag, c2-frag, c1-frag, …). c1 = invoice math fragments, c2 = access-guard fragments, braided into two composite service files (a realistic "god-class pair" smell). Region reporting must keep two clone identities disjoint within the same line neighborhoods; tools that merge adjacent matches into one region fail precision catastrophically. `cluster_topology: "braided"` (E-10).

**(c)** 2 composite carriers + 1 distractor + 1 clean + 1 support. Both clusters 2-member.

**(d)** Two clusters with disjoint fragments in the SAME files; V-9 legality via declared `relation: {"type": "braided", "with": "c1"}`; `requires: [multi_cluster_reporting, boundary_precision, gap_tolerance]`.

**(e)** Post-capstone adversarial rung — L10 placement acceptable if L11 isn't adopted.

**(f)** `{text:false, token:true-per-fragment, ast:true, metric:false, semantic:true}`; the bench needs fragment-aware scoring (F-17) to grade it at all.

## P-13 · `pd_budget_matrix_family` (L11) — the (volume × segments × N-of-M) factorial slice

**(b)** A designed-experiment family sampling the 3-D budget space on a Latin-square-like plan so tool results can be regressed against budget parameters: sets fix (unique_lines, segments, carriers_with_unique_code) at (4,1,1), (8,2,2), (16,2,3), (8,4,3), (16,4,2). Five points spanning the space's diagonal + off-diagonal — enough to fit "tool recall = f(volume, fragmentation, spread)" per tool in the profile report (F-20). This family exists to make the budgets *statistically analyzable*, not just present.

**(c)** Seed `report_builder`; geometry per plan above.

**(d)** E-2 blocks parameterize everything; `capability_probe` absent (this is a measurement family, not a probe); manifest gains a `budget_matrix` index (F-14).

**(e)** Sits beside P-1/P-3 as their factorial generalization.

**(f)** `requires: [gap_tolerance, boundary_precision]`; expectations computed per §8.1 floors from realized fragment sizes and cited per set.

## P-14 · `pd_unique_decoy` (L11) — unique segments that are near-misses of EACH OTHER

**(b)** The trap fusion: each carrier's unique segments are engineered as high-vocabulary-overlap near-misses of the OTHER carriers' unique segments (same variable names, same shape, different computation — classic `nd_same_domain` bait, relocated INSIDE clone members). A greedy matcher extends the true clone region across the unique segments because they "look similar too"; ground truth punishes via `unique_segment_penalty` + `non_duplicates[]` entries covering each unique segment with `trap: true`. First family where traps live inside cluster members — the reason `non_duplicates` and `members` must be allowed to overlap ranges (schema comment update; scoring handles precedence: fragments win, traps apply to the unique sub-ranges).

**(c)** Seed `tax_bracket`; unique segments: three different "special-case rebate" computations sharing vocabulary (`$rebate`, `$threshold`, `$adjusted`) but different formulas. Bag-of-token overlap high, longest common run < 40 (Guard B for the traps, verified).

**(d)** `unique_segments[].kind: "unique_logic"`; matching `non_duplicates[]` trap entries with `reason: "divergent rebate rule — vocabulary-similar, computationally distinct"`; `trap_fp` metric directly exercised inside a positive set (today traps only live in distractor files).

**(e)** Rung `heavy`/adversarial; final exam of the partial arc before L10.

**(f)** `requires: [gap_tolerance, boundary_precision, semantic_reasoning]`; `{text:false, token:false, ast:true, metric:false, semantic:true}`.

---

### §3 summary table

| ID | Family | What is graduated | Tracked by | Level |
|---|---|---|---|---|
| P-1 | pd_budget_ladder | unique-lines budget 0→32 | E-2 min/max + actuals | L11 |
| P-2 | pd_asymmetric_budget | N-of-M carriers diverged | carriers_with_unique_code | L11 |
| P-3 | pd_segment_count_walk | fragmentation 1→6 segments | per_carrier_segments, E-5 | L11 |
| P-4 | pd_position_sweep | segment position | unique_segments[].reason | L11 |
| P-5 | pd_ratio_arc | ratio 0.0→1.0 incl. present:false | duplication_ratio | L11 |
| P-6 | pd_shared_block_only | block-granularity clone | granularity: block | L11 |
| P-7 | pd_grow_shrink | head/tail accretion | asymmetric budgets | L11 |
| P-8 | pd_replacement_drift | shared steps replaced 1→3 | replaces sub-field | L11 |
| P-9 | pd_noise_vs_logic | interruption SEMANTICS | unique_segments[].kind | L11 |
| P-10 | pd_threshold_shred | per-fragment token size vs floors | notes token counts | L10/11 |
| P-11 | pd_fork_pair | file-granularity 2-file fork | file granularity + fragments | L11 |
| P-12 | pd_braided_clusters | two clusters interleaved | cluster_topology: braided | L10/11 |
| P-13 | pd_budget_matrix_family | 3-D budget factorial | E-2 as experiment design | L11 |
| P-14 | pd_unique_decoy | traps inside members | trap:true on unique segments | L11 |

---

# 4. Refactorability-labeled sets (goal 3)

**Design doctrine.** Every rf_* set is built *backwards from the refactoring*: first the unified abstraction is written (`solution/Unified.php`), then the members are **derived from it by substituting each member's hole values** — so the duplication is refactorable *by construction*, and the `refactor_proof.php` harness (F-12) mechanically re-verifies it (V-6). The `intended_refactoring` object (E-6) is therefore emitted, not asserted. This inverts the current pipeline (payload → transforms → members) into (unified + holes → members) — a new `SetBuilder` mount path (F-12) that doubles as a *generator of graduated near-duplicates* whose divergence is exactly the holes.

**The graduation dimension is the hole ladder:**

| Rung | holes | hole kinds | kind (typical) | refactor_difficulty |
|---|---|---|---|---|
| 1 | 1 | literal | parameterize_literal | trivial |
| 2 | 2–3 | literal + identifier | extract_function | easy |
| 3 | 1 | behavior_toggle | parameter_toggle_boolean | moderate |
| 4 | 1 enum over ≥3 | behavior_toggle (enum) | parameter_toggle_enum | moderate |
| 5 | 1–2 | expression | extract_closure_hole | hard |
| 6 | 4–6 traveling together | literal+toggle | introduce_parameter_object | moderate–hard |
| 7 | whole algorithm step | strategy | strategy_object / template_method | hard |
| 8 | member = class member | — | pull_up_method / extract_trait | architectural |
| 9 | parallel conditionals | — | replace_conditional_with_polymorphism | architectural |

**Old-corpus synergy:** `refactored/` already holds 619 worked solutions; E-18's `old_corpus_ref` lets rf_* sets cite the hand-written scenario they modernize (e.g. R-7 ↔ `samples/algorithm/1` + `refactored/algorithm/1/code.php`), turning the disconnected OLD corpus into a solutions library.

**Bench synergy:** phpdup already emits "suggested-refactor / parameterised signature + hole inventory" (feature-matrix) — rf_* sets give that output ground truth to be SCORED against for the first time: new metrics `refactor_kind_match`, `hole_recall`, `hole_precision`, `signature_similarity` (F-21).

---

## R-1 · `rf_param_literal` (L4) — one literal hole (rung 1)

**(b)** The minimal refactorable pair: three carriers byte-identical except ONE numeric literal — tax rate `0.07` / `0.09` / `0.05` (exactly what LT-01 already produces, now *labeled*). Refactoring: extract to one function with the rate as a parameter.

**(c)** Seed `invoice_totals`; carriers `RetailInvoiceCalc/WholesaleInvoiceCalc/PartnerInvoiceCalc`; standard 3+1+1; interference `[{code: "LT-01", params: {"target": "taxRate-literal"}}]`.

**(d)** `intended_refactoring`: `kind: "parameterize_literal"`, `unified_signature: "function computeTotals(array $lineItems, float $taxRate, float $discountRate): array"` (the literal becomes the *already-existing* param — notes explain the degenerate elegance), `holes: [{kind:"literal", php_type:"float", values_by_member: {...: "0.07", ...: "0.09", ...: "0.05"}}]`, `hole_count: 1`, `refactor_difficulty: "trivial"`, `advisability: "recommended"`; set.json echo `refactoring: {kind, hole_count: 1, advisability}`.

**(e)** Rung 1 of the refactor arc; difficulty_band `medium` (L4 base) — deliberately EASY detection so the refactor label is the only novelty.

**(f)** `requires: [literal_abstraction]` + `refactoring_synthesis` (E-14) for refactor-aware tools; `{text:false, token:true, ast:true, metric:true, semantic:true}`.

**(g)** As (d).

## R-2 · `rf_hole_ladder` (L4→L6) — hole COUNT graduated 1→6 (rungs 1–2, 6)

**(b)** Five sets walking hole count with kind held at literal/identifier: 001 = 1 literal; 002 = 2 literals (rate + threshold `100.0`); 003 = 3 (+ shipping fee `9.99`); 004 = 4 (+ the `'qty'`/`'unitPrice'` array-key strings — LT-02); 005 = 6 (+ result-array key names). At 005 the honest label flips to `introduce_parameter_object` with a proposed `readonly class TotalsPolicy` — the set *teaches the threshold* at which parameter lists should become objects. Signature evolves in metadata: `computeTotals(array $items, float $rate)` → `computeTotals(array $items, TotalsPolicy $policy)`.

**(c)** Seed `invoice_totals`; the shipping ternary `$subtotal > 100.0 ? 0.0 : 9.99` supplies holes h2/h3 naturally.

**(d)** `hole_count: 1..6`; 005's `kind: "introduce_parameter_object"` + `holes[].name` grouped under `parameter_object: "TotalsPolicy"` (new optional sub-field); `refactor_difficulty` trivial→moderate.

**(e)** The canonical hole-count rung ladder (rungs 1→2→6 of the doctrine table).

**(f)** `requires: [literal_abstraction, refactoring_synthesis]`; all-true expectations throughout (detection stays easy — ONLY refactor complexity is graduated: the isolation principle from goal 5 applied to goal 3).

**(g)** As above; solution + proof per set.

## R-3 · `rf_toggle_boolean` (L5) — the user's canonical example (rung 3)

**(b)** *"Refactor these 2 blocks into ONE function with a parameter that toggles behavior."* Carrier A includes a guarded shipping step; carrier B is identical but the step is absent; carrier C includes it. Unified: `computeDocumentTotals(..., bool $includeShipping)` where the step runs `if ($includeShipping)`. Mechanically: members derive from the unified body with the toggle resolved and dead branches folded — carrier B's member is A's minus 2 lines (UQ-04 unique_branch geometry, shared with §3: the toggle IS a tracked unique segment of A and C).

**(c)** Seed `invoice_totals`; A `InvoiceTotals::computeTotals` (with shipping), B `QuoteTotals::computeQuote` (without — quotes don't ship), C `OrderTotals::computeOrderTotals` (with). Domain story makes the toggle *meaningful*. Distractor: margin near-miss.

**(d)** Full E-6 block (the E-6 example above IS this family); `uniqueness_budget` records A/C's extra 2 lines (goal 2 + goal 3 in one set); interference cites UQ-04.

**(e)** Rung 3 — the doctrine's pivot from data holes to behavior holes.

**(f)** `requires: [gap_tolerance, refactoring_synthesis]`; `{text:false, token:true (fuzzy), ast:true, metric:true, semantic:true}` — a 2-line delta keeps every class viable.

**(g)** `kind: "parameter_toggle_boolean"`, `hole_count: 1`, `refactor_difficulty: "moderate"`, `advisability: "recommended"`.

## R-4 · `rf_toggle_enum` (L6) — three-way behavioral fork → enum hole (rung 4)

**(b)** Three carriers differing in ONE step done three ways: rounding mode — `round($x, 2)` (half-up) vs `floor($x * 100) / 100` (truncate) vs `round($x, 2, PHP_ROUND_HALF_EVEN)` (banker's). Unified: `enum RoundingMode { HalfUp; Truncate; Bankers }` + a `match` on the hole. NOT behaviorally identical across members (by design — refactorability ≠ equivalence): the positive proof runs per-member against the unified+hole-value combination (V-6), not member-vs-member. This family formally introduces the **"same-modulo-holes" equivalence class** into the corpus's proof system.

**(c)** Seed `money_math` (S-1). Support file: none needed (enum ships inside solution/, not src/).

**(d)** `holes: [{kind:"behavior_toggle", php_type:"RoundingMode", values_by_member: {...:"RoundingMode::HalfUp", ...}}]`; `kind: "parameter_toggle_enum"`; cluster `notes` state the members are NOT IO-equivalent and cite the per-member proof.

**(e)** Rung 4; prerequisite R-3.

**(f)** `requires: [api_abstraction, refactoring_synthesis, semantic_reasoning]`; `{text:false, token:false, ast:true, metric:true, semantic:true}` (the differing step breaks token runs; Guard B satisfied).

**(g)** As (d); `refactor_difficulty: "moderate"`.

## R-5 · `rf_closure_hole` (L7) — the varying part is an EXPRESSION → callable hole (rung 5)

**(b)** Carriers identical except one embedded computation: line-total adjustment = `$lineTotal * 0.9` (bulk discount) vs `max(0.0, $lineTotal - 5.0)` (flat rebate) vs `$lineTotal` (none). No scalar parameter can unify these — the hole is a `Closure(float): float`. Unified: `computeTotals(array $items, float $rate, ?Closure $adjust = null)`. The set teaches the literal→expression frontier: where parameterize ends and higher-order extraction begins.

**(c)** Seed `invoice_totals`; the adjuster sits on the `$lineTotal` line.

**(d)** `holes: [{kind: "expression", php_type: "Closure(float): float", values_by_member: {"src/A.php": "fn(float $t) => $t * 0.9", ...}}]`; `kind: "extract_closure_hole"`; `refactor_difficulty: "hard"`.

**(e)** Rung 5.

**(f)** `requires: [ast_canonicalization, refactoring_synthesis]`; `{text:false, token:false, ast:true, metric:true, semantic:true}`.

**(g)** As (d).

## R-6 · `rf_strategy_object` (L8) — whole-algorithm hole (rung 7)

**(b)** Three classes each embedding a different discount algorithm inside an otherwise-identical checkout flow: tiered table walk vs flat percentage vs coupon lookup. Unified: `interface DiscountStrategy { public function apply(float $subtotal, array $context): float; }` + one `CheckoutCalculator` taking the strategy. The direct modernization of `samples/algorithm/1` (`BulkHardwareDiscounter`/`JanitorialSupplyDiscounter`) — `related.old_corpus_ref: "samples/algorithm/1"` (E-18), with `refactored/algorithm/1/code.php` as solution/ inspiration.

**(c)** 3 carriers (the flow methods are the cluster — the *shared* checkout skeleton; the strategy bodies are `unique_segments[].kind: "unique_logic"`); 1 support file (`DiscountStrategy` interface pre-existing in src/, role `support`, E-15 — realistic: interfaces often already exist); distractor + clean.

**(d)** `kind: "strategy_object"`; `holes: [{kind: "strategy", name: "discountStrategy", php_type: "DiscountStrategy"}]`; `collapses`: the 3 flow methods; `residual_unique_lines`: the 3 strategy bodies' SLOC (they *survive* the refactor as strategy classes — a new honesty field semantics: residual = code that remains after unification, relocated); `refactor_difficulty: "hard"`.

**(e)** Rung 7; prerequisite R-5.

**(f)** `requires: [semantic_reasoning, refactoring_synthesis, gap_tolerance]`; `{text:false, token:false, ast:false, metric:true, semantic:true}`.

**(g)** As (d).

## R-7 · `rf_template_method` (L8) — fixed skeleton, abstract steps (rung 7)

**(b)** Three report generators repeating fetch → aggregate → render → archive where fetch/archive are identical and aggregate/render differ per report type. Unified: abstract base with a `final public function generate()` orchestrator + two `abstract protected` steps. Mirrors `samples/process/*`. The cluster = the orchestration skeleton (fragments!); the differing steps = unique segments — template-method sets are *structurally* partial-duplication sets, which is why goals 2 and 3 share machinery.

**(c)** Seed `report_builder` multi-step payload; 3 carriers; support: none (base class is the solution, not an input). Distractor: an exporter with the same verbs but different order (order matters — SEM-11 vocabulary).

**(d)** `kind: "template_method"`; `holes`: two `strategy`-kind holes (`aggregate`, `render`) with `values_by_member` naming each member's step implementation; `unified_signature`: the abstract-class skeleton signature; fragment truth per E-4.

**(e)** Rung 7 (peer of R-6 — object vs inheritance flavors of the same unification; notes cross-reference).

**(f)** `requires: [semantic_reasoning, refactoring_synthesis, gap_tolerance, cfg_comparison]`; `{text:false, token:false, ast:false, metric:false, semantic:true}`.

**(g)** As (d).

## R-8 · `rf_pull_up_method` (L8) — sibling classes, identical method → parent (rung 8)

**(b)** Two sibling classes `CorporateCustomerNotifier` / `RetailCustomerNotifier` extending an existing `AbstractNotifier` (support file in src/), each defining a byte-identical `formatSalutation()`. Refactoring: pull the method up. The FIRST family whose refactor target is *class-hierarchy position*, not parameterization — `holes: []`, `hole_count: 0` is legal and meaningful (pure relocation).

**(c)** 2 carriers + 1 support (`AbstractNotifier`) + 1 distractor (a notifier whose `formatSalutation` differs subtly — locale-aware — `trap: true`: pulling THAT up would be a bug) + 1 clean. `role_composition: {carrier: 2, distractor: 1, clean: 1, support: 1}`.

**(d)** `kind: "pull_up_method"`; `collapses`: both methods; `unified_symbol: "AbstractNotifier::formatSalutation"`; `refactor_difficulty: "architectural"`; the distractor's near-miss is *refactor-specific* bait (over-eager pull-up), recorded via `advisability`-style reasoning in the trap `reason`.

**(e)** Rung 8.

**(f)** `requires: [refactoring_synthesis]` — detection itself is L1-trivial (byte-identical members; `{text:true, token:true, ast:true, metric:true, semantic:true}`): the set isolates refactor-target reasoning from detection difficulty, mirroring the probe philosophy.

**(g)** As (d).

## R-9 · `rf_pull_up_field_promote` (L8) — duplicated ctor wiring → promoted-property base (rung 8)

**(b)** Both siblings duplicate constructor property assignments (`$this->logger = $logger; $this->clock = $clock;`) and field declarations. Refactoring: pull up fields AND express the target in PHP 8 constructor property promotion (`public function __construct(protected LoggerInterface $logger, protected ClockInterface $clock)`) — the modern-PHP flavor makes the solution non-textual (the unified form uses syntax the members don't). Tests whether refactor-suggesting tools can propose *idiom-upgrading* unifications.

**(c)** As R-8's topology; cluster granularity `block` (the ctor bodies).

**(d)** `kind: "pull_up_field"`; `unified_signature` shows the promoted-ctor form; `notes` flag the promotion delta; `min_php: "8.1"`.

**(e)** Rung 8 companion.

**(f)** `requires: [refactoring_synthesis, ast_canonicalization]`; all detection classes true (blocks are near-identical).

**(g)** As (d).

## R-10 · `rf_parallel_switch_polymorphism` (L8) — same `switch($type)` in 3 methods (rung 9)

**(b)** The classic smell: `calculateFee()`, `describe()`, and `validate()` EACH contain a `switch ($this->accountType)` over the same three cases — the duplication is the *parallel conditional structure* (3 switches × 3 arms), and the refactoring is `replace_conditional_with_polymorphism`: three subclasses each owning its fee/description/validation. The cluster members are the three switch STATEMENTS (granularity `statement-run` — first real use), inside ONE class per carrier file... richer: 3 carrier files each with the 3-switch class for cross-file + intra-file duplication simultaneously (pairs with CP-01 topology).

**(c)** Seed `account_type_rules` (S-17 variant of tax_bracket) or `state_transition` (planned). One support file: the `AccountType` enum.

**(d)** `kind: "replace_conditional_with_polymorphism"`; `collapses`: nine switch statements; `holes`: one `strategy` hole per abstract method; multi-cluster option: c1 = the switches ACROSS files, c2 = the parallel-structure WITHIN each file (needs F-2; single-cluster fallback documented).

**(e)** Rung 9 — the architectural summit.

**(f)** `requires: [controlflow_normalization, semantic_reasoning, refactoring_synthesis, multi_cluster_reporting]`; `{text:false, token:true (arms share literals), ast:true, metric:true, semantic:true}`.

**(g)** As (d); `refactor_difficulty: "architectural"`.

## R-11 · `rf_consolidate_conditional` (L6) — same fragment in every branch (statement-run granularity)

**(b)** An `if/else` (or 3-arm match) where EVERY branch ends with the same 3-line audit-log + notification fragment. Refactoring: hoist the fragment below the conditional (`consolidate_conditional_fragments`). The cluster members are the per-branch fragments WITHIN one method — an *intra-method* clone at `statement-run` granularity, per carrier file (×3 files = 6–9 members, the highest member count in the corpus; also exercises CP-01 same-file semantics without needing full topology work since members share a file).

**(c)** Seed `access_guard` extended body (branch-heavy variant); members: 2–3 fragments per carrier file.

**(d)** `granularity: "statement-run"`; members' `symbol: null`, ranges = the 3-line runs; `kind: "consolidate_conditional_fragments"`; `holes: []`; `refactor_difficulty: "easy"`; scoring `line_tolerance: 1` (tight — the fragments are tiny), `min_members_for_credit: 4`.

**(e)** Rung 2½ — the smallest-granularity refactor rung; also the corpus's first sub-method member emission (needs F-4).

**(f)** `requires: [boundary_precision]`; `{text:false, token:false (each run ~25 tokens < floors — cited), ast:true, metric:false, semantic:true}` — doubles as a threshold probe.

**(g)** As (d).

## R-12 · `rf_extract_trait` (L8) — unrelated hierarchies, identical method (rung 8)

**(b)** Two classes in *different* hierarchies (a `CsvExportController` extending a framework controller; a `NightlyExportJob` extending a job base — both support files) that duplicate `buildExportRows()`. No common parent is possible → the correct unification is `extract_trait` (or composition). Ground truth records trait as intended, with `notes` acknowledging the composition alternative — introducing `alternatives[]` (optional array of second-choice kinds: `["extract_function"]`) so tools proposing a defensible alternative aren't scored zero (F-21 scoring reads it).

**(c)** 2 carriers + 2 supports + 1 distractor. Extends E.2.3's trait_use_variation from *transform* (trait vs inlined = detection axis) to *label* (what SHOULD happen).

**(d)** `kind: "extract_trait"`; `alternatives: ["extract_function", "strategy_object"]` (new E-6 sub-field); `collapses`: both methods.

**(e)** Rung 8 (hierarchy-constrained variant).

**(f)** `requires: [refactoring_synthesis]`; detection all-true (Type-1/2 members).

**(g)** As (d).

## R-13 · `rf_table_driven` (L8) — three hardcoded rule methods → config table + loop (rung 6–7)

**(b)** Three validators with parallel if-chains (`if (strlen($v) < 8) …; if (!preg_match('/[A-Z]/', $v)) …`) differing only in thresholds/patterns/messages. Refactoring: ONE `applyRules(array $rules)` loop + three rule TABLES (the members' logic collapses into data). Deepens `sem_config_driven` (SEM-10) from detection axis into labeled refactor with the rule-table as the extracted "hole": `holes[].kind: "literal"` but `php_type: "array<string, mixed>"` — data-holes at scale.

**(c)** Seed `password_policy` (S-3); carriers: password/username/apikey validators.

**(d)** `kind: "table_driven_config"`; `holes: [{kind:"literal", name:"rules", php_type:"array", values_by_member: {…: "PASSWORD_RULES table", …}}]`; solution shows the table extraction.

**(e)** Rung 6–7 bridge.

**(f)** `requires: [semantic_reasoning, refactoring_synthesis, literal_abstraction]`; `{text:false, token:true, ast:true, metric:true, semantic:true}`.

**(g)** As (d).

## R-14 · `rf_hole_position_ladder` (L5) — same 2 holes, five positions

**(b)** Hole count/kind fixed (2 literal holes); their POSITION walks: both in the first 3 lines / both mid / both last / split head+tail / adjacent mid. Position determines how contiguous the shared region stays around the holes — i.e. how obvious the extract-boundary is to a tool proposing `extract_method_with_holes`. The refactor analogue of P-4.

**(c)** Seed `invoice_totals`; holes = tax-rate + shipping-fee literals relocated via semantically-neutral statement reordering (ST-05 legality).

**(d)** `holes[].site` (E-6 per-hole location) does the tracking; `kind: "extract_method_with_holes"`.

**(e)** Rung 2 refinement ladder.

**(f)** `requires: [literal_abstraction, refactoring_synthesis, boundary_precision]`; all-true detection.

**(g)** As (d).

## R-15 · `rf_wrapper_absorb` (L7) — identical bodies, one wrapped in try/catch (decorator target)

**(b)** Carrier A = bare body; carrier B = same body inside `try { … } catch (Throwable $e) { $this->alerts->notify($e); throw $e; }`; carrier C = same body wrapped in timing instrumentation. Refactoring: extract the shared body; wrappers become decorators (or a middleware pipeline). Labels the *wrapper-vs-core* separation — a refactor kind (`decorator_for_noise` folded into kind enum as `extract_function` + `notes`, or first-class `kind: "decorator_for_noise"` — recommend first-class, added to E-6 enum) that no detection family expresses today. Wrapper lines are `unique_segments[].kind: "noise"` — third family fusing goals 2+3.

**(c)** Seed `webhook_verifier` (S-13 — verification logic people love to wrap). Wrappers from `uniques/` noise pool.

**(d)** `kind: "decorator_for_noise"`; `holes: []`; `collapses`: the three bodies; wrapper ranges as noise segments.

**(e)** Rung 5–6.

**(f)** `requires: [noise_filtering, gap_tolerance, refactoring_synthesis]`; `{text:false, token:true (body is a long run), ast:true, metric:true, semantic:true}`.

**(g)** As (d).

## R-16 · `rf_scatter_gather` (L8) — the unification must CROSS files (needs F-2/F-3)

**(b)** Member A = one cohesive method; member B = the same logic scattered across two files (a thin orchestrator + a helper holding the middle steps — SEM-08 geometry). The refactoring: gather B's scatter into the same shape as A, THEN extract the now-visible duplicate. Two-step refactor ground truth: `kind: "extract_function"`, plus new optional `preconditions: ["inline_expansion of src/HelperB.php::middleSteps"]` — refactor labels that require normalization *first*, mirroring how `requires` works for detection.

**(c)** Seed `access_guard` (its `sem_scatter` variant exists); B's member uses `fragments[]` across two files (the SEM-08 fragment case the schema anticipated).

**(d)** `preconditions[]` (E-6 addition); fragments across files; `kind: "extract_function"`.

**(e)** Rung 8 (cross-file).

**(f)** `requires: [inline_expansion, semantic_reasoning, refactoring_synthesis]`; `{text:false, token:false, ast:false, metric:false, semantic:true}`.

**(g)** As (d).

## R-17 · `rf_negative_control` (L8/L10) — looks refactorable, must NOT be unified

**(b)** The restraint test: structurally near-identical members from *diverging domains* — a `calculateLateFee()` for library books and a `calculateLateFee()` for loan payments, currently identical, but with `advisability: "inadvisable"` + `anti_reason: "domains evolve independently; unifying couples an accounting rule to a lending rule (accidental duplication per code_duplication_types.md #35 vs intentional)"`. A refactor-suggesting tool is scored on NOT proposing unification (or proposing with a coupling warning). Complements L0 (which tests detection restraint) with *refactoring* restraint — nothing in either corpus does this.

**(c)** 2 carriers, deliberately Type-1; distractor/clean standard. Cluster EXISTS (they ARE duplicates — detection tools should report them); only the refactor label says "leave it".

**(d)** `intended_refactoring: {kind: "none", advisability: "inadvisable", anti_reason: "...", hole_count: 0}`; new `anti_reason` (E-6 addition).

**(e)** Terminal rung of the refactor arc — judgment, not mechanics.

**(f)** Detection all-true; `requires: [semantic_reasoning, refactoring_synthesis]` — the *refactor* verdict needs domain reasoning.

**(g)** As (d).

## R-18 · `rf_dedup_distance_ladder` (L5→L8) — one refactoring, five detection depths

**(b)** The SAME intended refactoring (`parameter_toggle_boolean`, same holes) rendered at five detection difficulties: 001 members are Type-1 apart from the toggle; 002 adds RN renames; 003 adds WS/CM stack; 004 swaps one member to a CF variant; 005 full L9-style 8-axis stack. Proves the orthogonality claim of §10: refactor labels are a LAYER over detection difficulty, not a level — and gives refactor-aware tools a graded "how deep can you see the same abstraction" ladder.

**(c)** Seed `invoice_totals`; toggle = shipping step (R-3's).

**(d)** Identical `intended_refactoring` blocks in all five sets (byte-equal except member paths — a verifier delight, V-6 across sets); `interference_profile` walks 0→8 axes.

**(e)** A *vertical* rung ladder connecting L5→L8 (progression edges across levels).

**(f)** `requires` grows per set; expectations degrade token→ast→semantic; `refactoring_synthesis` constant.

**(g)** As (d).

## R-19 · `rf_multi_refactor_composite` (L9) — one set, two labeled refactorings (needs F-2)

**(b)** Two clusters, each with its own `intended_refactoring`: c1 = a parameterize_literal trio; c2 = a pull_up_method pair — coexisting in one 7-file set. Tests whether refactor-suggesting tools handle multiple simultaneous opportunities and rank them (new optional `priority: 1|2` per cluster refactoring: which unification a reviewer should do first, by payoff = collapsed SLOC × instance count).

**(c)** Seeds `invoice_totals` + notifier siblings; role_composition {carrier: 5, support: 1, distractor: 1, clean: 1}.

**(d)** Per-cluster E-6 + `priority`; `duplication.cluster_topology: "disjoint"`.

**(e)** Refactor-arc capstone.

**(f)** `requires: [refactoring_synthesis, multi_cluster_reporting]`; per-cluster expectations.

**(g)** Two blocks as above.

## R-20 · `rf_signature_drift` (L7) — same abstraction, incompatible surface signatures

**(b)** Members compute identically but their signatures diverged: `computeTotals(array $lineItems, float $taxRate, float $discountRate)` vs `computeTotals(Invoice $invoice)` (data arrives bundled — the method unwraps it in 2 extra lines) vs `computeTotals(array $config)` (rate/discount inside an options array, `$config['taxRate'] ?? 0.0`). The unified signature must pick a canonical parameter shape and adapters. Labels the hardest practical unification blocker — *interface drift* — with `holes[].kind: "type"` (the parameter SHAPE is the hole). Pairs with SY-01 named-args and API-07 data-shape axes.

**(c)** Seed `invoice_totals` + 2 hand variants (unwrap preludes are unique_segments); support: the `Invoice` DTO.

**(d)** `kind: "introduce_parameter_object"`; `holes: [{kind: "type", name: "input shape", php_type: "TotalsInput"}]`; `preconditions: ["normalize parameter shapes"]`.

**(e)** Rung 6½.

**(f)** `requires: [api_abstraction, semantic_reasoning, refactoring_synthesis]`; `{text:false, token:false, ast:true, metric:true, semantic:true}`.

**(g)** As (d).

---

### §4 summary table

| ID | Family | kind | holes | Rung | Detection level |
|---|---|---|---|---|---|
| R-1 | rf_param_literal | parameterize_literal | 1 literal | 1 | L4 |
| R-2 | rf_hole_ladder | …→introduce_parameter_object | 1→6 | 1–2,6 | L4–L6 |
| R-3 | rf_toggle_boolean | parameter_toggle_boolean | 1 toggle | 3 | L5 |
| R-4 | rf_toggle_enum | parameter_toggle_enum | 1 enum | 4 | L6 |
| R-5 | rf_closure_hole | extract_closure_hole | 1 expression | 5 | L7 |
| R-6 | rf_strategy_object | strategy_object | 1 strategy | 7 | L8 |
| R-7 | rf_template_method | template_method | 2 strategies | 7 | L8 |
| R-8 | rf_pull_up_method | pull_up_method | 0 | 8 | L8 (detection L1-easy) |
| R-9 | rf_pull_up_field_promote | pull_up_field | 0 | 8 | L8 |
| R-10 | rf_parallel_switch_polymorphism | replace_conditional_with_polymorphism | per-method strategies | 9 | L8 |
| R-11 | rf_consolidate_conditional | consolidate_conditional_fragments | 0 | 2½ | L6, statement-run |
| R-12 | rf_extract_trait | extract_trait (+alternatives) | 0 | 8 | L8 |
| R-13 | rf_table_driven | table_driven_config | 1 data table | 6–7 | L8 |
| R-14 | rf_hole_position_ladder | extract_method_with_holes | 2, position walks | 2 | L5 |
| R-15 | rf_wrapper_absorb | decorator_for_noise | 0 | 5–6 | L7 |
| R-16 | rf_scatter_gather | extract_function + preconditions | 0 | 8 | L8 |
| R-17 | rf_negative_control | none (inadvisable) | 0 | judgment | L8/L10 |
| R-18 | rf_dedup_distance_ladder | parameter_toggle_boolean | 1 | vertical | L5→L8 |
| R-19 | rf_multi_refactor_composite | two kinds, prioritized | mixed | capstone | L9 |
| R-20 | rf_signature_drift | introduce_parameter_object | 1 type | 6½ | L7 |

**E-6 enum additions surfaced by this section:** `alternatives[]`, `anti_reason`, `preconditions[]`, `priority`, `parameter_object`, `holes[].site`, kind `decorator_for_noise`.

---

# 5. Basic → advanced → combined progression (goal 4)

The corpus has a coarse ladder (11 levels, 7 bands). Goal 4 needs a **walkable, labeled, fine-grained** one. The machinery is E-8 (2-D difficulty) + E-9 (`progression` block); this section defines the *content* of the ladder: rung semantics, the 18 axis arcs, the grid, minimal pairs, bridges, and the ordered walk artifact.

## G-1 · Rung semantics — the five intra-family rungs, normatively defined

Every family's 5 sets get `progression.rung` 1–5 with STANDARD meanings (retrofit to all 108 existing families is mechanical — the recipes already order sets roughly this way; the label makes it contractual):

| rung | rung_label | Contract |
|---|---|---|
| 1 | `minimal` | ONE code, ONE occurrence, smallest legal param (e.g. WS-03 `blank_lines:1` at one boundary). The **minimal pair** — the family's unit test. |
| 2 | `light` | Same code(s), 2–3 occurrences or one param step up. |
| 3 | `moderate` | All natural occurrences, mid params; or the family's second code joins. |
| 4 | `heavy` | Maximum intensity of the family's own axes (`intensity_dial: 6–8`). |
| 5 | `combined` | The family's axes + ONE adjacent-axis guest code (the *only* rung allowed a second axis below L9) — the on-ramp to L9 mixing. |

Retrofit task: emit `progression` for all existing sets from the recipes' current ordering + params (a one-time `gen/tools/retrofit_progression.php` pass, human-reviewed where ordering is ambiguous).

## G-2 · The 18 axis arcs — cross-level named walks

An **arc** is the ordered chain of families that grow one axis from its minimal pair to its combined form and into the mixes. `progression.axis_arc` names it; `manifest.php` compiles each arc's full set ordering into `progression.json` (G-8).

| Arc | Chain (existing → new) |
|---|---|
| `whitespace` | L2 ws_* (13 fams, rungs 1–5 each) → C-1 mix_t1_blizzard → C-4 |
| `comments` | L3 cm_* → C-1 → C-4 |
| `rename` | L4 rn_* → mix_rn_ws / mix_rn_cm_ws → C-2 → C-4 |
| `literals` | L4 lt_* → C-2 |
| `types` | L4 ty_* → T-TY additions (union types) → C-2 |
| `namespaces` | L4 ns_* → C-8(4) → C-2 |
| `statement` | L5 st_* → C-3 mix_t3_torrent |
| `controlflow` | L6 cf_* → C-11 selector-composed → C-4 |
| `boolean` | L6 bl_* → K-9..K-11 probes → C-11 |
| `api` | L7 api_* → C-10 modernization → C-11 |
| `semantic` | L8 sem_* → C-11 → R-6/R-7 |
| `noise` | (new) K-19 NZ probes → C-12 noise_blizzard → R-15 |
| `encoding` | (new) K-24..K-27 ENC probes → C-13 gauntlet |
| `topology` | L10 adv_same_file/overlap/many_files → C-15 multi-cluster → P-12 braided |
| `partiality` | (new) P-5 ratio arc → P-1/P-3 budgets → P-10 shred → C-17 |
| `refactor` | (new) R-1 → R-2 → R-3 → R-4 → R-5 → R-6/R-7 → R-8..R-12 → R-17 judgment |
| `modernization` | (new) SY/LEG probes → C-10 |
| `mixed` | L9 mix_* ordered by axis_count → C-5/C-6/C-7 → C-4 capstone |

## G-3 · The axis × intensity grid — filling the plane

Plot every set at `(axes.breadth, axes.intensity)` (E-8). Today's corpus occupies a thin L-shape: single-axis levels hug breadth=1 with varying intensity; mix_* hug breadth 2–5 at moderate intensity. The grid program **deliberately commissions sets for empty cells**: (breadth 1, intensity 8) — one axis, brutal (exists partially); (breadth 12, intensity 3) — C-1/C-14 rung 1; (breadth 20, intensity 8) — C-4; (breadth 3, intensity 8 + partiality 0.4) — P/C fusions. Manifest emits a `grid_coverage` report (F-14) showing cell occupancy so future recipes target holes. **Acceptance target: every cell of the 5×5 downsampled grid (breadth buckets 1/2–4/5–8/9–14/15+, intensity buckets 0–1/2–3/4–5/6–7/8) holds ≥ 2 sets.**

## G-4 · Minimal-pair index — "one set per capability, zero confounders"

Formalize rung-1 sets into a queryable index: `manifest.json.minimal_pairs: [{capability, set_id, code}]` — for each of the 18 requires-values (12 existing + 6 new), the ONE cheapest set that isolates it. §6's K-* probe families supply missing entries. This is the "start here" list for a detector author on day one: 18 sets, each failing for exactly one reason.

## G-5 · Per-axis basic→moderate→heavy→combined arcs INSIDE L2–L8 (new sets)

Levels L2–L8 already have families ≈ axes, but several families jump from light to heavy without intermediate rungs, and some axes lack a `combined` on-ramp entirely. Gap-fill program (new sets INSIDE existing families, raising some families from 5 to 7–8 sets — requires relaxing the uniform 5/family convention, F-19):

- `ws_indent_width`: add rung-5 (indent + brace-style guest) — currently pure.
- `cm_docblock`: add rung-1 TRUE minimal pair (one docblock summary-line word changed; extends E.2.5 §5.4 cm_docblock_only with a rung STRUCTURE rather than a single set).
- `rn_locals`: add rung-2 "one variable renamed, used 6 times" (scope-tracking micro-step between "renamed once-used var" and "all locals").
- `lt_arrays`: rung-4 heavy = reorder + retype array literals simultaneously.
- `st_reorder`: rung-5 combined = reorder + rename guest.
- `cf_loop_forms`: rungs for `for`→`foreach`→`while`→`array_map` as four stepped sets (today one variant hop).
- `api_strings`: ladder `sprintf` → interpolation → concat → `implode` — four rungs of the same emission.
- `sem_rule`: rung-1 minimal = the two-idiom age check (`$age >= 18` vs `!($age < 18)`) before the full five-idiom spread.

## G-6 · Bridge sets — one-axis deltas across level boundaries

For each level boundary L(n)/L(n+1), commission a **bridge pair**: two sets identical except for exactly the axis that defines the higher level (`progression.bridge_pair` points to the twin). Example: `L05-st_insert_logging-005` vs a new `L06-cf_if_ternary-000b` sharing seed/scaffold/params so the ONLY delta is the ternary rewrite. A tool passing the lower twin and failing the upper localizes the marginal capability with zero confounders — minimal pairs *between levels*, which single-axis design gives within levels but never across them. 10 bridge pairs (L1→L2 … L10→L11).

## G-7 · Difficulty-decile audit — a monotone walk with no cliffs

`score`(clamped) currently clusters at level_base+ε values; deciles 46–59 and 61–71 are thin (mechanically checkable). The progression compiler (F-14) emits a decile histogram; the gap-fill program commissions sets targeting empty deciles via intensity dials, so the global ordered walk (G-8) climbs in steps of ≤ ~5 score points — a tool builder never faces a 20-point jump between consecutive walk nodes.

## G-8 · `testsets/progression.json` — the machine-readable walk (generated)

Built by `gen/manifest.php` (F-14) from E-9 fields:

```json
{
  "schema_version": 1,
  "walk": ["L01-ex_function-001", "…", "L10-mix_full_spectrum-005"],
  "arcs": { "rename": ["L04-rn_locals-001", "…"] },
  "grid": [ {"set_id": "L09-mix_t1_blizzard-005", "breadth": 20, "intensity": 5, "partiality": 0.0} ],
  "minimal_pairs": { "commutative_reordering": "L06-probe_commutative-002" },
  "bridges": [ {"lower": "L05-st_insert_logging-005", "upper": "L06-cf_if_ternary-000b", "delta_axis": "CF"} ],
  "prerequisite_edges": [ ["L02-ws_blank_inside-001", "L09-mix_ws_cm-001"] ]
}
```

Consumers: the bench (order results by walk position → "how far did the tool get before first miss"), tool-builder tutorials ("implement capability X to pass node N"), and the capability profile's narrative ("Tool T walks cleanly to node 61, first by-design stop at 74").

## G-9 · `walk_report` bench mode — progression-aware scoring

`bench/run-testsets.php --walk`: run sets IN walk order, stop-loss after k consecutive misses per arc, and emit "frontier" per arc: the last set passed and first failed, plus the failed set's `requires` delta vs the passed set — the automated "what to build next" recommendation for detector authors. Cheap to implement (ordering + a diff of requires arrays) and converts the corpus from a scoreboard into a curriculum.

---

# 6. Detector-capability probe sets (goal 5)

**The probe pattern.** A probe family = **2 sets**: `-001` CONTROL (pristine exact clone, comfortably above every tool floor: ~120 tokens, 12+ lines) and `-002` PROBE (identical except ONE code at minimal intensity). Verdict logic (consumed by F-20): control ✓ + probe ✓ ⇒ capability PRESENT; control ✓ + probe ✗ ⇒ capability ABSENT (crisp); control ✗ ⇒ NO VERDICT (tool can't see the baseline — threshold problem, not capability problem). Probes deliberately sit at LOW score (probe difficulty ≈ level_base + one small weight) — **capability isolated from difficulty**, fixing F.5(d). All probes carry `capability_probe` (E-12), live in their natural level dirs with family prefix `probe_`, and use `role_composition: {carrier: 2, distractor: 1, clean: 2}` (two members suffice; the third carrier adds nothing to a binary verdict).

## K-1…K-18 · Normalization-capability probes (one per requires-value and then some)

| ID | Family (level) | Probe delta (exact, minimal) | Capability / requires | Probe detection_expectation |
|---|---|---|---|---|
| K-1 | `probe_ws_blank` (L2) | ONE blank line inserted (WS-03 `blank_lines:1`) | whitespace_normalization | text:F token:T ast:T metric:T sem:T |
| K-2 | `probe_ws_wrap` (L2) | ONE long line wrapped (WS-06 `occurrence:1`) | whitespace_normalization (line-based split) | text:F token:T ast:T metric:F sem:T |
| K-3 | `probe_cm_line` (L3) | ONE `//` comment added (CM-01) | comment_stripping | text:F token:T ast:T metric:T sem:T |
| K-4 | `probe_cm_mid` (L3) | ONE `/* … */` mid-statement (CM-08) | comment_stripping (token-interleaved) | text:F token:T ast:T metric:T sem:T |
| K-5 | `probe_rn_one_var` (L4) | ONE local renamed, used 5× (RN-01) | identifier_canonicalization | text:F token:T(param-abstracting) ast:T metric:T sem:T |
| K-6 | `probe_rn_case` (L4) | camelCase→snake_case, identifiers only (RN-05) | identifier_canonicalization (convention) | as K-5 |
| K-7 | `probe_lt_number` (L4) | ONE numeric literal changed (LT-01) | literal_abstraction | text:F token:T ast:T metric:T sem:T |
| K-8 | `probe_ns_alias` (L4) | `use A\B\C;` + short name ↔ FQCN inline (NS-01) | ast_canonicalization (name resolution) | token:F ast:T |
| K-9 | `probe_demorgan` (L6) | ONE `!($a && $b)` ↔ `!$a \|\| !$b` (BL-01), rest byte-identical | commutative_reordering + controlflow (boolean algebra) — extracts BL-01 from L6 bundles per E.2.5 §5.2 | token:F ast:F(most) metric:T sem:T |
| K-10 | `probe_commutative` (L6) | ONE `$a + $b` ↔ `$b + $a` (BL-03) | commutative_reordering | token:F ast:T(if canonical operand order) sem:T |
| K-11 | `probe_bool_split` (L6) | ONE `if ($a && $b)` ↔ nested ifs (BL-02) | controlflow_normalization | token:F ast:T sem:T |
| K-12 | `probe_ternary` (L6) | ONE if/else ↔ ternary (CF-01) | controlflow_normalization | token:F ast:T sem:T |
| K-13 | `probe_deadcode` (L5) | ONE unreachable `if (false)` block (ST-02) | deadcode_elimination | token:F(strict)/T(fuzzy) ast:T sem:T |
| K-14 | `probe_loop_form` (L6) | ONE `for(;;)` ↔ `foreach` (CF-04) | controlflow_normalization (loop canon) | token:F ast:T sem:T |
| K-15 | `probe_map_loop` (L7) | ONE `foreach` accumulate ↔ `array_map` (API-01) | api_abstraction | token:F ast:F metric:F sem:T |
| K-16 | `probe_inline_helper` (L8) | 3 statements ↔ private-helper call (SEM-02) | inline_expansion | token:F ast:F sem:T |
| K-17 | `probe_early_return` (L6) | ONE guard `if (!$x) return` ↔ nested (CF-03/05) | cfg_comparison | token:F ast:T(CFG) sem:T |
| K-18 | `probe_rule_idiom` (L8) | age check via 2 idioms (SEM-01 minimal) | semantic_reasoning | all F except sem:T |

Each = 2 sets, ~36 sets total; every entry feeds G-4's minimal-pair index and registry `probe_family` back-references (E-16).

## K-19…K-23 · Noise probes (require T-NZ implementations)

| ID | Family | Probe delta | requires |
|---|---|---|---|
| K-19 | `probe_nz_logging` (L5) | ONE `$this->logger->info('…', ['id' => $id]);` line (NZ-01, density 1) | noise_filtering |
| K-20 | `probe_nz_timing` (L5) | `microtime(true)` pair top/bottom (NZ-09) | noise_filtering |
| K-21 | `probe_nz_attr` (L3/L4) | ONE `#[Route('/totals')]`-style attribute (NZ-04) | comment_stripping-adjacent (attributes are NOT comments — the trap) |
| K-22 | `probe_nz_flag` (L6) | body wrapped in `if ($this->flags->enabled('v2'))` (NZ-06) | noise_filtering + cfg_comparison |
| K-23 | `probe_nz_i18n` (L4) | ONE string wrapped `$this->translator->trans('…')` (NZ-05) | literal_abstraction + noise_filtering |

K-21 deserves emphasis: PHP 8 attributes look like metadata but are *tokens* — tools that strip docblocks pass K-3 and fail K-21, a diagnosis no current set can produce.

## K-24…K-27 · Encoding probes (require T-ENC implementations)

| ID | Family | Probe delta | Expected divergence |
|---|---|---|---|
| K-24 | `probe_enc_bom` (L2) | UTF-8 BOM on one member (ENC-01) | text:F; token:T iff lexer skips BOM |
| K-25 | `probe_enc_crlf` (L2) | CRLF line endings on one member (ENC-04) | line-based tools stumble; token:T |
| K-26 | `probe_enc_nbsp` (L2) | ONE U+00A0 in indentation (ENC-02) | tokenizers treating NBSP as identifier-char produce parse divergence — deeply diagnostic |
| K-27 | `probe_enc_heredoc` (L7) | ONE `"…"` ↔ `<<<SQL … SQL;` (ENC-06) | token:F(string token differs + line count shifts) ast:T |

Each needs `hygiene_exceptions` (C-13) so verify.php tolerates the deliberate violations.

## K-28 · `probe_token_floor` + `probe_line_floor` (L10) — threshold bracketing, not just laddering

**(b)** `ex_size_ladder`/`adv_below_threshold` show threshold EFFECTS; these families MEASURE the floor: 8 sets with pristine exact clones of 30/40/50/60/70/80/90/100 tokens (resp. 3/4/5/6/7/8 lines) — a binary-search grid over §8.1's known defaults (phpcpd 70t/5l, jscpd 50t, PMD 100t, Simian 6l). The profile generator reads pass/fail per rung and emits `threshold_estimate: {tokens: [60,70], lines: [5,6]}` per tool — measured, not documented, catching configuration drift (e.g. a CI running jscpd with non-default `--min-tokens`).

**(c)** Seed `pager` (S-class, planned) trimmed/padded to exact token counts by a token-targeting mount (F-10); notes cite exact counts (already mandatory).

**(d)** `capability_probe.capability: "threshold_floor"` (profile-only pseudo-capability); family overrides sets-per-family to 8 (F-19).

**(f)** expectations set per §8.1 per rung — the rare family where `expected_detection_by_tool` (the optional, unpopulated field) becomes worth emitting per set.

## K-29 · `probe_boundary_docblock` (L3) — the ±2-line off-by-docblock probe

**(b)** Two sets: member ranges identical logic, but one member's function carries a 4-line docblock and the ground truth *excludes* it (start_line at `function`, per line-audit). Tools that include leading docblocks in regions drift +4 lines — caught by `line_tolerance: 2` failing while `line_tolerance: 6` re-score passes. Profile output: mean boundary offset per tool (the R8 region-accuracy axis, currently thinly supported). Companion `probe_boundary_trailing` for `}` vs trailing-comment inclusion.

**(d)** `capability_probe.capability: "boundary_precision"`; scoring strict; bench re-scores at 3 tolerances (F-17) to compute offset rather than binary fail.

## K-30 · `probe_region_merge` (L10) — adjacent-distinct-clones merge bait

**(b)** Two DIFFERENT small clone clusters placed back-to-back (separated by 1 line) in the same file pair (needs F-2): tools that merge adjacent matches report one fat region spanning both — precision hit + `multi_cluster_reporting` fail; tools with proper cluster identity report two. The inverse of gap tolerance: *separation* tolerance.

**(d)** `duplication: {clusters: 2, cluster_topology: "disjoint"}` with 1-line spacing recorded in notes; `requires: [multi_cluster_reporting, boundary_precision]`.

## K-31 · `probe_fp_suite` (L0 extension) — three surgical FP probes

Extends E.2.5's fp_token_bait with binary-verdict discipline: (1) `probe_fp_getters` — two classes, 8 one-line getters each, same property names, different domains (`known_tool_fp: true` — an *acceptable* flag); (2) `probe_fp_arraymap` — the same `array_map(fn($r) => $r->toArray(), $rows)` idiom line in both files (idiom, not clone; trap:true); (3) `probe_fp_setup` — two PHPUnit-style `setUp()` methods with parallel fixture shapes. Verdict: FP-resistance per bait CLASS (boilerplate / idiom / fixture), feeding profile axis 4 with three separate numbers instead of one blended trap_fp.

## K-32 · `probe_scale_files` + `probe_scale_sloc` (L10) — scaling probes with measured curves

**(b)** Generated size series (needs F-9 inflation): 10 / 50 / 200 / 500 files (one constant clone pair hidden inside), and 1k / 5k / 20k SLOC single-file series. The harness already scrapes wall/RSS per run (`/usr/bin/time -v`); the profile fits the curve (linear/quadratic) and reports "phpcpd: O(n) 0.4s→9s; jscpd: O(n²) suspected" — R8 axis 6 (Scaling), currently unbuildable for lack of big sets. Raw material for realistic bulk: sanitized `/home/my/logs/smarty_templates_c/` files (587 files, already designated for adv_generated).

**(d)** New `set.json.files[]` entries at scale — manifest `corpus_stats` handles it; sets marked `capability_probe.capability: "scaling"` and EXCLUDED from default `--all` bench runs (opt-in `--scale` flag) to keep CI fast.

## K-33 · `probe_report_format` (harness-level, no new sets) — region-identity fidelity

Not a corpus family but a probe MODE: run each tool on `adv_overlap`/K-30 sets and check whether its output format can even EXPRESS overlapping clusters (jscpd pairwise JSON can; phpcpd PMD-XML duplications can; Simian plain text loses member identity). Emitted as a profile "reporting ceiling" table — distinguishes "tool can't detect" from "tool's FORMAT can't say" (a real R8 nuance nobody records).

## K-34 · The Tool Capability Profile generator — the R8 payoff artifact (spec sharpened)

`bench/profile.php` (F-20), consuming: run-testsets results + all set.json/expected.json + progression.json + probe verdicts. Emits per tool `bench/results/testsets-capability-<tool>.md` + machine `capability-profile.json`:

```json
{
  "tool": "phpcpd", "version": "6.0.3", "flags": "--fuzzy --min-lines 5 --min-tokens 50",
  "verdicts": {
    "whitespace_normalization": {"status": "present", "evidence": ["L02-probe_ws_blank-002"]},
    "commutative_reordering": {"status": "absent", "evidence": ["L06-probe_commutative-002"]},
    "gap_tolerance": {"status": "partial", "evidence": ["L11-pd_segment_count_walk-001..003 pass, 004+ fail"]}
  },
  "threshold_estimate": {"tokens": [60, 70], "lines": [5, 5]},
  "fp_resistance": {"boilerplate": 0.9, "idiom": 0.4, "fixture": 0.7},
  "boundary_offset_mean_lines": 2.3,
  "walk_frontier": {"arc": "rename", "last_pass": "L04-rn_locals-004", "first_fail": "L04-rn_case_style-001"},
  "scaling": {"files_curve": "linear", "sloc_curve": "linear", "peak_rss_mb_at_500_files": 210},
  "by_design_misses": 41, "capability_gap_misses": 12
}
```

The six R8 axes each map to a section; the *capability-gap vs by-design* split comes from `detection_expectation` (expected-recall metric, §13 of the master plan). The narrative verdict paragraph is templated from the JSON. This artifact is what makes every set family above legible as tool-building guidance — it should be the FIRST thing built after schema v2.

---

# 7. New seeds & domains

**Beyond the 8 already planned** (http_client, validation_rule, cache_manager, pager, state_transition, json_serializer, query_builder, event_dispatcher — E.2.2). Selection principles for THIS batch: (i) **multi-symbol** seeds to unlock multi-cluster/partial/file-granularity sets; (ii) **L/XL size classes** (all current seeds are 20–24-line M — too small to host 4 fragments or 32 unique lines); (iii) seeds with **natural refactor holes** (literals/toggles/strategies already in the domain); (iv) seeds whose domain unlocks **deep semantic/API variant pools** beyond access_guard (breaking the single-seed bottleneck that forces 2-carrier `role_composition` at L7/L8); (v) seeds designed as **noise carriers** or **encoding carriers**.

## S-1…S-24 · The seed catalog

| ID | Seed | Domain / clone symbol | Size | Key unlocks (families) | Natural interference |
|---|---|---|---|---|---|
| S-1 | `money_math` | cents-safe arithmetic, `allocate(int $cents, array $ratios): array` | S–M | R-4 rounding-enum; API float↔bcmath↔intdiv variants | LT-01, EX-01, API-06/15, SEM-11 |
| S-2 | `slug_generator` | `slugify(string $title): string` — trim/lower/transliterate/regex/collapse | S | SEM-11 order probes; P-7 grow/shrink; API-02/03 | API-02/03, SEM-11, RN-01 |
| S-3 | `password_policy` | `assess(string $pw): array` — length/classes/entropy/denylist | M | R-13 table-driven; BL-* heaven (4+ independent predicates) | BL-01/02/03, CF-03/05, LT-01/02 |
| S-4 | `csv_export` | `writeRow(array $row): string` — escaping/quoting/joining | S–M | API-06 (fputcsv vs manual); inverse pairing with csv_import for cross-seed distractors | API-02/06, LT-02, ST-05 |
| S-5 | `retry_backoff` | `attempt(callable $op, int $max): mixed` — exponential backoff loop | M | API-04 recursion↔iteration; NZ-09 timing reads natively | API-04, ST-01, NZ-09, CF-04 |
| S-6 | `tax_bracket` | `taxFor(int $cents): int` — progressive bracket walk | M | API-05 table↔if-chain; R-1/R-2 literal holes (bracket edges!); P-8 erosion | API-05, LT-01, CF-02, BL-05 |
| S-7 | `phone_normalizer` | `normalize(string $raw, string $region): string` | S–M | API-03 regex↔char-loop; ENC-05 escape probes (pattern strings) | API-03, LT-02, ENC-05 |
| S-8 | `ini_size_parser` | `toBytes(string $iniValue): int` — "128M"/"2G" suffix multipliers | S | CF-02 match↔switch minimal pairs (K-12); tiny threshold-probe payloads | CF-02, LT-01, API-05, SY-09 |
| S-9 | `duration_formatter` | `humanize(int $seconds): string` — "2h 15m" | S | API-09/15 (intdiv vs DateTime); K-probe scale | API-09/15, LT-02, CF-01 |
| S-10 | `basket_pricing` | **multi-symbol**: `addItem`, `applyVoucher`, `total` (3 payloads) | L (3×M) | C-15 multi-cluster single-domain; class-granularity clones; P-2 | full text/ast range + API-01 |
| S-11 | `report_builder` | **L-class** (70–100 lines): assemble header/sections/footer/summary | L | THE partial-dup host (P-1/P-3/P-10/P-13); R-7 template-method; CP-05 large-file cores | ST-*, UQ-*, SEM-08, CM-09 |
| S-12 | `user_repository` | **multi-symbol** CRUD: `findById`/`findByEmail`/`insert`/`update` | L (4×S) | P-11 fork-pair; intra-file self-similarity (the 4 methods are near-dups of EACH OTHER → CP-01 same-file, R-13); SEM-07 ORM↔SQL | RN-01/02, LT-02, SEM-07, ST-06 |
| S-13 | `webhook_verifier` | `verify(string $payload, string $sig, string $secret): bool` — HMAC | S–M | R-15 wrapper-absorb; **semantic near-miss gold**: `hash_equals` vs `===` is NOT equivalent → principled `trap:true` distractor + adv_near_miss_semantic reinforcement | NZ-03, API-02, BL-01 |
| S-14 | `template_renderer` | `render(string $tpl, array $vars): string` — placeholder substitution | M | API trio `str_replace` loop ↔ `strtr` ↔ `preg_replace_callback`; adv_html_mix payloads; ENC-06 heredoc templates | API-02/03, ENC-06, LT-02 |
| S-15 | `settings_merger` | `merge(array $base, array $override): array` — deep merge | M | API-07 (`array_replace_recursive` vs manual recursion vs `+`); NU-01/02/03 null-handling ladder | API-04/07, NU-*, CF-04 |
| S-16 | `address_formatter` | `format(array $addr, string $country): string` | M | R-4 enum-toggle (country cases); NZ-05 i18n; SEM-10 config-driven | CF-02, LT-02, NZ-05, SEM-10 |
| S-17 | `file_upload_guard` | `acceptable(array $file): bool` — ext/mime/size checks | S–M | BL split/combined ladders; security-noise NZ-03; K-9/K-11 probe host | BL-01/02, CF-03/05, LT-01/02 |
| S-18 | `pagination_links` | `links(int $page, int $pages): string` — emits HTML | M | adv_html_mix with REAL html; API-02; WS probes inside markup | CP-08, API-02, WS-*, ENC-* |
| S-19 | `weekday_scheduler` | `nextRun(DateTimeImmutable $from, array $days): DateTimeImmutable` | M | API-09 DateTime↔DateTimeImmutable↔strtotime; LT-03 day-array literals | API-09, LT-03, CF-04, TY-01 |
| S-20 | `xml_config_reader` | `parse(string $xml): array` — SimpleXML vs DOMDocument | M–L | API-08 serialization; ENC heredoc/escape hosts | API-08, ENC-05/06, ST-03 |
| S-21 | `stopwatch_metrics` | `measure(callable $fn, string $metric): mixed` | S | purpose-built **noise carrier**: every NZ code has an idiomatic anchor point (K-19..K-23 host) | NZ-01..10, ST-01 |
| S-22 | `discount_tiers` | tiered volume discount (port of `samples/algorithm/1`) | M | R-6 strategy canonical; E-18 old-corpus bridge proof-of-concept | LT-01, API-05, CF-02, SEM-01 |
| S-23 | `inventory_reservation` | `reserve(int $sku, int $qty): bool` — check/lock/decrement/release | M–L | temporal-coupling (acquire/release) semantics; SEM-11 order variants; ST-05 hazard pairs (which reorders are ILLEGAL — negative-proof material) | ST-05, SEM-11, CF-03, NZ-08 |
| S-24 | `json_pointer` | `resolve(array $doc, string $pointer): mixed` — RFC 6901 walk | M | API-04 recursion↔iteration deep case; NU-03 nullsafe ladder; K-15 alt host | API-04, NU-*, CF-05, LT-02 |

**S-25 · `legacy_order_export` — the XL seed (150–220 lines).** A deliberately ugly, realistic export routine (fetch, filter, group, format, write) whose SIZE unlocks: CP-05 large-file sets with *interior* clones at line 300+, P-10 shredding with 6+ fragments, multi-region file-granularity members (P-11), and R-7 template arcs. Written once, exploited by a dozen families. Ships with `uniques/` pool (12 tagged snippets) and 4 payload sub-regions (multi-symbol sentinels).

**S-26 · `variant_rich_guard` — access_guard's successor, designed FOR variant breadth.** The single highest-leverage seed: a 30-line authorization+rate-limit+audit decision engineered so that **every selector group has ≥3 hand-written variants** (CF×5, BL×3, EX×2, NU×2, API×4, SEM×6 = 22 variants + 6 composed pairs for C-11), each proven by one 100+-combo `equivalence_test.php` (property-based input matrix, F-16). Breaks the "one variant per family → 2-carrier role_composition" bottleneck (F.0-6): L7/L8 families regain honest 3-carrier sets.

## Seed-format extensions (apply to all new seeds; backfill where cheap)

1. **Multi-symbol sentinels:** `// <<<PAYLOAD:basket_pricing/addItem>>>` … — `seed.json.symbols[]` array replaces scalar `symbol`; `Payload.php` learns qualified names. Unlocks multi-cluster (F-2) and file-granularity members.
2. **`uniques/` pool:** `gen/seeds/<seed>/uniques/<tag>.php` — 5–15-line tagged snippets with declared insertion anchors (`after_statement: 3`), consumed by UQ transforms; each snippet carries a header comment with its `kind` (unique_logic|noise) and IO-effect declaration (pure / output-extending / logging-only) so V-5/V-6 know the proof obligations.
3. **`holes.json`:** declares the refactor holes a seed supports — `[{hole_id, kind, site_symbol, php_type, sample_values[]}]` — the R-* recipe compiler reads it to derive members from `solution/Unified.php`.
4. **Composed-variant naming:** `variants/<a>+<b>.php` (C-11) with equivalence coverage asserted by the same test.
5. **Size-class targets:** every new seed declares `size_class` S/M/L/XL and REAL `line_count`/`token_count` (verify recomputes — the current seed.json variant-list drift showed metadata rots without checks).
6. **Mandatory `equivalence_test.php`** for any seed with variants — closing the existing hole (csv_import has none today), and required by verify for L6+ regardless of seed.
7. **Distractor siblings:** each seed ships a paired `gen/distractors/nearmiss_<seed>.php` tuned to <40-token overlap (the rule today) — plus, new: a `gen/distractors/semantic_nearmiss_<seed>.php` whose overlap is CONCEPTUAL (same rule, wrong outcome — e.g. S-13's `===` comparator) for L8/L10 trap depth.

---

# 8. New transform axes / registry codes

**Beyond the 10 already planned** (E.2.3: ENC-06 impl, docblock↔attribute, SY-01 named args, auto match↔switch, API-10 encapsed↔concat, TY-03 union types, trait-use, SY-02 arrow fn, named-ctor factory, SEM-05 property injection). Registry conventions per E-16: every new code declares `kind`, `clone_type`, `weight`, `impact`, `requires`, `min_php`, `probe_family`, `composable_with`. Weight bands follow the house scale: text 2–5, ast 4–12, selector 14–24, wrapper 8–14.

## T-SY · Syntax-equivalence group (NEW group; kind=ast; the "same AST, different spelling" axis)

Cheap, high-frequency real-world variation; most are one-token rewrites nikic can drive mechanically. All `clone_type: type-2` unless noted, `requires: [ast_canonicalization]`.

| Code | Name | Rewrite (both directions) | Weight | min_php |
|---|---|---|---|---|
| SY-03 | trailing_comma | last array/arg/param item `,` added/removed | 2 | 8.0 |
| SY-04 | short_long_array | `array(1,2)` ↔ `[1,2]` | 3 | — |
| SY-05 | string_quote_style | `'no interp'` ↔ `"no interp"` (only when interp-free) | 3 | — |
| SY-06 | yoda_conditions | `$x === 42` ↔ `42 === $x` (also `!==`) | 5 | — |
| SY-07 | redundant_parens | `return ($a + $b);` ↔ `return $a + $b;` | 2 | — |
| SY-09 | increment_style | `$i++` ↔ `++$i` (statement position) ↔ `$i += 1` ↔ `$i = $i + 1` | 4 | — |
| SY-10 | compound_assignment | `$a = $a . $x` ↔ `$a .= $x` (and `+=`, `*=`) | 4 | — |
| SY-11 | elseif_spelling | `elseif` ↔ `else if` | 2 | — |
| SY-13 | interpolation_braces | `"$name"` ↔ `"{$name}"` | 2 | — |
| SY-14 | cast_style | `(int)$x` ↔ `intval($x)`; `(bool)` ↔ `!!` | 5 | — |
| SY-15 | static_self | `self::CONST` ↔ `static::CONST` ↔ `ClassName::CONST` (where equivalent) | 5 | — |

Probe hosts: S-8/S-9. These raise the *implemented text/ast* pool from 45 to ~56, making 20-axis stacks (C-1..C-3) combinatorially rich without any selector involvement.

## T-LEG · Modernization-pair group (NEW; kind=ast; the PHP-era axis powering C-10)

All rewrites stay valid ≥ the declared min_php (era *style*, not removed syntax). `clone_type: type-2/3`.

| Code | Name | Rewrite | Weight | min_php |
|---|---|---|---|---|
| LEG-01 | list_destructuring | `list($a, $b) = $r` ↔ `[$a, $b] = $r` | 3 | 7.1 |
| LEG-02 | pow_operator | `pow($a, $b)` ↔ `$a ** $b` | 4 | — |
| LEG-03 | dir_constant | `dirname(__FILE__)` ↔ `__DIR__` | 3 | — |
| LEG-06 | first_class_callable | `Closure::fromCallable([$this,'f'])` / `'strlen'` string callable ↔ `$this->f(...)` / `strlen(...)` | 8 | 8.1 |
| LEG-08 | constructor_promotion | classic props+ctor assignments ↔ promoted ctor params | 10 | 8.0 |
| LEG-09 | readonly_property | `private $x` + no writes ↔ `private readonly $x` | 5 | 8.1 |
| LEG-10 | enum_vs_consts | `class Status { const OPEN='open'; }` usage ↔ backed-enum usage | 14 (selector) | 8.1 |
| LEG-11 | match_true_chain | `if/elseif` chain ↔ `match(true) { $x > 10 => …, default => … }` | 14 (selector) | 8.0 |
| LEG-12 | null_check_style | `null === $x` ↔ `is_null($x)` ↔ `$x === null` | 4 | — |

## T-UQ · Unique-code injection group (NEW; kind=wrapper-like ast; powers ALL of §3)

`clone_type: type-3`; `requires: [gap_tolerance]` (+`cfg_comparison` for UQ-04/05); weights 8–14; all params carry the E-2 budget contract (`segments`, `lines_min`, `lines_max`, `pool`, `positions`).

| Code | Name | Effect | Weight |
|---|---|---|---|
| UQ-01 | unique_prologue | N tracked unique lines before shared logic (inside symbol) | 8 |
| UQ-02 | unique_epilogue | N tracked unique lines after shared logic | 8 |
| UQ-03 | unique_interleave | K segments × N lines at statement boundaries (AstAnalyzer's `bodyStatementEndLines` picks safe anchors) | 10 |
| UQ-04 | unique_branch | a whole guarded block only this member has (R-3's toggle geometry) | 12 |
| UQ-05 | unique_replacement | M shared lines replaced by N unique lines (`replaces` sub-field, P-8) | 14 |

Distinguished from ST-01/02/03 by *intent metadata*: UQ segments emit `unique_segments[].kind: "unique_logic"` and draw from the seed's `uniques/` pool (real domain code), whereas ST/NZ insertions emit `kind: "noise"`. The lineMap `-1` convention already carries the mechanics.

## T-RF · Refactor-shape emitters (NEW; kind=composite/selector; §4 at generator scale)

| Code | Name | Effect | Weight |
|---|---|---|---|
| RF-01 | extracted_helper | auto-extract N contiguous statements into a private method + call (nikic-driven: free variables → params, assigned-after-use → returns) — generalizes SEM-02 beyond hand variants | 18 |
| RF-02 | hole_substitution | render a member FROM `solution/Unified.php` + `holes.json` values (the derive-members-from-unified mount, F-12) | n/a (mount mode, cited for provenance) |
| RF-03 | wrapper_decorate | wrap the symbol body in try/catch / timing / logging decorator shells (R-15 supply) | 10 |

## T-NU/T-BL/T-CF/T-ST/T-LT/T-API additions (existing groups, new codes)

| Code | Group | Name | Rewrite | Weight | Kind |
|---|---|---|---|---|---|
| NU-02 | NU | coalesce_assignment | `$a = $a ?? $b` ↔ `$a ??= $b` | 6 | ast |
| NU-03 | NU | nullsafe_chain | `$x ? $x->y() : null` ↔ `$x?->y()` | 8 | ast |
| BL-05 | BL | range_check_forms | `$x >= 1 && $x <= 10` ↔ `!($x < 1 || $x > 10)` | 16 | selector |
| BL-06 | BL | bool_cast_forms | `(bool)$x` ↔ `$x == true` ↔ `!!$x` (where safe) | 12 | ast |
| CF-06 | CF | continue_vs_nested | loop-body guard `if (!$ok) continue;` ↔ wrapping `if ($ok) { … }` | 14 | selector |
| CF-07 | CF | while_vs_for | `for ($i=0; $i<$n; $i++)` ↔ `$i=0; while ($i<$n) { …; $i++; }` | 14 | selector |
| CF-09 | CF | flag_vs_early_exit | `$found = false; foreach … $found = true; break;` ↔ early `return true` from extracted loop | 16 | selector |
| CF-10 | CF | goto_spaghetti | guard chain ↔ `goto fail;` pattern (real PHP; L10 adversarial only) | 18 | selector |
| ST-10 | ST | hoist_invariant | loop-invariant expression moved out of/into the loop | 12 | ast |
| ST-11 | ST | tmp_var_introduction | `$tax = ($subtotal - $discount) * $r` ↔ `$taxable = $subtotal - $discount; $tax = $taxable * $r` — the single most common human edit | 9 | ast |
| ST-12 | ST | return_shape_reorder | reorder keys of a returned literal array (semantic for consumers by key — safe; order-visible only to `var_export` — noted) | 8 | ast |
| LT-05 | LT | digit_separators | `1000000` ↔ `1_000_000` | 3 | ast (7.4) |
| LT-06 | LT | numeric_base | `255` ↔ `0xFF` ↔ `0b11111111` | 5 | ast |
| LT-07 | LT | float_notation | `0.5` ↔ `.5` ↔ `5e-1` | 4 | ast |
| API-11 | API | filter_map_chain | `array_filter`+`array_map` ↔ one `foreach` with guard | 17 | selector |
| API-12 | API | sort_idioms | `usort` + comparator ↔ manual selection/insertion loop (small n) | 18 | selector |
| API-13 | API | json_vs_serialize | `json_encode/decode` ↔ `serialize/unserialize` round-trip (where shapes allow) | 17 | selector |
| API-15 | API | intdiv_forms | `intdiv($a, $b)` ↔ `(int) floor($a / $b)` (non-negative domain proven) | 15 | selector |
| API-16 | API | str_contains_family | `str_contains($h,$n)` ↔ `strpos($h,$n) !== false`; `str_starts_with` ↔ `substr($h,0,$k) ===` | 15 | ast-capable (8.0) |
| API-17 | API | membership_lookup | `in_array($k, $list, true)` ↔ `isset($map[$k])` (with flipped map) | 17 | selector |
| API-19 | API | array_column_vs_loop | `array_column($rows, 'id')` ↔ foreach collect | 16 | selector |

## T-ENC additions & implementation notes

Implement declared ENC-01..06 (already planned) **plus**: **ENC-07 `identifier_normalization_forms`** — the same identifier in NFC vs NFD (`$café`): PHP treats them as DIFFERENT variables (identifiers are byte-compared, bytes 0x80–0xFF allowed), so this is really a *rename* invisible to humans — clone_type type-2, weight 7, adversarial L10 only, `requires: [encoding_normalization, identifier_canonicalization]`. Implementation must pair with a same-file consistency rewrite so the code still runs (all uses converted). Every ENC code needs the `hygiene_exceptions` waiver plumbing (C-13) in verify.php.

## T-NZ implementation spec (sharpening the declared 10)

Each NZ transform = insertion of an idiomatic instrumentation pattern with params `{density: 1..3, style: enum, anchor: prologue|per_statement|epilogue}`; styles vary the VOCABULARY (`monolog` vs `error_log` vs `$this->logger`) so noise text differs across members while noise SHAPE is the constant — otherwise the noise itself becomes a token clone across carriers (the trap MAJOR-1 taught us to check; V-5 covers it). Every inserted line emits `unique_segments[].kind: "noise"`. NZ-04 (framework) must use PHP 8 attributes (`#[Route]`, `#[Assert\NotBlank]`) not annotations — pairing with K-21.

## T-CP → topology directives, not transforms

The 10 CP codes describe SET GEOMETRY, not member edits — implementing them as `Transform` classes is a category error (why they've stayed unimplemented). Re-spec as a recipe-level `topology` object interpreted by `SetBuilder` (F-10): `{"type": "same_file", "instances": 2}`, `{"type": "large_file", "target_sloc": 1200, "clone_at": 0.7}`, `{"type": "genealogy", "chain": ["A","B","C","D"], "per_hop": [["WS-03"],["RN-05"],["CM-03","ST-01"]]}` etc. Registry keeps CP-01..10 as citable codes (interference[] provenance + weights for difficulty) with a new registry field `"realization": "topology"` distinguishing them from `"realization": "transform"`. This one decision unblocks `adv_*` families' metadata honesty AND the drift/multi-cluster engines.

---

# 9. Generator / infrastructure features

Each item is a crisp capability with its consumers named. Order ≈ dependency order.

- **F-1 · Schema v2.** Extend both schemas with E-1…E-18 (all optional), `schema_version: enum [1,2]`, `level: maximum 11`, role enum + `support`, requires-enum +6, granularity comments updated. Touch: `testsets/schema/*.json`, `SetBuilder::build()` emitters, `verify.php` V-1…V-10. Consumers: everything.
- **F-2 · Multi-cluster build path.** Recipe gains `clusters[]` (each with own seed/symbol/carrier-groups); `SetBuilder` loops clusters, emits c1..cN, enforces V-9 cross-cluster distinctness; `duplication.clusters`/`instances` computed; manifest cluster totals become real (fixes "560 clusters" plan-vs-built gap). Consumers: C-15, K-30, P-12, R-10, R-19, ex_multi_cluster made honest.
- **F-3 · Fragment engine.** During render, compute per-member `fragments[]` (maximal runs of lineMap ≠ −1) and `unique_segments[]` (runs of −1, tagged by originating transform kind); emit E-1/E-2/E-3/E-5 accounting; per-fragment positive proof (V-4). Consumers: ALL §3, R-7, R-16, ST-06/07 retrofit (their currently-whole-member truth becomes fragment-accurate).
- **F-4 · Block & multi-region mounts.** New `mount: block` (payload inserted inside a host method from a new host-scaffold library; `symbol: null`; line-audit checks host integrity instead of `function` prefix) and `mount: multi` (several payload regions into one file — file-granularity members). Consumers: P-5/P-6/P-11, R-11 statement-run members.
- **F-5 · Drift-chain engine.** Topology directive `genealogy`: carrier i renders from carrier i−1's OUTPUT (not the pristine payload), transforms accumulate; emits `drift_generation` 0..k and E-11 `pairwise_expectation` (computed per hop-distance from measured common-run lengths). Consumers: adv_genealogy made real (its README currently references a member "D" that doesn't exist), C-16, mix_realistic_drift upgrade, E.2.6 Recipe 4.
- **F-6 · Variant composition + chain-order contract.** (a) Load composed variants `variants/<a>+<b>.php`; interference cites both codes. (b) Enforce the §2 transform-order (selector→ST→ast→CM→WS→ENC) with a build-time assertion and auto-sort + warning. Consumers: C-4, C-11, S-26.
- **F-7 · Recipe compiler: packs & designs.** Preprocessor expanding `transforms_pack` names (style packs: `psr12_modern`, `legacy_wordpress`, `enterprise_java_style`, `php56_era`; each a versioned JSON in `gen/transforms/packs.json`) and `combination_design` directives (pairwise/triple covering-array solver — a 50-line greedy solver suffices at these sizes). Consumers: C-7, C-9, C-10; keeps 20-axis recipes maintainable.
- **F-8 · Intensity dials.** Map each code's params to a 0–8 dial (`registry.json` gains per-code `dial` param descriptors); emit `generator.intensity_dial`, `difficulty.axes.intensity` = Σ dials. Consumers: C-14, E-8, G-3.
- **F-9 · Size-inflation engine.** Deterministic filler generator (seeded, hygiene-safe, vocabulary drawn from sanitized `/home/my/logs/smarty_templates_c/` + template corpus) growing any file to `target_sloc`; guarantees no accidental ≥40-token runs vs payload (checked). Consumers: K-32, CP-05/06 realization, adv_large_files made real-sized.
- **F-10 · Topology engine.** Implements T-CP directives: same_file, overlap (two clusters sharing a declared line range), below/at threshold (token-count-targeted payload trimming using `PhpTokens` counts), large_file (via F-9), many_files, html_mix (HTML host scaffolds), generated (smarty-derived hosts), near_miss_only. Consumers: L10 families cite real codes; K-28, K-30.
- **F-11 · Unique-snippet pools + UQ transforms.** `uniques/` loader, anchor resolution via `AstAnalyzer::bodyStatementEndLines`, budget enforcement (V-2), pool-exhaustion errors at build time. Consumers: §3, C-12, C-17, R-15.
- **F-12 · Refactor mount + proof harness.** `mount: from_solution` (RF-02): members derived from `solution/Unified.php` × `holes.json` values; emit E-6; run `refactor_proof.php` in verify (V-6). Solution dir excluded from `src/` hygiene scans but INCLUDED in determinism check. Consumers: ALL §4.
- **F-13 · Hygiene waivers.** `hygiene_exceptions` recipe key (bom|crlf|nbsp|unicode_ident) threaded to verify.php so ENC sets pass; waivers echoed in set.json `generator` block for auditability. Consumers: C-13, K-24..K-27.
- **F-14 · Progression compiler.** `manifest.php` additions: `progression.json` (G-8), grid-coverage + decile reports (G-3/G-7), `minimal_pairs` (G-4), `budget_matrix` (P-13), `capability_index` (capability → probe/consumer sets). Consumers: G-*, K-34.
- **F-15 · Registry coverage gate.** `verify --all` fails if any `implemented:true` code has zero referencing sets, or any code lacks `probe_family` once its probe wave ships; stale-doc check: `gen/README.md` implemented-list generated FROM registry.json (kills the current staleness class).
- **F-16 · Property-based equivalence harnesses.** Replace hand-enumerated input matrices with seeded generators (200+ cases per seed, deterministic via `Rng`); mandatory for seeds with variants (closes csv_import's missing-test hole); publishes per-seed coverage counts into seed.json (verified, so no more drift).
- **F-17 · Fragment/pairwise-aware bench scoring.** `run-testsets.php`: fragment-union Jaccard (`fragment_scoring`), unique-segment clipping + `unique_segment_penalty` boundary metric, per-pair credit (`pairwise_credit`, E-11), multi-tolerance re-scoring for K-29 offset estimation. Consumers: §3, C-6, K-29/K-30.
- **F-18 · Refactor-aware bench scoring.** Parse phpdup's suggested-refactor JSON (and any tool exposing one); metrics `refactor_kind_match` (exact/`alternatives[]` partial credit), `hole_recall/precision` (matched by hole site ± tolerance and kind), `signature_similarity` (normalized token similarity of proposed vs `unified_signature`), `advisability_accuracy` (R-17 restraint). Consumers: §4, K-34 profile section 7 (new axis).
- **F-19 · Flexible family cardinality.** Drop the uniform 5-sets/family assumption in build/verify/manifest: probe families = 2, ladders = 5–8, K-28 = 8. `R2 coverage` rule amended: "≥2 for probe families, ≥5 otherwise."
- **F-20 · `bench/profile.php` — Tool Capability Profile generator.** K-34's spec: verdict engine (control/probe logic), threshold bracketing, FP-class resistance, boundary offsets, walk frontiers, scaling curves, by-design vs capability-gap split, markdown + JSON emission. THE deliverable that converts corpus → guidance; buildable the moment probes exist (even before §2–§4 waves).
- **F-21 · `bench/run-testsets.php --walk` mode.** G-9: walk-ordered execution, per-arc frontier report, "next capability to build" recommendation from requires-delta.
- **F-22 · Difficulty model re-fit.** Once C-1/C-14/C-18 exist (breadth vs intensity vs application-count separated), regress real tool F1 against the axes and re-fit `interference_weight`/`level_base` empirically; version the formula in registry `meta` (`difficulty_model: "2.0"`); keep v1 numbers for comparability.
- **F-23 · Cross-seed distractor exchange.** Distractors drawn from OTHER seeds' domains at controlled vocabulary overlap (S-4↔csv_import), giving L0/probe FP baits more realistic diversity than the current 11 hand distractors; a `distractor_matrix.json` records tuned overlap levels (verified <40-token).
- **F-24 · `gen/tools/retrofit_*.php` one-shot migrators.** retrofit_progression (G-1), retrofit_region_sloc (E-1 for all 545 v1 sets — trivially `unique: 0`), retrofit_fragments for ST-06/07 families (recompute from stored recipes deterministically). Keeps the corpus single-schema-generation clean instead of bifurcating.

## H-1…H-12 · Repo-wide housekeeping & quality improvements (fold-in wave)

Known debt from the State-of-the-Corpus audit — cheap, high-trust fixes that should ride along with any expansion wave:

- **H-1** `seed.json.variants[]` drift: invoice_totals lists 1 of 6, access_guard 2 of 24 — regenerate from disk + add verify check (variant files ⊆ seed.json list, bijective).
- **H-2** `gen/README.md` stale implemented-list (says 6 pilots; registry says 75) — generate that section from registry.json (F-15).
- **H-3** Wire or delete the 8 orphan transform classes (`Rn/ClassRename.php`, `Rn/FunctionRename.php`, `Lt/ArrayLiteral.php`, `Ty/TypeHint.php`, `Ns/NamespaceInject.php`, `St/InsertStep.php`, `St/Partial.php`, `St/Reorder.php`) — either registry aliases or removal; today they're silent dead weight.
- **H-4** Recipe-dir naming: unify `gen/recipes/L0N_name/` vs `L0N/` (pick short form; move strays `L01/ex_function.json` duplicate, `L02/ws_blank_inside.json`, `L02/ws_line_wrap.json`; delete empty `L00/`), with a build.php glob-compat shim during migration.
- **H-5** `samples.json` repair (the standing Integration task): populate `challenges[]` from `code_duplication_challenges.md`'s 60 rows; add the 3 dir-only categories (`connection_handling_duplication`, `query_style_duplication`, `result_processing_duplication`); reconcile README's "54" → 59.
- **H-6** `adv_genealogy` titles referencing a nonexistent member "D" — fix titles now; supersede with F-5 chains later.
- **H-7** `bench/corpora/testsets.ground-truth.json` is consumed by nothing and holds 4 illustrative clusters — either make `run-testsets.php` read it (single-file GT mode for external consumers) or regenerate it complete via manifest.php and document it as the export artifact.
- **H-8** Dormant gen-1 bench scripts (`run.php`, `score.php`, `corpora.php`, `comparative.php`) depend on an uninstalled `Phpdup\Testing\*` vendor — quarantine under `bench/legacy/` with a README, or restore the dependency; today they're a trap for contributors.
- **H-9** `expected_detection_by_tool` is optional and unpopulated — either emit it for threshold-relevant families (K-28 needs it) or drop it from the schema in v2 to end the ambiguity.
- **H-10** csv_import: add variants + equivalence_test (S-26 pattern) or explicitly mark it text/ast-only in seed.json (`selector_capable: false`).
- **H-11** Add a `CITATION`/`DATASET.md` documenting corpus stats, licensing of smarty-derived material, and versioning policy (corpus semver: schema × content waves) — external tools will want to pin corpus versions for comparable scores.
- **H-12** CI matrix: run `build --check`, `verify --all`, registry-coverage gate, schema-lint of every recipe, and the decile/grid coverage reports on every push (some exist; make the full pentad explicit).

---

# 10. Organizing principles & suggested build order

## 10.1 Where the new material lives (level & layer placement)

**Principle 1 — refactorability is a LAYER, not a level.** `intended_refactoring` attaches at ANY detection difficulty (R-1 is L4-easy; R-6 is L8-hard; R-18 proves the orthogonality). So rf_* families slot into existing levels by their *detection* difficulty, and a generated index `testsets/refactor-index.json` (from F-14) lists all refactor-labeled sets with kind/holes/advisability. No new level for goal 3.

**Principle 2 — partiality IS an axis, so it gets a level.** The ladder's contract is "each level adds ONE axis"; tracked-budget partial duplication is a genuinely new axis (region composition), justifying **`L11_partial_budget`** (`level: 11`, schema bump). pd_* families live there; P-10/P-12 may sit at L10 if L11 is rejected — both placements documented in each family entry. Levels stay 12 (L0–L11) — micro-granularity comes from `rung`/`sublevel`, NOT more dirs.

**Principle 3 — probes live where their axis lives**, at rung 0/1, marked by `capability_probe` + the `probe_` prefix, so single-axis level semantics stay intact while difficulty stays minimal.

**Principle 4 — combinatorial families extend L9/L10** (their charter), with `interference_profile` restoring the discriminating power that score saturation destroyed.

**Principle 5 — every set stays verifier-provable.** Any family that cannot state its positive proof (token equality per fragment / edit budget / behavioral equivalence / hole-substitution proof) and its negative proof (40-token discipline) does not ship. This is what keeps a 20-axis, budget-tracked, refactor-labeled corpus *trustworthy* rather than merely large.

## 10.2 Naming conventions

New family prefixes: `mix_` (existing, extended) · `pd_` partial-duplication · `rf_` refactor-labeled · `probe_` capability probes. New seed assets: `uniques/`, `holes.json`, `variants/<a>+<b>.php`, `solution/` + `refactor_proof.php` per rf-set. New generated artifacts: `progression.json`, `refactor-index.json`, `capability-profile.json`, `packs.json`, `distractor_matrix.json`.

## 10.3 Build order (nine phases; each ends with `verify --all` + `build --check` green)

| Phase | Content | Depends on | Approx. new sets |
|---|---|---|---|
| **P1 — Schema v2 + accounting** | F-1, F-3 (fragments), F-8, E-1..E-9, E-13/E-14, V-1..V-8, F-24 retrofits, H-1..H-6 housekeeping | — | 0 (metadata wave) |
| **P2 — Probes + Profile** | K-1..K-18 probes (existing codes only), K-28/K-29/K-31, F-19, F-20 profile.php, F-21 walk mode, G-1 rung retrofit, G-4, G-8 | P1 | ~50 (2/family) |
| **P3 — Partial duplication** | T-UQ transforms, F-11 pools, S-11/S-12 seeds, P-1..P-9, E-2 loop, F-17 scoring | P1 | ~45 |
| **P4 — Refactor layer wave 1** | E-6, F-12 mount+proof, R-1..R-5, R-11, R-14, R-17, F-18 scoring, S-1/S-22 seeds | P1 | ~40 |
| **P5 — Combinatorial wave 1** | C-1/C-2/C-3/C-5/C-6/C-8/C-14/C-16/C-18 (implemented codes only), F-6 order contract, F-7 packs → C-7/C-9 | P1 | ~55 |
| **P6 — Seeds at scale + semantic depth** | S-26 variant-rich guard, S-10/S-25 multi-symbol/XL, F-16 property harnesses, C-11, R-6/R-7/R-20, G-5 gap-fill, G-6 bridges | P2–P5 | ~50 |
| **P7 — NZ/ENC/SY/LEG codes** | T-NZ impl + K-19..K-23, T-ENC impl + F-13 + K-24..K-27 + C-13, T-SY/T-LEG + C-10/C-12 | P1 | ~45 |
| **P8 — Topology & multi-cluster** | F-2, F-4, F-5, F-9, F-10, C-15, C-17, P-10..P-14, R-8..R-10/R-12/R-16/R-19, K-30/K-32/K-33, C-4 capstone | P1–P7 | ~60 |
| **P9 — Model & polish** | F-22 difficulty re-fit, G-3/G-7 grid & decile gap-fill commissions, H-7..H-12, remaining T-API/T-CF/T-BL/T-NU/T-ST/T-LT codes + their probes | all | ~30 |

Rationale for the order: **P2 before content waves** because the Profile generator + probes give immediate tool-builder value with zero new transforms and make every later wave measurable on arrival; **P3 before P4** because rf-toggle sets reuse UQ mechanics; **packs before deep stacks** because hand-authoring 20-axis recipes without F-7 is unmaintainable; **topology last among engines** because it touches SetBuilder's core assumptions (one cluster, whole-method) most invasively.

Rough total: **~375 new sets** across **~120 new families/probe-families**, on top of 545 — a corpus of ~920 sets, every one carrying v2 accounting, walkable via progression.json, and profiled by bench/profile.php.

## 10.4 Risk register (design-time mitigations)

1. **Deep stacks silently violating Guard B** → V-10 pairwise run-length check runs on every `token_based:false` cluster AND pair; C-3/C-6 declare per-pair expectations rather than pretending uniformity.
2. **Unique-segment pools accidentally cloning across sets** → pool snippets are per-seed, per-set param-varied (RN over pool identifiers), V-5 cross-checks; distractor_matrix tuning applies to pools too.
3. **Refactor proofs ossifying member behavior** (solution change ⇒ member drift) → members are DERIVED from solution (F-12), so the proof can't drift; determinism check covers solution/.
4. **Schema v2 fragmentation** (v1/v2 sets diverging) → F-24 retrofits move all 545 v1 sets to v2-with-trivial-values in P1; the corpus is single-version at every release tag.
5. **Score-model whiplash** (re-fit changing bands) → `score` v1 frozen for band derivation until F-22 ships `difficulty_model: 2.0` behind a manifest flag; both reported during transition.
6. **Set-count inflation without review capacity** → keep the ORCHESTRATION.md Builder→Reviewer→Fixer loop; probe families (2 sets, tiny surface) are cheap to review; the expensive reviews (C-4, P-12, R-10) are individually named above so they can be scheduled deliberately.

---

# 11. Appendix: idea inventory & counts

| Section | IDs | Count | Kind |
|---|---|---|---|
| §1 Schema/metadata extensions | E-1…E-18 | 18 | enabling fields/objects (+ 10 verifier checks V-1…V-10) |
| §2 Combinatorial families | C-1…C-18 | 18 | content families (10–20-axis regime) |
| §3 Partial-duplication families | P-1…P-14 | 14 | content families (tracked budgets) |
| §4 Refactorability families | R-1…R-20 | 20 | content families (intended_refactoring) |
| §5 Progression machinery | G-1…G-9 | 9 | ladder semantics, arcs, grid, bridges, walk artifacts |
| §6 Capability probes | K-1…K-34 | 34 | probe families + threshold/FP/scale/profile artifacts |
| §7 Seeds | S-1…S-26 | 26 | new seed domains (+7 seed-format extensions) |
| §8 Transform codes | T-* | ~50 | SY 11 · LEG 9 · UQ 5 · RF 3 · NU/BL/CF/ST/LT/API 20 · ENC +1 · NZ spec · CP re-spec |
| §9 Generator/infra | F-1…F-24 | 24 | engine capabilities |
| §9 Housekeeping | H-1…H-12 | 12 | repo debt fixes |
| **Total distinct new ideas** | | **≈225** | (86 content families · 50 codes · 26 seeds · 24 engine features · 18 schema extensions · 12 housekeeping · 9 progression) |

**The five goals, each answered by named machinery:**

1. **Combinatorial 10–20-axis sets** → C-1..C-18 on E-7/E-8 (axis accounting + unclamped 2-D difficulty), F-6/F-7 (order contract + packs), V-10 (legality at depth).
2. **Graduated partial-duplication with tracked budgets** → P-1..P-14 on E-1..E-5 (region_sloc, uniqueness_budget min/max, unique_segments, varied_segment_count, duplication_ratio), T-UQ, F-3/F-11, V-2..V-5.
3. **Refactorability labels** → R-1..R-20 on E-6 (`intended_refactoring`: 17 kinds, holes, unified signature, collapses, advisability), F-12 (derive-from-solution + mechanical proof), F-18 (scoring refactor-aware tools).
4. **Fine-grained basic→advanced→combined progression** → G-1..G-9 on E-9 (rungs, arcs, prerequisites, bridges) + grid/decile coverage targets + progression.json + --walk mode.
5. **Detector-capability exercise** → K-1..K-34 on E-12/E-14 (capability_probe + 6 new requires values), F-15 (registry coverage), F-20 (the Tool Capability Profile — the R8 payoff), K-28 threshold bracketing, K-31 FP classes, K-32 scaling curves.

---

# 12. Cluster-topology, instance-count & structural variations — [O,C]

Pushes cluster topology harder than the spine. **Extends** F:E-10 (multi-cluster schema), F:C-15 (`mix_multi_cluster_stacked`), F:P-12 (`pd_braided_clusters`), F:F-2 (multi-cluster engine). Net-new here: explicit **instance-count ladders**, **intra-file** clustering, the **clone-to-unique ratio scale**, and the nested / overlap / subset / one-member-off distinctions.

- **12.1 Multi-cluster sets** [O 3.1, C M1] — 2–4 independent clusters in one set. Combos: 2×Type-1; one Type-1 + one Type-4; 3 clusters of mixed types; clusters that **overlap** (share lines); clusters that **nest**. Also *multi-cluster single file* (2–3 clusters within one file). Requires F:F-2. Probes cluster **separation** (do tools merge distinct adjacent clusters? — cf. F:K-30 `probe_region_merge`). `detection_expectation`: token/ast true.
- **12.2 Clone-instance-count ladder** [O 3.2] — same clone appears **2 / 3 / 4 / 5 / 10 / 20+** times. Tests scalability and *complete grouping* (no missed instances; all in one cluster). Uses existing `duplication.instances`; add a family that sweeps it. (Its extreme is O Idea 7 "Mass Duplication Event", §21.)
- **12.3 Intra-file clone clustering** [O 3.3, C] — N methods in ONE file are the same clone (`calcA/B/C/D`). Extends F `adv_same_file`; members are within-file.
- **12.4 Clone-to-unique-code ratio scale** [O 3.4] — the explicit density ladder (complements F:P-5/P-9 and the plan's `mix_size_ratio`): **Sparse** 5u/20c (80% clone) · **Balanced** 20/20 (50%) · **Dense** 50/20 (29%) · **Very-dense** 100/20 (17%) · **Extreme** 200/20 (9%). Maps onto F:E-3 `duplication_ratio` + F:E-1 `region_sloc`. Its far tail is O 8.5 "opacity through complexity" (10×/50×/100×/500× unique:clone; a 1-line clone in a 500-line file) ≈ F `adv_large_file_tiny_clone` / plan `mix_size_tiny_buried`.
- **12.5 Nested clones (clone-in-clone)** [O 3.5, O Idea 3 "Matryoshka"] — outer 50-line fn ⊃ inner 20-line block ⊃ inner 5-line expr, each appearing in multiple files → hierarchical, multi-granularity detection. Extends F `adv_nested_overlap` (E.2.4); needs the fragment engine (F:F-3) + sub-method granularity.
- **12.6 Partial clone overlap** [O 3.6] — clusters share *some* lines (A 10–30, B 20–40, overlap 20–30) but are distinct clones. Tests overlap disambiguation. Extends F:P-12.
- **12.7 Subset clone — "The Inclusion Problem"** [O Idea 9] — Clone2 ⊂ Clone1; the tool must recognize the smaller as a *subset*, not a separate clone. Net-new adversarial topology.
- **12.8 One-member-slight-variation** [O 3.7, O Idea 6 "Twin Paradox"] — a 3-member cluster where one member has +1 line / one changed literal; or a 99%-identical pair with a single-line delta the tool should still cluster **while noting the delta**. Extends F:P + near-miss-within-cluster (see §21 `pd_twin_delta`).
- **12.9 Confusing/clustered distractors** [O 3.1, C M5] — a distractor that shares tokens with **both** clusters but matches neither. Extends F `non_duplicates[]` traps + §17 distractor tiers.

# 13. Granularity ladder — [O,C]

Exercises the whole `granularity` enum (`file|class|method|function|block|statement-run`), which the spine notes is **whole-method-only** today (needs F sub-method fragment engine + activating class/file granularities in `SetBuilder`).

| Rung | Slug idea | Size | Notes / cross-ref |
|---|---|---|---|
| Expression [O 7.2] | `gr_expression` | 1 expr (`$fullName = trim($first.' '.$last);`) | micro-clone; sub-threshold for most tools → F:K-28 token floor |
| Statement [O 7.1] | `gr_statement` | 2–5 lines (isset-default guard) | `granularity: statement-run` |
| Block [O 7.3] | `gr_block` | 10–50-line loop/if/switch body | `granularity: block` |
| Method/function [O 7.4] | (current norm) | 20–100 lines | already well covered by F |
| Class [O 7.5] | `gr_class` | whole 90%-identical classes (`InvoiceCalculator` vs `QuoteCalculator`) | `granularity: class` |
| File [O 7.6] | `gr_file` | whole near-identical files (only scaffold differs) | `granularity: file` |
| Multi-file pattern [O 7.7] | `gr_dirtree` | same structure+content repeated across a directory tree (`domain/orders` vs `domain/billing`) | ties §20-K realistic scenarios |

`detection_expectation` varies by rung: micro-clones expose min-token floors; class/file rungs expose whether a tool does coarse-granularity detection at all.

# 14. Size-class matrix (XS–XXL) & threshold ladders — [O,C]

Net-new organizing artifact: for each seed, emit multiple **size classes**, and mix them within sets. Extends the seed `size_class` field, F's `ex_size_ladder` and plan `mix_size_ratio`, and feeds F:K-28 threshold bracketing.

| Class | Lines | Tokens | Complexity |
|---|---|---|---|
| XS | 5–10 | 30–50 | trivial |
| S | 10–20 | 50–100 | simple |
| M | 20–40 | 100–200 | standard |
| L | 40–80 | 200–400 | complex |
| XL | 80–150 | 400–800 | very complex |
| XXL | 150–300 | 800–1500 | substantial |

- **Set compositions** [O 12]: **Homogeneous** (all 3 carriers same class) · **Heterogeneous** (2 small + 1 large, etc.) · **Mixed** (3 different classes).
- **Threshold ladders** [C Dim5/I]: token counts at **±1** of common floors (20/30/50/100 tok); line counts at ±1 (5/10/20/50); **%-overlap** ladder (95/97/98/99/100%); **identifier-count** variance (1/2/5/10/20 renamed). Direct fuel for F:K-28 and per-tool floor measurement.
- Metadata: `duplication.size_class` per cluster + `member.token_count` (F already records clone token size in `notes`).

# 15. Clone genealogy & drift taxonomy — [C,O]

Extends F:C-16 (`mix_commit_history`), the plan's `adv_genealogy` / `mix_realistic_drift`, F's drift-chain engine, and `drift_generation` activation (F:E-11). C and O supply the fuller **drift taxonomy** as families:

- **Genesis** (exact at T0) → **Single Edit** (one carrier +5% at T1) [C N1] → **Progressive Drift** (+10%/generation, 3 generations) [C N2, O Idea 5 "Evolutionary"] → **Branch Divergence** (clone splits into 2 families, each drifts independently) [C N3] → **Convergence** (2 independent impls become near-identical) [C N4] → **Partial Revert** (a drifted clone partly reverted to origin) [C N5].
- Metadata: `members[].drift_generation` (0..k) [F:E-11], degrading pairwise Jaccard, `pairwise_expectation` per member-pair [F:E-11]. Needs the drift-chain engine emitting per-hop transforms.
- `detection_expectation`: whether a tool reports the *whole* family or fragments it as drift accumulates; the pristine↔most-drifted pair is the hardest true positive (report via "expected-recall").

# 16. Domain & cross-semantic equivalence (+ seed/domain union) — [O,C]

Type-4/domain additions. **Extends** F:§8 `sem_*`, F:R-16 (`rf_scatter_gather`), plan `sem_rule`/`sem_orm_sql`. Each needs hand-written behaviorally-equivalent variants + `equivalence_test.php` (selector kind); `detection_expectation` text/token/ast = false, semantic = true; `difficulty.requires: [semantic_reasoning]`.

- **16.1 Cross-domain semantic equivalence** [O 5.1] — same rule, different domain vocabulary: invoice-calc ↔ quote-calc ↔ order-calc; authorize ↔ permission-check ↔ access-validate; email ↔ SMS ↔ push send. The hardest true positive; overlaps F:R-16 (refactor target `validateEntity(...)`).
- **16.2 State-machine encodings** [O 5.2] — switch-based vs array-transition-table vs class-per-state, same states/transitions → seed `state_transition` (F:S-*, plan seed 5).
- **16.3 Data-structure encoding** [O 5.3] — array vs `ArrayObject` vs generator (`yield`) producing identical results.
- **16.4 Functional vs imperative** [O 5.4] — `array_filter(array_map(fn,…))` vs foreach accumulation (a *semantic* pairing of F `api_map_loop` / §20-F `cf_loop_to_map`).
- **16.5 Framework-idiom equivalence** [O 5.5, O Idea 10] — same logic in plain PHP (PDO) vs Laravel (`DB::table`) vs Doctrine (`EntityManager`) vs CodeIgniter vs Yii. Needs framework-shaped, **dependency-free** seed stubs.
- **16.6 Same query, different ORM/SQL** [O 5.6] — WHERE-clause reorder, `IN ('pending')` vs `= 'pending'` → seed `query_builder` (F:S-*, plan seed 7). (Commutative WHERE-order overlaps F `bl_commutative` caveat.)
- **16.7 Business-rule encoding sweep** [O 5.7] — one discount rule as nested-if / guard-returns / `match` / lookup-table / strategy, all in one family (a strong L6→L8 arc; ties F:R-10 `rf_parallel_switch_polymorphism` + G-2 arcs).

### 16.8 Seed / domain union with F:§7 — [O,C]

Nothing dropped: the union of all proposed seed domains. F:§7 (S-1…S-26) already covers most; reconcile names at build time (a name clash = same seed).

- **[O] 20 domains** (Part 11): `validation`, `sorting`, `pagination`, `date_formatting`, `currency`, `json_api`, `file_processing`, `http_client`, `session_management`, `cache_invalidation`, `logging`, `email_building`, `permission_matrix`, `report_generation`, `state_transition`, `data_pipeline`, `rate_limiting`, `id_generation`, `config_merging`, `error_recovery`.
- **[C] 15 domains** (Dim3/J, grouped): billing (`quote_calculation`, `tax_handling`, `discount_application`, `payment_processing`), import/ETL (`json_batch_load`, `xml_parsing`, `delimited_file_handler`, `stream_processor`), authz (`role_checker`, `permission_evaluator`, `capability_verifier`, `resource_acl`), data-structures (`array_operations`, `collection_manipulation`, `tree_traversal`, `graph_search`, `linked_list_ops`), string (`regex_matching`, `text_normalization`, `encoding_conversion`, `token_splitting`, `format_parsing`), HTTP (`request_building`, `response_parsing`, `retry_handler`, `timeout_manager`, `auth_header_builder`), DB (`query_builder`, `transaction_handler`, `migration_executor`, `connection_pool`, `cache_decorator`), validation (`email_validator`, `password_rules`, `phone_formatter`, `url_checker`, `credit_card_verifier`), caching (`cache_warmer`, `invalidation_policy`, `ttl_calculator`, `bloom_filter`, `lru_eviction`), logging (`structured_logging`, `event_tracking`, `metric_emission`, `trace_decorator`, `error_aggregator`).
- These + F:S-1…S-26 → the master seed backlog. Target: **15+ seeds [C]** / **~45 seeds [F]**. Every new seed ships `payload.php` (≥~70–100 significant tokens), `seed.json`, `equivalence_test.php`, and `variants/` for CF/API/SEM families (F seed-format extensions apply).

# 17. Interference, noise & distractor sophistication — [O,C]

Extends F `non_duplicates[]` traps, F:K-31 (`probe_fp_suite`), the plan's near-miss distractors, and F's NZ noise codes (declared-only — see F:§8 / §22).

- **17.1 Distractor sophistication — 4 tiers** [C Dim2] — a taxonomy for the `distractor` role, per-set 1–4 by level:
  1. **Near-miss** — high token overlap, `<40` contiguous normalized tokens (current standard).
  2. **Confusingly-similar** — same domain, similar variable names, *different* logic.
  3. **Boilerplate-bait** — acceptable duplication tools *should not* flag (getters/setters, framework scaffolding).
  4. **Partial-match** — contains ~50% of clone tokens but different structure.
  New field: `non_duplicates[].bait_class: near_miss|confusable|boilerplate|partial` — **adopt into F:K-31's FP classes** (reconcile; see §26).
- **17.2 Near-miss bait file types** [O 6.1] — same names/structure diff logic; same signatures/comments diff impl; same control-flow shape diff ops; same class names diff bodies; similar comments describing similar ops.
- **17.3 Chaff duplication** [O 6.2] — a "slightly-modified CloneA" false trail sitting right beside the real clone.
- **17.4 Clone embedding in large functions** [O 6.3] — clone at start / middle / end / **split across regions** of a 50–200-line fn (difficulty rises with position and fragmentation) → ties F:P (partial) + F:E-4 `unique_segments` + region accuracy.
- **17.5 Dynamic code-generation snippets** [O 6.4] — PHP that generates PHP; the duplication lives in the *template definitions*, not the output → L10 `adv_generated`.
- **17.6 Macro/constant pseudo-dedup** [O 6.5] — `process(PROCESS_A)` / `process(PROCESS_B)`: dedup *via constants* but the duplication is still real/inlinable. Doubles as a refactor-negative and a semantic case.

# 18. Edge-case & adversarial additions — [O]

Extends F:§6 (K probes), L10, and F's ENC codes (declared-only). Net-new adversarial shapes:

- **18.1 Hash-collision sensitivity** [O 8.1] — clones that would hash identically in naive implementations but differ in token sequence.
- **18.2 Unicode / homoglyph tricks** [O 8.2] — `$total` vs `$tоtal` (Cyrillic `о`): look identical, are different identifiers → FP bait; plus emoji-in-strings and mixed encodings → real ENC transforms (F:T-ENC).
- **18.3 Line-split token boundary** [O 8.3] — `$sum =\n  $a + $b +\n  $c;` vs one line: same tokens, breaks line-based detectors → exposes token-vs-line (ties F `ws_long_line_only` / K line-floor).
- **18.4 Heredoc / nowdoc obfuscation** [O 8.4] — same multi-line SQL as `"…"` vs `<<<SQL` → ENC-06 (plan) / F:T-ENC.
- **18.5 Opacity through complexity** [O 8.5] — clone hidden in 10×/50×/100×/500× unique code (merged into §12.4 ratio scale + F `adv_large_file_tiny_clone`).
- **18.6 Minified vs fully-formatted** [O 8.6] — same logic minified (no ws/comments) vs formatted → an extreme WS+CM stack (ties F:C-1 `mix_t1_blizzard`).
- **18.7 Generated-code clones** [O 8.7] — scaffolding-tool output (Symfony/Laravel `make:`) boilerplate.
- **18.8 Third-party framework boilerplate** [O 8.8] — inherent controller/service duplication → boilerplate-bait (§17.1 tier 3 / acceptable dup).

# 19. Hidden-challenge coverage map (60 types) — [C]

The corpus's R6 goal is to cover **every** "hidden duplication" type in `code_duplication_challenges.md` (60). The spine relies on the master plan's **Appendix A** mapping (58/60 covered, 2 out of scope). C adds explicit standalone *set* proposals for the trickiest ones (1 basic + 1 compound each), mostly L6–L8/L10, each feeding F:K capability probes:

- floating-point comparison (`==` vs `abs()<epsilon`) · null-coalescing (`??` vs `?:` vs `isset`) · off-by-one (`<` vs `<=`, `i++` vs `++i`) · exception hierarchy (`catch(Exception)` vs `catch(RuntimeException)`) · type juggling (`==` vs `===`, `intval()` vs `(int)`) · timezone handling (different init, same UTC result) · array-key iteration (`foreach(array_keys($a) as $k)` vs `$k=>$v`) · string encoding (`mb_strlen` vs `strlen`) · mocking variants (PHPUnit vs Prophecy vs manual).
- …plus the full remaining 60 (cross-ref master-plan **Appendix A**, Ch1–Ch60; Ch30 async/sync, Ch36 cross-language, Ch56 platform-conditional are the out-of-scope items). **Success criterion [C]:** 100% of the 60 mapped to ≥1 set.

# 20. Per-category family expansion lists (copilot A–N) — [C]

copilot's concrete, ready-to-scaffold family lists — the **breadth within existing levels** that complements fable's depth. These are additional *families* (≈5–12 sets each) under existing levels; where a family already exists in the plan/registry it's a set-count increase. (If D-A=B, they distribute across L11–L20 instead.)

- **A. Whitespace (L2)** — `ws_tab_insertion`, `ws_mixed_tabs_spaces`, `ws_newline_endings` (LF/CRLF/CR), `ws_blank_line_counts` (0/1/2/4/8), `ws_indentation_levels`, `ws_line_length_wraps` (80/120/160), `ws_function_spacing`, `ws_operator_spacing`, `ws_array_formatting` (1-line vs multiline), `ws_chained_calls` (fluent-chain wrapping).
- **B. Comments (L3)** — `cm_single_line_density` (0/10/25/50%), `cm_block_comment_style` (`/* */` vs `/** */`), `cm_docblock_tags` (@param/@return/@throws), `cm_inline_comment_position`, `cm_comment_language` (EN/FR/JA), `cm_todo_markers` (TODO/FIXME/XXX/HACK), `cm_copyright_headers`, `cm_deprecated_markers`, `cm_type_hints_as_comments` (old-style vs modern).
- **C. Renaming (L4)** — `rn_parameter_names`, `rn_method_names` (`getData`/`get_data`/`GetData`), `rn_class_names` (`UserService`/`IUserService`/`AbstractUserService`), `rn_constant_names`, `rn_loop_variables` (`$i`/`$idx`/`$index`), `rn_abbreviations` (`$service`/`$svc`), `rn_plural_vs_singular`, `rn_hungarian_notation`, `rn_prefix_suffix_only`, `rn_magic_number_constants` (`2592000` vs `DAY_IN_SECONDS`).
- **D. Literals (L4)** — `lt_numeric_formatting` (`1000`/`1_000`/`0x3E8`/`1e3`), `lt_float_precision`, `lt_string_quotes`, `lt_escape_sequences`, `lt_unicode_escapes` (`é` vs `é`), `lt_array_constant_syntax` (`array()` vs `[]`), `lt_boolean_constants`, `lt_null_variations`, `lt_magic_constant_forms`, `lt_percentage_variance`.
- **E. Statements (L5)** — `st_null_check_styles` (`isset`/`!==null`/`is_null`), `st_ternary_vs_if`, `st_loop_style` (for/foreach/while), `st_early_return`, `st_defensive_copy` (intermediate `$temp`), `st_statement_reordering` (order-independent), `st_compound_assignment` (`$x=$x+5` vs `$x+=5`), `st_list_vs_array` (`list()` vs `[]` destructuring), `st_try_catch_finally`, `st_variable_scope` (local/`use()`/`$GLOBALS`).
- **F. Control-flow & semantics (L6–L8)** — `cf_guard_clause_inversion`, `cf_loop_to_map`, `cf_recursion_vs_iteration`, `cf_strategy_pattern` (switch vs strategy object), `cf_collector_vs_filter` (manual vs `array_filter`+`array_map`), `cf_validation_chains` (nested ifs vs fluent vs guards), `api_immutable_vs_mutable`, `api_fluent_vs_imperative`, `api_generator_vs_array` (`yield` vs return array), `sem_equivalent_algorithms` (bubble vs insertion sort — same semantics).
- **H. Compound interference (L9)** [C] — extends F:§2 (F pushes to 10–20 axes; C fills the 2–4-axis breadth): `ws+cm`(12), `ws+rn`(12), `ws+rn+cm`(15), `ws+lt`(10), `cm+rn`(12), `rn+lt`(12), `st+cf`(15), `lt+st+rn`(12), + ~150 more pairwise/triple combos.
- **I. Threshold/edge (L2/L4/L10)** [C] — token-boundary ±1 (20/30/50/100), line-boundary ±1 (5/10/20/50), %-overlap (95/97/98/99/100), identifier-count variance (1/2/5/10/20) → feeds F:K-28 (also folded into §14).
- **K. Real-world scenario sets** [C] — multi-file/multi-method, 2+ clusters, realistic refactor paths: e-commerce order flow (validate→tax→discount→invoice), auth flow (register→verify→OAuth→session), API rate-limit (quota→increment→decay→headers), cache-invalidation (mark→rebuild→verify→publish), permission cascade (own→group→org→system), DB transaction (begin→lock→execute→commit/rollback), error recovery (primary→catch→fallback→log→default), batch processing, file upload, search indexing → ties §16 + F multi-cluster.
- **L. Tool-specific blind-spot sets** [C] — target measured weaknesses: phpcpd whitespace-normalization (8), jscpd encoding (5), Simian 6-token edge (8), PHPMD comment-handling (5), PMD-CPD scope-blindness (6) → feeds F:K + F:F-20 profile.

> Cross-ref: many A–F/H families map onto F:T-* transform codes and plan families; copilot's added value is the **enumerated breadth + explicit set counts** (a ready backlog), not new mechanics.

# 21. Named "flagship" set ideas — [O]

Ten fully-specified showcase sets — kept with their evocative titles, each given a fable-style slug. Several deliberately combine dimensions from §12–§18.

| Title | Slug (proposed) | Level | Composition (what varies / what's tested) |
|---|---|---|---|
| **Tax Calculator Zoo** [O Idea 1] | `mix_tax_zoo` | L9 | 20 files: 3 exact + 3 ws + 3 cm + 3 rn + 3 literal-rate + 3 cf(if/else vs `match`) + 2 near-miss; one dominant cluster + traps |
| **The Seven Samurai** [O Idea 2] | `probe_axis_septet` | L-probe | 7 instances, each isolating ONE axis (pristine/ws/cm/rn/lt/cf/api) — isolates each axis's detection difficulty (= F:K minimal-pair spirit / G-4) |
| **Nested Matryoshka Clones** [O Idea 3] | `adv_matryoshka` | L10 | outer 50-line ⊃ 20-line ⊃ 5-line, each in multiple files → multi-granularity (needs fragment engine, §12.5) |
| **Clone Marriage** [O Idea 4] | `adv_shared_subclone` | L10 | two clusters sharing a middle `SharedCode` block across files → shared sub-clone handling (§12.6) |
| **Evolutionary Clones** [O Idea 5] | `mix_generations` | L9 | Gen1 exact → +ws → +cm → +rn → +lt across file pairs → detectability-degradation curve (§15 / F:C-16) |
| **The Twin Paradox** [O Idea 6] | `pd_twin_delta` | L5 | 99%-identical, 1-line delta; must cluster + note the delta (§12.8) |
| **Mass Duplication Event** [O Idea 7] | `adv_mass_instances` | L10 | 50 files, same 10-line clone at varying positions (line 5…200) → scalability + region accuracy (F:K-32) |
| **Dependency-Injection Variety** [O Idea 8] | `sem_di_zoo` | L8 | same body via ctor/setter/method inject / static factory / service locator / global singleton (extends F `sem_di_style` / plan Recipe 10) |
| **The Inclusion Problem** [O Idea 9] | `adv_subset_clone` | L10 | Clone2 ⊂ Clone1 → subset vs separate (§12.7) |
| **Cross-Framework Translation** [O Idea 10] | `sem_framework_xlate` | L8 | plain / Laravel / Symfony / CodeIgniter / Yii, same behavior (§16.5) |

# 22. Adopted extra tiers: L11–L20 — [O] (per §0 D-A — do both)

**Decision D-A = do both:** these L11–L20 tiers are **adopted as real levels**, *and* the families + 2-D `difficulty.axes` + rungs machinery (§5) applies within them exactly as within L00–L10. So the ladder now runs **L00–L20**, with fine intra-level grading everywhere. Each tier keeps its fable cross-reference — the tier is the coarse thematic home, the family/rung/axis is the fine grade. (Note L11 = Deep-Rename Stacks, so partial-duplication lives at **L05-extension + the `partiality` axis**, per §0 D-C.)

- **L11 Deep Renaming Stacks** — RN-01+02+03+04 + case-convention + TY toggles stacked; identical only under identifier canonicalization. (≈ F:C-2 `mix_t2_saturation`.)
- **L12 Whitespace Chaos Layers** — 5+ WS transforms at once + heredoc-vs-string + array-multiline. (≈ F:C-1 `mix_t1_blizzard`.)
- **L13 Comment Obfuscation Layers** — 5+ CM transforms + commented-out lines + decoy comments naming real vars + mixed docblock/annotation. (≈ F:§2 + F `cm_*`.)
- **L14 Literal Variation Storm** — int/float/string/hex/binary of the same value; `[1,2,3]` vs `range(1,3)` vs `array(...)`; `3600` vs `60*60` vs `pow(60,2)`. (≈ F:C-* + `lt_*`.)
- **L15 Structural Divergence** — `if($x)` vs `if($x===true)` vs `!empty`; ternary vs if/else; `match` vs `switch`; `foreach` vs `for`; `array_map` vs `foreach`; `??` vs isset-ternary. (≈ F L6.)
- **L16 Function-Signature Divergence** — same body, different params/return/name; ctor-inject vs static+global; `__invoke` vs plain fn; callable-as-string vs array vs closure; named vs positional args. (≈ F:R-20 `rf_signature_drift` + plan `named_positional_args`.)
- **L17 OOP Structural Divergence** — class-with-methods vs trait+`use`; inheritance vs composition; interface vs concrete; trait-reuse vs copy-paste; static vs singleton; flyweight vs repeated `new`. (≈ F:R-6/R-7/R-8/R-12 + `sem_oo_split`.)
- **L18 Namespace/Autoloading Divergence** — same class in different namespaces w/ `use`; global vs namespaced fns; PSR-4 vs PSR-0; `require` vs `require_once`; composer vs manual autoload. (extends plan `ns_imports` / F NS codes.)
- **L19 Mixed CF + Semantic** — CF-02 `match_switch` + SEM-02 `inline_vs_extracted`; CF-03 guard + SEM-04 `oo_split`; CF-04 loop-form + SEM-03 rule-reexpr. (≈ F:§2 cross-group stacks.)
- **L20 The Kitchen Sink (10–20 stacked)** — the flagship: up to **34 stacked transforms** on one clone, refactorable only after extreme normalization. Sub-tiers **L20a** 10–12 axes · **L20b** 15–17 · **L20c** 20+. (= F:C-4 `mix_full_spectrum`; F expresses this at L10 + 2-D difficulty rather than as a new level.)

# 23. Combinatorial & refactorability additions — [O,C]

### 23.1 Combinatorial [O Part 10, C Dim1] — extends F:C-1…C-18
- **Mutation-testing framing** [O 10] — systematically mutate across 10 dimensions (ws / cm / identifier / literal / type / control-flow / API / structure / namespace / surrounding-context). Ready-made stack recipes: **Combo A** medium (10 axes) · **Combo B** hard (15) · **Combo C** expert (20+). (= F's C-* regime; O's templates are drop-in stacks.) Record via F:E-7 `interference_profile`.
- **"Sets where NOT all variations apply"** [O 10 — net-new; directly serves the user's partial-normalization idea] — deliberately apply some axis groups and *withhold* others so only a **subset** of normalization capabilities suffices: e.g. apply WS+CM+RN+LT but NOT CF/API (Type-2 focus); apply CF+API+SEM but NOT WS/CM/RN (preserves visual similarity); apply RN+LT+TY but NOT WS/CM (preserves structural appearance). Isolates *which* normalizations a tool has. New metadata: `interference_profile.groups_applied` / `groups_withheld`. Strong tie to F:K probes + §5 progression.
- **Cartesian volume math** [C Dim1] — 5×5×5 per level per seed = 125 combos; ×3 existing seeds = **375 compound sets from current material alone**.

### 23.2 Refactorability [O Part 4] — extends F:R-1…R-20 (all land in F:E-6 `intended_refactoring`)
- **Parametric spectrum** [O 4.1] — **Easy** 1 param (`addTax`/`addVat`/`addMarkup` → `process($rate,$label)`) ≈ F:R-1/R-3; **Medium** 2–3 params (`applyStandard`/`applyPremium` → `calculate($base,$rate,$threshold)`) ≈ F:R-2 mid-rung; **Hard** 4+ params / conditional (`legacyCalc`) ≈ F:R-2 top rung / parameter-object.
- **Template-method candidates** [O 4.2] = F:R-7. **Strategy candidates** [O 4.3] = F:R-6.
- **Exploded vs composed** [O 4.4 — net-new refactoring kind] — same logic as 50 nested-`if` lines vs a 1-line composed expression → refactoring kind `simplify_expression`/`consolidate`; **add to F:E-6 `intended_refactoring.kind` enum.**
- **Business-logic extraction** [O 4.5] = F:R-16 `rf_scatter_gather` / §16.1 (`isValidCustomer`/`isValidUser` → `validateEntity($entity,$txn,$typeField,$thresholdField)`).

# 24. Phased build plan, scope & prioritization — [C,O]

Use F:§10's 9-phase order as canonical; C adds a schedule + targets, O adds a priority-tier view.

- **copilot 8-phase (12-week) plan** [C]: P1 foundation audit & tooling → P2 seed expansion (12 new seeds) → P3 compound-interference gen (200+ L9) → P4 threshold & edge (80+) → P5 hidden-challenge mapping (100+) → P6 real-world scenarios (50+) → P7 L10 adversarial (100+) → P8 integration & validation (`build --all` / `verify --all` / bench / profile).
- **opencode 3-tier priority** [O]: **T1** (high-value / moderate-effort) — new tiers/families L11–L15, multi-cluster, instance-count, new seeds, size-classes, sub-level progression. **T2** (high-value / higher-effort) — L20 kitchen-sink, cross-domain semantic, functional-vs-imperative, template/strategy refactor, OOP divergence, granularity. **T3** (specialized/adversarial) — unicode/homoglyph, minified, generated code, mass-dup, overlap, inclusion.
- **Scope targets (a range, not a decision):** copilot **1000+** sets / 15 seeds / 60+ families-per-level / 100% hidden-challenge coverage; fable **≈920** total (~375 new); opencode open-ended. All agree: keep `verify --all` + `build --check` green every wave; end every wave benchmarkable.
- **copilot success criteria** [C]: all sets pass `verify`; byte-identical on regen; bench scores align with `detection_expectation`; tool capability matrix populated (P/R/F1 per family); 100% hidden-challenge mapping; docs + contribution guide complete.
- **Reconciled order (recommended):** F:§10 P1 schema v2 + line-accounting → P2 capability probes + `bench/profile.php` (F:F-20) → P3 partial-duplication → P4 refactorability → P5 combinatorial → then seeds / NZ-ENC / topology (folds in C's P2–P7 as the content waves once the schema/engine prerequisites land).

# 25. Negative controls — [O]

Extends F L0 / plan `L00` `nd_*` families. O adds: truly-clean utility files (no dup); single-responsibility classes; unique business logic; and **self-contained "looks-like-a-clone-but-isn't"** — same pattern with different values (validation rules for *different* fields), intentional near-misses that must **not** be reported. All feed FP-resistance (F:K-31, plan L0 + traps).

# 26. Metadata additions from [O]/[C] & reconciliation — [O,C] (see §0 D-B)

opencode Part 13 and copilot both propose `set.json` metadata; most concepts already have a richer home in F:§1 (E-1…E-18). This records the **flat-field alternative** (D-B option B) and maps each to its fable equivalent so reconciliation is one table.

| [O]/[C] flat field | Concept | fable rich equivalent | Verdict |
|---|---|---|---|
| `schema_version: 2` | version bump | F:E — `schema_version` enum `[1,2]` | **agree** |
| `variation_count` | # axes applied | F:E-7 `interference_profile.axis_count` | flat mirror |
| `variation_types[]` | codes applied | F:E-7 `interference_profile.per_carrier[].codes` + existing `interference[]` | flat mirror |
| `clone_instance_count` | # members | existing `duplication.instances` | already exists |
| `clone_cluster_count` | # clusters | F:E-10 multi-cluster (`clusters` count) | already / extend |
| `unique_to_clone_ratio` | density | F:E-3 `duplication.duplication_ratio` + F:E-1 `region_sloc` | flat mirror |
| `refactorability{can_parametrize,param_count,requires_pattern}` | refactor hint | F:E-6 `intended_refactoring{kind,holes[],unified_signature,collapses,advisability}` | F is a superset |
| `overlap_with_other_clones` | overlap flag | F:E-10 topology + §12.6 | flat mirror |
| `near_miss_count` | # traps | derivable from `non_duplicates[]` | derived |
| `non_duplicates[].bait_class` | distractor tier (§17.1) | new (from C Dim2) | **new — adopt into F:K-31 FP classes** |

**Decision (D-B — resolved):** fable's rich objects are **canonical** (generator-emittable + verifier-checkable); the flat fields above are emitted as **optional derived mirrors** for quick filtering/sorting.

---

# 27. Appendix (combined) — source-coverage matrix

Where each source's major material landed. "Merged→F:ID" = folded in as a cross-reference to a fable idea rather than duplicated.

| Source section | Landed in | Disposition |
|---|---|---|
| **[F]** entire catalog (E/V/C/P/R/G/K/S/T/F/H) | §1–§11 | verbatim spine |
| [O] Part 2 — new tiers L11–L20 | §22 | adopted (D-A: do both); each cross-ref'd to F equivalent |
| [O] Part 3 — structural variations | §12 | net-new + merged→F:E-10/P-12/C-15 |
| [O] Part 4 — refactorability axis | §23.2 | merged→F:R-1…R-20/E-6 (+ new kind `simplify_expression`) |
| [O] Part 5 — domain/semantic equivalence | §16 | net-new (semantic families) |
| [O] Part 6 — interference/noise | §17 | net-new + merged→F:K-31 |
| [O] Part 7 — granularity | §13 | net-new (activates `granularity` enum) |
| [O] Part 8 — edge/adversarial | §18 | net-new + merged→F ENC/K |
| [O] Part 9 — progressive complexity (a–e; Auth 1a–1j) | merged→F:§5 (G-1…G-9) | overlaps F rungs/arcs; example kept as §5 illustration |
| [O] Part 10 — combined/mutation combos | §23.1 | merged→F:C-* (+ new "groups_withheld") |
| [O] Part 11 — 20 seeds | §16.8 | unioned with F:§7 |
| [O] Part 12 — size classes | §14 | net-new matrix |
| [O] Part 13 — metadata | §26 | reconciled → F:§1 |
| [O] Part 14 — 10 named sets | §21 | net-new flagship sets |
| [O] Part 15 — negative controls | §25 | extends F L0 |
| [C] Strategic goals / Dimensions 1–10 | §12–§20, §23–§24 | mapped by dimension (see below) |
| [C] Dimensions 1/2/3/4/5/6/7/8/9/10 | §23 / §17 / §16.8 / §13 / §14 / §15 / §19 / §18+§20-L / §12 / §20-K | all placed |
| [C] Detailed categories A–N | §20 | net-new family backlog |
| [C] Phased plan + metrics | §24 | merged with F:§10 |

**Combined size (indicative):** fable ≈225 distinct ideas (unchanged, §1–§11) + opencode's ~10 new tiers, ~30 structural/semantic/granularity/topology families, 10 flagship sets, 20 seeds + copilot's ~55 enumerated A–N families, 4 distractor tiers, tool-blind-spot sets, 60-challenge map, and the phased plan. Overlaps are cross-referenced, not double-counted. **Everything from all three source files is represented above.**

