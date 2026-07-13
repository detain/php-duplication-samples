# Schema Reference

Formal JSON Schemas (draft-07) for the corpus metadata. `gen/verify.php` validates
every `set.json` / `expected.json` / `manifest.json` against these before a set counts
as done. The normative shapes come from `plan_samples.md` §7.

| File | Validates |
|---|---|
| `set.schema.json` | each set's `set.json` (the set descriptor, R4) |
| `expected.schema.json` | each set's `expected.json` (line-accurate ideal answers, R5) |
| `manifest.schema.json` | the generated `testsets/manifest.json` global index (§7.3) |

Every field also carries a `$comment` in the schema itself; this page is the prose companion.

---

## `set.json` — set descriptor

| Field | Type | Meaning |
|---|---|---|
| `schema_version` | int (=1) | Schema revision. |
| `set_id` | string | Canonical id `L{NN}-{family}-{NNN}`; **must** match the directory path. |
| `level` | int 0–10 | Difficulty tier. |
| `level_name` | string | Human name of the tier (e.g. `whitespace_variation`). |
| `family` | string | Variation-family slug (e.g. `ws_blank_inside`). |
| `title` / `description` | string | Human-readable provenance (Set-Builder owns these). |
| `language` | `"php"` | Corpus is PHP-only. |
| `min_php` | string | Minimum PHP the files require (`8.1`). |
| `seed` | string \| null | Seed domain the clone came from; `null` for L0 sets. |
| `difficulty_band` | enum | `baseline\|trivial\|easy\|medium\|hard\|very_hard\|expert`, derived **mechanically** from `difficulty.score`. |
| `files[]` | array | One entry per `src/` file: `path`, `role` (`carrier\|distractor\|clean`), `duplicate_group` (label or `null`), `sloc`. |
| `duplication` | object | `present`, `clone_type` (`none\|type-1..4\|semantic-domain`), `granularity` (`file\|class\|method\|function\|block\|statement-run` or `null` for L0), `clusters`, `instances`. |
| `interference[]` | array | Applied codes: `code` (registry), `name`, `params` (reproduction-sufficient), `applied_to` (src paths). `[]` for L0/L1. |
| `difficulty` | object | `score` (0–100, computed by the generator), `requires` (normalization-capability vocabulary). |
| `expected_detection_by_tool` | object (optional) | Convenience `tool → bool` hint; authoritative per-*class* expectation lives in `expected.json`. |
| `generator` | object | `tool`, `recipe`, `version`, `rng_seed` (determinism). |

### `difficulty.score` formula (§7.1)

```
score = clamp(0..100, level_base[level] + Σ interference_weight[code] + intensity_bonus)
```

`level_base` and per-code `weight` live in `gen/transforms/registry.json`; `band_cutoffs` there
map the score to `difficulty_band`. Never hand-edit the score — the generator computes it.

### `difficulty.requires` vocabulary

`whitespace_normalization`, `comment_stripping`, `identifier_canonicalization`,
`literal_abstraction`, `ast_canonicalization`, `commutative_reordering`,
`controlflow_normalization`, `deadcode_elimination`, `api_abstraction`, `inline_expansion`,
`cfg_comparison`, `semantic_reasoning`.

---

## `expected.json` — ideal answers

| Field | Type | Meaning |
|---|---|---|
| `schema_version` | int (=1) | |
| `set_id` | string | Must equal `set.json.set_id`. |
| `clusters[]` | array | Each duplicate cluster (`[]` for L0). |
| `non_duplicates[]` | array | Regions that must **not** be reported (false-positive bait). |
| `scoring` | object | `line_tolerance`, `member_jaccard_min`, `min_members_for_credit`. |

### `clusters[]` entry

| Field | Meaning |
|---|---|
| `id` | Cluster id (`c1`, …). |
| `group_id` | Matches a `set.json` `files[].duplicate_group` label. |
| `clone_type` | `type-1..4` \| `semantic-domain`. |
| `granularity` | `file\|class\|method\|function\|block\|statement-run`. |
| `normalized_by` | Ordered normalization stages that make the members equal (`whitespace`, `comments`, `identifiers`, `literals`, `types`, `namespaces`, `controlflow`, `api`, `semantic`). Drives `gen/verify.php` positive-proof. |
| `token_hash` / `normalized_hash` | Content fingerprints of the pristine member (dedup / drift detection). |
| `members[]` | `file`, `start_line`, `end_line` (1-based inclusive), `symbol`, `pristine`. Optional `fragments[]` (gapped/partial clones) and `drift_generation` (genealogy). Maps directly onto `bench/run.php` ground-truth clusters. |
| `detection_expectation` | Which detector **class** should find it: `text_based`, `token_based`, `ast_based`, `metric_based`, `semantic`. Separates "missed" from "not expected by design" in the reports. |
| `notes` | Why the members are equivalent (doubles as LLM-judge rationale at L8; records clone token/line size for threshold families). |

### `non_duplicates[]` entry

`file`, `start_line`, `end_line`, `reason`, `trap` (bool — `true` bait counts toward
false-positive-resistance), optional `known_tool_fp` (acceptable boilerplate a real token tool
flags).

---

## `manifest.json` — generated index

Built by `gen/manifest.php`; do not hand-edit. `schema_version`, `generated_at`, `totals`
(`levels`/`families`/`sets`/`files`/`clusters`), `corpus_stats`, and `levels[]` →
`families[]` → `sets[]` with per-set `clusters`/`files`/`difficulty`.
