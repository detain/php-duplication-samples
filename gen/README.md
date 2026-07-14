# Generator (`gen/`)

The deterministic generator that assembles the graduated duplication test sets under
`testsets/`. It applies parameterized transforms to hand-written seed payloads and emits
line-accurate ground truth **mechanically**, so answers can never drift out of sync with the
code (`plan_samples.md` §11).

> **Dependency policy.** `gen/` depends on `nikic/php-parser` (**dev-only**, via the root
> `composer.json`). The corpus files under `testsets/` are **dependency-free** — nothing there
> ever loads `vendor/`. Run `composer install` once before using the generator.

## Entry points

| Command | Does |
|---|---|
| `php gen/build.php --set=<id>` | Build one set into `testsets/`. |
| `php gen/build.php --family=<slug>` / `--level=<n>` / `--all` | Build a family / level / everything. |
| `php gen/build.php --check` | Regenerate in memory and byte-diff against the committed tree; nonzero exit on drift (CI gate). |
| `php gen/verify.php --set=<id>` / `--level=<n>` / `--all` | The §12 QA pass (8 checks). Nonzero exit on any failure. |
| `php gen/manifest.php` | Rebuild `testsets/manifest.json` + `bench/corpora/testsets.ground-truth.json`. |
| `php bench/run-testsets.php --set=<id>` | Run detectors against a set and score vs `expected.json`. |

## Pipeline

```
recipe (family JSON, lists 5+ set specs)
  -> for each set spec:
       mt_srand(rng_seed)                                   # determinism
       load seed payload region (sentinels stripped)
       for each carrier:
         mount (method/function) -> apply transform chain -> re-indent -> insert at scaffold marker
         record payload start/end line during render        # ground truth, never hand-typed
       copy distractor + clean assets (strip near-miss markers, record the trap region)
       emit src/ files, set.json, expected.json
  -> gen/manifest.php: rebuild manifest + aggregated bench ground truth
```

The renderer computes each cloned region's `start_line`/`end_line` from the scaffold prefix
length plus the transformed payload's line count — so line numbers are always exact, even after
wraps/insertions.

## The transform interface

Every transform implements `Gen\Transforms\Transform`:

```php
interface Transform {
    public function code(): string;   // registry code, e.g. "WS-03"
    public function name(): string;   // e.g. "blank_inside"
    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult;
}
```

- `TransformInput`  — `{ lines: list<string>, lineMap: list<int> }` (the payload region, one
  string per line, plus a map from each output line back to its source line index).
- `TransformResult` — `{ lines, lineMap }`; each `lineMap` entry is the source line index the
  output line came from, or `-1` for a line the transform inserted.

The line map lets the renderer (and later, gapped/partial-clone modeling) compute exact
ground-truth ranges after any wrap or insertion.

### Transform kinds (`gen/transforms/registry.json` → `kind`)

- **text** (WS/CM) — operate on the `token_get_all` stream via `Gen\Lib\PhpTokens` so they never
  split inside a string literal, heredoc, or multi-line comment. Implemented: `WS-03`
  (`Ws/BlankInside`), `WS-06` (`Ws/LineWrap`), `CM-03` (`Cm/Docblock`).
- **ast** (RN/LT/ST) — use `Gen\Lib\AstAnalyzer` (nikic) to *decide what to edit* (which
  identifiers are local variables, which tokens are numeric literals, where statements end), then
  apply the edit at the token/line level so formatting is preserved exactly and no second
  (whitespace) axis is introduced. Implemented: `RN-01` (`Rn/LocalVars`), `LT-01` (`Lt/Numeric`),
  `ST-01` (`St/InsertLogging`).
- **selector** (CF/API/BL/NU/SEM) — do not compute a rewrite; they pick a hand-written,
  behaviorally-equivalent variant body from `gen/seeds/<seed>/variants/<name>.php`
  (`Selector/VariantSelector`). Behavioral equivalence is proven by the seed's
  `equivalence_test.php` (§12.4).
- **wrapper** (NZ/UQ) — noise injection transforms that insert semantically inert but textually
  real code. NZ transforms are pure instrumentation (logging, metrics, assertions). UQ transforms
  inject unique business logic that preserves behavioral equivalence but erodes shared code
  from within.
- **encoding** (ENC) — byte-level encoding transforms that alter how source text is represented
  without changing its semantic content (BOM, NBSP, Unicode confusables, EOL styles, escape
  sequences).
- **topology** (CP) — cluster topology directives for multi-cluster sets, not actual transforms.
  Handled by SetBuilder's multi-cluster assembly logic; no Transform class required.

The registry lists **all** §8 codes with weights (for the difficulty formula) and marks which are
`implemented` in this build; the rest are declared for later phases.

### New transform groups (Wave 2)

- **SY** (PHP-era modernization, SY-01..SY-10) — text transforms that modernize old PHP 5.x
  syntax to PHP 8.x equivalents: positional→named arguments, anonymous→arrow functions,
  array()→[], concatenation→interpolation, Yoda conditions, list()→[] destructuring, etc.
  (`gen/transforms/Sy/`).
- **LEG** (Legacy PHP syntax, LEG-01..LEG-10) — text/ast transforms for migrating deprecated
  PHP features: pow()→**, eregi→preg, split→preg_split, each→foreach, mcrypt→openssl,
  constructor property promotion, etc. (`gen/transforms/Leg/`).
- **UQ** (Unique-code injection, UQ-01..UQ-08) — wrapper transforms that inject tracked,
  behavior-preserving unique code into clones to support partial duplication detection:
  per-currency rounding, audit timestamps, bounds clamping, pre/post-processing hooks.
  All emit `unique_segments[]` metadata. (`gen/transforms/Uq/`).
- **NZ** (Noise injection, NZ-01..NZ-10) — wrapper transforms that insert semantically inert
  instrumentation: logging, metrics, security assertions, framework attributes, i18n wrappers,
  feature flags, debug dumps, environment checks, timing, request context. (`gen/transforms/Nz/`).
- **ENC** (Encoding normalization, ENC-01..ENC-07) — encoding transforms at the byte level:
  BOM insertion/removal, NBSP indentation, Unicode confusables, mixed EOL, escape sequence
  variation, heredoc↔double-quoted strings, tabs in strings. (`gen/transforms/Enc/`).
- **CP** (Clone topology, CP-01..CP-09) — metadata directives for multi-cluster sets specifying
  cluster count, topology, instance ladder, intra-file duplication, cross-seed clusters,
  overlapping regions, subset relationships, twin clusters, and confusable clusters.
  Not actual transforms; handled by SetBuilder assembly logic.

## Authoring guide

### A new transform

1. Add a class under `gen/transforms/<Group>/` implementing `Transform`.
2. Add its file to `gen/bootstrap.php`.
3. Register it in `gen/transforms/registry.json` with `implemented: true`, a `class`, a `weight`
   (WS/CM ≈ 2–4, RN/LT ≈ 5–8, ST ≈ 8–12, CF/BL/API ≈ 12–18, SEM ≈ 18–25), `kind`, `clone_type`,
   and `requires`.
4. Keep it deterministic (draw only from the passed `Rng`) and syntax-safe.

### A new seed (`gen/seeds/<name>/`)

- `payload.php` — a valid PHP file whose clonable region is wrapped in sentinels:
  ```php
  // <<<PAYLOAD:name>>>
  public function doThing(...): ... { ... }
  // <<<END-PAYLOAD>>>
  ```
  Store the region in **method form** (`public function …`); the builder strips the visibility
  keyword when mounting into a `function` scaffold. Keep the region ≥ ~70 significant tokens so it
  clears common detector floors (§8.1) unless the family is a deliberate sub-threshold rung.
- `seed.json` — `domain`, `size_class`, `symbol`, line/token counts, `mount_modes`,
  `compatible_interference`, `variants[]`.
- `variants/<code_name>.php` — hand-written rewrites for CF/API/SEM families, each wrapped in the
  same sentinels.
- `equivalence_test.php` — runs the pristine payload and every variant against shared inputs and
  asserts identical output. Must exit 0. `gen/verify.php` runs it for L6+ sets.

### A new scaffold (`gen/scaffolds/<name>.php`)

A host-file template with `__NAMESPACE__` / `__CLASS__` placeholders and a single `<<<INSERT>>>`
marker line whose indentation sets the payload indentation. Include **unique** filler code (the
3 carriers of a set must use 3 different scaffolds so their non-clone regions don't accidentally
match). Method scaffolds mount methods; `fn_*` scaffolds mount standalone functions.

### A distractor / clean asset (`gen/distractors/<name>.php`)

Same placeholders. A near-miss distractor marks its tempting region with
`// <<<NEARMISS>>> … // <<<END-NEARMISS>>>` (stripped at render; the region is recorded as a
`trap`). Tune near-misses to high bag-of-tokens overlap with the clone but **no ≥40-token
contiguous normalized run** (the verifier enforces this). Docblocks and names must be neutral —
never mention clone/near-miss/role/level (hygiene lint).

### A recipe (`gen/recipes/L{NN}/<family>.json`)

One JSON per family. Header: `level`, `level_name`, `level_dir`, `family`. Then `sets[]`, each:
`set_id`, `title`, `description`, `seed`, `mount`, `clone_symbol`, `granularity`, `clone_type`,
`normalized_by`, `rng_seed`, `notes`, `detection_expectation`, optional `scoring`, and
`carriers[]` / `distractors[]` / `cleans[]`. A carrier lists its `scaffold`, `namespace`,
`class`, `file`, `group`, `pristine`, optional `variant`, and a `transforms[]` chain of
`{code, params}` (or `{code, variant}` for selectors).

> **One writer per file.** In a batch, at most one Set-Builder per family (the recipe file is a
> single file). Shared substrate (schemas, transforms, seeds, scaffolds, distractors) is created
> in Foundation and is read-only to builders (`plan_samples.md` §18.6).

## Determinism

`mt_srand(rng_seed)` is called once per set. `php gen/build.php --check` regenerates everything
to memory and byte-diffs against the committed tree — wire it into CI alongside
`php gen/verify.php --all`.
