# Graduated Duplication-Detection Test Sets (`testsets/`)

A generator-first corpus of **difficulty-graded** benchmark sets for PHP code-duplication
detection, with machine-readable per-set metadata (`set.json`) and **line-accurate ideal-answer
ground truth** (`expected.json`). It complements — and never modifies — the category-reference
`samples/` tree.

> **Status: foundation / Wave-1 complete.** The engine, schemas, verifier, bench runner, and one
> pilot set per pipeline path are built and green. The full ~540-set corpus is generated wave by
> wave against this substrate (see `ORCHESTRATION.md`). This README describes the whole design;
> the counts below reflect what is currently on disk (2026-07-13).

## North-star

After running a detector against this corpus you should be able to state **precisely** what it
can and cannot detect, where its size/token thresholds blind it, and where it raises false
positives. Every design choice — single-axis levels, near-miss distractors, per-cluster
`detection_expectation`, threshold-boundary sets — exists to make that capability profile fall
out of the scores (`plan_samples.md` §1, R8).

## The L0–L10 ladder

| Level | Dir | Clone type | What it isolates |
|---|---|---|---|
| L0 | `L00_no_duplication` | none | Negative controls — a perfect tool reports **zero** clusters. |
| L1 | `L01_exact` | type-1 | Byte-identical clones (calibrates the harness). |
| L2 | `L02_whitespace` | type-1 | Whitespace & layout only. |
| L3 | `L03_comments` | type-1 | Comment variation only. |
| L4 | `L04_rename_literals` | type-2 | Renames, literals, types, namespaces. |
| L5 | `L05_statement_edits` | type-3 | Insertions, deletions, reordering, gaps. |
| L6 | `L06_controlflow_rewrites` | type-3/4 | Control-flow & expression rewrites. |
| L7 | `L07_api_idioms` | type-4 | API / idiom substitution. |
| L8 | `L08_semantic_idioms` (+ `L08_semantic_variants`) | type-4/domain | Semantic / architectural duplication. |
| L9 | `L09_mixed_interference` | mixed | Compound interference (stacked axes). |
| L10 | `L10_adversarial_edge_cases` | mixed/edge | Threshold, overlap, encoding, topology traps. |

Each level's single-axis design (`plan_samples.md` §4 D5) means a miss **localizes the exact
missing capability**: a pass at L1 plus a miss at L4-`rn_locals` says "no identifier
canonicalization" and nothing else.

## Layout

```
testsets/
├── README.md            ← you are here
├── ORCHESTRATION.md     ← how the corpus is (re)built by agents (plan §18 + §20, verbatim)
├── manifest.json        ← generated global index (gen/manifest.php)
├── schema/              ← JSON Schemas + field reference (schema/README.md)
└── L{NN}_<name>/
    └── <family>/<NNN>/
        ├── set.json     ← this set's metadata + provenance
        ├── expected.json← this set's ideal answers (+ rationale notes)
        └── src/         ← the ONLY thing a detector is pointed at
```

A **standard set = 5 files**: 3 `carrier`s (each holds one member of the duplicate cluster,
surrounded by different code), 1 near-miss `distractor` (false-positive bait, listed under
`expected.json → non_duplicates`), and 1 unrelated `clean` filler. File names inside `src/` are
realistic domain names — role/level/duplication information lives **only** in `set.json`, so a
tool (or LLM) cannot cheat by reading names.

## Running detectors

```bash
composer install                                   # once, for the generator's dev dependency
php bench/run-testsets.php --set=L01-ex_function-001
php bench/run-testsets.php --level=2
php bench/run-testsets.php --all
```

The runner invokes `phpcpd` (via `bench/tools/phpcpd.phar`) and `jscpd`, normalizes each tool's
output to `{file,start,end}` member groups, and scores against every set's `expected.json` using
member-set matching (±`line_tolerance`, member Jaccard ≥ `member_jaccard_min`,
`min_members_for_credit`). It captures each tool's **version + flags** into
`bench/results/testsets-<label>.json` for reproducibility. A set's scores are designed to be
*consistent with its `detection_expectation`* — token tools pass L1–L2 but are not punished for
missing L6.

## Regenerating / extending

```bash
php gen/build.php --all       # (re)assemble every set from recipes
php gen/build.php --check     # byte-diff vs committed tree (determinism gate)
php gen/verify.php --all      # the 8-check QA pass (plan §12)
php gen/manifest.php          # rebuild manifest.json + bench ground truth
```

See `gen/README.md` for how to author a new transform / seed / scaffold / distractor / recipe.
See `ORCHESTRATION.md` for the parallel-agent build → review → fix workflow and the non-negotiable
rules (work in place, never run git, stay in scope).

## A note on `samples.json`

`samples.json` is stale relative to the `samples/` tree (3 directory-only categories are missing
from its index, and `challenges[]` is empty). Repairing that index is an **Integration**-phase
task; it touches only the index, never any `samples/` file content.
