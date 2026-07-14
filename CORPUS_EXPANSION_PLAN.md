# Corpus Expansion Plan
**Status:** PLANNED — not yet built  
**Author:** Research phase output  
**Date:** 2026-07-13  
**Corpus baseline:** 540 sets / 97 families / 3 seeds / 108+ recipes / 118 transform codes

---

## Table of Contents

1. [Mixed-Variation Sets](#1-mixed-variation-sets) — the user's primary request
2. [New Seeds Needed](#2-new-seeds-needed)
3. [New Transform Families](#3-new-transform-families)
4. [Structural Variations](#4-structural-variations)
5. [Anti-Pattern Sets](#5-anti-pattern-sets)
6. [20+ Concrete Recipe Concepts](#6-concrete-recipe-ideas-20)
7. [Priority Order & Dependencies](#7-priority-order--dependencies)
8. [Summary](#8-summary)

---

## 0. Current State Baseline

### 0.1 What exists

| Asset | Count | Notes |
|---|---|---|
| Levels | L00–L10 | 11 difficulty tiers |
| Sets (planned) | 540 | Across all levels, 97 families |
| Sets (current) | ~540 on disk | In various states of completion |
| Seeds | 3 | `invoice_totals`, `csv_import`, `access_guard` |
| Recipes | 108+ | One JSON per family |
| Transform codes | 118 | WS/CM/RN/LT/TY/NS/ST/CF/BL/API/SEM/NZ/ENC/CP |
| Families | 97 | Level → family → set hierarchy |

### 0.2 Current seed domains

| Seed | Domain | Size | Symbol | Key compatibility |
|---|---|---|---|---|
| `invoice_totals` | Billing line-item math | M (24l, 170t) | `computeTotals` | WS-03, WS-06, CM-03, RN-01/02, LT-01, ST-01, API-01/06 |
| `csv_import` | Delimited row parsing | M (22l, 160t) | `parseRow` | WS-03, WS-06, CM-03, RN-01/02, LT-02, ST-01 |
| `access_guard` | Resource authorization | M (20l, 150t) | `authorize` | WS-03, CM-03, RN-01, CF-02/03/05, BL-02 |

### 0.3 Current L9 mix recipe families (existing)

The current `mix_*` families in L09 are:
- `mix_ws_cm` — WS-* + CM-* (Type-1 compound)
- `mix_rn_ws` — RN/LT + WS (Type-2 + layout)
- `mix_rn_cm_ws` — 3-axis full reformat+rename
- `mix_st_rn` — Type-3 edits + renames
- `mix_cf_rn_ws` — rewrite + rename + layout
- `mix_noise_heavy` — NZ-01..10 stacked
- `mix_4plus` — ≥4 axes
- `mix_realistic_drift` — 18-month maintenance narrative

### 0.4 L10 adversarial families (existing)

- `adv_near_miss`, `adv_below_threshold`, `adv_boundary`, `adv_overlap`, `adv_same_file`, `adv_html_mix`, `adv_generated`, `adv_encoding`, `adv_large_files`, `adv_many_files`, `adv_genealogy`

---

## 1. Mixed-Variation Sets

**The user's primary request.** These are sets where 10–20 variation axes are applied simultaneously to the same base code, but the underlying code remains detectably duplicate. This is distinct from the current `mix_*` families which apply 2–4 stacked transforms.

### 1.1 Full-Stack Polyglot Variation (mix_polyglot)

**What it is:** A single set where every carrier applies a *different* combination of 4–8 interference axes simultaneously, creating a "messy copy-paste that went through years of independent maintenance" pattern.

**Design:**
- 3 carriers: A gets WS-06 + CM-03 + RN-05; B gets WS-03 + LT-01 + CF-03; C gets RN-01 + CM-07 + ST-01 + WS-06 + RN-05
- Each carrier is a valid Type-3/4 clone after normalization
- The ground truth records all applied codes: `["WS-06","CM-03","RN-05","WS-03","LT-01","CF-03","RN-01","CM-07","ST-01"]`

**Why it matters:** Tests detectors on real-world "copy-paste-edit" scenarios where every copy evolved differently. Current `mix_4plus` sets apply the *same* stacked axes to all carriers — this is the asymmetric version.

**Existing recipe to build on:** `gen/recipes/L09/mix_4plus.json` — extend with asymmetric intensity variation.

### 1.2 Drift Genealogy (mix_genealogy_detailed)

**What it is:** The existing `adv_genealogy` family uses a 3-carrier chain with annotations about drift generation. This new family formalizes and extends it: a 4-carrier A→B→C→D chain where each hop adds one more transform, accumulating changes.

**Design:**
- Carrier A: pristine (no transforms)
- Carrier B: A + WS-03 (one whitespace change)
- Carrier C: B + RN-05 (whitespace + case convention)
- Carrier D: C + CM-03 + ST-01 (whitespace + rename + one logging line)
- Ground truth: one cluster with `drift_generation` metadata on each member (0, 1, 2, 3)
- `detection_expectation` should show degrading Jaccard similarity: A↔B ~0.9, B↔C ~0.7, C↔D ~0.5

**Why it matters:** Models the actual copy-paste genealogy that exists in real codebases. Existing genealogy sets note "infrastructure gap: SetBuilder does not support drift chain topology" — this formalizes it.

**Recipe:** `gen/recipes/L10/adv_genealogy.json` already exists with 5 sets using 3-carrier chains. New sets would add 4-carrier chains with explicit `drift_generation` metadata.

### 1.3 Size-Ratio Extremes (mix_size_ratio)

**What it is:** Sets where the ratio of unique-to-duplicate code varies dramatically, to test how detectors behave when clone regions are tiny vs. enormous relative to surrounding unique code.

**Variants:**
- `mix_size_tiny_clone_big_surrounding`: duplicate region = 5 lines, surrounding unique code = 200 lines per carrier (clone buried in noise)
- `mix_size_big_clone_tiny_surrounding`: duplicate region = 120 lines, surrounding unique code = 10 lines per carrier (clone dominates)
- `mix_size_mixed_ratio`: one carrier has small clone+big surround, another has big clone+small surround, third has medium clone+medium surround

**Why it matters:** phpcpd's `--min-lines` default of 5 means tiny clones are invisible by default. The current `ex_size_ladder` addresses threshold *detection* but not the effect of clone/surround *ratio* on region reporting accuracy.

**Recipe concept:** New `gen/recipes/L09/mix_size_ratio.json` family, 5 sets.

### 1.4 Refactoring Pairs (mix_refactor_candidates)

**What it is:** Pairs of code that represent the same logic — one "as written" (with duplication) and one that shows the refactored extraction into a shared function with a parameter.

**Design:**
- Carrier A: the duplicated code as-is (imperative loop, repeated logic)
- Carrier B: calls a shared helper function `normalizeLineItems(array $items, float $rate): array` with different arguments
- The extracted function *body* is the clone; carriers A and B both reference it
- For detection purposes: the *call pattern* to the helper is the clone across carriers
- Ground truth: one cluster where members are the callsites (not the function body)

**Why it matters:** This bridges duplication detection and refactoring opportunity detection. It tests whether a tool can identify that duplicated code *could* be extracted, not just that it exists.

**Note:** This requires a new seed with an extractable helper pattern, or a new interference pattern where the "transform" is extracting a shared function.

### 1.5 Asymmetric Multi-Transform Intensity (mix_asymmetric_heavy)

**What it is:** Rather than A/B/C getting similar-intensity transforms (the current `mix_4plus` pattern), the set has one near-pristine clone, one medium-modified clone, and one heavily-modified clone — where "heavy" means all 4 axes applied at maximum intensity.

**Design:**
- Set 001: A = pristine; B = RN-05 + CM-03; C = RN-05 + CM-03 + WS-03 + WS-06 + ST-01
- Set 002: A = WS-03 only; B = WS-03 + RN-05; C = WS-03 + RN-05 + LT-01 + CM-03 + ST-01
- Set 003: A = ST-01 (1 line); B = ST-01 + CM-03 + WS-06; C = ST-01 + CM-03 + WS-06 + RN-05 + LT-01 + RN-01

**Why it matters:** The hardest clone pair (pristine vs. heavy) is what challenges detectors. This systematically probes whether detectors can match across a wide range of divergence levels.

**Recipe concept:** Extend `gen/recipes/L09/mix_4plus.json` sets 001–005 with asymmetric rather than symmetric stacking.

---

## 2. New Seeds Needed

The corpus currently has 3 seeds. The plan calls for ~30 seeds at launch. Missing realistic PHP domains:

### 2.1 `http_client` — HTTP Request/Response Normalization

**Use case:** Batching, retry, header-normalization logic reused across API clients.  
**Size class:** M (30–50 lines)  
**Clone symbol:** `normalizeResponse`  
**Payload sketch:**
```php
public function normalizeResponse(Response $response): array {
    $body = json_decode($response->getBody()->getContents(), true);
    $status = $response->getStatusCode();
    if ($status >= 200 && $status < 300) {
        return ['ok' => true, 'data' => $body, 'status' => $status];
    }
    return ['ok' => false, 'error' => $body['message'] ?? 'Unknown', 'status' => $status];
}
```
**Compatible interference:** RN-01, LT-02, CF-03, ST-01, API-03, SEM-04  
**Why needed:** HTTP client logic is one of the most-duplicated patterns in real PHP codebases. Current seeds are math/authorization — no HTTP layer.

### 2.2 `validation_rule` — Input Validation Chain

**Use case:** Multi-field form validation where validation rules are repeated across entities.  
**Size class:** M–L (40–80 lines)  
**Clone symbol:** `validate` or `applyRules`  
**Payload sketch:**
```php
public function validate(array $data, array $rules): array {
    $errors = [];
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? null;
        if ($rule['required'] && ($value === null || $value === '')) {
            $errors[$field][] = "{$field} is required";
        }
        if ($value !== null && $rule['type'] === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$field][] = "{$field} must be a valid email";
        }
        if ($value !== null && $rule['type'] === 'numeric' && !is_numeric($value)) {
            $errors[$field][] = "{$field} must be numeric";
        }
    }
    return $errors;
}
```
**Compatible interference:** RN-01, LT-02, ST-03, CF-02, BL-01, API-05, SEM-01  
**Why needed:** Validation logic is a prime source of copy-paste duplication. The current seeds don't cover this domain.

### 2.3 `cache_manager` — Cache Get/Put/Invalidate Pattern

**Use case:** Cache-aside pattern with get/put/invalidate repeated across services.  
**Size class:** S–M (20–35 lines)  
**Clone symbol:** `getCached` or `cacheFetch`  
**Payload sketch:**
```php
public function getCached(string $key, callable $loader, int $ttl = 3600): mixed {
    $cached = $this->cache->get($key);
    if ($cached !== null) {
        return $cached;
    }
    $value = $loader();
    $this->cache->set($key, $value, $ttl);
    return $value;
}
```
**Compatible interference:** RN-01, RN-04, ST-01, NZ-02, NZ-09, API-04, SEM-05  
**Why needed:** Caching is an extremely common duplication source. This seed enables NZ noise injection sets and API idiom substitution.

### 2.4 `pager` — Pagination Calculation

**Use case:** Offset/limit pagination math reused in data access layers.  
**Size class:** S (15–25 lines)  
**Clone symbol:** `paginate`  
**Payload sketch:**
```php
public function paginate(int $total, int $page, int $perPage): array {
    $totalPages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    return [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_next' => $page < $totalPages,
        'has_prev' => $page > 1,
    ];
}
```
**Compatible interference:** RN-01, LT-01, TY-01, TY-02, ST-09  
**Why needed:** Pagination math is notoriously copy-pasted. Small seed (S class) useful for threshold and size-ladder families.

### 2.5 `state_transition` — State Machine Transition Guard

**Use case:** Order/workflow state machines where transition guards are duplicated across entities.  
**Size class:** M (30–45 lines)  
**Clone symbol:** `canTransition`  
**Payload sketch:**
```php
public function canTransition(string $from, string $to, array $context = []): bool {
    $allowed = [
        'draft' => ['submitted', 'cancelled'],
        'submitted' => ['approved', 'rejected'],
        'approved' => ['fulfilled'],
        'rejected' => ['draft'],
        'fulfilled' => [],
        'cancelled' => ['draft'],
    ];
    if (!isset($allowed[$from])) { return false; }
    if (!in_array($to, $allowed[$from], true)) { return false; }
    if ($from === 'submitted' && ($context['amount'] ?? 0) > 10000) {
        return false; // requires approval for large amounts
    }
    return true;
}
```
**Compatible interference:** RN-01, LT-02, BL-01, BL-03, CF-03, SEM-09, SEM-11  
**Why needed:** Supports `sem_state_machine` and `bl_*` families. State machine logic is common in business logic code.

### 2.6 `json_serializer` — Object-to-Array Serialization

**Use case:** DTO-to-JSON serialization logic duplicated when converting entities to API responses.  
**Size class:** M (30–50 lines)  
**Clone symbol:** `toArray`  
**Payload sketch:**
```php
public function toArray(): array {
    return [
        'id' => $this->id,
        'name' => $this->name,
        'email' => $this->email,
        'status' => $this->status->value,
        'created_at' => $this->createdAt->format('c'),
        'updated_at' => $this->updatedAt->format('c'),
        'tags' => array_map(fn($t) => $t->name, $this->tags->toArray()),
    ];
}
```
**Compatible interference:** RN-01, RN-04, LT-02, API-08, SEM-07, ST-01  
**Why needed:** The `api_serialization` family needs a typed object serialization seed. The current invoice_totals seed is better for this than nothing, but a dedicated serializer seed would be more targeted.

### 2.7 `query_builder` — SQL WHERE Clause Assembly

**Use case:** Dynamic query building with conditional WHERE clauses — repeated pattern in data access.  
**Size class:** M–L (40–70 lines)  
**Clone symbol:** `buildWhere`  
**Payload sketch:**
```php
public function buildWhere(array $filters, array $allowedFields): string {
    $conditions = [];
    $bindings = [];
    foreach ($filters as $field => $value) {
        if (!in_array($field, $allowedFields, true)) { continue; }
        if ($value === null) {
            $conditions[] = "{$field} IS NULL";
        } elseif (is_array($value)) {
            $placeholders = implode(',', array_fill(0, count($value), '?'));
            $conditions[] = "{$field} IN ({$placeholders})";
            $bindings = array_merge($bindings, array_values($value));
        } else {
            $conditions[] = "{$field} = ?";
            $bindings[] = $value;
        }
    }
    return [implode(' AND ', $conditions), $bindings];
}
```
**Compatible interference:** RN-01, LT-02, ST-03, ST-05, API-03, SEM-07, SEM-11  
**Why needed:** Query builder patterns are a massive source of duplication in PHP applications. This would support `sem_orm_sql` and `api_regex_string` families.

### 2.8 `event_dispatcher` — Event Publication Pattern

**Use case:** Domain event dispatching duplicated across service classes.  
**Size class:** S (15–25 lines)  
**Clone symbol:** `dispatch`  
**Payload sketch:**
```php
protected function dispatch(object $event): void {
    foreach ($this->listeners[$event::class] ?? [] as $listener) {
        $listener($event);
    }
}
```
**Compatible interference:** RN-01, ST-01, SEM-06  
**Why needed:** Supports `sem_event` family. Also a natural companion to the `access_guard` seed — authorization often dispatches events.

---

## 3. New Transform Families

### 3.1 ENC-06 Heredoc/Nowdoc Variation (heredoc_nowdoc)

**Current state:** `ENC-06` is declared in registry.json with weight 6 but `implemented: false`.  
**What it tests:** PHP allows string content as heredoc (`<<<EOT...EOT;`) or nowdoc (`<<<'EOT'...EOT;`) or regular quoted strings. These are semantically equivalent but lexically very different.

**Transform implementation:** 
- In a seed with string literals, replace one double-quoted string `"hello world\n"` with a heredoc containing the same content
- The heredoc spans multiple lines, shifting all subsequent line numbers
- This is both a whitespace and an encoding transformation

**Why important:** Heredoc/nowdoc is a real-world variation that affects line-based clone detectors but not token-based ones. It's a common pattern in Laravel/PHP codebases (route definitions, SQL queries as heredocs).

**Registry code:** Already exists as `ENC-06` in `gen/transforms/registry.json` — needs implementation in `gen/transforms/Enc/Heredoc.php`.

### 3.2 PHPDoc vs Attribute Variation (doctrine_attribute)

**What it tests:** The same type/constraint information can be expressed as a PHPDoc annotation `/** @var int $x */` or as a PHP 8 attribute `#[Type('int')]`. Detectors that strip comments may or may not handle attributes correctly.

**Current status:** CM-10 (`annotation_docblock`) covers `@param`/`@var` annotations but not attributes.

**New interference pattern:** Replace docblock annotations with equivalent PHP 8 attributes on methods/parameters:
```php
// Before:
/** @param positive-int $amount */
/** @return non-empty-string */

// After:
#[Param(type: 'positive-int')]
#[Return(type: 'non-empty-string')]
```

**Why important:** PHP 8 attributes are increasingly common in modern PHP code. Detection tools that handle docblocks but not attributes will produce false negatives.

### 3.3 Named Argument vs Positional Argument (named_positional_args)

**What it tests:** Call sites for the same function can use positional args `foo($a, $b, $c)` or named args `foo(a: $x, b: $y, c: $z)`. For clone detection, these are equivalent after normalization but look very different to string matchers.

**New transform family:** Generate variant call sites where arguments are passed positionally vs. by name. This affects `api_map_loop` (where array_map callbacks can use named args) and any seed with multi-parameter functions.

**Registry addition:** New code, e.g. `SY-01: named_args` in a new `SY` (Syntax) family.

**Why important:** Named arguments are a PHP 8.0 feature rapidly becoming standard in modern PHP codebases. Most PHP clone detectors don't handle this normalization.

### 3.4 Match Expression vs Switch Statement (match_switch_variants)

**What it tests:** `CF-02` (`match_switch`) covers general switch/match equivalence. This more specific pattern tests only the *syntax* difference between a multi-arm `match` and a `switch` — which produce identical results but have very different token streams.

**Current status:** `CF-02` exists as a variant selector in `gen/transforms/registry.json` but requires hand-written variants. The issue: the current `access_guard` seed's `cf_match_guard.php` variant uses guard-style logic, not literal match-vs-switch equivalence.

**New approach:** A dedicated transform that rewrites `switch ($x) { case 'a': $r = 1; break; case 'b': $r = 2; break; default: $r = 0; }` to `match` syntax automatically.

### 3.5 Encapsed String vs Concatenation (encapsed_concat)

**What it tests:** `"Hello, {$name}"` (encapsed) vs `'Hello, ' . $name` (concatenation). These are semantically equivalent in many cases but lexically different.

**New interference code:** `API-10: encapsed_concat` — transforms string interpolation into concatenation and vice versa.

**Why important:** String manipulation is a major duplication source. The `api_strings` (API-02) family already covers `sprintf` vs concat vs interpolation — this would be a more specific sub-variant.

### 3.6 Union Type vs Nullable (union_vs_nullable)

**What it tests:** PHP 8.0 union types `int|string` vs PHPDoc-style nullable `int|null`. The same type constraint expressed two ways.

**Current status:** `TY-02: nullable` handles `?T` vs docblock-only nullability. It does NOT handle union types `int|string` which require PHP 8.0+.

**New interference code:** `TY-03: union_types` — transforms `?int` to `?int` with `?` syntax removed but `null` kept in union, or converts `?int` to `int|null` explicit union.

### 3.7 Trait Use Statements (trait_use_variation)

**What it tests:** The same behavior can be achieved by including a trait in a class vs. copying the trait's methods directly into the class. This is a form of structural equivalence that tests how detectors handle `use TraitName` statements vs. inlined code.

**New interference family:** For the L8 `sem_inline_extract` equivalent: one member uses a trait `use CalculatesTotals;` while another has the trait's methods directly inlined in the class.

**Why important:** Trait usage is a very common PHP pattern. The question of whether a class "duplicates" trait code is a real detection challenge.

### 3.8 Arrow Function vs Anonymous Function (arrow_fn_anon)

**What it tests:** `fn($x) => $x * 2` (arrow function, PHP 7.4+) vs `function($x) { return $x * 2; }` (anonymous function). These are semantically equivalent in most contexts.

**New interference code:** `SY-02: arrow_fn` — transforms anonymous functions to arrow functions where possible.

**Why important:** Arrow functions are increasingly common in modern PHP (Laravel collections, array_map callbacks). A detector that normalizes to AST would see these as equivalent, but token-based tools would see very different token streams.

### 3.9 Named Constructor / Static Factory (named_ctor_factory)

**What it tests:** The same object can be constructed via `new Foo($a, $b)` or via `Foo::create($a, $b)` (static factory). This is a Type-4 semantic equivalence.

**New interference family:** For L7/L8: one member calls the constructor directly, another calls a static factory. The factory method body is semantically equivalent to the constructor body.

**Why important:** This is a common refactoring pattern. Detectors that only find Type-1/2/3 clones will miss this.

### 3.10 Constructor Injection vs Property Injection (di_style_variants)

**What it tests:** The same dependency can be injected via constructor (`private $client; public function __construct(HttpClient $c) { $this->client = $c; }`) or via property injection (`#[Inject] private HttpClient $client;`).

**Current status:** `SEM-05: di_variants` exists and uses variant selectors. However, the current variants likely don't cover property injection specifically.

**Enhancement:** Add a property-injection variant to the `access_guard` seed's `sem_di_style` variants.

---

## 4. Structural Variations

### 4.1 Intra-Method Clone Granularity (st_intra_method)

**What it is:** The clone is inside a method but is NOT the whole method. This is the "partial fragment" scenario — a 10-line block inside a 50-line method is duplicated, but the method as a whole is not.

**Current status:** `ST-07: partial_fragment` exists with weight 12 and is implemented.

**What's missing:** Sets where the partial fragment is at a *different position* in each carrier (top of method in A, middle in B, bottom in C) — currently the position is fixed by the scaffold.

**Recipe concept:** `gen/recipes/L05/st_partial.json` already exists. New sets within this family could vary position systematically.

### 4.2 Nested Clones — Clone Inside Clone (adv_nested_overlap)

**What it is:** A large clone region containing a smaller clone region that also appears elsewhere (independently). This is more systematic than the existing `adv_overlap` which uses CP-02.

**Example topology:**
```
File A: [====== CLONE-X ======]
                 [--CLONE-Y--]
File B: [--CLONE-Y--]
File C: [====== CLONE-X ======]
```
CLONE-Y appears in A (inside X) and B (standalone). CLONE-X appears in A and C.

**Why important:** Real-world nested clones are common (e.g., a generic "validate not null" helper inside a "validate email format" function that gets copied). This stresses detectors' ability to report overlapping clusters.

**Recipe concept:** `gen/recipes/L10/adv_overlap.json` could be extended with this systematic nesting variant. The current `adv_overlap` sets may not have this exact topology.

### 4.3 Cross-File Clone Scattering (sem_scatter_variants)

**What it is:** One member of a clone cluster has its logic spread across 2–3 files in the set, rather than contained in one file. The cluster member is the *logical unit* (function body), not the file.

**Current status:** `SEM-08: cross_file_scatter` exists with weight 24. The description says "member logic spans 2 files; cluster members list multiple fragments."

**Gap:** The ground truth fragment reporting for scatter sets is complex — it requires listing multiple file/line ranges per member. The current `bench/score.php` Jaccard scoring may not handle this well. New sets should explicitly document fragment reporting behavior.

**Recipe concept:** `gen/recipes/L08/sem_scatter.json` with 5 sets, each with explicit `fragments` metadata in `expected.json`.

### 4.4 Multi-Cluster Sets (ex_multi_cluster_detailed)

**What it is:** A set with 3 independent clone regions, each with 3 members = 9 carrier files (plus distractors/clean). Currently `ex_multi_cluster` at L1 only covers exact clones.

**New family:** `mix_multi_cluster` at L9 — combines multiple independent clone regions with mixed interference on each, to test whether detectors report all clusters or cherry-pick.

**Design:**
- Cluster A (3 files): invoice_totals with WS-03 only
- Cluster B (3 files): csv_import with RN-01 only
- Cluster C (3 files): access_guard with CM-03 only
- Each cluster is independent and non-overlapping

**Why important:** Real codebases have multiple independent clone regions simultaneously. Tools that only report the "best" cluster miss others.

### 4.5 Intra-File Multiple Instances of Same Clone (adv_same_file_multi)

**What it is:** The same clone region appears twice in the *same file* (not just multiple members across files). A function `process()` appears in File A and appears twice in File B (at different positions).

**Current status:** `CP-01: same_file` handles "≥2 cluster members inside one file" — but it's unclear if it covers the "same clone twice in one file" topology.

**Recipe concept:** New family `adv_same_file_instances` under L10, 5 sets with `CP-01` topology extended to include repeated instances.

---

## 5. Anti-Pattern Sets

These sets are designed to probe specific false-positive and false-negative behaviors of concrete tools.

### 5.1 Token-Matcher False Negatives (fn_token_missed)

**What it tests:** Clones that ONLY token-based tools (phpcpd, jscpd) would miss because they require semantic normalization.

**Specific variants:**
- **Type:** Semantic duplication (L8 sem_* level) but *styled* to look like exact clones at the token level — so phpcpd-class tools should find them but won't because they need semantic reasoning
- Actually this is the opposite: semantic clones that token matchers won't find — which IS already covered by L6–L8

**More specific:** What about Type-2 clones with whitespace-only interference where the identifier renaming is invisible to phpcpd at default settings?

### 5.2 AST-Matcher False Negatives (fn_ast_missed)

**What it tests:** Clones where AST normalization would HELP (token matchers fail) but current AST matchers fail too because they lack the specific normalization.

**Specific topology:** `BL-01: demorgan` — the expression `!($a && $b)` and `!$a || !$b` are semantically equivalent via De Morgan's law. Most AST-based detectors do NOT do this normalization. So this is a false-negative set for AST-based detectors.

**Current status:** `BL-01` exists at L6 with variant selectors. But the key question: do existing AST-based detectors actually apply boolean algebra normalization? If not, these sets produce false negatives for AST tools too.

### 5.3 Token-Matcher False Positives (fp_token_bait)

**What it is:** Code that produces false positives from phpcpd/jscpd but not from semantic detectors — high token overlap but semantically different.

**Specific topology:** The current `nd_same_domain` L0 family covers this. Extend with:
- **Boilerplate-bait sets:** Getter/setter chains that look like clones to token matchers: `getId()`, `getName()`, `getEmail()` repeated with same variable names but different classes
- **Common framework patterns:** Laravel model getters that look identical at token level: `$this->attribute` access patterns

### 5.4 PHPDoc-Only Difference Sets (cm_docblock_only)

**What it is:** The ONLY difference between clone members is their PHPDoc. The code body is byte-for-byte identical.

**Current status:** `CM-03: docblock` exists but typically varies docblock in combination with other changes. This pure docblock-only variation tests whether comment-stripping normalization is applied correctly by token-based detectors.

**Why important:** Some tools strip docblocks, some don't. This set should produce different results for tools with different comment-handling settings.

### 5.5 Whitespace-Only Difference on Long Lines (ws_long_line_only)

**What it is:** Clone members differ only in whitespace, but the whitespace involves long lines (120+ characters) being wrapped vs. not wrapped.

**Current status:** `WS-06: line_wrap` exists and covers this. But specifically: a set where the only variation is long-line wrapping (no other changes) and the clone region is 30+ lines of single-line code wrapped differently.

**Why important:** Simian (line-based) is very sensitive to line wrapping. This could expose differences between line-based and token-based tools.

### 5.6 Encoding Trap Sets (enc_mixed_boundary)

**What it is:** Clone members with mixed encodings (BOM, NBSP, mixed CRLF/LF) that appear identical in editors but differ at the byte level.

**Current status:** `ENC-01..06` exist in registry with `implemented: false`. Implement and build the `adv_encoding` family.

**Specific traps:**
- ENC-01: BOM at start of some carriers but not others
- ENC-02: Non-breaking space in indentation (`\xC2\xA0` looks like space)
- ENC-04: CRLF in some files, LF in others
- ENC-05: `"\n"` (backslash-escaped) vs actual newline inside strings

---

## 6. Concrete Recipe Ideas (20+)

### Recipe 1: `mix_polyglot_asymmetric`

**Family:** `L09_mixed` → `mix_polyglot_asymmetric`  
**Level:** L9  
**What it tests:** Asymmetric multi-axis interference where each carrier uses a different 5–7 transform combination from a pool of 12 possible transforms.  
**Key transforms applied:** RN-05 + CM-03 + WS-06 + ST-01 + LT-01 + WS-03 + RN-01, with asymmetric distribution (A gets 2, B gets 4, C gets 7).  
**detection_expectation:** Token-based: true; AST-based: true; semantic: true. High difficulty (score 75–85).  
**Gap filled:** The current `mix_4plus` applies the same axes to all carriers. This applies *different* axes to each carrier — true real-world drift.

---

### Recipe 2: `mix_size_tiny_buried`

**Family:** `L09_mixed` → `mix_size_tiny_buried`  
**Level:** L9  
**What it tests:** 5-line duplicate region buried in 200-line file. Tests min-lines threshold behavior AND region reporting accuracy when clone is tiny relative to surrounding noise.  
**Key transforms:** WS-03 (blank lines inside) + RN-05 (case convention) applied to the tiny clone.  
**detection_expectation:** phpcpd: false (below 70-token default); jscpd: depends on settings; semantic: true.  
**Gap filled:** Current `ex_size_ladder` covers threshold detection but not the buried-in-noise scenario.

---

### Recipe 3: `adv_encoding_heredoc`

**Family:** `L10_adversarial` → `adv_encoding_heredoc`  
**Level:** L10  
**What it tests:** ENC-06 (heredoc/nowdoc) + WS-10 (newline style) + ENC-01 (BOM) simultaneously.  
**Key transforms:** ENC-06 (heredoc for string literals), ENC-10 (CRLF), ENC-01 (BOM) on one carrier only.  
**detection_expectation:** token_based: true (after encoding normalization); text_based: false; semantic: true.  
**Gap filled:** ENC-06 is not implemented. This would be the first implementation of heredoc/nowdoc variation.

---

### Recipe 4: `adv_genealogy_4chain`

**Family:** `L10_adversarial` → `adv_genealogy_4chain`  
**Level:** L10  
**What it tests:** 4-carrier genealogy chain A→B→C→D with accumulating transforms. Explicit `drift_generation` metadata (0, 1, 2, 3).  
**Key transforms:** A: pristine; B: +WS-03; C: +RN-05; D: +CM-03 + ST-01.  
**detection_expectation:** token_based: true for A↔B and A↔C; partial for A↔D; metric_based: false for A↔D.  
**Gap filled:** Current `adv_genealogy` uses 3-carrier chains with notes about infrastructure gaps. A 4-carrier chain with proper metadata is new.

---

### Recipe 5: `st_gap_split_many`

**Family:** `L05_statement_edits` → `st_gap_split_many`  
**Level:** L5  
**What it tests:** Gapped clone where the gap varies from 3 lines (carrier A) to 15 lines (carrier B) to 40 lines (carrier C). Tests gap tolerance of detectors.  
**Key transforms:** ST-06 with varying gap sizes.  
**detection_expectation:** AST-based gap-tolerant: true; strict token: false.  
**Gap filled:** Current `st_gap_split` family may not systematically vary gap size across carriers.

---

### Recipe 6: `cm_docblock_only`

**Family:** `L03_comments` → `cm_docblock_only`  
**Level:** L3  
**What it tests:** The clone body is byte-identical; ONLY the docblock on the cloned symbol differs.  
**Key transforms:** CM-03 (docblock variation) applied in isolation — no WS, no RN, no other changes.  
**detection_expectation:** text_based: false; token_based: true (with comment stripping); metric_based: false (line-based won't see it as duplicate since line numbers differ).  
**Gap filled:** Current `cm_docblock` family likely combines docblock variation with whitespace. Pure docblock-only variation is missing.

---

### Recipe 7: `rn_params_with_bodies`

**Family:** `L04_rename_literals` → `rn_params_with_bodies`  
**Level:** L4  
**What it tests:** Function parameter renames where the parameter is used multiple times within the function body — tests whether detectors correctly map parameter renames through their entire scope.  
**Key transforms:** RN-02 (param names) + RN-01 (local vars within that scope).  
**detection_expectation:** token_based: true; ast_based: true.  
**Gap filled:** Current `rn_params` may only test simple single-use parameter renames.

---

### Recipe 8: `cf_match_guard_advanced`

**Family:** `L06_controlflow_rewrites` → `cf_match_guard_advanced`  
**Level:** L6  
**What it tests:** Guard-return pattern vs. match expression with multiple arms — more complex than the basic CF-03. Uses the `access_guard` seed with multiple authorization rules expressed as match vs. nested if.  
**Key transforms:** CF-02 (match_switch) variant with 5+ match arms.  
**detection_expectation:** token_based: false; ast_based: true (with control flow normalization); semantic: true.  
**Gap filled:** Current CF-02 variant uses simple 2-arm switch/match. Real-world match expressions have many arms.

---

### Recipe 9: `api_arrow_fn_loop`

**Family:** `L07_api_idioms` → `api_arrow_fn_loop`  
**Level:** L7  
**What it tests:** `array_map(fn($x) => $x * 2, $arr)` (arrow function) vs. `array_map(function($x) { return $x * 2; }, $arr)` (anonymous function) vs. `foreach ($arr as $x) { $result[] = $x * 2; }` (imperative loop).  
**Key transforms:** API-01 (map_vs_loop) with explicit arrow function variant.  
**detection_expectation:** token_based: false; ast_based: true (with function canonicalization); semantic: true.  
**Gap filled:** The current `api_map_loop` variants likely don't include arrow function equivalence.

---

### Recipe 10: `sem_di_property_inject`

**Family:** `L08_semantic` → `sem_di_property_inject`  
**Level:** L8  
**What it tests:** Constructor injection (`private $client; public function __construct(HttpClient $c) { $this->client = $c; }`) vs. property injection (`#[Inject] private HttpClient $client;`). The cloned method body is the same; the DI mechanism differs.  
**Key transforms:** SEM-05 variant with property-injection style.  
**detection_expectation:** token_based: false; ast_based: false; semantic: true.  
**Gap filled:** Current `sem_di_style` variants may not include property injection (only constructor injection vs. service locator).

---

### Recipe 11: `mix_noise_framework_attrs`

**Family:** `L09_mixed` → `mix_noise_framework_attrs`  
**Level:** L9  
**What it tests:** An exact clone with framework attribute noise (PHP 8 `#[Route]`, `#[OA\Response]`, `#[Assert\NotBlank]`) added to each carrier at different densities.  
**Key transforms:** NZ-04 (framework attrs) + CM-03 (docblock attrs) stacked on exact clone.  
**detection_expectation:** token_based: true; ast_based: true; semantic: true.  
**Gap filled:** `mix_noise_heavy` uses NZ-01..10 but may not focus specifically on framework attributes.

---

### Recipe 12: `adv_large_file_tiny_clone`

**Family:** `L10_adversarial` → `adv_large_file_tiny_clone`  
**Level:** L10  
**What it tests:** A 1200-line carrier file with a 5-line clone region buried at line 800+. Tests whether detectors limit window size and whether they find clones near file end.  
**Key transforms:** WS-03 + RN-05 on the tiny clone; large surrounding filler.  
**detection_expectation:** phpcpd: false (below min-lines + buried); semantic: true (if it can handle the overall context).  
**Gap filled:** Current `adv_large_files` may test large files but not specifically tiny-clone-buried-in-large scenario.

---

### Recipe 13: `mix_refactor_extractable`

**Family:** `L09_mixed` → `mix_refactor_extractable`  
**Level:** L9  
**What it tests:** The "refactoring opportunity" scenario — one carrier has duplicated inline code, another has a call to an extracted helper. The clone is the call pattern, not the function body.  
**Key transforms:** ST-03 (insert_functional — but here it's more like "extract to helper" pattern).  
**detection_expectation:** This set type would need a new ground truth shape where the "clone" is the call-site pattern, not the body.  
**Gap filled:** No current family tests refactoring-opportunity detection.

---

### Recipe 14: `ws_alignment_extreme`

**Family:** `L02_whitespace` → `ws_alignment_extreme`  
**Level:** L2  
**What it tests:** Column-aligned assignment operators with 20+ character padding: `$var = 1` vs `$var          = 1` vs `$var                   = 1`.  
**Key transforms:** WS-12 (alignment_padding) at maximum intensity.  
**detection_expectation:** text_based: false; token_based: true; line_based: false (Simian would see these as different lines).  
**Gap filled:** Current `ws_alignment` family may not test extreme padding scenarios.

---

### Recipe 15: `cm_license_header_shift`

**Family:** `L03_comments` → `cm_license_header_shift`  
**Level:** L3  
**What it tests:** A 20–40 line license/comment header before the cloned function shifts all line numbers. The code body is identical; only the header differs.  
**Key transforms:** CM-09 (license_header) with extreme variation (0 lines vs 40 lines).  
**detection_expectation:** text_based: false (different line counts); token_based: true; line_based: false.  
**Gap filled:** Current `cm_license_header` family exists but may not test the extreme shift case.

---

### Recipe 16: `ty_union_vs_nullable`

**Family:** `L04_rename_literals` → `ty_union_vs_nullable`  
**Level:** L4  
**What it tests:** `?string` (nullable shorthand) vs. `string|null` (explicit union) — PHP 8.0+ union types.  
**Key transforms:** TY-03 (union_types) — a new transform code to be implemented.  
**detection_expectation:** token_based: true; ast_based: true.  
**Gap filled:** TY-02 handles `?T` vs docblock nullability, but not union types `T1|T2`.

---

### Recipe 17: `bl_commutative_string`

**Family:** `L06_controlflow_rewrites` → `bl_commutative_string`  
**Level:** L6  
**What it tests:** String concatenation is not commutative (`"a" . "b"` ≠ `"b" . "a"`), but the BL-03 commutative transform should recognize that `.` is not mathematically commutative in the string domain. Wait — BL-03 is for `&&`/`||` which ARE mathematically commutative. For strings, this is different.

**Corrected:** The commutative issue for strings is: `"$a$b"` vs `"$b$a"` where `$a` and `$b` are variables. These produce different output but are "structurally similar."

**Better recipe:** `cf_string_template` — `sprintf('%s-%s', $a, $b)` vs `sprintf('%s-%s', $b, $a)` — these ARE actually different outputs, so this is more of a `st_param_reorder` (ST-08) case.

---

### Recipe 18: `api_datetime_immutable`

**Family:** `L07_api_idioms` → `api_datetime_immutable`  
**Level:** L7  
**What it tests:** `DateTime` (mutable) vs `DateTimeImmutable` for the same date arithmetic. The API-09 (datetime) variant should cover this.  
**Key transforms:** API-09 with explicit `DateTime` vs `DateTimeImmutable` variants.  
**detection_expectation:** token_based: false; ast_based: true; semantic: true.  
**Gap filled:** Current `api_datetime` may not specifically include the mutable/immutable distinction.

---

### Recipe 19: `mix_realistic_drift_4member`

**Family:** `L09_mixed` → `mix_realistic_drift_4member`  
**Level:** L9  
**What it tests:** 4 carriers instead of 3 — all derived from same original, each with different accumulated changes over staggered time periods (6mo, 12mo, 18mo, 24mo).  
**Key transforms:** ST-01 + CM-03 + RN-05 + WS-06 with per-carrier accumulation.  
**detection_expectation:** token_based: true; ast_based: true; semantic: true.  
**Gap filled:** Current `mix_realistic_drift` uses 3 carriers. 4 carriers better models real maintenance history.

---

### Recipe 20: `adv_near_miss_semantic`

**Family:** `L10_adversarial` → `adv_near_miss_semantic`  
**Level:** L10  
**What it tests:** Near-miss where the structure is nearly identical but the semantics differ critically — e.g., `if ($balance >= $withdrawal)` vs `if ($balance <= $withdrawal)` (off-by-one comparison operator flip).  
**Key transforms:** ST-09 (expr_tweak) with `>=` → `<=` change.  
**detection_expectation:** All tools should report: false (these are not clones). This is a pure FP-bait set at L10.  
**Gap filled:** Current `adv_near_miss` likely covers structural near-miss but not semantic near-miss with critical operator flips.

---

### Recipe 21: `nz_timing_budget`

**Family:** `L09_mixed` → `nz_timing_budget`  
**Level:** L9  
**What it tests:** An exact clone with `$start = microtime(true)` at the top and `$this->logDuration($start)` at the bottom — the instrumentation is identical but the log format differs.  
**Key transforms:** NZ-09 (timing) + LT-02 (string) for the log message.  
**detection_expectation:** token_based: false (timing calls add tokens); ast_based: true (after dead code elimination); semantic: true.  
**Gap filled:** Current `mix_noise_heavy` includes NZ-09 but may not combine it with string literal variation on the log messages.

---

### Recipe 22: `sem_normalization_chain`

**Family:** `L08_semantic` → `sem_normalization_chain`  
**Level:** L8  
**What it tests:** The same data normalization pipeline (trim, lower, sanitize) in different order — `trim(strtolower($s))` vs `strtolower(trim($s))`. For strings, order matters; this is SEM-11.  
**Key transforms:** SEM-11 (normalization_order) with multiple ordering permutations.  
**detection_expectation:** token_based: false; semantic: true.  
**Gap filled:** Current `sem_normalization_order` may not test the string-pipeline case specifically.

---

### Recipe 23: `ex_multi_cluster_3way`

**Family:** `L01_exact` → `ex_multi_cluster_3way`  
**Level:** L1  
**What it tests:** Three independent exact clone clusters in one set (9 carrier files + distractors).  
**Key transforms:** None — exact clones.  
**detection_expectation:** All tools should find all 3 clusters.  
**Gap filled:** Current `ex_multi_cluster` has 2–3 independent clusters, but may not systematically test the 3-cluster case.

---

### Recipe 24: `cf_early_return_guard`

**Family:** `L06_controlflow_rewrites` → `cf_early_return_guard`  
**Level:** L6  
**What it tests:** A complex guard-chain pattern: `if (!$cond) { return false; } /* then main logic */` vs `if ($cond) { /* main logic */ } else { return false; }` vs ternary-with-early-return.  
**Key transforms:** CF-05 (early_return) combined with CF-03 (guard_nested).  
**detection_expectation:** token_based: false; ast_based: true; semantic: true.  
**Gap filled:** Current `cf_early_return` may not test the guard-combination scenario.

---

### Recipe 25: `api_data_shape_dto_array`

**Family:** `L07_api_idioms` → `api_data_shape_dto_array`  
**Level:** L7  
**What it tests:** `stdClass` vs typed DTO vs associative array for the same data shape — three ways to represent `$data['name'], $data['email']`.  
**Key transforms:** API-07 (data_structure) with explicit stdClass / array / typed-object variants.  
**detection_expectation:** token_based: false; ast_based: false; semantic: true.  
**Gap filled:** Current `api_data_shape` may not have the stdClass variant explicitly.

---

## 7. Priority Order & Dependencies

### Phase 1: Foundation Extensions (pre-fan-out)
These must be built before any new sets can be generated:

1. **Implement ENC-06 (heredoc/nowdoc)** — `gen/transforms/Enc/Heredoc.php`
   - Needed by: `adv_encoding_heredoc` (Recipe 3)
   - Weight: 6; clone_type: type-2

2. **Implement NZ noise wrappers** (NZ-01..10 all have `implemented: false`)
   - Priority: NZ-01 (logging), NZ-09 (timing) — needed for `mix_noise_heavy` extension
   - Weight: 9 each

3. **Implement CP topologies** (CP-01..10 all have `implemented: false`)
   - Priority: CP-01 (same_file), CP-07 (genealogy) — needed for `adv_genealogy_4chain` and `adv_same_file_instances`
   - CP-07 has `drift_generation` metadata requirement

4. **Implement TY-03 (union_types)**
   - Needed by: `ty_union_vs_nullable` (Recipe 16)

### Phase 2: New Seeds
Build these seed domains first (needed by multiple recipes):

1. `http_client` seed — HTTP response normalization (Recipe 2.1)
2. `validation_rule` seed — validation chain (Recipe 2.2)
3. `cache_manager` seed — cache-aside pattern (Recipe 2.3)
4. `pager` seed — pagination math (Recipe 2.4)
5. `state_transition` seed — state machine (Recipe 2.5)

### Phase 3: New Transform Classes
These existing registry codes need implementation:

| Code | Name | Priority | Needed by |
|---|---|---|---|
| ENC-01 | bom | Medium | `adv_encoding` |
| ENC-02 | nbsp | Medium | `adv_encoding` |
| ENC-03 | unicode_ident | Low | Future |
| ENC-04 | mixed_eol | Medium | `adv_encoding` |
| ENC-05 | string_escapes | Medium | `adv_encoding` |
| ENC-06 | heredoc | **High** | `adv_encoding_heredoc` |
| TY-03 | union_types | High | `ty_union_vs_nullable` |
| NZ-01 | logging | Medium | `mix_noise_heavy` |
| NZ-02 | metrics | Low | Future |
| NZ-03 | security | Low | Future |
| NZ-04 | framework | Medium | `mix_noise_framework_attrs` |
| NZ-09 | timing | Medium | `nz_timing_budget` |
| CP-01 | same_file | High | `adv_same_file` |
| CP-02 | overlap | High | `adv_overlap` |
| CP-03 | below_threshold | High | `adv_below_threshold` |
| CP-04 | threshold_boundary | High | `adv_boundary` |
| CP-05 | large_file | High | `adv_large_files` |
| CP-06 | many_files | High | `adv_many_files` |
| CP-07 | genealogy | **High** | `adv_genealogy_4chain` |

### Phase 4: New Recipe Families (by build order)

**Wave 1 — L9 Mixed extensions:**
- `mix_polyglot_asymmetric` (Recipe 1)
- `mix_size_tiny_buried` (Recipe 2)
- `mix_realistic_drift_4member` (Recipe 19)

**Wave 2 — L10 Adversarial extensions:**
- `adv_encoding_heredoc` (Recipe 3) — needs ENC-06
- `adv_genealogy_4chain` (Recipe 4) — needs CP-07
- `adv_large_file_tiny_clone` (Recipe 12)
- `adv_near_miss_semantic` (Recipe 20)

**Wave 3 — L5 Statement edit extensions:**
- `st_gap_split_many` (Recipe 5)
- `st_intra_method_partial` (Section 4.1)

**Wave 4 — L3 comment extensions:**
- `cm_docblock_only` (Recipe 6) — pure docblock variation
- `cm_license_header_shift` (Recipe 15)

**Wave 5 — L4 Type-2 extensions:**
- `rn_params_with_bodies` (Recipe 7)
- `ty_union_vs_nullable` (Recipe 16) — needs TY-03

**Wave 6 — L6/L7 semantic extensions:**
- `cf_match_guard_advanced` (Recipe 8)
- `api_arrow_fn_loop` (Recipe 9)
- `sem_di_property_inject` (Recipe 10)
- `api_datetime_immutable` (Recipe 18)
- `api_data_shape_dto_array` (Recipe 25)

**Wave 7 — Cross-cutting:**
- `mix_noise_framework_attrs` (Recipe 11) — needs NZ-04
- `mix_refactor_extractable` (Recipe 13)
- `ws_alignment_extreme` (Recipe 14)
- `nz_timing_budget` (Recipe 21)
- `sem_normalization_chain` (Recipe 22)
- `cf_early_return_guard` (Recipe 24)
- `ex_multi_cluster_3way` (Recipe 23)

---

## 8. Summary

### What Already Exists

The corpus is already well-developed with:
- **540 sets** across **11 levels** (L0–L10)
- **97 families** with 5+ sets each
- **3 seed domains** (`invoice_totals`, `csv_import`, `access_guard`)
- **118 transform codes** across WS/CM/RN/LT/TY/NS/ST/CF/BL/API/SEM/NZ/ENC/CP
- **Strong foundation** with generator, verifier, bench runner, schemas

### What the Expansion Adds

| Category | Current | Added | Total |
|---|---|---|---|
| Mixed-variation families | 8 `mix_*` | 6 new `mix_*` families | 14 |
| New seeds | 3 | 8 | 11 |
| New transform implementations | ~20 | ~30 | ~50 |
| L10 adversarial families | 11 | 4 new + extensions | 15+ |
| Structural topology variants | basic | advanced (nested, scatter, multi-cluster) | extended |
| Anti-pattern families | basic | 6 targeted anti-pattern sets | expanded |

### Key Gaps in Priority Order

1. **Implement ENC-06 (heredoc/nowdoc)** — only declared, not built
2. **Build 5 new seed domains** — current 3 seeds limit domain coverage severely
3. **Implement CP-07 (genealogy) with drift_generation metadata** — core for drift chains
4. **Build NZ-01/NZ-04/NZ-09 (noise wrappers)** — needed for realistic noise stacking
5. **Create asymmetric multi-transform mixed sets** — true real-world drift modeling
6. **Build 4-carrier genealogy chains** — next-level genealogy stress test
7. **Build pure docblock-only variation sets** — tests comment-stripping normalization
8. **Build encoding trap sets** — BOM, NBSP, mixed EOL handling

### Effort Estimate

| Phase | Transform Impl | Seeds | Recipes | Total Sets |
|---|---|---|---|---|
| Foundation ext | 10 transforms | 5 seeds | — | — |
| L9 extensions | 3 transforms | 2 seeds | 6 families | 30 |
| L10 extensions | 7 transforms | 2 seeds | 4 families | 20 |
| L3/L4/L5 ext | 2 transforms | 3 seeds | 5 families | 25 |
| L6/L7 ext | 0 | 4 seeds | 5 families | 25 |
| Cross-cutting | 5 transforms | 0 | 7 families | 35 |
| **Total** | **~27 transforms** | **~16 seeds** | **~27 families** | **~135 sets** |

The expansion adds approximately **135 new sets** across **27 new/extended recipe families**, leveraging **~27 new transform implementations** and **~16 new seed domains**, bringing the total corpus to approximately **675 sets** with **~45 seeds** and **~145 transform implementations** — a substantial, comprehensive benchmark suite.
