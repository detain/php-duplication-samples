# L4 — Partial Duplication (`L04_partial_duplication/`)

**What it tests:** Detection of Type-3 partial clones where only a subset of lines are duplicated — clones with gaps, insertions, or deletions within the cloned region.

This is distinct from `L04_rename_literals` (Type-2: identifier renaming) and `L05_statement_edits` (Type-3: statement-level insertions/deletions). Partial duplication tests the ability to find **subsequence matches** — clones that share most lines but not all.

## Concept

A partial clone has a **shared core** (lines that are duplicated across carriers) and **unique fragments** (lines that are specific to one carrier). The unique fragments break token-based detectors that require full contiguous matches.

```
Carrier A (pristine):   function foo() { [AAA] [AAA] [AAA] [AAA] }
Carrier B (head trim):         [UNIQUE] [AAA] [AAA] [AAA]
Carrier C (tail trim):   [AAA] [AAA] [AAA]        [UNIQUE]
```

The detector must recognize that `[AAA]` is the shared clone even though it appears at different positions.

## Families

| Family | Description | Interference |
|--------|-------------|--------------|
| `pd_minimal_insertion` | 1-2 unique lines at head or tail | ST-07 partial_fragment |
| `pd_head_unique` | Unique lines only at start of method | ST-07 |
| `pd_tail_unique` | Unique lines only at end of method | ST-07 |
| `pd_middle_unique` | Unique lines in the middle (gaps) | ST-07 |
| `pd_maximal_insertion` | Many unique lines interspersed | ST-07 |
| `pd_overlapping` | Clone regions that partially overlap | ST-07 + topology |
| `pd_fragments` | Very short shared fragments | ST-07 + size |
| `pd_boundary` | Clone fragment at method boundary | ST-07 |
| `ct_two_cluster` | Two distinct clone clusters in same set | topology |
| `ct_three_cluster` | Three distinct clone clusters | topology |
| `ct_ratio_varied` | Different duplication ratios per carrier | size |
| `gr_method` | Method-granularity partial clones | granularity |
| `sz_xs` | Extra-small clone fragments | size |
| `sz_s` | Small clone fragments | size |

## How set.json Describes Partial Duplication

The `interference` array captures the partiality transform:

```json
"interference": [
    {
        "code": "ST-07",
        "name": "partial_fragment",
        "params": {
            "head_trim": 2,
            "tail_trim": 0
        },
        "applied_to": ["src/CarrierB.php"],
        "intensity": 3
    }
]
```

`region_sloc` in `set.json` breaks down each carrier's region:

```json
"region_sloc": {
    "sloc": 24,
    "duplicate": 24,   // lines shared with other carriers
    "unique": 0,       // lines unique to this carrier
    "filler": 0        // non-clone filler lines in region
}
```

## How expected.json Captures Ground Truth

The `start_line`/`end_line` in expected.json point to the **full carrier region**, but `normalized_by` includes `identifiers` and `literals` — a partial clone detector must use structural matching (AST or semantic) to find the shared subsequence.

```json
"normalized_by": [
    "whitespace",
    "comments",
    "identifiers",
    "literals"
]
```

`detection_expectation` for partial clones:

```json
"detection_expectation": {
    "text_based": false,      // requires structural matching
    "token_based": true,      // AST/token approach can work
    "ast_based": true,
    "metric_based": true,
    "semantic": true
}
```

## Running Verification

```bash
# Verify a specific partial duplication set
php gen/verify.php --set=L04-pd_minimal_insertion-001

# Verify all L04_partial_duplication sets
php gen/verify.php --level=4

# Build and verify
php gen/build.php --level=4
php gen/verify.php --level=4
```

## Difficulty

`requires`: `ast_canonicalization`, `deadcode_elimination`
`difficulty_band`: medium to hard (varies by family)

Partial duplication is harder than exact or whitespace-only clones because:
1. No single carrier is a pristine reference
2. The clone region's boundaries are fuzzy
3. Unique fragments create non-contiguous matches

## Current Status

_14 families built across multiple sub-levels (L04_partial_duplication, L04_noise_probing, L04_refactorability_ etc)._
