# Plan: Graduated Duplication-Detection Test Sets — Combined Master Plan

Status: **IN PROGRESS** — **P0 Foundation is BUILT & INDEPENDENTLY VERIFIED (2026-07-13)**: JSON
schemas, the `gen/` generator engine (build/verify/manifest + transform interface + Wave-1
transforms WS-03/WS-06/CM-03/RN-01/LT-01/ST-01 + CF/API variant-selectors), 3 seed domains with
equivalence tests, `bench/run-testsets.php`, doc skeletons, and **5 verified pilot sets**
(L00/L01/L02/L04/L06). Remaining: finish Wave-1 fan-out (5 more starter sets) then phases P1–P8.
See **Current build status** at the end of §0 and §16/§18.10.
Scope: a new, self-contained corpus of **graduated-difficulty** test sets for benchmarking
duplicate-code detection, with machine-readable per-set metadata and line-accurate
ideal-answer ground truth. Complements (does not replace or modify) the existing
`samples/` tree.

---

## 0. How to Read This Plan (start here)

**This plan is long by design — you are not expected to read all of it for every task.** Read by
role. Each agent is *also* handed the exact excerpts it needs inside its prompt (§20), but the map
below says where the full detail lives so nothing is missed.

| If you are… | Read in full | Skim / reference as needed |
|---|---|---|
| **Orchestrator / lead** (drives the whole build) | §1–§2, §4–§13, **§18, §19, §20** | everything else |
| **Foundation agent** (builds the shared engine) | §4, §5, §7, §8, §10, §11, §12, §18.1, §18.6, **§20.1** | §9 (skim), §13 |
| **Set-Builder agent** (generates one set) | §6, §7, §8, §9 *(only your level)*, §10, §18.5–§18.8, **§20.2 + §20.6** | §11, §12 |
| **Reviewer agent** (evaluates one set) | §6, §7, §12, §18.5, §18.7, **§20.3 + §20.6** | §8, §9 *(the set's level)* |
| **Fixer agent** (repairs a failed set) | the set's `REVIEW.md`, §18.6, **§20.4 + §20.6** | same as Set-Builder |
| **Integration agent** (finalizes a wave) | §7.3, §13, §14, §18, §19, **§20.5** | §15 |
| **Tool evaluator** (ran a detector, reading results) | §1, §2 *(esp. R8)*, §13 *(capability profile)*, §15 | §9 |

**If you read nothing else,** read **§1** (what & why), **§6** (what a set is), **§18.1** (the five
non-negotiable rules), and **§20** (your role's prompt).

**North-star outcome** (§1, R8): *after running a detector against this corpus you should be able
to state precisely what it can and cannot detect, where its thresholds blind it, and where it
raises false positives.* Every design choice — single-axis levels, near-miss distractors,
`detection_expectation`, threshold-boundary sets — exists to make that profile fall out of the
scores. Keep it in mind whatever your role.

Section numbers are stable; `§N.M` refers to a subsection. Appendices are lettered (A–D) and
referenced by letter, not number.

### Current build status (2026-07-13)

**P0 Foundation: DONE and independently verified** — built, adversarially reviewed, fixed, and
re-verified in the live tree. Present and green:

- `composer.json` + `vendor/` (dev-only `nikic/php-parser` v5.8) — corpus files stay dep-free.
- `testsets/schema/{set,expected,manifest}.schema.json` (+ README) — real draft-07 validation.
- `gen/` engine: `build.php` (`--set/--family/--level/--all/--check`, deterministic), `verify.php`
  (all §12 checks — incl. a token-distinctness check for `token_based:false` clusters and role
  composition), `manifest.php`, `lib/*`, and transforms **WS-03, WS-06, CM-03, RN-01, LT-01,
  ST-01** + CF/API **variant-selectors** + `registry.json` (all §8 codes weighted).
- `gen/seeds/` — 3 domains (`invoice_totals`, `csv_import`, `access_guard`) with payloads,
  `seed.json`, and behavioral **equivalence tests** for the variant-bearing seeds.
- `gen/scaffolds/`, `gen/distractors/`, `gen/recipes/`.
- `bench/run-testsets.php` (per-set phpcpd+jscpd, `{file,start,end}` extraction, Jaccard scorer,
  tool-version capture) + `bench/corpora/testsets.ground-truth.json`.
- Docs: `testsets/README.md`, `testsets/ORCHESTRATION.md` (verbatim §18+§20), `gen/README.md`,
  per-level README stubs.
- **5 verified pilot sets = the first 5 of the Wave-1 batch:** `L00-nd_distinct_domains-001`,
  `L01-ex_function-001`, `L02-ws_blank_inside-001`, `L04-rn_locals-001`, `L06-cf_guard_nested-001`.
  All pass `gen/verify.php`; `gen/build.php --check` is byte-clean; bench scores align with each
  set's `detection_expectation` (token tools: L1/L2/L4 recall 1.00, L6 recall 0.00, traps 0 FP).

**Review trail:** `gen/FOUNDATION_REVIEW.md` + `gen/FOUNDATION_FIXLOG.md`.

**Next step (a fresh session per `prompt_samples.md`):** sanity-check Foundation, then finish
Wave 1 (the **5 remaining** starter sets — `L02-ws_line_wrap-001`, `L03-cm_docblock-001`,
`L04-lt_numbers-001`, `L05-st_insert_logging-001`, `L07-api_map_loop-001`), then drive phases
P1–P7 and P8 Integration via §18.10. Nothing is committed — the tree is uncommitted for review.

---

## Table of Contents

0. [How to Read This Plan (start here)](#0-how-to-read-this-plan-start-here)
1. [Executive Summary](#1-executive-summary)
2. [Goals and Requirements](#2-goals-and-requirements)
3. [What Exists Today and What's Missing](#3-what-exists-today-and-whats-missing)
4. [Core Design Decisions](#4-core-design-decisions)
5. [Directory Layout and Naming](#5-directory-layout-and-naming)
6. [Anatomy of a Set](#6-anatomy-of-a-set)
7. [JSON Schemas](#7-json-schemas)
8. [Interference & Variation Registry](#8-interference--variation-registry)
9. [Level Progression (L0 – L10)](#9-level-progression-l0--l10)
10. [Seed Payload Library & Code Templates](#10-seed-payload-library--code-templates)
11. [Generation Strategy](#11-generation-strategy)
12. [Verification & QA](#12-verification--qa)
13. [Bench Integration & Reporting](#13-bench-integration--reporting)
14. [Real-World Extensions](#14-real-world-extensions)
15. [Volume Summary & Difficulty/Detection Matrix](#15-volume-summary--difficultydetection-matrix)
16. [Phased Milestones](#16-phased-milestones)
17. [Defaulted Decisions / Open Questions](#17-defaulted-decisions--open-questions)
18. [Execution via Parallel Agents](#18-execution-via-parallel-agents)
19. [Documentation Deliverables](#19-documentation-deliverables)
20. [Agent Prompt Library (Ready-to-Use)](#20-agent-prompt-library-ready-to-use)
21. [Appendix A: Challenge-Taxonomy → Level Mapping](#appendix-a-challenge-taxonomy--level-mapping)
22. [Appendix B: Existing Category → Seed/Level Mapping (Coverage Audit)](#appendix-b-existing-category--seedlevel-mapping-coverage-audit)
23. [Appendix C: Flat Interference-ID Cross-Reference (I001–I032 ↔ Registry Codes)](#appendix-c-flat-interference-id-cross-reference-i001i032--registry-codes)
24. [Appendix D: Level-Scheme Reconciliation](#appendix-d-level-scheme-reconciliation)

---

## 1. Executive Summary

This plan details the comprehensive build-out of a structured benchmark corpus for PHP
code duplication detection. The repository already contains a rich taxonomy of duplication
types (54-category `code_duplication_types.md`) and "hidden" duplication challenges
(60-type `code_duplication_challenges.md`), plus ~600 realistic sample files, but it lacks
a **systematic, difficulty-graded set structure with explicit line-accurate ground truth**
suitable for benchmarking.

**Goal:** Create a new top-level `testsets/` corpus of **500+ benchmark sets across 11
difficulty tiers (L0–L10)**, organized three levels deep (level → family → set). Every set
contains **≥5 files** — when duplication is present, **exactly 3 files carry the duplicated
region** (the "carriers"), plus a **near-miss distractor** and a **clean filler**. Every set
ships **machine-readable per-set metadata** (`set.json`) and a **line-accurate ideal-answer
ground-truth file** (`expected.json`) describing the exact duplication topology, so that
precision/recall/F1 can be computed per level, per family, and per interference code by the
existing `bench/` scoring pipeline.

The corpus is **generator-first**: a deterministic generator applies parameterized
transforms to hand-written seed payloads and emits ground-truth line numbers *mechanically*,
so answers can never drift out of sync with the code. Difficulty ramps from L0 (no
duplication — negative controls) through L1 (byte-identical clones) to L10 (adversarial edge
cases: overlaps, intra-file clones, encoding tricks, generated code, drifted genealogies).

**North-star outcome — a capability profile, not just a score.** The point of the corpus is not
to rank tools with one number; it is that **after running a duplication detector against it, you
can describe that tool completely** — exactly which clone types and interference axes it detects,
where its size/token thresholds blind it, how it behaves on near-misses and boilerplate (false
positives), and how its region reporting drifts. This is achievable *because* the levels are
**single-axis** (a miss at L4-`rn_locals` but a pass at L1 pinpoints "no identifier
canonicalization"), *because* every set carries `detection_expectation` (so "missed" is separated
from "not expected to find"), and *because* L0 + near-miss distractors make false positives
measurable. The reporting layer (§13) turns those scores into a per-tool **capability report
card** ([R8](#2-goals-and-requirements)). Keep this end-goal in view at every level of the build.

---

## 2. Goals and Requirements

Restated from the original request as hard requirements:

- **R1 — Set shape.** Every set contains **at least 5 files**. When the set contains
  duplication, **exactly 3 of the files carry the duplicated code** (the "carriers"); the
  remaining files are non-duplicate distractors/filler. Higher levels may add more files,
  but never fewer than 5, and never fewer than 3 carriers when duplication exists.
- **R2 — Coverage per level.** **At least 5 sets for every variation family at every
  level.** Each level is organized into "families" — one family per variation type (e.g.
  "extra blank lines", "tab characters", "comments") — and each family gets ≥5 sets.
- **R3 — Graduated difficulty.** Level 0 = no duplication at all (negative controls).
  Level 1 = exact byte-identical duplication, same formatting/spacing. Each subsequent
  level introduces one new axis of variation (spacing before/after/inside the clone,
  statements broken across lines, tab characters, comments, renames, edits, rewrites,
  semantics), ending in levels that **mix multiple interference types**.
- **R4 — Per-set metadata.** Every set ships a JSON descriptor stating the duplication
  type(s), interference type(s) with parameters, difficulty, and the role of every file.
- **R5 — Ideal answers.** Every set ships a machine-readable ground-truth file naming every
  duplicate cluster and the exact file + line ranges of every member, plus explicit "must
  NOT be reported" regions for false-positive traps.
- **R6 — Thoroughness.** Incorporate every duplication/interference type already documented
  in this repo (54-category taxonomy in `code_duplication_types.md`, 60-type "hidden
  duplication" taxonomy in `code_duplication_challenges.md`, and the 59 existing `samples/`
  categories — 56 indexed in `samples.json` plus 3 directory-only categories not yet indexed;
  see [Appendix B](#appendix-b-existing-category--seedlevel-mapping-coverage-audit)), plus
  additional types identified here (encoding tricks, threshold-boundary
  clones, overlapping clusters, clone genealogy, generated code, HTML/PHP mixing, etc.).
- **R7 — Benchmarkable.** Ground truth must plug into the existing `bench/` scoring pipeline
  (cluster members as `{file, start, end}`, Jaccard ≥ 0.6, ±2-line tolerance) so
  precision/recall/F1 can be computed per level, per family, and per interference code.
- **R8 — Capability profiling (the end-goal).** The corpus + reports must be sufficient to produce
  a **complete profile of any detector run against it**: for each tool, a statement of (a) which
  clone types / interference axes it *detects* vs *misses*, (b) its *false-positive* behavior on
  L0 negatives, near-miss distractors, and acceptable-boilerplate idioms, (c) its *threshold blind
  spots* (min-lines / min-tokens below which it goes silent), (d) its *region-reporting accuracy*
  (boundary drift, gapped/partial handling), and (e) its *runtime/scaling* behavior. Every set is
  designed so its pass/fail contributes an unambiguous bit to this profile — that is what
  "single-axis levels + `detection_expectation` + measurable false positives" buys. The
  materialized deliverable is the **Tool Capability Profile** report (§13.4).

**Non-goals:**

- Not modifying or migrating the existing `samples/` tree (it stays as the
  category-reference corpus; the new suite lives in a new top-level `testsets/` tree).
- Not building the detector itself; only the corpus, ground truth, and harness glue.

---

## 3. What Exists Today and What's Missing

### Exists

| Asset | Contents | Reusable for this effort |
|---|---|---|
| `samples/<cat>/<id>/block_{a,b,c}.php` | 59 categories, ~600 sets of 2–3 realistic PHP files each, organized by duplication *type* | Bodies are excellent **seed-payload** material (realistic domain code, PHP 8.1, strict types) |
| `refactored/` | Deduplicated refactored solutions | Reference for "what the shared abstraction looks like"; variant inspiration |
| `samples.json` | Master index (category → sample ids); references `.md` files that don't exist | Pattern for our `manifest.json`, but ours needs much richer per-set data |
| `code_duplication_types.md` | 54-category taxonomy + examples + refactors | Source list for semantic-level families and seed domains |
| `code_duplication_challenges.md` | 60-type "hidden duplication" taxonomy + "what mature detectors normalize" table | **The backbone of the interference registry (§8) and level design (§9)** |
| `bench/run.php`, `bench/score.php` | Tool runners + precision/recall/F1 scorer against `.ground-truth.json` (clusters of `{file,start,end}`, Jaccard ≥ 0.6, ±2 lines) | Our `expected.json` format is designed to compile into exactly this shape |
| `bench/run-samples.php`, `bench/corpora.php` | Runs phpcpd/jscpd per category, counts findings | Template for the new `bench/run-testsets.php` |
| `/home/my/logs/smarty_templates_c/` (outside repo) | 587 real compiled-Smarty PHP files | Raw material for the L10 "generated code" family (§14) |

### Missing (what this plan adds)

1. **No difficulty grading.** Samples exist but aren't organized by *detection* difficulty;
   you can't tell how far up the ladder a tool climbs.
2. **No negative controls.** Nothing in `samples/` is guaranteed duplication-free; a tool
   that flags everything scores "perfectly" against the current tree.
3. **No distractor files.** Every existing file is a clone member; there are no
   files-that-should-not-match inside a set, so **false positives are unmeasurable**.
4. **No graduated, single-axis difficulty.** Existing categories mix many differences at
   once (renames + literals + comments + spacing simultaneously). You can't tell *which*
   normalization a tool is missing.
5. **No per-set metadata.** No JSON describes which interference a set exercises.
6. **No ground truth for samples.** Only the synthetic-fuzz bench corpus has ground truth;
   the sample tree has none. **Line-accurate ideal answers do not exist anywhere.**
7. **No structured ≥5-file set format.** Current format is `block_a/block_b/block_c` per
   sample, not role-tagged 5-file sets.
8. **Format mismatch.** `samples.json` references `.md` files but actual content is `.php`.
9. **No compound-interference ladders** and **no adversarial cases** (threshold boundaries,
   overlaps, intra-file clones, encoding tricks, HTML mixing, drifted genealogies).
10. **Missing bench infrastructure.** `bench/corpora/` for a graduated corpus doesn't exist.

### Coverage audit — every existing type is accounted for

An audit of the live corpus (2026-07-13) reconciled the source-of-truth lists so that **every
duplication type the repo already knows about is guaranteed to be either *generated* by the new
suite or explicitly *used* as seed/inspiration material**:

- **`samples/` directory:** **59** category dirs.
- **`samples.json → types[]`:** **56** entries.
- **Delta:** 3 categories exist on disk but are **absent from `samples.json`** —
  `connection_handling_duplication`, `query_style_duplication`, `result_processing_duplication`.
- **`samples.json → challenges[]`:** an **empty array** — the 60 "hidden duplication"
  challenges live only in `code_duplication_challenges.md`, not in the index.

**Resolution (decisions for this effort):**

1. The **union of both lists** (59 dir categories ∪ 56 JSON types ∪ 60 challenge rows) is the
   authoritative input. Every one of the 59 directory categories is mapped to a target
   level/family in [Appendix B](#appendix-b-existing-category--seedlevel-mapping-coverage-audit)
   (complete, explicit, no wildcards); every one of the 60 challenges is mapped in
   [Appendix A](#appendix-a-challenge-taxonomy--level-mapping) (58 covered, 2 deferred with
   rationale). **Nothing is left unaccounted for.**
2. **`samples.json` is treated as stale, not authoritative.** A tiny stretch task
   ([§18](#18-execution-via-parallel-agents), Integration phase) regenerates/repairs it: add
   the 3 missing type entries and (optionally) populate `challenges[]` from the markdown, so the
   index matches disk. This is documentation hygiene only — it does **not** modify any existing
   `samples/` content (D1 non-goal preserved).

---

## 4. Core Design Decisions

**D1 — New top-level tree `testsets/`.** Keeps the graduated suite separate from the
category-reference `samples/` tree. No churn in existing paths; README sections stay valid.

**D2 — Three-tier hierarchy: level → family → set.**
`testsets/L02_whitespace/ws_line_wrap/003/`. A *level* is a difficulty tier; a *family* is
one variation type at that level (R2's "sets of sets"); a *set* is one benchmark unit. This
three-tier shape is what makes per-normalization capability reporting possible (§13) and is
strictly more scalable than a flat `sets/level_N/set_NNN/` layout.

**D3 — Metadata lives beside the code, but code lives in `src/`.** Detection tools (and
LLM-based detectors) are pointed at `<set>/src/` only. `set.json` and `expected.json` sit
one level up so a tool cannot "cheat" by reading the answers, and file names inside `src/`
are realistic domain names (`InvoiceTaxCalculator.php`), never `clone_a.php` /
`distractor.php` — role information exists **only** in `set.json`.

**D4 — Generator-first, hand-curated where machines can't.** Levels 0–5, 9, and most of 10
are produced by a deterministic generator (`gen/`) that applies parameterized transforms to
hand-written seed payloads and **emits ground-truth line numbers mechanically**.
Hand-maintained line numbers rot the moment anyone touches a file; generated ones cannot.
Levels 6–8 (rewrites/semantic) need hand-authored variant bodies, but the generator still
assembles them into carrier files and computes the line ranges. Every set records its RNG
seed; regeneration is byte-identical (CI-enforced).

**D5 — One new axis per level (the "minimal-pair" principle).** A set at level N contains **only**
its family's interference type on top of an otherwise Level-1-exact clone. Compound interference
is exclusively the business of L9/L10. Think of each L2–L8 set as a *minimal pair*: it differs
from a known-detectable L1 clone by exactly one controlled variable, so a **miss localizes the
exact missing capability** (a pass at L1 + a miss at L4-`rn_locals` ⇒ "no identifier
canonicalization"; nothing else can explain it). This is the mechanism behind R8 capability
profiling and per-normalization reporting (§13) — you can say "phpcpd falls over *exactly* at
`rn_locals`". Builders must therefore guard against **accidental second axes** (e.g. do not let a
whitespace transform also renumber a literal, and do not let a rename collide with an existing
symbol) — that would blur which variable caused a miss.

**D6 — Standard set = 5 files: 3 carriers + 1 near-miss distractor + 1 clean filler.**
The near-miss distractor shares vocabulary/shape with the clone but is semantically
different (false-positive bait, listed under `non_duplicates` in ground truth). The clean
filler is unrelated same-domain code establishing base-rate noise. Levels 9–10 may use 6–8
files (extra distractors, extra cluster members) but never fewer than the standard.
*(This refines the earlier "3 duplicates + 2 unique" shape by splitting the 2 non-dupes into
a measurable near-miss trap and a clean filler.)*

**D7 — Ground truth is authoritative at line granularity** with declared tolerance
(default ±2 lines to match `bench/score.php`), and includes *negative* assertions
(regions that must NOT be reported).

**D8 — All PHP 8.1+, `declare(strict_types=1)`, one class or function library per file**,
consistent with the existing corpus style, except where a family deliberately varies
formatting (that's the point of L2) or file structure (L10 HTML-mix, generated code).

---

## 5. Directory Layout and Naming

```
testsets/
├── README.md                     # how to run, how to add sets, schema docs pointer
├── manifest.json                 # generated global index (see §7.3)
├── schema/
│   ├── set.schema.json           # JSON Schema for set.json
│   ├── expected.schema.json      # JSON Schema for expected.json
│   └── manifest.schema.json
├── L00_no_duplication/
│   ├── nd_distinct_domains/
│   │   ├── 001/
│   │   │   ├── set.json
│   │   │   ├── expected.json
│   │   │   └── src/
│   │   │       ├── PayrollCalendar.php
│   │   │       ├── DnsRecordParser.php
│   │   │       ├── ImageThumbnailer.php
│   │   │       ├── RateLimiter.php
│   │   │       └── SitemapBuilder.php
│   │   ├── 002/ …
│   │   └── 005/
│   ├── nd_same_domain/ …
│   └── …
├── L01_exact/
│   ├── ex_function/  ex_method/  ex_block/  ex_full_file/
│   ├── ex_position/  ex_size_ladder/  ex_multi_cluster/
├── L02_whitespace/ …
├── L03_comments/ …
├── L04_rename_literals/ …
├── L05_statement_edits/ …
├── L06_controlflow_rewrites/ …
├── L07_api_idioms/ …
├── L08_semantic/ …
├── L09_mixed/ …
└── L10_adversarial/ …

gen/
├── build.php                     # assemble all (or filtered) sets from recipes
├── verify.php                    # full QA pass (§12)
├── manifest.php                  # rebuild testsets/manifest.json + bench ground truth
├── seeds/                        # payload library (§10)
│   ├── invoice_totals/
│   │   ├── payload.php           # the code that gets cloned (marked region)
│   │   ├── seed.json             # size class, domain, token count, variants list
│   │   ├── equivalence_test.php  # behavioral-equivalence fixture for L6–L8 variants
│   │   └── variants/             # hand-written rewrites for L6–L8 (cf_*.php, api_*.php …)
│   └── …
├── scaffolds/                    # carrier file templates with insertion markers
├── distractors/                  # near-miss + clean filler library, tagged by domain
├── transforms/                   # one class per interference code (§8)
│   ├── registry.json             # machine-readable interference registry
│   ├── Ws/BlankBefore.php … Cm/BlockComment.php … Rn/LocalVars.php …
└── recipes/                      # per-family recipe files describing each set
    └── L02/ws_line_wrap.json

bench/
└── run-testsets.php              # run tools per set, score, emit matrices (§13)
```

**Naming rules:**

- Level dirs: `L{NN}_{slug}` (zero-padded). Family dirs: `{prefix}_{slug}` where the prefix
  groups related families (`nd`, `ex`, `ws`, `cm`, `rn`, `lt`, `ty`, `ns`, `st`, `cf`, `bl`,
  `api`, `sem`, `mix`, `adv`). Set dirs: zero-padded `001`–`999`.
- Canonical set id: `L02-ws_line_wrap-003` (level-family-set), used everywhere in JSON and
  reports.
- Files inside `src/` use realistic PSR-ish class names in a per-set fictional domain. No
  hints of role, level, or duplication in any name, namespace, or comment inside `src/`.

**File naming inside `src/` (illustrative, from a parser-domain set):**

```
InvoiceTotalsService.php     ← carrier   (cluster member)
QuoteTotalsService.php       ← carrier   (cluster member)
OrderTotalsService.php       ← carrier   (cluster member)
CurrencyPresenter.php        ← distractor (near-miss FP bait)
TaxRateRepository.php        ← clean     (unrelated filler)
```

The mapping from filename → role lives **only** in `set.json`; nothing in the filename,
namespace, or comments betrays it.

---

## 6. Anatomy of a Set

Standard composition (5 files):

| Role | Count | Purpose |
|---|---|---|
| `carrier` | 3 | Each contains one member of the duplicate cluster, embedded in otherwise-unique surrounding code |
| `distractor` | 1 | Near-miss bait: shares vocabulary, shape, or partial token stream with the clone but is **not** a duplicate; listed in `expected.json → non_duplicates` |
| `clean` | 1 | Unrelated filler in the same fictional domain; establishes base-rate noise |

**Rules:**

1. **Carriers are not clones of each other in their entirety** (except in the
   `ex_full_file` family). The cloned region is a function/method/block *inside* a file that
   otherwise differs — surrounding code comes from different scaffolds. This tests region
   reporting, not just file pairing.
2. **The cloned region's position varies across carriers** (top/middle/bottom) unless the
   family explicitly pins position (`ex_position` studies exactly this).
3. **Interference is applied asymmetrically.** In a family like `ws_blank_inside`, carrier A
   keeps the pristine payload, carrier B gets moderate interference, carrier C gets heavy
   interference. `set.json` records exactly which files got which transform with which
   parameters. (Detectors must match pristine↔heavy, the hardest pair.)
4. **No-duplication sets (L0)** have roles `distractor`/`clean` only,
   `duplication.present = false`, and an empty `clusters` array with populated
   `non_duplicates`.
5. **Multi-cluster sets** (family `ex_multi_cluster`, several L9/L10 families) contain 2–3
   independent clusters; every cluster still has ≥3 members but members may co-reside in the
   same file (intra-file duplication is its own adversarial family).
6. Every file is valid PHP (passes `php -l`), 40–150 lines except where a family dictates
   otherwise (`ex_size_ladder` small end, `adv_large_files` big end).
7. Line-ending is LF, encoding UTF-8 without BOM — except in families that deliberately vary
   these (`ws_newline`, `adv_encoding`), where deviations are declared in `set.json` so QA
   doesn't flag them.

---

## 7. JSON Schemas

Formal JSON Schema files live in `testsets/schema/`; the shapes below are normative.

### 7.1 `set.json` — set descriptor (R4)

```json
{
  "schema_version": 1,
  "set_id": "L02-ws_line_wrap-003",
  "level": 2,
  "level_name": "whitespace_variation",
  "family": "ws_line_wrap",
  "title": "Method-chain clone re-wrapped across lines",
  "description": "Identical 24-line totals computation in 3 files; in carrier B the fluent chain is broken one call per line, in carrier C long argument lists are wrapped and re-indented.",
  "language": "php",
  "min_php": "8.1",
  "seed": "invoice_totals",
  "difficulty_band": "easy",
  "files": [
    {"path": "src/InvoiceTotalsService.php", "role": "carrier",    "duplicate_group": "A", "sloc": 84},
    {"path": "src/QuoteTotalsService.php",   "role": "carrier",    "duplicate_group": "A", "sloc": 91},
    {"path": "src/OrderTotalsService.php",   "role": "carrier",    "duplicate_group": "A", "sloc": 88},
    {"path": "src/CurrencyPresenter.php",    "role": "distractor", "duplicate_group": null, "sloc": 63},
    {"path": "src/TaxRateRepository.php",    "role": "clean",      "duplicate_group": null, "sloc": 57}
  ],
  "duplication": {
    "present": true,
    "clone_type": "type-1",
    "granularity": "function",
    "clusters": 1,
    "instances": 3
  },
  "interference": [
    {"code": "WS-06", "name": "line_wrap",
     "params": {"style": "method_chain", "max_line": 60},
     "applied_to": ["src/QuoteTotalsService.php"]},
    {"code": "WS-06", "name": "line_wrap",
     "params": {"style": "args_and_arrays", "max_line": 48},
     "applied_to": ["src/OrderTotalsService.php"]}
  ],
  "difficulty": {
    "score": 12,
    "requires": ["whitespace_normalization"]
  },
  "expected_detection_by_tool": {
    "phpcpd": true, "jscpd": true, "pmd_cpd": true, "simian": true, "phpdup": true
  },
  "generator": {
    "tool": "gen/build.php",
    "recipe": "gen/recipes/L02/ws_line_wrap.json",
    "version": "1.0.0",
    "rng_seed": 8675309
  }
}
```

**Field notes:**

- `duplication.clone_type`: `none | type-1 | type-2 | type-3 | type-4 | semantic-domain`.
  L2/L3 sets remain `type-1` (whitespace/comments are the Type-1 definition); L4 is
  `type-2`; L5 `type-3`; L6–L8 `type-3`/`type-4`/`semantic-domain`; L9/L10 whatever the mix
  produces.
- `granularity`: `file | class | method | function | block | statement-run`.
- `duplicate_group`: a short label (`"A"`, `"B"`, …) grouping carriers that share a cluster;
  `null` for distractor/clean. (Carried forward from the earlier plan's per-file
  `duplicate_group` field so a set can host multiple clusters unambiguously.)
- `interference[].code` references the registry (§8). Params are transform-specific and
  sufficient to reproduce the transform.
- `difficulty.score`: 0–100 heuristic, computed **mechanically** by the generator (not
  hand-guessed) so it's reproducible and comparable. Formula:
  `score = clamp(0..100, level_base[level] + Σ interference_weight[code] + intensity_bonus)`
  where `level_base = {0:0, 1:5, 2:10, 3:15, 4:30, 5:45, 6:60, 7:72, 8:85, 9:78, 10:82}`,
  each registry code carries a weight in `gen/transforms/registry.json` (WS/CM ≈ 2–4, RN/LT ≈
  5–8, ST ≈ 8–12, CF/BL/API ≈ 12–18, SEM ≈ 18–25, ENC/CP modifiers ≈ 3–10), and `intensity_bonus`
  scales with the family dial (set 001 → 0, set 005 → up to +8). Compound sets (L9/L10) sum every
  applied code's weight, which is why their base is lower than L8 yet their totals reach 60–90.
  `difficulty_band` is the coarse human label (`baseline | trivial | easy | medium | hard |
  very_hard | expert`) derived from `score` cutoffs and matching the summary table in §15.
- `difficulty.requires`: the normalization capabilities a detector needs (vocabulary from
  the "what mature detectors normalize" table): `whitespace_normalization`,
  `comment_stripping`, `identifier_canonicalization`, `literal_abstraction`,
  `ast_canonicalization`, `commutative_reordering`, `controlflow_normalization`,
  `deadcode_elimination`, `api_abstraction`, `inline_expansion`, `cfg_comparison`,
  `semantic_reasoning`.
- `expected_detection_by_tool`: optional convenience map of "should this concrete tool find
  it" (the earlier plan's per-tool booleans). Authoritative per-*class* expectation lives in
  `expected.json` (§7.2); this field is a derived hint for quick reports.

### 7.2 `expected.json` — ideal answers (R5)

```json
{
  "schema_version": 1,
  "set_id": "L02-ws_line_wrap-003",
  "clusters": [
    {
      "id": "c1",
      "group_id": "A",
      "clone_type": "type-1",
      "granularity": "function",
      "normalized_by": ["whitespace"],
      "token_hash": "abc123…",
      "normalized_hash": "def456…",
      "members": [
        {"file": "src/InvoiceTotalsService.php", "start_line": 23, "end_line": 46,
         "symbol": "calculateTotals", "pristine": true},
        {"file": "src/QuoteTotalsService.php", "start_line": 31, "end_line": 68,
         "symbol": "calculateTotals", "pristine": false},
        {"file": "src/OrderTotalsService.php", "start_line": 19, "end_line": 55,
         "symbol": "calculateTotals", "pristine": false}
      ],
      "detection_expectation": {
        "text_based": false,
        "token_based": true,
        "ast_based": true,
        "metric_based": true,
        "semantic": true
      },
      "notes": "Members differ only in line breaks/indentation; token streams are identical."
    }
  ],
  "non_duplicates": [
    {"file": "src/CurrencyPresenter.php", "start_line": 18, "end_line": 41,
     "reason": "near-miss: same fluent-chain shape and shared vocabulary, different computation",
     "trap": true}
  ],
  "scoring": {
    "line_tolerance": 2,
    "member_jaccard_min": 0.6,
    "min_members_for_credit": 2
  }
}
```

**Field notes:**

- `members[]` is the shape `bench/score.php` already consumes (`file`/`start`/`end` after a
  trivial key mapping); paths are set-relative.
- `token_hash` / `normalized_hash`: content fingerprints (carried from the earlier
  `answers.json`) — useful for dedup, drift detection, and sanity-checking that "pristine"
  members really are identical after normalization.
- `detection_expectation` says which *class* of detector is expected to find the cluster —
  this is how "tool X missed it" becomes "tool X missed it *and should have found it*" vs
  "this is beyond token-based tools by design". `text_based` = raw-line matcher;
  `token_based` = phpcpd/jscpd class; `ast_based`; `metric_based`; `semantic` =
  embedding/LLM class.
- `min_members_for_credit`: a tool reporting only 2 of the 3 members gets partial/full
  credit per this knob (default 2 = credit with recall penalty; exact policy in §13).
- `non_duplicates` entries with `trap: true` are counted in a separate
  **false-positive-resistance** metric (§13).
- For L0 sets: `clusters: []` and the whole `src/` is implicitly non-duplicate; explicit
  `non_duplicates` entries mark the deliberate bait regions only.

### 7.3 `manifest.json` — generated global index

Built by `gen/manifest.php`. One entry per set:

```json
{
  "schema_version": 1,
  "generated_at": "<git commit of generator run>",
  "totals": {"levels": 11, "families": 108, "sets": 540, "files": 2755, "clusters": 560},
  "corpus_stats": {
    "total_php_files": 2755, "total_sloc": 190000,
    "total_duplicate_regions": 560, "distractor_files": 540, "clean_files": 540
  },
  "levels": [
    {
      "level": 2,
      "dir": "L02_whitespace",
      "title": "Whitespace & layout variation",
      "families": [
        {
          "family": "ws_line_wrap",
          "interference_codes": ["WS-06"],
          "sets": [
            {"set_id": "L02-ws_line_wrap-001", "dir": "L02_whitespace/ws_line_wrap/001",
             "clusters": 1, "files": 5, "difficulty": 10},
            "…"
          ]
        }
      ]
    }
  ]
}
```

`gen/manifest.php` also emits `bench/corpora/testsets.ground-truth.json` — the concatenation
of every set's clusters with paths prefixed by the set dir — so the existing scorer can run
over the whole tree in one pass as well as per-set. (This supersedes the earlier plan's
separate `answers/master_index.json` + per-level answer files, folding both the global index
and the corpus stats into one generated artifact.)

---

## 8. Interference & Variation Registry

The single source of truth for "what can differ between clone members." Every family
exercises one (L2–L8) or several (L9–L10) codes; `set.json.interference[].code` must
reference this registry. Stored machine-readable at `gen/transforms/registry.json`.

- **"Ch#"** = row in the 60-type taxonomy of `code_duplication_challenges.md`.
- **"I##"** = the flat interference ID from the earlier plan's taxonomy (full cross-reference
  in [Appendix C](#appendix-c-flat-interference-id-cross-reference-i001i032--registry-codes)).
- **"Impact"** = how much harder this makes detection: ● easy · ●● medium · ●●● hard ·
  ●●●● much harder.
- **"Example"** = a concrete before/after illustration (from the earlier plan where available).

### WS — Whitespace / Layout (all preserve token stream ⇒ still Type-1)

| Code | Name | What changes | Example | Impact | Ch# | I## |
|---|---|---|---|---|---|---|
| WS-01 | blank_before | Blank lines inserted immediately before the cloned region | — | ● | 1 | I005 |
| WS-02 | blank_after | Blank lines inserted immediately after the cloned region | — | ● | 1 | I005 |
| WS-03 | blank_inside | Blank lines inserted between statements *inside* the clone | — | ● | 1 | I005 |
| WS-04 | operator_spacing | Spaces added/removed around operators, commas, parens | `$x=1` → `$x  =  1`; `$a.$b` → `$a . $b` | ● | 1 | I001 |
| WS-05 | indent_width | Re-indent 2 ↔ 4 ↔ 8 spaces | — | ● | 1 | I003 |
| WS-06 | line_wrap | One statement broken across lines (args, fluent chains, long conditions, array literals) | `foo($a, $b, $c)` → one arg per line | ●● | 1 | I004 |
| WS-07 | line_join | Formerly-wrapped statement collapsed onto one long line | — | ●● | 1 | I004 |
| WS-08 | tabs | Indentation converted to tabs (and mixed tab+space) | spaces → `\t` | ● | 1 | I002 |
| WS-09 | trailing_space | Trailing whitespace after statements | — | ● | 1 | I001 |
| WS-10 | newline_style | CRLF line endings; missing/extra final newline | LF ↔ CRLF | ● | 1 | I006 |
| WS-11 | brace_style | K&R ↔ Allman brace placement | `) {` ↔ `)\n{` | ● | 1 | I001 |
| WS-12 | alignment_padding | Column-aligned `=` / `=>` padding | `$a = 1` ↔ `$a   = 1` | ● | 1 | I001 |

### CM — Comments (token stream unchanged after comment stripping ⇒ Type-1)

| Code | Name | What changes | Example | Impact | Ch# | I## |
|---|---|---|---|---|---|---|
| CM-01 | line_comment_add | `//` comments inserted between clone statements | — | ● | 2 | I007 |
| CM-02 | block_comment_add | `/* … */` blocks inserted (incl. multi-line) | — | ● | 2 | I009 |
| CM-03 | docblock | Docblock added/removed/altered on the cloned symbol | `/** */` present ↔ absent | ● | 2 | I010 |
| CM-04 | inline_trailing | Trailing `// note` on clone lines | — | ● | 2 | I009 |
| CM-05 | comment_remove | One member stripped of comments the others keep | — | ● | 2 | I008 |
| CM-06 | comment_text_change | Same positions, different comment text | — | ● | 2 | I007 |
| CM-07 | commented_out_code | Dead code as comments inside the clone (looks like tokens!) | `// $x = old();` | ●● | 2, 23 | I011 |
| CM-08 | mid_statement_comment | `/* … */` embedded inside a wrapped statement | — | ●● | 2 | I009 |
| CM-09 | license_header | Large header banner shifting all line numbers | — | ● | 2 | I008 |
| CM-10 | annotation_docblock | `@param`/`@var`/`@phpstan-*` annotation noise | — | ● | 25 | I010 |

### RN / LT / TY / NS — Renames, Literals, Types, Namespaces (⇒ Type-2)

| Code | Name | What changes | Example | Impact | Ch# | I## |
|---|---|---|---|---|---|---|
| RN-01 | local_vars | Local variable names | `$total` → `$sum`; `$price` → `$cost` | ●● | 3 | I012 |
| RN-02 | params | Parameter names | `function foo($a,$b)` → `foo($x,$y)` | ●● | 3 | I012 |
| RN-03 | functions | Function/method names (incl. call sites within clone) | `calculateTotal()` → `computeTotal()` | ●● | 3 | I013 |
| RN-04 | classes | Class/interface names + property names | `UserParser` → `CustomerParser` | ●● | 3 | I014 |
| RN-05 | case_convention | snake_case ↔ camelCase for same identifiers | `max_size` ↔ `maxSize` | ●● | 46 | I012 |
| LT-01 | numeric | Numeric constants differ (thresholds, rates) | `0.07` → `0.08`; `> 100` → `> 200` | ●● | 4 | I017 |
| LT-02 | string | String literals differ (messages, keys) | `'active'` → `'enabled'` | ●● | 4, 47 | I016 |
| LT-03 | array_values | Array literal contents differ, structure same | `['a','b']` → `['x','y']` | ●● | 4 | I016 |
| LT-04 | const_vs_literal | Named constant in one member, inline literal in another | `MAX` ↔ `100` | ●● | 4 | I017 |
| TY-01 | type_hints | Param/return type hints added/removed/changed | `int $x` ↔ `$x` | ●● | 5 | I012 |
| TY-02 | nullable | `?T` vs docblock-only nullability | `?int` ↔ `int` + `@param int|null` | ●● | 5, 53 | I012 |
| NS-01 | imports | `use` import vs fully-qualified name vs alias | `use A\B;` ↔ `\A\B` | ●● | 26 | I015 |
| NS-02 | namespace_depth | Different namespace hierarchies for same code | `Acme\Foo` → `Acme\Bar` | ●● | 26 | I015 |

### ST — Statement-level Edits (⇒ Type-3)

| Code | Name | What changes | Example | Impact | Ch# | I## |
|---|---|---|---|---|---|---|
| ST-01 | insert_logging | Logging/metrics lines interleaved into the clone | `Logger::info(...)` added | ●●● | 24 | I020 |
| ST-02 | insert_dead | Unused assignments/dead branches inserted | 2–3 unused lines | ●●● | 23 | I019 |
| ST-03 | insert_functional | A genuinely new small step added in one member | extra `$x = trim($x);` | ●●● | 22, 40 | I019 |
| ST-04 | delete_stmt | One member missing 1–3 statements | — | ●●● | 22 | I019 |
| ST-05 | reorder_independent | Independent statements swapped | — | ●●● | 7 | I019 |
| ST-06 | gap_split | Clone interrupted mid-region by a foreign block ("gapped clone") | — | ●●● | 35 | I019 |
| ST-07 | partial_fragment | Only a fragment of the region is shared; heads/tails differ | — | ●●● | 35 | I019 |
| ST-08 | param_reorder | Same logic, function parameters reordered (call sites updated) | `f($a,$b)` → `f($b,$a)` | ●●● | 6 | I019 |
| ST-09 | expr_tweak | Small expression edits (`>=` vs `>`, `+1` offsets) | `$i > 0` → `$i >= 1` | ●●● | 22 | I019 |

### CF / BL / EX / NU — Control-flow & Boolean Rewrites (⇒ Type-3/4 border)

| Code | Name | What changes | Example | Impact | Ch# | I## |
|---|---|---|---|---|---|---|
| CF-01 | if_ternary | `if/else` ↔ ternary | `if($x){return $a;}` ↔ `$x?$a:$b` | ●●●● | 9 | I026 |
| CF-02 | match_switch | `switch` ↔ `match` ↔ if-chain | — | ●●●● | 9, 17 | I026 |
| CF-03 | guard_vs_nested | Early-return guards ↔ nested blocks | — | ●●●● | 15 | I028 |
| CF-04 | loop_form | `for` ↔ `foreach` ↔ `while` ↔ `do-while` | `for(...)` ↔ `foreach(...)` | ●●●● | 10 | I025 |
| CF-05 | early_return | Result variable + single return ↔ multiple returns | — | ●●●● | 15 | I028 |
| BL-01 | demorgan | De Morgan / negation inversion | `!($a && $b)` ↔ `!$a \|\| !$b` | ●●●● | 14 | I030 |
| BL-02 | split_combined | Nested single-condition ifs ↔ one compound condition | — | ●●●● | 28 | I030 |
| BL-03 | commutative | Operand order swapped in `&&`/`\|\|`/`+`/`*`/comparisons | `$a && $b` ↔ `$b && $a` | ●●●● | 39 | I030 |
| EX-01 | arith_rewrite | Algebraically equivalent expressions | `($a+$b)>10` ↔ `$a>10-$b` | ●●●● | 8, 38 | I029 |
| NU-01 | null_ops | `isset()` ↔ `??` ↔ `?->` ↔ explicit if-null | `isset($x['a'])?$x['a']:null` ↔ `$x['a']??null`; `$u?->name` ↔ `$u?$u->name:null` | ●●●● | 53 | I031 |

### API — Library/Idiom Substitution (⇒ Type-4)

| Code | Name | What changes | Example | Impact | Ch# | I## |
|---|---|---|---|---|---|---|
| API-01 | map_vs_loop | `array_map`/`array_filter`/`array_reduce` ↔ foreach | — | ●●●● | 18 | I027 |
| API-02 | string_fns | `sprintf` ↔ concatenation ↔ interpolation; `str_*` alternates | `$a.$b.$c` ↔ `implode('',[$a,$b,$c])` | ●●●● | 12 | I031 |
| API-03 | regex_vs_string | `preg_*` ↔ `explode`/`substr`/`str_contains` | `strpos($s,$x)!==false` ↔ `str_contains($s,$x)` | ●●●● | 54 | I031 |
| API-04 | recursion_iteration | Recursive ↔ iterative same algorithm | — | ●●●● | 31 | I032 |
| API-05 | table_driven | Lookup-array ↔ if/switch chain | `in_array($x,['a','b'])` ↔ `switch($x)` | ●●●● | 32 | I027 |
| API-06 | builtin_vs_manual | `array_sum`/`max`/`usort` ↔ hand-rolled loop | — | ●●●● | 12 | I027 |
| API-07 | data_structure | Assoc array ↔ typed object/DTO for same data | — | ●●●● | 13 | I031 |
| API-08 | serialization | `json_encode` ↔ manual array building ↔ `serialize` | — | ●●●● | 45 | I031 |
| API-09 | datetime | `strtotime`/`date` ↔ `DateTimeImmutable` | — | ●●●● | 12 | I031 |

### SEM — Semantic / Architectural (⇒ Type-4 / domain)

| Code | Name | What changes | Impact | Ch# | I## |
|---|---|---|---|---|---|
| SEM-01 | rule_reexpression | Same business rule, unrelated syntax (`age>=18` ↔ `!isMinor()`) | ●●●● | 37, 60 | I029 |
| SEM-02 | inline_vs_extracted | Logic inline in one member, helper-extracted in another | ●●●● | 11 | I029 |
| SEM-03 | oo_split | Behavior distributed across methods/classes in one member | ●●●● | 42 | I029 |
| SEM-04 | error_style | Exceptions ↔ null returns ↔ error codes/result objects | ●●●● | 16, 44 | I029 |
| SEM-05 | di_variants | Constructor injection ↔ service locator ↔ global/static | ●●●● | 49 | I029 |
| SEM-06 | event_indirection | Direct call ↔ event dispatch + listener | ●●●● | 52 | I029 |
| SEM-07 | orm_vs_sql | ORM/query-builder ↔ raw SQL for same operation | ●●●● | 51 | I031 |
| SEM-08 | cross_file_scatter | One member's logic spread over several files of the set | ●●●● | 58 | I029 |
| SEM-09 | state_machine | Explicit state field ↔ branching encodes same transitions | ●●●● | 33 | I029 |
| SEM-10 | config_driven | Behavior in code ↔ same behavior driven by config array | ●●●● | 21 | I029 |
| SEM-11 | normalization_order | trim/lower/sanitize pipeline in different (equivalent) order | ●●●● | 27, 29 | I029 |

### NZ — Noise Insertions (interference wrappers, usually combined)

| Code | Name | What changes | Impact | Ch# | I## |
|---|---|---|---|---|---|
| NZ-01 | logging | Logger calls sprinkled through | ●●● | 24 | I020 |
| NZ-02 | metrics | Counters/timers instrumentation (`Metrics::increment()`) | ●●● | 24 | I021 |
| NZ-03 | security | Escaping/sanitization/authz checks inserted | ●●● | 48 | I022 |
| NZ-04 | framework | Attributes `#[Route]`, framework hooks, boilerplate ceremony | ●●● | 25 | I022 |
| NZ-05 | i18n | Literal strings wrapped in translation calls | ●●● | 47 | I016 |
| NZ-06 | feature_flag | Clone body wrapped/split by feature-flag branches | ●●● | 41 | I024 |
| NZ-07 | debug | `var_dump()`/`error_log()`/conditional `if (DEBUG){…}` blocks | ●●● | 24 | I023 |
| NZ-08 | env_checks | `getenv()` / environment-conditional branches scattered in | ●●● | 24 | I024 |
| NZ-09 | timing | `$start = microtime(true)` timing calls added | ●●● | 24 | I021 |
| NZ-10 | request_context | Request-ID / correlation-ID propagation threaded through | ●●● | 24 | I024 |

*(NZ-07…NZ-10 fold in the earlier plan's noise-injection variants — debug statements,
environment checks, timing calls, request-ID propagation — that the registry didn't yet
name explicitly.)*

### ENC — Encoding / Lexical Oddities

| Code | Name | What changes | Ch# |
|---|---|---|---|
| ENC-01 | bom | UTF-8 BOM on some members | — |
| ENC-02 | nbsp | Non-breaking / unicode spaces in indentation (valid PHP) | — |
| ENC-03 | unicode_ident | Multibyte identifiers in one member | — |
| ENC-04 | mixed_eol | CRLF and LF mixed within one file | — |
| ENC-05 | string_escapes | `"\n"` vs actual newline in strings; single vs double quotes | — |
| ENC-06 | heredoc | Heredoc/Nowdoc vs quoted string for same content | — |

### CP — Corpus/Topology Properties (set-level, mostly L10)

| Code | Name | What it stresses | Ch# |
|---|---|---|---|
| CP-01 | same_file | ≥2 cluster members inside one file (intra-file clone) | — |
| CP-02 | overlap | Two clusters whose regions overlap/nest | — |
| CP-03 | below_threshold | Clone deliberately under common min-tokens/min-lines defaults — ground truth marks it `detection_expectation` all-false (a "known-invisible" clone) | 35 |
| CP-04 | threshold_boundary | Clone exactly at 5-line / 50-token style boundaries | 35 |
| CP-05 | large_file | Clone buried in a 1000–2000-line file | — |
| CP-06 | many_files | Cluster members spread over 6–8 files | 58 |
| CP-07 | genealogy | Drifted copy chain A→B→C, each hop adds edits (B closer to A than C) | 55 |
| CP-08 | html_mix | Clones inside mixed HTML/PHP template files | 34 |
| CP-09 | generated | Compiled/generated code style (Smarty-like) carrying clones | 20 |
| CP-10 | near_miss_only | Set whose *only* interesting content is FP bait | — |

**Deliberately out of scope** (noted for completeness, may become L11+ later):
cross-language duplication (Ch#36 — repo is PHP-only), async/sync duplication (Ch#30 — thin
in PHP; a fibers/amphp family could be added later), platform-conditional code (Ch#56),
macro/metaprogramming expansion beyond CP-09.

### 8.1 Calibration — known detector defaults (targets for the threshold families)

The `adv_below_threshold` (CP-03) and `adv_boundary` (CP-04) families, and the `ex_size_ladder`
sub-threshold rung, must be built against **real tool defaults** so they land just under / astride
the line where common detectors go silent. These defaults are the calibration targets (verify
against the installed versions during Foundation; record actual versions in the reports, §13.4):

| Tool | Default min clone size | Notes for corpus calibration |
|---|---|---|
| **phpcpd** | ~5 lines / ~70 tokens (`--min-lines=5`, `--min-tokens=70`) | Token-based; CP-04 clones should straddle exactly 70 tokens / 5 lines |
| **jscpd** | ~5 lines / ~50 tokens (`--min-lines`, `--min-tokens`) | Format-aware; `--mode` affects normalization |
| **PMD CPD** | ~100 tokens (`--minimum-tokens`) | Highest default floor — a clone 70–99 tokens is invisible to CPD but seen by phpcpd |
| **Simian** | ~6 lines (`-threshold`) | Line-based; sensitive to layout unless `-ignoreX` flags set |
| **phpdup** (repo tool) | per `bin/phpdup` config | Confirm at Foundation; treat as first-class in the matrix |

**Consequence for ground truth:** a CP-03 set with a 60-token clone should carry
`detection_expectation` all-false *for token tools at default settings*, with a `notes` line
recording the exact size and which tools' floors it sits under. This turns "tool X found nothing"
into the precise, documented statement "clone is 60 tokens; below PMD-CPD's 100 and phpcpd's 70
floors by design" — a capability fact (R8), not a failure. Builders MUST cite the numeric size in
`expected.json.notes`; reviewers MUST confirm the size actually sits where the notes claim.

---

## 9. Level Progression (L0 – L10)

Per R2, **every family below gets at least 5 sets**. Within a family the 5+ sets vary along
the family's own dial (intensity, position, granularity, seed domain) so no two sets are
trivially isomorphic. Difficulty inside each level also ramps: set 001 is the gentlest
expression of the family, set 005 the harshest.

Every non-L0 level uses seeds of at least 3 different domains and at least 2 size classes
(§10), so results never hinge on one payload.

> **Level-scheme note.** This L0–L10 scheme is the principled clone-type progression from the
> `testsets/` plan (Type-1 → Type-2 → Type-3 → control-flow → API → semantic → mixed →
> adversarial). The earlier plan's separate "literal changes", "noise injection", and
> "structural" levels are fully absorbed: literals → L4 `lt_*`; structural (loop/control
> forms) → L6 `cf_*` + L7 `api_map_loop`; noise → L5 `st_insert_*` + the NZ-* codes stacked
> in L9 `mix_noise_heavy`. A full old→new mapping is in
> [Appendix D](#appendix-d-level-scheme-reconciliation).

---

### L0 — `L00_no_duplication` — Negative Controls

No duplication anywhere. A perfect tool reports **zero** clusters in every set. Measures the
false-positive floor. All sets: `clusters: []`, roles `distractor`/`clean` only.

| Family | Contents / trap being laid | Example file mix | Sets |
|---|---|---|---|
| `nd_distinct_domains` | 5 utterly unrelated files | UserService, OrderProcessor, PaymentGateway, InventoryCheck, EmailSender | 5 |
| `nd_same_domain` | All files in one fictional domain (billing) with shared vocabulary but disjoint logic — bait for semantic/embedding detectors | AuthService, BillingService, NotificationService, ReportingService, AuditService | 5 |
| `nd_boilerplate` | Getter/setter/constructor/DTO ceremony that naturally repeats (the "acceptable idiom" floor) | 5 DTOs with different fields | 5 |
| `nd_structural_echo` | Same skeleton (class, 3 public methods, ctor injection) with genuinely different bodies — bait for structure-only metrics | 5 services, same shape | 5 |
| `nd_shared_vocab` | Same identifier names reused (`process`, `$total`, `Normalizer`) across files with different logic — bait for identifier-weighted matchers | 5 processors | 5 |
| `nd_size_spread` | Mix of 15-line and 300-line files, no dupes — exercises windowing/chunking FPs | CSVParser, JSONHandler, XMLProcessor, ConfigLoader, MarkdownRenderer | 5 |

**30 sets.** Detector requirement: restraint. Each file: 40–80 lines of genuinely unique,
non-duplicated PHP.

---

### L1 — `L01_exact` — Exact Duplication, Identical Formatting (Type-1)

The cloned region is **byte-for-byte identical** in all 3 carriers (same spacing, same
comments, same everything; only surrounding scaffold + class/function names differ enough to
compile). Any detector must get L1 perfect; it calibrates the harness itself.

| Family | Dial across the ≥5 sets | Sets |
|---|---|---|
| `ex_function` | A standalone function duplicated; dial = seed domain + length | 5 |
| `ex_method` | A method inside differing classes; dial = class-context divergence | 5 |
| `ex_block` | A statement-run inside larger differing functions (no shared signature); dial = block length 5→30 lines | 5 |
| `ex_full_file` | Entire file identical except file name; dial = file size | 5 |
| `ex_position` | Same clone at top/middle/bottom/interleaved positions across carriers | 5 |
| `ex_size_ladder` | Clone sizes step 3 → 6 → 12 → 25 → 50 lines (the 3-liner documents the below-default-threshold floor) | 5 |
| `ex_multi_cluster` | 2–3 independent exact clusters per set; dial = cluster count and proximity | 5 |

**35 sets.** `requires`: nothing beyond exact matching. `detection_expectation`: all classes
true (except the deliberate sub-threshold rung of `ex_size_ladder`).

---

### L2 — `L02_whitespace` — Whitespace & Layout Only (Type-1)

Token stream identical to L1; only layout differs. One family per whitespace phenomenon
("spacing before… after… in the middle of… a command broken across several lines… a tab
char…").

| Family | Interference | Dial across sets | Example | Sets |
|---|---|---|---|---|
| `ws_blank_before` | WS-01 | 1 → 8 blank lines; one vs all carriers | — | 5 |
| `ws_blank_after` | WS-02 | ditto, after region | — | 5 |
| `ws_blank_inside` | WS-03 | blanks between 1 → every statement | — | 5 |
| `ws_operator_spacing` | WS-04 | `a+b` ↔ `a + b`, comma spacing, paren padding | `$arr=['a','b']` ↔ `$arr = [ 'a' , 'b' ]` | 5 |
| `ws_indent_width` | WS-05 | 2 vs 4 vs 8 spaces; whole-file vs region-only | — | 5 |
| `ws_line_wrap` | WS-06 | wrap style: args / fluent chains / long conditions / arrays | long call → one arg per line | 5 |
| `ws_line_join` | WS-07 | join 2 lines → aggressively to 120-col lines | — | 5 |
| `ws_tabs` | WS-08 | all-tabs, tabs-then-spaces, alternating (mixed) | — | 5 |
| `ws_trailing` | WS-09 | trailing spaces on some → all lines | — | 5 |
| `ws_newline` | WS-10 | CRLF one member; CRLF all; missing final newline | — | 5 |
| `ws_brace_style` | WS-11 | K&R ↔ Allman on functions/loops/conditionals | — | 5 |
| `ws_alignment` | WS-12 | column-aligned assignments/arrays vs plain | — | 5 |
| `ws_combined` | 2–3 WS-* together (still whitespace-only) — the level's mini-boss | — | 5 |

**65 sets.** `requires: [whitespace_normalization]`, `detection_expectation.token_based:
true` (every token tool should pass; raw-text matchers fail — that gap is the measurement).

---

### L3 — `L03_comments` — Comment Variation (Type-1)

Layout held identical to L1; only comments differ.

| Family | Interference | Dial | Sets |
|---|---|---|---|
| `cm_line_added` | CM-01 | 1 comment → comment-per-statement | 5 |
| `cm_block_added` | CM-02 | small block → 15-line block mid-clone | 5 |
| `cm_docblock` | CM-03 | none ↔ minimal ↔ full docblock on cloned symbol | 5 |
| `cm_inline_trailing` | CM-04 | trailing notes on some → all lines | 5 |
| `cm_removed` | CM-05 | pristine member keeps comments; others stripped | 5 |
| `cm_text_changed` | CM-06 | same comment slots, different prose | 5 |
| `cm_commented_code` | CM-07 | commented-out code inside clone (near-tokens as bait) | 5 |
| `cm_mid_statement` | CM-08 | `/* … */` inside wrapped call args | 5 |
| `cm_license_header` | CM-09 | 5 → 40-line banners shifting line offsets | 5 |
| `cm_annotations` | CM-10 | `@param`/`@phpstan`/`@psalm` noise densities | 5 |
| `cm_combined` | 2–3 CM-* + one WS-* | first cross-axis mix, gentle | 5 |

**55 sets.** `requires: [comment_stripping]` (+ whitespace for `cm_combined`).

---

### L4 — `L04_rename_literals` — Type-2: Renames, Literals, Types, Namespaces

Structure and layout identical; identifiers/literals/types differ.

| Family | Interference | Dial | Example | Sets |
|---|---|---|---|---|
| `rn_locals` | RN-01 | 1 var renamed → all locals renamed | `$total`→`$sum` | 5 |
| `rn_params` | RN-02 | param renames incl. consistent body usage | `foo($a,$b)`→`foo($x,$y)` | 5 |
| `rn_functions` | RN-03 | cloned symbol + intra-clone call sites renamed | `calculateTotal()`→`computeTotal()` | 5 |
| `rn_classes` | RN-04 | class/property renames; `new X` sites | `UserParser`→`CustomerParser` | 5 |
| `rn_case_style` | RN-05 | snake ↔ camel ↔ SCREAMING for same names | `MAX_SIZE`→`LIMIT_SIZE` | 5 |
| `lt_numbers` | LT-01 | one threshold → every numeric literal differs | `0.07`→`0.08` | 5 |
| `lt_strings` | LT-02 | message text → keys → format strings differ | `'active'`→`'enabled'` | 5 |
| `lt_arrays` | LT-03 | array contents differ, shape identical | `['a','b']`→`['x','y']` | 5 |
| `lt_const_indirection` | LT-04 | literal ↔ class const ↔ global const | `100`↔`MAX_SIZE` | 5 |
| `ty_hints` | TY-01/TY-02 | hints removed ↔ added ↔ widened; nullable styles | `int $x`↔`$x` | 5 |
| `ns_imports` | NS-01/NS-02 | FQCN ↔ use ↔ alias; namespace depth | `Acme\Foo`→`Acme\Bar` | 5 |
| `rn_combined` | RN-* + LT-* together | full Type-2: everything renamed and re-literaled | — | 5 |

**60 sets.** `requires: [identifier_canonicalization, literal_abstraction]`.

---

### L5 — `L05_statement_edits` — Type-3: Insertions, Deletions, Reordering

The copies have drifted: statements added/removed/moved. Ground truth records per-member
regions of differing length; `notes` documents the edit.

| Family | Interference | Dial | Sets |
|---|---|---|---|
| `st_insert_logging` | ST-01 / NZ-01 | 1 log line → log-per-step | 5 |
| `st_insert_dead` | ST-02 | one dead assignment → dead branch blocks | 5 |
| `st_insert_step` | ST-03 | genuinely new functional step in 1 member | 5 |
| `st_delete` | ST-04 | 1 → 3 statements missing from one member | 5 |
| `st_reorder` | ST-05 | 1 swap → shuffled independent block | 5 |
| `st_gap_split` | ST-06 | clone split by 3 → 25-line foreign block (gapped clone; ground truth = one cluster, members carry `fragments` list) | 5 |
| `st_partial` | ST-07 | shared fragment shrinks 90% → 40% of region | 5 |
| `st_param_reorder` | ST-08 | 2 params swapped → full signature scramble | 5 |
| `st_expr_tweak` | ST-09 | `>=`↔`>`, off-by-one, +ε changes | 5 |
| `st_combined` | multiple ST-* | drift resembling real maintenance divergence | 5 |

**50 sets.** `requires: [ast_canonicalization]` (+ gap tolerance for `st_gap_split` — the
classic Type-3 "gapped clone" detectors advertise against). For `st_partial`, ground truth
pins the *shared* sub-region only, with the scoring tolerance widened and documented in
`scoring`.

---

### L6 — `L06_controlflow_rewrites` — Control-flow & Expression Rewrites

Same computation, different control shape. Variant bodies are hand-written per seed (§10
variants), verified behaviorally equivalent (§12).

| Family | Interference | Example | Sets |
|---|---|---|---|
| `cf_if_ternary` | CF-01 | `if($x){return $a;}` ↔ `$x?$a:$b` | 5 |
| `cf_match_switch` | CF-02 | `switch` ↔ `match` ↔ if-chain | 5 |
| `cf_guard_nested` | CF-03 | early-return guard ↔ nested `if` | 5 |
| `cf_loop_forms` | CF-04 | `for` ↔ `foreach` ↔ `while` | 5 |
| `cf_early_return` | CF-05 | multiple returns ↔ single result var | 5 |
| `bl_demorgan` | BL-01 | `!($a && $b)` ↔ `!$a \|\| !$b` | 5 |
| `bl_split_combined` | BL-02 | nested ifs ↔ compound condition | 5 |
| `bl_commutative` | BL-03 | `$a && $b` ↔ `$b && $a` | 5 |
| `ex_arith` | EX-01 | `($a+$b)>10` ↔ `$a>10-$b` | 5 |
| `nu_null_styles` | NU-01 | `isset()?:` ↔ `??` ↔ `?->` | 5 |

**50 sets.** `requires: [controlflow_normalization, commutative_reordering]`;
`detection_expectation.token_based: false` from here on (documented per cluster) — missing
these is expected for phpcpd-class tools, and the matrix should show that honestly rather
than punishing them silently.

---

### L7 — `L07_api_idioms` — API & Idiom Substitution (Type-4)

Same outcome via different library usage. Hand-written variants, behavior-verified.

| Family | Interference | Example | Sets |
|---|---|---|---|
| `api_map_loop` | API-01 | `array_map(...)` ↔ `foreach` | 5 |
| `api_strings` | API-02 | `sprintf` ↔ concat ↔ interpolation | 5 |
| `api_regex_string` | API-03 | `preg_match` ↔ `str_contains`/`strpos` | 5 |
| `api_recursion` | API-04 | recursive ↔ iterative | 5 |
| `api_table_driven` | API-05 | lookup array ↔ if/switch chain | 5 |
| `api_builtins` | API-06 | `array_sum`/`usort` ↔ hand loop | 5 |
| `api_data_shape` | API-07 | assoc array ↔ typed DTO | 5 |
| `api_serialization` | API-08 | `json_encode` ↔ manual build | 5 |
| `api_datetime` | API-09 | `strtotime`/`date` ↔ `DateTimeImmutable` | 5 |

**45 sets.** `requires: [api_abstraction, ast_canonicalization]`.

---

### L8 — `L08_semantic` — Semantic / Architectural Duplication (Type-4 / domain)

The "same business rule everywhere" tier. Only semantic-class detectors are *expected* to
score here.

| Family | Interference | Sets |
|---|---|---|
| `sem_rule` | SEM-01 (`age>=18` ↔ `birthDate<=-18y` ↔ `!isMinor()`) | 5 |
| `sem_inline_extract` | SEM-02 | 5 |
| `sem_oo_split` | SEM-03 (one member spread across 2 classes in its file) | 5 |
| `sem_error_style` | SEM-04 | 5 |
| `sem_di_style` | SEM-05 | 5 |
| `sem_event` | SEM-06 | 5 |
| `sem_orm_sql` | SEM-07 | 5 |
| `sem_scatter` | SEM-08 (member logic spans 2 files; cluster members list multiple fragments) | 5 |
| `sem_state_machine` | SEM-09 | 5 |
| `sem_config_driven` | SEM-10 | 5 |
| `sem_normalization_order` | SEM-11 | 5 |

**55 sets.** `requires: [semantic_reasoning]` (variously + `cfg_comparison`,
`inline_expansion`). Ground-truth `notes` must explain *why* the members are equivalent —
these double as an **LLM-judge evaluation corpus**.

---

### L9 — `L09_mixed` — Compound Interference

Everything above, combined, with escalating stack depth. Recipes pick combinations
deterministically; `set.json.interference[]` lists every applied code.

| Family | Mix | Sets |
|---|---|---|
| `mix_ws_cm` | WS-* + CM-* (still Type-1 after normalize) | 5 |
| `mix_rn_ws` | RN/LT + WS (Type-2 + layout) | 5 |
| `mix_rn_cm_ws` | 3 axes (full "reformatted and renamed copy-paste") | 5 |
| `mix_st_rn` | Type-3 edits + renames | 5 |
| `mix_cf_rn_ws` | rewrite + rename + layout | 5 |
| `mix_noise_heavy` | NZ-01..10 stacked onto an exact clone (logging+metrics+security+i18n+flags+debug+timing) | 5 |
| `mix_4plus` | ≥4 axes; set 005 = everything except semantics | 5 |
| `mix_realistic_drift` | curated "18 months of maintenance" narrative per set: member B got a bugfix + logging; member C got reformatted + renamed + a feature | 5 |

**40 sets.** Difficulty scores 60–90; `requires` = union of stacked axes.

---

### L10 — `L10_adversarial` — Edge Cases, Traps, Topology Stress

| Family | Interference | What it proves | Sets |
|---|---|---|---|
| `adv_near_miss` | CP-10 | High token overlap, different semantics: FP bait *only* (`clusters: []`, L0-style but maximally tempting) | 5 |
| `adv_below_threshold` | CP-03 | Real duplicates too small for default thresholds; `detection_expectation` all-false documents the blind spot | 5 |
| `adv_boundary` | CP-04 | Clones straddling exact 5-line/50-token boundaries; measures off-by-one region reporting | 5 |
| `adv_overlap` | CP-02 | Nested + overlapping clusters (a big clone containing a smaller shared block that also appears elsewhere) | 5 |
| `adv_same_file` | CP-01 | Intra-file members + cross-file members in one cluster | 5 |
| `adv_html_mix` | CP-08 + WS/CM | Clones spanning HTML/PHP template code, heredocs | 5 |
| `adv_generated` | CP-09 | Compiled-template-style code (Smarty-like, §14) with planted clones amid mechanical boilerplate | 5 |
| `adv_encoding` | ENC-01..06 | BOM, NBSP indentation, unicode identifiers, mixed EOL, heredoc/quote equivalence | 5 |
| `adv_large_files` | CP-05 | 1000–2000-line carriers, clone buried mid-file; also stresses tool runtime | 5 |
| `adv_many_files` | CP-06 | 6–8 file sets, cluster spread over 5 members + 3 distractors | 5 |
| `adv_genealogy` | CP-07 | Drift chains A→B→C→(D): pairwise similarity decays along the chain; ground truth models one cluster with per-member `drift_generation` | 5 |

**55 sets.** This level is also where scoring subtleties live, so `scoring` overrides (wider
tolerances, fragment matching) are per-set explicit.

---

## 10. Seed Payload Library & Code Templates

Seeds are the realistic code regions that get cloned. Stored under `gen/seeds/<name>/`.

- **~30 seeds** at launch, mined/adapted from the strongest existing `samples/` bodies
  (already realistic, PHP 8.1, domain-flavored) plus new ones. Target domains: billing/
  invoice math, CSV/import parsing, HTTP client wrappers, validation, caching, session/
  state, notification dispatch, report building, inventory, geo/shipping rates, password/
  token handling (sanitized), templating/render helpers, queue/retry logic.
- **Size classes:** S (3–10 lines), M (15–40), L (60–120). Every level ≥2 classes;
  `ex_size_ladder` and `adv_below_threshold` use S deliberately.
- `payload.php` marks the clonable region with sentinel comments the generator strips:

  ```php
  // <<<PAYLOAD:invoice_totals>>>
  …region…
  // <<<END-PAYLOAD>>>
  ```

- `seed.json` records domain, size class, token/line counts, required symbols, and which
  interference codes are compatible (e.g. a seed with no boolean logic can't serve BL-01).
- `variants/` holds the **hand-written rewrite bodies for L6–L8** (e.g. `cf_guard_nested.php`,
  `api_map_loop.php`, `sem_rule_b.php`, `sem_rule_c.php`), each tagged with the interference
  code it realizes. Behavioral equivalence of every variant vs the pristine payload is
  enforced by tests (§12.4).
- **Scaffolds** (`gen/scaffolds/`) are host-file templates — class shells, service files,
  procedural scripts — with an `<<<INSERT>>>` marker and their own unique filler code.
  Carriers = scaffold × payload(±transform). **Distractor library** (`gen/distractors/`)
  contains near-miss twins (hand-tuned to ~60–80% token overlap while semantically
  different) and clean fillers per domain.

### Code Template Guidelines

Every generated PHP file (payload, scaffold, distractor, clean) must:

1. Start with `<?php` and `declare(strict_types=1);`.
2. Have a namespace relevant to the set's fictional domain (never leaking level/role).
3. Contain 40–150 lines of meaningful PHP (except where a family dictates otherwise).
4. Include appropriate `use` statements.
5. Have no syntax errors — pass `php -l`.
6. Be self-contained (no missing dependencies; corpus files have **zero** Composer deps).

**Example payload file:**

```php
<?php
declare(strict_types=1);

namespace Acme\Billing\Totals;

final class InvoiceTotalsService
{
    // <<<PAYLOAD:invoice_totals>>>
    public function calculateTotals(array $lineItems, float $taxRate): array
    {
        $subtotal = 0.0;
        foreach ($lineItems as $item) {
            $subtotal += $item['qty'] * $item['unitPrice'];
        }
        $tax = round($subtotal * $taxRate, 2);
        $total = $subtotal + $tax;

        return [
            'subtotal' => round($subtotal, 2),
            'tax'      => $tax,
            'total'    => round($total, 2),
        ];
    }
    // <<<END-PAYLOAD>>>
}
```

The generator lifts the region between the sentinels, applies the family's transform chain,
inserts it into each carrier's scaffold at a family-chosen position, and records the exact
resulting `start_line`/`end_line` for `expected.json` — no line number is ever hand-typed.

---

## 11. Generation Strategy

### 11.1 Pipeline

```
recipe (family JSON, lists 5+ set specs)
  → for each set spec:
      pick seed + scaffolds + distractors (explicit in recipe; rng_seed for tie-breaks)
      for each carrier: apply transform chain (interference codes + params) to payload
      render carrier files; record payload start/end lines during render
      write src/ files, set.json, expected.json (line numbers from the renderer — never hand-typed)
  → gen/manifest.php: rebuild manifest.json + aggregated bench ground truth
```

### 11.2 Transform engine

- One class per interference code in `gen/transforms/`, implementing
  `transform(PayloadAst|PayloadText $in, array $params, Rng $rng): TransformResult`
  where `TransformResult` carries the emitted text **plus a line map**, so the renderer can
  compute exact ground-truth ranges even after wraps/insertions.
- WS/CM/ENC transforms operate on text with a lightweight PHP token stream
  (`token_get_all`) to stay syntax-safe (never split inside a string literal, etc.).
- RN/LT/TY/NS and ST transforms operate on the token stream / nikic-style AST (add
  `nikic/php-parser` as a **dev-only** dependency for `gen/`; corpus files themselves have
  zero dependencies).
- CF/API/SEM "transforms" are *selectors*: they pick the pre-written variant file from the
  seed's `variants/` dir rather than computing a rewrite.
- Determinism: `mt_srand($rng_seed)` per set (matches the existing synthetic-fuzz
  convention); `gen/build.php --check` regenerates everything to a temp dir and diffs
  byte-for-byte against the committed tree (CI gate).

### 11.3 Recipes

One JSON per family (`gen/recipes/L02/ws_line_wrap.json`) listing each set's seed, scaffold
ids, distractor ids, transform params, and rng_seed. Recipes are the reviewable artifact —
adding a 6th set to a family is a small recipe diff, then `gen/build.php`.

### 11.4 What stays hand-written

- Seeds, variants (L6–L8), distractor twins, scaffolds — i.e. all *creative* code.
- Everything positional/mechanical (assembly, spacing, renames, line numbers, JSON) is
  generated. This split is what keeps 540 sets maintainable.

---

## 12. Verification & QA

Run via `php gen/verify.php [--level=N] [--set=ID]`; wired into `run-all.sh` and CI.

1. **Syntax:** `php -l` on every file in every `src/` (including deliberately weird ENC-*
   files — they must still be *valid* PHP).
2. **Schema:** validate every `set.json` / `expected.json` / `manifest.json` against
   `testsets/schema/*.json`; cross-check (files listed exist, roles consistent, every
   cluster member's file has role `carrier`, interference codes exist in registry, set_id
   matches path).
3. **Positive ground-truth proof (L1–L5, L9-mechanical):** for each cluster, extract member
   regions, run the *declared* normalization pipeline (`normalized_by`: whitespace →
   comments → identifiers → literals → …), and assert the normalized token streams are equal
   (L1–L4) or within the declared edit budget (L5, using token-level diff ≤ params). A
   failing assertion means the generator or recipe is wrong — the ground truth is *proven*,
   not asserted.
4. **Behavioral-equivalence proof (L6–L8):** every seed used at these levels ships a
   fixture-based equivalence test (`gen/seeds/<name>/equivalence_test.php`): run pristine
   payload and every variant against the same inputs, assert identical outputs/exceptions.
   Guarantees Type-4 clones really are semantically equivalent.
5. **Negative assurance (all levels, crucial for L0):** run a token-normalized
   cross-comparison over every pair of non-cluster regions in each set; assert no accidental
   match ≥ 40 normalized tokens. Additionally run phpcpd + jscpd over L0; any finding is
   triaged — either the set is fixed or (if it's the deliberate boilerplate floor of
   `nd_boilerplate`) the region is added to `non_duplicates` with
   `trap: false, known_tool_fp: true`.
6. **Line-number audit:** re-parse each carrier and assert the ground-truth
   `start_line/end_line` actually bound the payload sentinel-derived region (belt and braces
   on top of the generator's line map).
7. **Determinism:** `gen/build.php --check` byte-diff as in §11.2.
8. **Corpus hygiene:** no role/level/dup hints inside `src/` (grep-based lint: forbid
   `clone`, `dup`, `carrier`, `distractor`, `L0`–`L9` tokens in src file names, namespaces,
   and comments); UTF-8/LF everywhere except declared ENC/WS-10 files.

---

## 13. Bench Integration & Reporting

New runner `bench/run-testsets.php`:

- **Per-set invocation:** each tool runs against `<set>/src/` in isolation (mirrors
  `run-samples.php` mechanics: phpcpd via phar, jscpd via node bin, phpdup via `bin/phpdup`,
  pmd-cpd/simian if present). Per-set = no cross-set clone pollution, and L10 large-file sets
  get honest per-set wall-time numbers.
- **Normalization of tool output → clusters** of `{file, start, end}` (reuse `run.php`
  extractors).
- **Scoring per set** against `expected.json` using the established member-set Jaccard ≥
  `member_jaccard_min` with `line_tolerance` (defaults match `bench/score.php`: 0.6 / ±2).
  Gapped/fragmented members (ST-06, SEM-08) match if the union of reported fragments covers
  ≥ 60% of the member's union.
- **Metrics emitted:**
  - `recall` / `precision` / `F1` per set → aggregated per family, per level, per tool;
  - **expected-recall:** recall computed only over clusters where
    `detection_expectation.<tool-class>` is true — separates "missed what it should find"
    from "missed what it can't find by design";
  - **FP-resistance:** 1 − (reported clusters overlapping `non_duplicates` trap regions ÷
    trap count), reported for L0 and all `trap: true` regions;
  - **boundary accuracy:** mean line-offset of matched members (L10 `adv_boundary`);
  - wall time + RSS per set (existing plumbing).
- **Reports** (`bench/results/`):
  - `testsets-matrix.md` — tools × levels grid of F1 (+ expected-recall in parens) — the
    headline "how far up the ladder does each tool climb";
  - `testsets-by-family.md` — tools × families, the per-normalization capability matrix that
    D5 makes possible ("phpcpd falls over exactly at `rn_locals`");
  - `testsets-by-interference.md` — tools × interference codes (joins set.json interference
    lists with per-set scores);
  - `testsets-<label>.json` — raw run for `score.php`-style reprocessing.
- `gen/manifest.php` also writes the aggregate `.ground-truth.json` (§7.3) so the *existing*
  `bench/run.php --corpus=testsets` whole-tree path works unchanged for speed benchmarking.

### 13.4 Tool Capability Profile — the R8 deliverable

The matrices above are inputs; the headline artifact is a **per-tool narrative report card**,
`bench/results/testsets-capability-<tool>.md`, generated by joining the tool's per-set scores with
each set's `difficulty.requires`, `detection_expectation`, and interference codes. Each profile
states, in plain language backed by the numbers:

1. **Detects** — the clone types and interference axes the tool handles (highest level passed per
   axis; e.g. "Type-1 ✓, Type-2 ✓ up to full rename, Type-3 ✗ beyond single-statement inserts").
2. **Misses (and whether by design)** — axes it fails, each tagged *capability gap* (missed
   something `detection_expectation` said it should find) vs *out of class* (not expected for this
   detector class). This distinction is the core of R8.
3. **Threshold blind spots** — from `ex_size_ladder` + `adv_below_threshold`: the smallest clone
   size the tool still reports, and the documented floor below which it goes silent (§8.1).
4. **False-positive behavior** — from L0 + all `trap:true` distractors: FP-resistance score, plus
   which bait *types* fooled it (shared-vocab? structural-echo? boilerplate idioms?).
5. **Region accuracy** — boundary drift (mean line offset) and gapped/partial/overlap handling
   (from L5 `st_gap_split`/`st_partial` and L10 `adv_overlap`).
6. **Scaling** — wall-time/RSS trend across `adv_large_files` and `adv_many_files`.

A short **"one-paragraph verdict"** heads each profile (e.g. *"A fast token-based matcher: perfect
on Type-1/2, blind to all control-flow and semantic rewrites (as expected for its class), silent
under 70 tokens, and prone to false positives on shared-vocabulary near-misses — trustworthy for
copy-paste hunting, not for refactoring-drift detection."*). **This paragraph is the concrete
realization of the north-star goal.**

### 13.5 Reproducibility of the profile

- **Tool versions captured.** `bench/run-testsets.php` records each tool's version + invocation
  flags into every report (so `phpcpd 6.0.3 --min-tokens=70` is pinned, not assumed). A profile is
  only meaningful against a stated version.
- **Golden baseline written.** Integration *writes* a baseline snapshot to
  `bench/results/baseline/…` (a human commits it later — no agent runs git, §18.1) so a future tool
  upgrade (or a corpus change) can be **diffed**: "phpcpd 7 now passes `st_reorder` — new
  capability" or "corpus edit regressed L2 scoring — bug".
- **Determinism.** Because generation is deterministic (D4) and tolerances are declared per set,
  re-scoring the same tree with the same tool version reproduces the same profile bit-for-bit.

---

## 14. Real-World Extensions

Optional Phase 7+ material — valuable, but everything in §9 stands without it.

1. **Smarty compiled-template corpus** (`/home/my/logs/smarty_templates_c/`, 587 files):
   real generated code with massive mechanical similarity — ideal raw material for
   `adv_generated`. Plan: select ~20 representative templates, **sanitize** (strip real
   hostnames/paths/business strings; rename to neutral domains), re-emit as deterministic
   fixtures inside `L10_adversarial/adv_generated/*/src/`, plant known clones, and hand-build
   ground truth for the planted clones plus `non_duplicates` entries for the boilerplate
   preambles every compiled template shares. The sanitized copies are kept in the repo (the live log
   dir is only a source, never referenced at runtime).
2. **Existing `samples/` back-fill (stretch):** generate `expected.json` files for the
   existing 3-file categories (their duplication is whole-file-ish and coarse), giving the
   old tree ground truth without restructuring it. Low priority; separate effort.
3. **Cross-language and async families (future L11):** explicitly out of scope now (§8).

---

## 15. Volume Summary & Difficulty/Detection Matrix

### Volume by level

| Level | Dir | Clone type | Families | Sets | Files (≈) |
|---|---|---|---|---|---|
| L0 | L00_no_duplication | none | 6 | 30 | 150 |
| L1 | L01_exact | type-1 | 7 | 35 | 175 |
| L2 | L02_whitespace | type-1 | 13 | 65 | 325 |
| L3 | L03_comments | type-1 | 11 | 55 | 275 |
| L4 | L04_rename_literals | type-2 | 12 | 60 | 300 |
| L5 | L05_statement_edits | type-3 | 10 | 50 | 250 |
| L6 | L06_controlflow_rewrites | type-3/4 | 10 | 50 | 250 |
| L7 | L07_api_idioms | type-4 | 9 | 45 | 225 |
| L8 | L08_semantic | type-4/domain | 11 | 55 | 275 |
| L9 | L09_mixed | mixed | 8 | 40 | 210 |
| L10 | L10_adversarial | mixed/edge | 11 | 55 | 320 |
| **Total** | | | **108** | **540** | **~2,755** |

Plus: ~30 seeds × (payload + seed.json + variants + equivalence tests), ~40 scaffolds, ~60
distractor files, ~108 recipes, 3 schemas, 2 generator scripts, 1 bench runner.

Every family ≥5 sets (R2 ✓); every set ≥5 files with exactly 3 carriers when duplication
exists (R1 ✓, with declared exceptions only *upward*: 6–8 files in `adv_many_files`).

### Difficulty band & detection expectation (headline view)

| Level | Name | Band | Expected to detect | Not expected (by design) |
|---|---|---|---|---|
| L0 | No duplication | baseline | *nothing* (0 clusters) | — |
| L1 | Exact clone | trivial | all tools (text, token, AST, metric, semantic) | sub-threshold rung of `ex_size_ladder` |
| L2 | Whitespace | easy | token, AST, metric, semantic | raw-text matchers |
| L3 | Comments | easy | token (w/ comment strip), AST, metric, semantic | raw-text matchers |
| L4 | Renaming (Type-2) | medium | token (parameterized), AST, metric, semantic | plain token matchers |
| L5 | Statement edits (Type-3) | hard | AST (gap-tolerant), metric, semantic | strict token matchers |
| L6 | Control-flow rewrites | very hard | AST-canonicalizing, semantic | token-based tools |
| L7 | API idioms (Type-4) | very hard | semantic / AI | token & most AST tools |
| L8 | Semantic / architectural | expert | semantic / AI (LLM-judge) | everything below semantic |
| L9 | Mixed | expert | depends on stacked axes (per-set) | tools missing any stacked normalization |
| L10 | Adversarial | expert | per-set explicit `detection_expectation` | documented blind spots (sub-threshold, encoding) |

---

## 16. Phased Milestones

This table is the **schedule** for building the whole corpus; the **driver** that executes it to
completion — extend-Foundation → fan-out-in-waves → gate → next phase, until every family is
built — is the completion loop in [§18.10](#1810-driving-to-completion--the-full-corpus-all-levels-all-sets).

Each phase ends with `gen/verify.php` green, a benchmark smoke run, and a **checkpoint report**
(no git commit — version control is the human's, §18.1).

| Phase | Deliverable | Details |
|---|---|---|
| **P0 — Foundations** ✅ **DONE (2026-07-13, verified)** | Schemas + generator engine + verifier + runner + seeds | `testsets/schema/*`, `gen/build.php\|verify.php\|manifest.php` + transform interface + Wave-1 transforms (WS-03, WS-06, CM-03, RN-01, LT-01, ST-01) + CF/API variant-selectors, 3 seed domains w/ equivalence tests, scaffolds/distractors, `bench/run-testsets.php`, docs, and **5 verified pilot sets** (one per pipeline path) proving line-accurate ground truth end-to-end. Reviewed + fixed (see `gen/FOUNDATION_REVIEW.md`). |
| **P1 — Seeds + L0 + L1** | 30 seeds, 40 scaffolds, 60 distractors; 65 sets | Proves negative-assurance QA (§12.5) and exact-clone assembly. Baseline tool run recorded. |
| **P2 — L2 + L3** | 120 sets, all WS-*/CM-* transforms | The "formatting ladder" the request centers on (spacing before/after/inside, wraps, tabs, comments). |
| **P3 — L4 + L5** | 110 sets, RN/LT/TY/NS/ST transforms | AST-level transform work (php-parser); gapped-clone ground-truth modeling. |
| **P4 — L6 + L7** | 95 sets + variant library + equivalence tests | Hand-writing ~2–3 variants × ~20 seeds; behavioral test harness. |
| **P5 — L8** | 55 sets | Semantic variants + rationale notes; the LLM-judge corpus. |
| **P6 — L9** | 40 sets | Transform-chain composition + `mix_realistic_drift` curation. |
| **P7 — L10** | 55 sets + sanitized Smarty fixtures | Topology/edge scoring extensions (fragments, overlap, boundary accuracy). |
| **P8 — Reporting & docs** | Matrices + README + samples.json pointer | `testsets-*.md` reports, `testsets/README.md`, root README section, baseline published numbers, CI wiring (`--check` determinism + verify). |

Rough effort ranking: **P4/P5 dominate** (hand-authored variants); P2/P3 are transform
engineering; P1/P6/P7 are mostly recipe work once the engine exists.

> **Cross-reference to the earlier 5-phase view.** The earlier plan's coarser phasing maps
> cleanly: its Phase 1 (Foundation, sets 1–20) ≈ P0–P1; Phase 2 (Core Coverage) ≈ P2–P3;
> Phase 3 (Advanced) ≈ P3–P4; Phase 4 (Expert) ≈ P5–P7; Phase 5 (Validation & Bench
> Integration) ≈ P8. The P0–P8 breakdown is finer-grained and is the one to execute against.

---

## 17. Defaulted Decisions / Open Questions

Defaults chosen so work can proceed; flag if any should change:

1. **Location:** new `testsets/` tree; existing `samples/` untouched. (Alt considered: a
   flatter `sets/level_N/set_NNN/` layout with `files/` + `answers.json` — rejected in favor
   of the three-tier `level/family/set` + `src/` + `expected.json` layout, which is more
   scalable and prevents tools reading the answers. A second alt, nesting under
   `samples/graduated/`, was rejected to keep `samples.json` semantics stable.)
2. **Answer file name:** `expected.json` (not `answers.json`). Single name across the corpus;
   the earlier plan's `answers.json` fields (hashes, per-file line ranges) are all preserved
   inside it.
3. **Dependency:** `nikic/php-parser` as a dev dependency for `gen/` only. Corpus files
   remain dependency-free.
4. **Exactly-3 carriers** is the standard even at L9/L10 (extra members only in
   `adv_many_files`/`adv_genealogy`, always ≥3).
5. **Set counts:** 5 per family everywhere (the R2 floor). The structure trivially extends to
   10 per family later by appending recipe entries — the generator makes volume cheap;
   curated levels (L6–L8) are the only real cost. (This supersedes the earlier plan's "100+
   sets" target; the combined corpus is ~540 sets and can double without redesign.)
6. **Line-number convention:** 1-based, inclusive, matching `bench/score.php`.
7. **Checked-in generated output:** the rendered `testsets/` tree is kept in the repo (not just
   recipes), because the corpus must be usable without running the generator; the determinism
   check keeps tree ↔ recipes honest. (Agents write the files; a human commits them — §18.1.)
8. **The old `samples/` back-fill** (§14.2) is out of scope for P0–P8.

---

## 18. Execution via Parallel Agents

This section is the **operational playbook**: how the plan is decomposed into small,
independently-executable steps and farmed out to a pool of agents working **concurrently in the
one live working directory**, with a self-correcting **build → review → fix** loop on every unit
of work. It is written to be copied verbatim into `testsets/ORCHESTRATION.md`
([§19](#19-documentation-deliverables)) and used as the standing contract for every agent.

### 18.1 Principles (non-negotiable)

1. **Live directory, in place.** All agents operate directly on
   `/home/sites/php-duplication-samples`. **No git worktrees, no branches, no isolation.**
   (Concretely: spawn subagents in the repo's working directory — do not create a worktree or
   switch branches for them. On Claude Code that means leaving the Agent tool's `isolation` at its
   default/OFF; on **opencode** and other runtimes, subagents already share the working directory,
   so just don't switch branches. Runtime specifics: §18.9.1.) Because everyone shares one tree,
   the file-scope rules in §18.6 are what prevent collisions — they are mandatory, not advisory.
2. **No git, ever.** No agent runs *any* `git` subcommand — not `add`, `commit`, `branch`,
   `worktree`, `checkout`, `switch`, `stash`, `reset`, `rm`, `push`, or `tag`. Version control is
   the human's job, performed later, by hand. An agent that believes it needs git must **stop and
   report**, not proceed.
3. **Scope discipline.** Each agent has an explicit **allow-list** and **deny-list** of paths
   (§18.6). Writing outside the allow-list is a hard failure — the agent stops and reports rather
   than reaching outside its box. Shared substrate (schemas, generator, seeds, transforms) is
   created once in Foundation and is thereafter **read-only** to set-builders.
4. **Every unit is reviewed before it counts as done.** No set is "finished" on the builder's
   say-so; an independent reviewer must return **PASS** (§18.5).
5. **Bounded concurrency.** At most **10 work-chains run at once** (§18.4). The starter scope is
   exactly 10 sets, so the whole starter batch runs in one concurrent wave after the Foundation
   gate.

### 18.2 The three task types (units of work)

| Task type | Granularity | Runs when | Count in starter scope |
|---|---|---|---|
| **Foundation task** | The shared substrate (schemas, generator core, the specific transforms/seeds/scaffolds/distractors the batch needs, verifier, bench runner, doc skeletons) | **First, as a gate** — must finish & pass review before any fan-out | 1 (optionally split into ≤2 sub-agents: "engine" + "seeds/assets") |
| **Set-Builder task** | **Exactly one set** (`src/` files + `set.json` + `expected.json` + its family recipe entry) | In parallel, after the gate | **10** (one per starter set) |
| **Integration task** | Whole-corpus finalize: regenerate `manifest.json` + aggregate ground truth, run whole-tree verify + bench smoke, write reports, finalize top-level docs, repair `samples.json` index | **Last, as a gate** — after all set chains are DONE | 1 |

Each Set-Builder task carries its own **review → fix sub-loop** (§18.5), so "one set" is the
atomic schedulable chain: `build → review → (fix → review)* → done`.

### 18.3 Dependency DAG & the starter batch of 10

```
        ┌─────────────────────── Foundation (gate: build + review) ───────────────────────┐
        │  schemas · gen/ engine · transforms{WS-03,WS-06,CM-03,RN-01,LT-01,ST-01}         │
        │  variant-selectors{CF,API} · ~3 seed domains + CF/API variant bodies             │
        │  scaffolds · distractors · gen/verify.php · bench/run-testsets.php · doc stubs    │
        └───────────────────────────────────┬──────────────────────────────────────────────┘
                                             │ (gate passes)
   ┌──────────┬──────────┬──────────┬────────┴─┬──────────┬──────────┬──────────┬──────────┐
   ▼          ▼          ▼          ▼           ▼          ▼          ▼          ▼          ▼   (≤10 concurrent)
 set 1      set 2      set 3      set 4       set 5      set 6      set 7      set 8   set 9 & 10
 each: Builder → Reviewer → (Fixer → Reviewer)* → DONE
   └──────────┴──────────┴──────────┴──────────┴──────────┴──────────┴──────────┴──────────┘
                                             │ (all 10 DONE)
                                             ▼
                            Integration (gate: manifest + reports + docs + samples.json repair)
```

**The 10 starter sets** — deliberately one per distinct family (so no two builders share a
recipe file) and spread across the ladder to exercise **every generation mechanism** (text
transforms, AST transforms, and hand-written variant-selectors):

| # | Set id | Level | Mechanism exercised |
|---|---|---|---|
| 1 | `L00-nd_distinct_domains-001` | L0 | Negative control (no duplication; distractor+clean only) |
| 2 | `L01-ex_function-001` | L1 | Exact assembly (no transform) |
| 3 | `L02-ws_blank_inside-001` | L2 | Text transform WS-03 (blank lines inside region) |
| 4 | `L02-ws_line_wrap-001` | L2 | Text transform WS-06 (statement broken across lines — request's centerpiece) |
| 5 | `L03-cm_docblock-001` | L3 | Text transform CM-03 (comment/docblock variation) |
| 6 | `L04-rn_locals-001` | L4 | AST transform RN-01 (Type-2 local-variable rename) |
| 7 | `L04-lt_numbers-001` | L4 | AST transform LT-01 (Type-2 numeric-literal change) |
| 8 | `L05-st_insert_logging-001` | L5 | AST transform ST-01 (Type-3 statement insertion) |
| 9 | `L06-cf_guard_nested-001` | L6 | Variant-selector CF-03 (control-flow rewrite; behavior-verified) |
| 10 | `L07-api_map_loop-001` | L7 | Variant-selector API-01 (idiom substitution; behavior-verified) |

L8 semantic and L9/L10 sets are intentionally **not** in this first wave — they need the richest
seed/variant substrate and the LLM-judge harness, so they come in later waves once the engine is
proven. Adding another wave is just "10 more Set-Builder tasks" against the same DAG.

**This 10-set batch is Wave 1 (the pilot), not the finish line.** A full run continues wave after
wave until *every* set implied by §9 (all families, ≥5 sets each) and §15 (~540 sets) is DONE. The
completion loop that drives all waves to the end is **§18.10**; do not stop after Wave 1 unless
explicitly told to.

### 18.4 Concurrency model

- The orchestrator keeps **≤10 live work-chains**. In the starter batch that's all 10 sets at
  once; if a future batch has >10 sets, the extras **queue** and start as slots free.
- A set's Reviewer and Fixer run **inside that set's single slot** — they do not open new slots.
  So "10 sets" never balloons into 30 concurrent agents; it is 10 chains, each internally
  sequential (build, then review, then maybe fix, then review…).
- Foundation and Integration are **gates**: they run alone (Foundation may internally use ≤2
  sub-agents), and nothing in the next stage starts until the gate's own review passes.

### 18.5 The build → review → fix loop (per set)

```
Builder(set)                       # produces src/ + set.json + expected.json + recipe entry
      │
      ▼
Reviewer(set)                      # independent; runs the Definition-of-Done checklist (§18.7)
      │
      ├─ verdict PASS  ──────────►  set is DONE — locked; no further agents touch it
      │
      └─ verdict FAIL  ──►  Fixer(set)  ──►  Reviewer(set)   ⟲  (repeat)

Guard: at most 5 review↔fix rounds. If still FAIL after round 5 → write BLOCKED.md
       (unresolved issues + last reviewer verdict), stop the chain, surface to the human.
       Never “force PASS”, never silently loosen the checklist to make it pass.
```

- **Reviewer independence.** The Reviewer is a *fresh* agent (not the builder continuing), so it
  evaluates the artifact, not its own reasoning. It is adversarial: its job is to find what's
  wrong, defaulting to FAIL when uncertain.
- **Reviewer output** is written to `REVIEW.md` beside `set.json` (outside `src/`, so detection
  tools never see it): a PASS/FAIL verdict, the §18.7 checklist with per-item ✅/❌, and an
  itemized issue list (`file:line`, severity, what's wrong, suggested fix).
- **Fixer scope** equals the Builder's scope for that one set (§18.6). It reads `REVIEW.md`,
  fixes, re-runs local verify, and appends a "fix round N" entry to `FIXLOG.md`. It must fix the
  actual defect, not paper over the check.

### 18.6 File-scope allow/deny matrix

Paths are relative to the repo root. **Deny always wins.** Anything not listed as allowed is
denied by default.

**Global deny (all roles, all tasks):**
`git` (any subcommand) · `samples/**` · `refactored/**` · `.git/**` · `.gitignore` ·
`code_duplication_*.md` · `plan_samples.md` · root `README.md` *(except Integration, append-only)* ·
`.caliber/**` · `.opencode/**` · `.logs/**` · network access · any set directory not assigned to
you · `samples.json` *(except Integration)*.

| Role | May CREATE / EDIT | May READ | May RUN (read-only exec) |
|---|---|---|---|
| **Foundation** | `testsets/schema/**` · `gen/**` · `bench/run-testsets.php` · `testsets/README.md`, `testsets/ORCHESTRATION.md`, `gen/README.md` (skeletons) · `composer.json` (dev-dep add only) · `testsets/L0*/**/README.md` (level stubs) | whole repo | `php -l`, `php gen/build.php --check`, `php gen/verify.php` |
| **Set-Builder** (per set `Lxx/<fam>/<NNN>`) | `testsets/Lxx_*/<fam>/<NNN>/src/**` · `.../set.json` · `.../expected.json` · **its own** `gen/recipes/Lxx/<fam>.json` | `gen/transforms/**`, `gen/seeds/**`, `gen/scaffolds/**`, `gen/distractors/**`, `testsets/schema/**`, sibling sets (reference only) | `php gen/build.php --set=<id>` · `php -l` · `php gen/verify.php --set=<id>` · `php bench/run-testsets.php --set=<id>` |
| **Reviewer** (per set) | **only** `.../<NNN>/REVIEW.md` | whole repo | `php -l` · `php gen/verify.php --set=<id>` · `php bench/run-testsets.php --set=<id>` |
| **Fixer** (per set) | same as that set's Set-Builder + `.../<NNN>/FIXLOG.md` | same as Set-Builder | same as Set-Builder |
| **Integration** | `testsets/manifest.json` · `bench/corpora/testsets.ground-truth.json` · `bench/results/testsets-*.{md,json}` · `testsets/README.md` (finalize) · root `README.md` (**append one section only**) · `samples.json` (**index repair only**: add 3 missing type entries, optionally populate `challenges[]`) | whole repo | `php gen/manifest.php` · `php gen/verify.php` · `php bench/run-testsets.php` |

**Critical no-collision rule:** in a batch, **at most one Set-Builder per family**, because the
family recipe file `gen/recipes/Lxx/<fam>.json` is a single file. The starter batch honors this
(all 10 families distinct). A future batch that wants two sets of the same family must either (a)
give each set its own recipe file, or (b) build them in different waves. **No two agents ever
write the same file.** Shared, race-prone files (`manifest.json`, aggregate ground truth, level
READMEs, root README, `samples.json`) are owned exclusively by Foundation/Integration — builders
never touch them.

**Missing-substrate rule:** if a Set-Builder discovers it needs a transform/seed/scaffold that
Foundation didn't provide, it **reports the gap and stops** — it does **not** add shared
substrate itself (that would race other builders and escape its scope). The orchestrator handles
it as a Foundation follow-up. The starter batch is scoped so this should not arise.

### 18.7 Definition of Done (the Reviewer's checklist)

A set is DONE only when the Reviewer confirms **all** of:

1. **Shape (R1).** Exactly 5 files in `src/` (or the family-declared count); roles in `set.json`
   match — 3 `carrier` + 1 `distractor` + 1 `clean` for duplication sets; `distractor`/`clean`
   only for L0.
2. **Syntax.** `php -l` is clean on every `src/` file (including deliberately-weird ENC files —
   still valid PHP).
3. **Schema.** `set.json` and `expected.json` validate against `testsets/schema/*`; `set_id`
   matches the directory path; every `interference[].code` exists in the registry; every cluster
   member's file has role `carrier`.
4. **Line-accuracy (R5, D4).** `expected.json` `start_line`/`end_line` actually bound the cloned
   regions (Reviewer re-parses and checks) **and** were generator-emitted, not hand-typed.
5. **Positive proof.** Declared normalization pipeline makes the carrier members equal (L1–L4) or
   within the declared edit budget (L5); for L6+, the seed's behavioral-equivalence test passes.
6. **Negative proof (false-positive safety).** No accidental ≥40 normalized-token match among
   non-cluster regions; the `distractor` is genuinely a non-duplicate and is listed under
   `expected.json → non_duplicates`. For L0, `clusters: []` holds.
7. **Hygiene (D3).** No role/level/dup hints (`clone`, `dup`, `carrier`, `distractor`, `L0`–`L9`)
   in `src/` file names, namespaces, or comments; LF + UTF-8 (unless the family declares
   otherwise, and the deviation is recorded in `set.json`).
8. **Benchmarkable (R7).** `bench/run-testsets.php --set=<id>` runs and scores; results are
   consistent with the set's `detection_expectation` (e.g. token tools pass L2, are not punished
   for missing L6).
9. **Determinism (D4).** Re-running `gen/build.php --set=<id>` reproduces byte-identical output.
10. **Scope & git.** The builder wrote nothing outside its allow-list; no `git` command was run.

### 18.8 Agent task-contract template

Every spawned agent receives a contract with these fields (the orchestrator fills the brackets),
**followed by the agent's role prompt and shared-knowledge block from [§20](#20-agent-prompt-library-ready-to-use)** and the specific §excerpts that role needs. The contract says *which files*; §20 says *how to do the work well and what "good" looks like* — always send both:

```
ROLE:        Foundation | Set-Builder | Reviewer | Fixer | Integration
TARGET:      <set id or "foundation"/"integration">
GOAL:        <one-sentence deliverable>
ALLOW-WRITE: <explicit path list from §18.6>
DENY:        the global deny-list (§18.6) + everything not in ALLOW-WRITE
MAY-RUN:     <the read-only commands for this role>
RULES:       Work in place in /home/sites/php-duplication-samples. Do NOT run any git command.
             Do NOT use worktrees/branches. Do NOT write outside ALLOW-WRITE — if you think you
             must, STOP and report. Do NOT commit. Determinism required.
DONE-WHEN:   <for builders/fixers: local verify green; for reviewers: checklist §18.7 emitted to
             REVIEW.md with a PASS/FAIL verdict; for foundation/integration: gate criteria met>
OUTPUT:      <what to return to the orchestrator: paths touched, verify/bench results, verdict>
```

### 18.9 How to launch a batch (operator steps)

1. Run/refresh the **Foundation** task; wait for its review to pass (it is itself reviewed).
2. Fan out the **Set-Builder** tasks for the batch's set list (≤10 at a time), each as its own
   `build → review → (fix → review)* → done` chain.
3. When all chains report DONE (or BLOCKED), run the **Integration** task.
4. Read the Integration report + `bench/results/testsets-matrix.md`; decide the next wave.

This is **runtime-agnostic** — it needs only "a primary agent that can spawn role-scoped
subagents." It maps onto Claude Code's **Agent tool** (spawn each role as a subagent; keep ≤10
builder chains live) or its **Workflow** script (`pipeline(setList, build, reviewFixLoop)` with the
concurrency cap and per-stage gates), onto **opencode** subagents (§18.9.1), or onto any
comparable harness. Whatever the runtime: subagents share the one working tree, and no role is
granted git.

### 18.9.1 Running on opencode (or any non-Anthropic runtime)

The plan assumes only a subagent mechanism; it does **not** depend on the Anthropic-specific
`Workflow` tool (there is no equivalent in opencode — the primary agent orchestrates directly).
Concrete opencode mapping:

- **Materialize the §20 role prompts as opencode agent definitions.** Create one subagent per role
  under `.opencode/agent/` (`foundation.md`, `set-builder.md`, `reviewer.md`, `fixer.md`,
  `integration.md`), each with `mode: subagent`, a capable model, and — critically — a **restricted
  tool/permission set** (§20.0). This turns the guard-rails from prose into config.
- **Enforce the non-negotiables via opencode `permission` config, not just the prompt.** In
  `opencode.json` (or per-agent frontmatter) deny git and out-of-scope writes, e.g.
  `"permission": { "bash": { "git *": "deny", "*": "ask" }, "edit": "allow", "webfetch": "deny" }`.
  Denying `git *` at the config layer makes the no-git rule (§18.1) impossible to violate even if a
  prompt is imperfect. Scope the set-builder/reviewer/fixer agents so they cannot edit shared
  substrate. (Reviewers: `edit: deny` except their `REVIEW.md`.)
- **Surface the five non-negotiables (§18.1) in `AGENTS.md` at the repo root.** opencode auto-loads
  `AGENTS.md` into every agent's context (its analogue of `CLAUDE.md`), so the live-dir + no-git +
  scope rules apply to *every* agent, primary and sub, without repeating them per prompt. Keep the
  full playbook in `testsets/ORCHESTRATION.md` (§19) and let `AGENTS.md` point to it.
- **Parallelism is best-effort.** If your opencode setup runs subagents sequentially rather than
  10-wide, that is fine — process the 10 chains one at a time. The concurrency cap (§18.4) is an
  *upper bound* for correctness/collision-safety, not a requirement; sequential execution changes
  only wall-clock, never the result (determinism holds either way).
- **Model choice.** Foundation (writing the deterministic generator + php-parser transforms),
  Set-Builders, and Reviewers are demanding — use a strong coding model for those roles; a cheaper
  model is fine for the mechanical Fixer if desired.
- **Everything else is portable:** PHP, `nikic/php-parser` (dev-only Composer dep), `php -l`, node
  for jscpd, and the JSON schemas are provider-agnostic and unchanged.

### 18.10 Driving to completion — the full corpus, all levels, all sets

Wave 1 (§18.3) is a 10-set pilot that proves the engine end-to-end. A **full run does not stop
there** — it continues until the entire corpus defined by §9 and §15 exists and passes. This
section is the outer loop the orchestrator runs to get there.

**Goal state ("the whole plan is complete") — all of:**
- Every family in §9 (all 11 levels, ~108 families) has **≥5 sets, each DONE** (Reviewer PASS).
- `testsets/manifest.json` totals match §15 (≈108 families / ≈540 sets / ≈2,755 files) — or the
  agreed target if the per-family count was raised from the R2 floor of 5.
- Whole-tree `gen/verify.php` is green and `gen/build.php --check` is byte-clean.
- Per-tool **Capability Profile** reports (§13.4) and all matrices (§13) are generated.
- Documentation Definition of Done (§19) is satisfied.

**Why it runs phase-by-phase, not one giant fan-out.** Later levels need substrate that must exist
before their sets can be built: L4/L5 need AST transforms, L6–L8 need **hand-authored variant
bodies + equivalence tests**, L8 needs the LLM-judge rationale corpus, L10 needs adversarial
scaffolds + sanitized Smarty fixtures (§14). So Foundation is **extended once per phase**, then
that phase's sets fan out. This is exactly the P0–P8 ladder in §16 — §16 is the *schedule*, this
loop is the *driver*.

**The completion loop:**

```
for phase in [P1, P2, P3, P4, P5, P6, P7]:          # P0 = initial Foundation (Wave 1 gate)
    1. EXTEND FOUNDATION for this phase              # add the transforms/seeds/variants/
       (foundation agent) → review → must pass       #   scaffolds/distractors the phase needs
    2. ENUMERATE this phase's sets from §9/§15        # every family's ≥5 sets for the phase's levels
    3. FAN OUT in waves of ≤10 concurrent chains:     # §18.4 cap; queue the rest
         each set: Builder → Reviewer → (Fixer → Reviewer)* → DONE   # §18.5, max 5 rounds
       keep launching waves until this phase's set list is exhausted
    4. PHASE GATE: whole-subtree gen/verify.php green # BLOCKED sets are recorded, not skipped over
    5. CHECKPOINT: report phase status, then continue automatically (unless told to pause)
run P8 INTEGRATION once, over the whole tree         # manifest + aggregate GT + reports +
                                                     #   capability profiles + docs + samples.json
assert goal state (above) holds; list any BLOCKED sets
```

**Rules that hold across the entire run:**
- **≤10 concurrent chains at all times** (§18.4) — a "wave" is just the next ≤10 sets off the
  phase's queue; when one finishes, the next queued set starts.
- **Every set is reviewed** (§18.5); no phase advances with un-reviewed sets. BLOCKED sets
  (5 rounds exhausted) are logged in `BLOCKED.md`, surfaced, and do **not** block *other* sets or
  later phases — they are collected and reported at the end for human follow-up.
- **One Set-Builder per family per wave** (recipe-file rule, §18.6). A family's 5 sets can be built
  across sequential waves (same recipe file, one writer at a time) or given per-set recipe files if
  you want them concurrent — the builder is told which.
- **Hand-authored content is real work, not generation.** For L6–L8 the Set-Builder (or a
  dedicated seed-variant author task) writes the variant bodies and the `equivalence_test.php`, and
  the Reviewer confirms behavioral equivalence actually passes. For L10 it authors adversarial
  fixtures and sanitizes Smarty templates (§14). Budget models/time accordingly (P4/P5/P7 dominate,
  §16).
- **Idempotent & resumable.** Because generation is deterministic and every set is
  independent, a run can stop and resume: on restart, skip sets whose directory exists and whose
  Reviewer verdict is PASS; rebuild/finish the rest. The orchestrator should track a simple
  progress ledger (done / in-review / blocked / pending) so a resumed session knows what remains.
- **No git, ever** (§18.1) — completion means the full tree exists **uncommitted** in the working
  directory for human review; the orchestrator never commits, not even between phases.

**Definition of "done driving":** the goal state holds, Integration has produced the capability
profiles, and a final report lists total sets built, any BLOCKED sets with reasons, and the
headline capability matrix. Only then does the orchestrator stop.

---

## 19. Documentation Deliverables

Documenting the corpus — what it is, how it's laid out, and how it's used — is a **first-class
deliverable of every phase**, not an afterthought. The following docs are produced and kept
current; ownership is assigned so no two agents write the same doc file (§18.6).

| Doc | Location | Owner | Contents |
|---|---|---|---|
| **Corpus guide** | `testsets/README.md` | Foundation (skeleton) → Integration (finalize) | What the suite is; the L0–L10 ladder; directory layout (level→family→set, `src/` vs metadata); how to run `bench/run-testsets.php`; how to read the report matrices; how to add a set; the `samples.json` staleness note + what the repair did |
| **Orchestration playbook** | `testsets/ORCHESTRATION.md` | Foundation | Verbatim copy of [§18](#18-execution-via-parallel-agents) **and [§20](#20-agent-prompt-library-ready-to-use)**: task types, DAG, concurrency cap, the build→review→fix loop, the allow/deny matrix, the no-git rule, the Definition of Done, the task-contract template, the launch steps, **and the ready-to-use per-role agent prompts** |
| **Generator guide** | `gen/README.md` | Foundation | The generation pipeline; the transform interface (`transform(...)→TransformResult` with line map); how to author a new transform / seed / `payload.php` sentinel / scaffold / distractor / recipe; the `--check` determinism gate; the `nikic/php-parser` dev-only dependency |
| **Schema reference** | `testsets/schema/*.json` `$comment`s + `testsets/schema/README.md` | Foundation | Field-by-field docs for `set.json`, `expected.json`, `manifest.json` (roles, clone types, granularity, `interference[].code`, `detection_expectation`, `normalized_by`, `scoring` knobs) |
| **Per-level notes** | `testsets/L0X_*/README.md` | Foundation (stub) → Integration (fill) | One short blurb per level: what it tests, its families, the `requires` capabilities, and detection expectations. *(Builders do NOT edit these — to avoid write races they put per-set notes in their own `set.json.description` / `expected.json.notes` instead.)* |
| **Per-set provenance** | each set's `set.json` (`description`, `generator`) + `expected.json` (`notes`) | Set-Builder | Human-readable description of the set, the exact transforms+params applied, the RNG seed, and *why* the members are equivalent (the L8 `notes` double as the LLM-judge rationale corpus) |
| **Review/fix trail** | each set's `REVIEW.md` / `FIXLOG.md` / (`BLOCKED.md`) | Reviewer / Fixer | The Definition-of-Done checklist results, itemized issues, and the fix history — an auditable record of how each set reached DONE |
| **Report matrices** | `bench/results/testsets-{matrix,by-family,by-interference}.md` + `testsets-<label>.json` | Integration | Tools × levels / families / interference-codes grids of F1, expected-recall, FP-resistance, boundary accuracy (see [§13](#13-bench-integration--reporting)) |
| **Root pointer** | root `README.md` (append one section) | Integration | A new "Graduated test sets (`testsets/`)" section linking to the corpus guide and the latest report matrices |

**Layout at a glance (how a reader navigates it):**

```
testsets/README.md            ← start here: what & how
testsets/ORCHESTRATION.md     ← how the corpus is (re)built by agents
gen/README.md                 ← how the generator works / how to extend it
testsets/schema/README.md     ← what every JSON field means
testsets/L0X_*/README.md       ← what each difficulty level tests
  └─ <family>/<NNN>/
       ├─ set.json            ← this set's metadata + provenance
       ├─ expected.json       ← this set's ideal answers (+ rationale notes)
       ├─ REVIEW.md/FIXLOG.md ← this set's QA trail
       └─ src/                ← the code a detector actually sees
bench/results/testsets-*.md   ← how the tools scored
```

**Documentation Definition of Done** (checked by Integration before a wave is closed): every new
set is reachable from `testsets/README.md`; every level touched has a filled-in level README;
`ORCHESTRATION.md`, `gen/README.md`, and the schema docs reflect the current engine; the root
README points at the suite; and the report matrices are regenerated from the current tree.

---

## 20. Agent Prompt Library (Ready-to-Use)

The orchestrator should **not** improvise agent instructions from scratch — that risks dropped
guidelines and inconsistent output. This library gives the **exact content to embed in each
agent's prompt**, so every agent starts with the same domain knowledge, quality bar, and
guard-rails. It complements (does not replace) the machine-checkable Definition of Done (§18.7)
and the task-contract template (§18.8): the contract says *what file to touch*; these prompts say
*how to do the work well and what "good" looks like*.

### 20.0 How to use this library

For any agent, assemble its prompt from four parts, in order:

1. **The task contract** (§18.8) — role, target, allow/deny paths, may-run commands.
2. **The role prompt** from this section (§20.1–§20.5).
3. **The shared knowledge block** (§20.6) — appended to every Builder/Reviewer/Fixer.
4. **The specific §excerpts** named in the role prompt (e.g. a Set-Builder for an L4 set gets
   §6, §7, the L4 row of §9, §10, §20.2, §20.6). Paste the excerpts inline — do not merely cite
   them; the agent may not read the whole plan.

Keep the four parts labeled so the agent can tell contract from guidance. Everything here is
written to be pasted verbatim.

**On opencode (§18.9.1):** instead of pasting parts 1–3 into each spawn call, persist them as
subagent definitions under `.opencode/agent/<role>.md` (role prompt + shared block as the system
prompt; the allow/deny scope as the agent's tool/permission config). The primary agent then only
supplies part 4 (the target set id + the specific §excerpts) at invocation time. Same content,
enforced by config rather than by prose.

### 20.1 Foundation agent — prompt content

> **Mission.** Build the shared substrate every Set-Builder depends on, and prove it end-to-end on
> one pilot set per pipeline path before fan-out. You are the gate; if your output is wrong, all
> 10 builders inherit the bug.
>
> **You must produce & know:**
> - The JSON Schemas (`testsets/schema/*.json`) for `set.json`, `expected.json`, `manifest.json`,
>   matching the normative shapes in §7 exactly (field names, enums, required-ness).
> - The generator (`gen/build.php`, `gen/verify.php`, `gen/manifest.php`) and the **transform
>   interface** `transform(in, params, rng) → {text, lineMap}` (§11.2). The line map is
>   non-negotiable: ground-truth line numbers are computed from it, never hand-typed.
> - The specific transforms the starter batch needs — WS-03, WS-06, CM-03, RN-01, LT-01, ST-01 —
>   plus the CF/API **variant-selectors** (which pick a hand-written variant file, not compute a
>   rewrite). WS/CM operate on a `token_get_all` stream (never split inside a string literal);
>   RN/LT/ST operate on the php-parser AST (dev-only dependency).
> - ≥3 seed domains with `payload.php` (sentinel-marked clonable region, §10), `seed.json`
>   (domain, size class, token/line counts, compatible interference codes), and — for the CF/API
>   sets — hand-written `variants/` bodies **plus** an `equivalence_test.php` proving each variant
>   is behaviorally identical to the pristine payload.
> - Scaffolds (host-file templates with an `<<<INSERT>>>` marker + unique filler) and a distractor
>   library (near-miss twins at ~60–80% token overlap, and clean fillers), tagged by domain.
> - `bench/run-testsets.php` (per-set invocation + scoring + version capture, §13).
> - Doc skeletons: `testsets/README.md`, `testsets/ORCHESTRATION.md` (copy §18 verbatim),
>   `gen/README.md`, `testsets/schema/README.md`, per-level `README.md` stubs.
>
> **Determinism is a hard requirement.** `mt_srand(rng_seed)` per set; `gen/build.php --check`
> must regenerate byte-identical output. Wire it as a gate.
>
> **Done when:** schemas validate; the transform interface is documented in `gen/README.md`; one
> pilot set per path (text-transform, AST-transform, variant-selector, exact, negative) builds
> with generator-emitted ground truth and passes `gen/verify.php`; `--check` is byte-clean.
>
> **Do not:** create any `testsets/L*/**` set content (that is fan-out); touch `samples/`; run git.
> If the starter batch will need a transform/seed you weren't told about, build it now or flag it —
> builders are forbidden from adding shared substrate themselves.

### 20.2 Set-Builder agent — prompt content (what the sample generator must know)

> **Mission.** Produce **one** benchmark set — its `src/` files, `set.json`, `expected.json`, and
> its family recipe entry — that is a clean, single-axis, line-accurate probe of exactly one
> detector capability. A detector's score on your set becomes a *bit in its capability profile*
> (§13.4), so the set must be unambiguous.
>
> **The mental model (from §6).** A standard set = **3 carriers + 1 near-miss distractor + 1 clean
> filler**. The cloned region lives *inside* each carrier, surrounded by different code; the
> carriers are not wholesale copies of each other (except the `ex_full_file` family).
>
> **The twelve things you must get right:**
> 1. **No leakage (D3).** File names, namespaces, classes, and comments in `src/` must be realistic
>    domain names and reveal **nothing** about role, level, or duplication. Never `clone_a.php`,
>    `carrier2`, `dupServiceB`, `// duplicated below`. Use e.g. `InvoiceTotalsService.php`. A
>    detector (or LLM) must be unable to cheat by reading names. The answers live *outside* `src/`.
> 2. **Single axis (D5, minimal pair).** Apply **only** your family's interference on top of an
>    otherwise L1-exact clone. Do not introduce a second axis by accident — no stray rename during
>    a whitespace set, no literal change during a comment set. If your transform would, stop.
> 3. **Asymmetric interference.** Make carrier A pristine, B moderate, C heavy (record exactly what
>    each got, and its params, in `set.json.interference[].applied_to`). The **hardest pair
>    (pristine ↔ heavy) must still be a true clone** — verify it, since that pair is what
>    discriminates capable detectors.
> 4. **Position variation.** Place the cloned region at different offsets (top/middle/bottom) across
>    carriers unless the family pins position. This tests region reporting, not just file pairing.
> 5. **Near-miss distractor = false-positive bait.** One file must share vocabulary/shape/partial
>    token-stream with the clone but be **genuinely not a duplicate** (~60–80% token overlap, but
>    different computation). List it in `expected.json → non_duplicates` with `trap:true` and a
>    reason. Getting this *wrong* — accidentally making it a real clone — is the most common and
>    most damaging defect. Prove it is not a clone (no ≥40-token normalized match to the carriers).
> 6. **Clean filler.** One unrelated same-domain file, no shared logic — establishes base-rate.
> 7. **Ground truth is generator-emitted (D4, R5).** Never hand-type line numbers. Run the
>    generator, read the emitted `start_line/end_line` from the line map into `expected.json`.
>    After any edit, regenerate — do not nudge numbers by hand.
> 8. **PHP standard.** Every file: `<?php` + `declare(strict_types=1)` + a domain namespace,
>    40–150 lines, zero external/Composer dependencies, and **`php -l` clean**.
> 9. **Determinism.** Set an `rng_seed` in the recipe; re-running `gen/build.php --set=<id>` must be
>    byte-identical. Record the seed in `set.json.generator`.
> 10. **Truthful `detection_expectation`.** Per cluster, mark which detector *classes* should find
>    it (text/token/ast/metric/semantic). Use the level's guidance in §9 (e.g. L2 token=true,
>    L6+ token=false). This is what separates "missed" from "not expected" in reports — getting it
>    wrong corrupts the capability profile.
> 11. **Correct `clone_type` & `granularity`.** L1–L3 = type-1, L4 = type-2, L5 = type-3, L6–L8 =
>    type-3/4/semantic-domain; granularity = function/method/block/etc. Match §7 enums.
> 12. **Scope & git.** Write only inside your set dir + your own family recipe. Read shared
>    substrate; never edit it. Never run git. If you need a transform/seed that doesn't exist,
>    **stop and report** — do not add it (you would race other builders).
>
> **Level-specific must-knows:** you will be given the §9 row for your level. For **threshold
> families** (CP-03/CP-04, `ex_size_ladder` low rung) you must cite the exact clone token/line size
> in `expected.json.notes` and calibrate to the tool floors in §8.1. For **L6–L8** you assemble
> hand-written variants (selectors), and the seed's `equivalence_test.php` must pass — your job is
> assembly + correct ground truth, and a `notes` field explaining *why* the members are equivalent
> (this doubles as LLM-judge rationale). For **L0** there is no cluster: `clusters:[]`, roles are
> distractor/clean only, and the whole point is to be tempting but clean.
>
> **Builder gotchas (self-check before handoff):**
> - Comment stripping changed the token stream? → a comment was inside a string, or you removed a
>   `#[Attribute]`, not a comment. Re-verify the normalized streams match.
> - Line numbers off by one after a wrap/insert? → you edited a file post-generation; regenerate.
> - Rename collided with a real symbol or a PHP keyword/builtin? → pick a non-colliding name.
> - CRLF/encoding family: declare the deviation in `set.json` or QA will flag it (and confirm the
>   editor/git didn't silently normalize it).
> - Distractor too similar (accidental real clone) or too different (not actually tempting)? → tune
>   to the 60–80% band and re-check the no-match assertion.
> - `php -l` fails on the heavy carrier? → your transform broke syntax; fix the transform input,
>   not the output file.
>
> **Done when:** `gen/verify.php --set=<id>` is green, `php -l` clean on all `src/`, ground truth
> line-accurate and generator-emitted, `bench/run-testsets.php --set=<id>` scores consistently with
> `detection_expectation`, and re-build is byte-identical. Then a Reviewer takes over.

### 20.3 Reviewer agent — prompt content (things to look for)

> **Mission.** Independently and **adversarially** verify one finished set against the Definition
> of Done (§18.7). You did not build it; your job is to find what's wrong. **Default to FAIL when
> uncertain.** A set that ships with a subtle defect corrupts every capability profile computed
> from it, so be strict. Write your verdict to `REVIEW.md` (beside `set.json`, never in `src/`).
>
> **Run the checks, don't eyeball them:** execute `php -l` on every `src/` file, `gen/verify.php
> --set=<id>`, and `bench/run-testsets.php --set=<id>`. Re-derive line numbers yourself and compare
> to `expected.json`. Confirm a rebuild is byte-identical.
>
> **High-yield defect hunt (the subtle failures generators make):**
> - **Answer leakage.** Grep `src/` for `clone|dup|carrier|distractor|copy|sample|L0..L9|level` in
>   names/namespaces/comments. Any hint = FAIL (breaks D3; lets tools/LLMs cheat).
> - **The distractor is actually a clone.** The #1 defect. Independently check the near-miss shares
>   no ≥40-token normalized run with any carrier. If it does, it's a mislabeled positive = FAIL.
> - **The hardest pair isn't a clone.** Normalize pristine-A vs heavy-C through the declared
>   pipeline; if they don't match (type-1/2) or exceed the edit budget (type-3), the set is broken.
> - **Second axis crept in.** Diff the carriers: does a "whitespace" set also rename a variable or
>   change a literal? That blurs which capability a miss localizes = FAIL (violates D5).
> - **Line numbers.** Do `start_line/end_line` actually bound the cloned code? Off-by-one after a
>   wrap/insert is common. Were they generator-emitted (seed reproduces them) or hand-typed?
> - **`detection_expectation` wrong for the level.** A token=true on an L6 control-flow rewrite, or
>   token=false on an L2 whitespace set, is a profiling bug even if scoring "passes."
> - **Threshold notes.** For CP-03/04: does the clone's *actual* token/line count match the size
>   claimed in `notes` and sit under the §8.1 floor it names? Re-count; don't trust the note.
> - **Equivalence (L6–L8).** Did `equivalence_test.php` actually run and pass? Does the `notes`
>   rationale truly explain the equivalence, or is it hand-wavy?
> - **Hygiene.** LF/UTF-8 unless declared; exactly the right file/role counts; schema-valid;
>   `set_id` matches path; interference codes exist in the registry.
> - **Scope/git.** Evidence the builder wrote outside its box or ran git = FAIL.
>
> **Severity & verdict.** Tag each issue **blocker** (breaks ground truth, leakage, mislabeled
> dup, non-determinism), **major** (wrong expectation/type, hygiene), or **minor** (cosmetic).
> **Any blocker or major ⇒ overall FAIL.** Emit `REVIEW.md` as: a one-line **PASS/FAIL**, the
> §18.7 checklist with ✅/❌ per item, then an itemized issue list — each with `file:line`,
> severity, what's wrong, and a concrete suggested fix (so the Fixer can act without guessing).
> Do not fix anything yourself; do not loosen a check to make it pass.

### 20.4 Fixer agent — prompt content

> **Mission.** Resolve every blocker/major issue in `REVIEW.md` for this one set. Your write-scope
> equals the Set-Builder's for this set (its dir + its recipe) — nothing else; no git.
>
> **Rules:** Fix the **root cause**, not the symptom — if the line numbers are wrong, fix the
> transform/recipe and regenerate; do not hand-edit the numbers to match. **Never** loosen
> `expected.json` tolerances, weaken the checklist, or delete a hard case to make review pass. If a
> fix genuinely requires shared substrate you can't touch (a transform bug, a bad seed), **stop and
> escalate** — write the blocker to `FIXLOG.md` and report; do not reach outside scope. After
> fixing, re-run `gen/verify.php --set=<id>` + `php -l` + a byte-identical rebuild, append a "fix
> round N: <what changed, why>" entry to `FIXLOG.md`, and hand back to the Reviewer. Address the
> whole issue list, not just the first item — partial fixes waste a review round.

### 20.5 Integration agent — prompt content

> **Mission.** Close a wave: make the corpus coherent, benchmarkable, documented, and profile-ready.
>
> **Do, in order:** (1) `gen/manifest.php` → rebuild `testsets/manifest.json` + the aggregate
> `bench/corpora/testsets.ground-truth.json`. (2) Whole-tree `gen/verify.php` — the wave does not
> close on any red. (3) `bench/run-testsets.php` across installed tools, **capturing each tool's
> version + flags** (§13.5). (4) Generate the report matrices **and the per-tool Capability Profile
> report cards** (§13.4) — the R8 deliverable; each must state detects/misses/thresholds/FPs/region
> accuracy/scaling with a one-paragraph verdict. (5) Commit a **golden baseline** snapshot for
> future diffing. (6) Finalize docs: fill per-level READMEs, finalize `testsets/README.md` with real
> counts, append the "Graduated test sets" section to the **root README** (append-only). (7)
> **Repair `samples.json`** (index-only): add the 3 missing type entries
> (`connection_handling_duplication`, `query_style_duplication`, `result_processing_duplication`)
> and optionally populate `challenges[]` from `code_duplication_challenges.md` — **without touching
> any existing `samples/` file content**.
>
> **Do not** run git (baseline "commit" means writing the snapshot files for a human to commit
> later, per the no-git rule); do not modify set content (that's the builders' domain — if a set is
> wrong, send it back through review/fix, don't patch it here).

### 20.6 Shared knowledge block (append to every Builder / Reviewer / Fixer)

> **Why this corpus exists (keep it in mind).** The goal is R8: after a detector runs against these
> sets, a reader must be able to state *exactly* what it can and cannot detect, its false-positive
> behavior, and its threshold blind spots (§1 north-star, §13.4). Your set/review is one clean bit
> in that profile — ambiguity anywhere (a leaked hint, a mislabeled distractor, a second axis, a
> wrong expectation, a fudged line number) corrupts conclusions about real tools. Precision here is
> the whole point.
>
> **The five non-negotiables (§18.1):** (1) work in place in the live dir; (2) **never run git** —
> no commits, branches, worktrees, stash, or reset; (3) stay strictly inside your allow-listed
> paths — if you think you must write outside, STOP and report; (4) shared substrate
> (schemas, generator, seeds, transforms) is read-only to everyone but Foundation; (5) nothing is
> "done" until a Reviewer says PASS — never self-certify, never force a pass.
>
> **Determinism & honesty:** everything is reproducible (fixed seeds, declared tolerances); report
> outcomes faithfully — if a check fails, say so; if something is skipped, say so; do not paper over
> a defect to move on.

---

## Appendix A: Challenge-Taxonomy → Level Mapping

Coverage of the 60-row taxonomy in `code_duplication_challenges.md`:

| Ch# | Type | Covered by |
|---|---|---|
| 1 | Whitespace/formatting | L2 (all `ws_*`) |
| 2 | Comment differences | L3 (all `cm_*`) |
| 3 | Identifier renaming | L4 `rn_*` |
| 4 | Literal value changes | L4 `lt_*` |
| 5 | Type annotation differences | L4 `ty_hints` |
| 6 | Parameter reordering | L5 `st_param_reorder` |
| 7 | Statement reordering | L5 `st_reorder` |
| 8 | Expression rewriting | L6 `ex_arith` |
| 9 | Control structure substitution | L6 `cf_if_ternary`, `cf_match_switch` |
| 10 | Loop form changes | L6 `cf_loop_forms` |
| 11 | Inlined vs extracted | L8 `sem_inline_extract` |
| 12 | Different API usage | L7 `api_strings`, `api_builtins`, `api_datetime` |
| 13 | Different data structures | L7 `api_data_shape` |
| 14 | Boolean transformations | L6 `bl_demorgan` |
| 15 | Early return vs nested | L6 `cf_guard_nested`, `cf_early_return` |
| 16 | Exception vs conditional | L8 `sem_error_style` |
| 17 | Polymorphic variants | L6 `cf_match_switch` (switch-vs-OO variant sets) |
| 18 | Functional vs imperative | L7 `api_map_loop` |
| 19 | SQL/string template variants | L7 `api_strings` + L8 `sem_orm_sql` |
| 20 | Macro/metaprogramming expansion | L10 `adv_generated` |
| 21 | Configuration-driven | L8 `sem_config_driven` |
| 22 | Copy-paste with small edits | L5 `st_expr_tweak`, `st_insert_step`, `st_delete` |
| 23 | Dead-code noise | L5 `st_insert_dead`, L3 `cm_commented_code` |
| 24 | Logging/instrumentation noise | L5 `st_insert_logging`, L9 `mix_noise_heavy` |
| 25 | Framework boilerplate noise | L9 `mix_noise_heavy` (NZ-04), L3 `cm_annotations` |
| 26 | Namespace/import differences | L4 `ns_imports` |
| 27 | Order of helper calls | L8 `sem_normalization_order` |
| 28 | Split vs combined conditions | L6 `bl_split_combined` |
| 29 | Data normalization variants | L8 `sem_normalization_order` |
| 30 | Async vs sync | out of scope (future L11) |
| 31 | Recursive vs iterative | L7 `api_recursion` |
| 32 | Table-driven vs hardcoded | L7 `api_table_driven` |
| 33 | State machine encodings | L8 `sem_state_machine` |
| 34 | DSL/template indirection | L10 `adv_html_mix` |
| 35 | Partial duplication | L5 `st_partial`, `st_gap_split`; L10 `adv_below_threshold`, `adv_boundary` |
| 36 | Cross-language | out of scope (PHP-only repo) |
| 37 | Semantic equivalence | L8 `sem_rule` |
| 38 | Algebraic equivalence | L6 `ex_arith` |
| 39 | Commutative operations | L6 `bl_commutative` |
| 40 | Optional step variants | L5 `st_insert_step` |
| 41 | Feature-flag divergence | L9 `mix_noise_heavy` (NZ-06) |
| 42 | OO decomposition | L8 `sem_oo_split` |
| 43 | Generated builder patterns | L7 `api_serialization` (builder variants) |
| 44 | Error handling style | L8 `sem_error_style` |
| 45 | Serialization differences | L7 `api_serialization` |
| 46 | Naming conventions | L4 `rn_case_style` |
| 47 | Localization differences | L4 `lt_strings`, L9 NZ-05 |
| 48 | Security/sanitization noise | L9 NZ-03 |
| 49 | DI variants | L8 `sem_di_style` |
| 50 | Temporal coupling variants | L8 `sem_normalization_order` (ordering semantics noted per set) |
| 51 | ORM vs raw SQL | L8 `sem_orm_sql` |
| 52 | Event-driven vs direct | L8 `sem_event` |
| 53 | Null handling | L6 `nu_null_styles`, L4 `ty_hints` |
| 54 | Encoding/parsing variants | L7 `api_regex_string` |
| 55 | Refactoring drift | L10 `adv_genealogy`, L9 `mix_realistic_drift` |
| 56 | Platform conditional code | out of scope (future) |
| 57 | Generic abstraction drift | L8 `sem_inline_extract` (abstraction-layer sets) |
| 58 | Copy-paste across files | inherent everywhere; stressed in L10 `adv_many_files`, L8 `sem_scatter` |
| 59 | Behavioral duplication | L6–L8 as a whole (equivalence-tested) |
| 60 | Domain-level duplication | L8 `sem_rule` |

**58/60 covered; 2 explicitly deferred with rationale.**

## Appendix B: Existing Category → Seed/Level Mapping (Coverage Audit)

**Complete, explicit enumeration of all 59 `samples/` category directories** (as seed material
and family inspiration — not moved or modified). This table is exhaustive on purpose: every
directory is listed individually so coverage can be verified by eye, with **no reliance on
wildcards**. Columns: whether the category is indexed in `samples.json` (⚠ = directory-only,
missing from the index), and where it feeds the new suite.

| # | Existing category | In `samples.json`? | Feeds (target level / family / role) |
|---|---|:--:|---|
| 1 | `algorithm` | ✅ | L6/L7 seeds (threshold-style algorithms → `cf_*`, `api_builtins`) |
| 2 | `architectural` | ✅ | L8 `sem_oo_split`, `sem_scatter` inspiration; distractor material |
| 3 | `behavioral` | ✅ | L6–L8 behavioral-equivalence seeds + variant inspiration |
| 4 | `build` | ✅ | Seed domain + clean-filler / distractor material |
| 5 | `caching` | ✅ | Seed domain (`caching` payloads) + NZ-* noise vocabulary |
| 6 | `clone_type_1` | ✅ | L1–L3 seed bodies (e.g. CSV importer → seed `csv_import`) |
| 7 | `clone_type_2` | ✅ | L4 seeds + calibration (full Type-2 reference) |
| 8 | `clone_type_3` | ✅ | L5 seeds and edit patterns |
| 9 | `clone_type_4` | ✅ | L7/L8 semantic seeds + variant inspiration |
| 10 | `configuration` | ✅ | L4 `lt_*` / `ty_*` seed domain; L8 `sem_config_driven` |
| 11 | `connection_handling_duplication` | ⚠ dir-only | Seed domain + distractor material (resource-lifecycle code) |
| 12 | `copy_paste` | ✅ | L1–L3 seed bodies (the largest Type-1 pool, 60 samples) |
| 13 | `cross_service` | ✅ | L8 `sem_scatter` inspiration; seed domains |
| 14 | `data` | ✅ | L4 `lt_*` seed domain (error codes / message formats) |
| 15 | `dependency` | ✅ | Seed domains + `sem_di_style` inspiration |
| 16 | `dependency_config` | ✅ | `sem_di_style` / `sem_config_driven` seed material |
| 17 | `dependency_database` | ✅ | `sem_orm_sql` seed material |
| 18 | `dependency_logger` | ✅ | NZ-01 logging-noise vocabulary; `sem_di_style` |
| 19 | `documentation` | ✅ | L3 `cm_*` (docblock/comment) seed material |
| 20 | `documentation_api` | ✅ | L3 `cm_docblock` / `cm_annotations` material |
| 21 | `documentation_business` | ✅ | L3 comment material; SEM rationale-note inspiration |
| 22 | `documentation_deployment` | ✅ | L3 comment / license-header (`cm_license_header`) material |
| 23 | `error_handling_fallback` | ✅ | L8 `sem_error_style` seeds |
| 24 | `error_handling_logging` | ✅ | L5 `st_insert_logging`, NZ-01 noise |
| 25 | `error_handling_retry` | ✅ | L8 `sem_error_style`; queue/retry seed domain |
| 26 | `event` | ✅ | L8 `sem_event` seeds |
| 27 | `functional` | ✅ | L7 `api_map_loop` (functional vs imperative) seeds |
| 28 | `graph_query_duplication` | ✅ | `sem_orm_sql`, `api_*` query seeds |
| 29 | `knowledge` | ✅ | L8 `sem_rule` (business-rule / constant duplication) seeds |
| 30 | `lexical` | ✅ | L1–L2 Type-1 seed bodies |
| 31 | `localization` | ✅ | L4 `lt_strings`, L9 NZ-05 (i18n) material |
| 32 | `logic` | ✅ | L6 `bl_*` / `cf_*` (conditional business-rule) seeds |
| 33 | `mapping` | ✅ | L4 `lt_*` / `ty_*` (representation-mapping) seed domain |
| 34 | `monitoring` | ✅ | NZ-02 metrics-noise vocabulary; seed domain |
| 35 | `nosql_document_duplication` | ✅ | `sem_orm_sql`, `api_serialization` seeds |
| 36 | `orm_query_duplication` | ✅ | L8 `sem_orm_sql` seeds |
| 37 | `permission` | ✅ | NZ-03 security-noise vocabulary; L8 `sem_rule` (authz) |
| 38 | `process` | ✅ | Seed domains; `mix_realistic_drift` narrative material |
| 39 | `protocol` | ✅ | Seed domains (largest after copy_paste, 50 samples) + distractors |
| 40 | `query` | ✅ | L7 `api_*`, L8 `sem_orm_sql` seeds |
| 41 | `query_style_duplication` | ⚠ dir-only | `sem_orm_sql` variant seeds (query-builder vs raw) |
| 42 | `representation` | ✅ | L7 `api_data_shape`, L4 `lt_*` seed domain |
| 43 | `result_processing_duplication` | ⚠ dir-only | L7 `api_map_loop` / `api_data_shape` seeds |
| 44 | `schema` | ✅ | L4 `ty_*` / `lt_*` seed domain (schema-in-multiple-systems) |
| 45 | `semantic` | ✅ | L8 `sem_*` seed rules + variant inspiration |
| 46 | `serialization` | ✅ | L7 `api_serialization` seeds |
| 47 | `state_session` | ✅ | L8 `sem_state_machine`; session/state seed domain |
| 48 | `structural` | ✅ | L6 `cf_*` (control-structure) seeds |
| 49 | `syntactic` | ✅ | L2/L6 seeds (syntactic-variation payloads) |
| 50 | `temporal` | ✅ | L7 `api_datetime`; L8 `sem_normalization_order` (temporal coupling) |
| 51 | `test` | ✅ | Seed domain (test-code clones are their own realistic flavor) |
| 52 | `textual` | ✅ | L1–L3 Type-1 seed bodies |
| 53 | `type` | ✅ | **L4 `ty_hints` + L1 `type`-style parser seeds** (same import/parse logic per file format → `api_regex_string` variants) |
| 54 | `ui` | ✅ | L10 `adv_html_mix` (HTML/PHP template) material |
| 55 | `validation_email` | ✅ | Validation seed domain; L8 `sem_rule` (validation rules) |
| 56 | `validation_password` | ✅ | Validation seed domain + NZ-03 security noise |
| 57 | `validation_phone` | ✅ | Validation seed domain; `api_regex_string` seeds |
| 58 | `workflow` | ✅ | L8 `sem_state_machine`; `mix_realistic_drift` material |
| 59 | *(implicit)* | — | *(row 53 `type` also seeds distractor twins for `nd_shared_vocab`)* |

**Audit result:** all **59** directory categories are explicitly mapped above; the 3 ⚠ rows
(`connection_handling_duplication`, `query_style_duplication`, `result_processing_duplication`)
are the ones missing from `samples.json` and are flagged for the index-repair stretch task
([§3 Coverage audit](#coverage-audit--every-existing-type-is-accounted-for),
[§18 Integration](#18-execution-via-parallel-agents)). No category is dropped or unaccounted for.

## Appendix C: Flat Interference-ID Cross-Reference (I001–I032 ↔ Registry Codes)

The earlier plan enumerated a flat interference taxonomy (I001–I032). It is preserved here
mapped onto the richer registry codes (§8), so nothing from that taxonomy is lost.

| I## | Factor | Category | Registry code(s) | Detection impact |
|---|---|---|---|---|
| I001 | Extra whitespace | whitespace | WS-04, WS-09, WS-11, WS-12 | Harder |
| I002 | Tabs vs spaces | whitespace | WS-08 | Harder |
| I003 | Indentation depth | whitespace | WS-05 | Harder |
| I004 | Line wrapping | whitespace | WS-06, WS-07 | Harder |
| I005 | Blank lines | whitespace | WS-01, WS-02, WS-03 | Harder |
| I006 | CRLF vs LF | whitespace | WS-10 | Harder |
| I007 | Different comments | comments | CM-01, CM-06 | Harder |
| I008 | No comments vs comments | comments | CM-05, CM-09 | Harder |
| I009 | Inline vs block comments | comments | CM-02, CM-04, CM-08 | Harder |
| I010 | Docblock formatting | comments | CM-03, CM-10 | Harder |
| I011 | Commented code | comments | CM-07 | Harder |
| I012 | Variable rename | renaming | RN-01, RN-02, RN-05, TY-01, TY-02 | Harder |
| I013 | Function rename | renaming | RN-03 | Harder |
| I014 | Class rename | renaming | RN-04 | Harder |
| I015 | Namespace change | renaming | NS-01, NS-02 | Harder |
| I016 | String literal change | literals | LT-02, LT-03, NZ-05 | Harder |
| I017 | Numeric literal change | literals | LT-01, LT-04 | Harder |
| I018 | Boolean literal change | literals | LT-02 (bool-flag variant) | Harder |
| I019 | Dead code insertion / edits | partial | ST-02, ST-03, ST-04, ST-05, ST-06, ST-07, ST-08, ST-09 | Harder |
| I020 | Logging noise | noise | ST-01, NZ-01 | Harder |
| I021 | Instrumentation noise | noise | NZ-02, NZ-09 | Harder |
| I022 | Framework annotations | noise | NZ-03, NZ-04 | Harder |
| I023 | Debug statements | noise | NZ-07 | Harder |
| I024 | Feature flags / env checks | noise | NZ-06, NZ-08, NZ-10 | Harder |
| I025 | Loop form change | structural | CF-04 | Harder |
| I026 | Control structure change | structural | CF-01, CF-02 | Harder |
| I027 | Functional vs imperative | structural | API-01, API-05, API-06 | Harder |
| I028 | Guard vs nested | structural | CF-03, CF-05 | Harder |
| I029 | Expression rewriting | semantic | EX-01, SEM-01..06, SEM-08..11 | Much Harder |
| I030 | Boolean transformations | semantic | BL-01, BL-02, BL-03 | Much Harder |
| I031 | Different API same behavior | semantic | API-02, API-03, API-07, API-08, API-09, NU-01, SEM-07 | Much Harder |
| I032 | Recursive vs iterative | semantic | API-04 | Much Harder |

## Appendix D: Level-Scheme Reconciliation

The earlier plan used a slightly different L0–L10 scheme. Everything in it is absorbed by the
combined scheme; this table shows how.

| Earlier plan level | Combined scheme | Notes |
|---|---|---|
| L0 No duplication | L0 `L00_no_duplication` | Same intent; combined adds 6 trap families (vocab/structure/size baits). |
| L1 Exact clone | L1 `L01_exact` | Same; combined adds `ex_position`, `ex_size_ladder`, `ex_multi_cluster`. |
| L2 Whitespace | L2 `L02_whitespace` | Same; combined expands to 13 WS families. |
| L3 Comments | L3 `L03_comments` | Same; combined expands to 11 CM families. |
| L4 Renaming (Type-2) | L4 `L04_rename_literals` (`rn_*`, `ty_*`, `ns_*`) | Merged with literals. |
| L5 Literal changes | L4 `L04_rename_literals` (`lt_*`) | Folded into L4 — literals are the other half of Type-2. |
| L6 Partial clone (Type-3) | L5 `L05_statement_edits` | Same intent (insert/delete/reorder/gap). |
| L7 Noise injection | L5 `st_insert_*` + L9 `mix_noise_heavy` (NZ-01..10) | Noise is an interference *wrapper*, exercised alone at L5 and stacked at L9. |
| L8 Structural | L6 `L06_controlflow_rewrites` + L7 `api_map_loop` | Loop/control-form and functional-vs-imperative differences. |
| L9 Semantic (Type-4) | L7 `L07_api_idioms` + L8 `L08_semantic` | Split into API-idiom (L7) and true semantic/architectural (L8). |
| L10 Mixed | L9 `L09_mixed` + L10 `L10_adversarial` | Split into compound-interference (L9) and edge/topology traps (L10). |

The combined scheme is a strict superset: it preserves every earlier level's intent while
adding a cleaner Type-1→2→3→4 progression, a dedicated adversarial tier, and per-family
granularity that makes capability reporting possible.

---

*End of combined plan.*
