# Ideas for Expanding PHP Duplication Samples Corpus

## Overview

This document outlines ideas for greatly expanding the php-duplication-samples repository into a comprehensive, graduated corpus capable of thoroughly testing code deduplication software at every level of complexity. The existing structure is a solid foundation — these ideas push it into a truly exhaustive dataset.

---

## Part 1: Understanding What Already Exists

### Current Structure (Summary)

| Layer | Purpose | What's There |
|-------|---------|--------------|
| `samples/` | Original 54-category reference | ~600 PHP files across algorithm, behavioral, structural, semantic, etc. |
| `refactored/` | Deduplicated solutions | ~300 PHP files |
| `gen/` | Deterministic generator | Recipes, seeds, scaffolds, transforms (WS/CM/RN/LT/CF/API/SEM) |
| `testsets/` | Graduated benchmark corpus | L0-L10 (11 levels), full JSON metadata + ground truth |
| `bench/` | Benchmark harness | For comparing detection tools |

### Current Levels in testsets/

- **L00** — No duplication (negative controls)
- **L01** — Exact byte-identical clones (Type-1)
- **L02** — Whitespace variation only
- **L03** — Comment variation only
- **L04** — Renames + literals + types (Type-2)
- **L05** — Statement edits: insertions, deletions, reordering (Type-3)
- **L06** — Control-flow rewrites (Type-3/4)
- **L07** — API idiom substitution (Type-4)
- **L08** — Semantic/architectural equivalence (Type-4)
- **L09** — Mixed interference (stacked axes, e.g., rename + whitespace + comments)
- **L10** — Adversarial edge cases (threshold traps, encoding traps)

### Current Transform Types

- **WS-\*** — Whitespace (12 variants: blank lines, operator spacing, indentation, line wrapping)
- **CM-\*** — Comments (11 variants: docblocks, line comments, block comments, license headers, annotations)
- **RN-\*** — Renames (5 variants: local vars, params, functions, classes, case conventions)
- **LT-\*** — Literals (4 variants: numeric, string, array values, const vs literal)
- **CF-\*** — Control-flow (if/ternary, match/switch, guard/nested, loop form, early return)
- **API-\*** — API idioms (map vs loop, string functions, regex vs string, recursion vs iteration)
- **SEM-\*** — Semantic (rule reexpression, inline vs extracted, OO split, ORM vs SQL)

---

## Part 2: New Level Tiers (Beyond L10)

### L11 — Deep Renaming Stacks

**Concept:** Stack 3-4 rename axes simultaneously (local vars + function names + class names + case convention + type hints). The clone is identical under identifier canonicalization but looks very different visually.

**Variations within this level:**
- RN-01 (local vars) + RN-02 (params) stacked
- RN-01 + RN-03 (functions) + RN-04 (classes) stacked
- RN-01 + RN-02 + RN-05 (case convention: camelCase → snake_case → PascalCase) stacked
- All renames + LT-01 (numeric literals) stacked
- All renames + LT-03 (const vs literal) stacked
- Adding TY-01 (strict_types) toggle on/off
- Adding TY-02 (nullable vs union type) variation

**Example:**
```php
// File A
function calculateTotalPrice(float $netAmount, float $taxRate): float
{
    $subtotal = $netAmount * (1 + $taxRate);
    return $subtotal;
}

// File B (same logic, all renames + types changed)
function computeGrossValue(float|int $baseCost, float $vatRatio): float
{
    $gross = $baseCost * (1 + $vatRatio);
    return $gross;
}
```

### L12 — Whitespace Chaos Layers

**Concept:** Stack 5+ whitespace transforms. Indentation toggles (2/4/8 spaces, tabs), operator spacing, line wrapping at different column widths, blank line variations, heredoc vs string.

**Variations:**
- WS-01 (blank_before) + WS-02 (blank_after) + WS-03 (blank_inside) all at once
- WS-04 (operator_spacing) + WS-05 (operand_spacing) combined
- WS-06 (indent_width: 2) + WS-07 (indent_width: 4) + WS-08 (indent_width: tab)
- WS-09 (line_wrap at 80) + WS-10 (line_wrap at 120) + WS-11 (no_wrap)
- Heredoc vs nowdoc vs plain string for multi-line text
- Array multiline formatting variations (trailing commas, different alignments)

### L13 — Comment Obfuscation Layers

**Concept:** Stack 5+ comment transforms. Not just adding different comment styles, but also:
- Commenting out non-essential code lines
- Adding decoy comments that mention variables that exist in the code
- Mixing docblock styles (phpdoc vs annotation style)
- Adding TODO/FIXME comments that reference real functions

**Variations:**
- CM-01 (docblock) + CM-02 (line comment) + CM-03 (block comment)
- CM-04 (commented_code) — commenting out a non-duplicate line inside the clone
- CM-05 (license_header) — prepend different license headers (MIT, Apache, GPL)
- CM-06 (annotations) — add @param, @return, @throws that are accurate vs slightly misleading
- CM-07 (false_doctrine) — add doctrine annotations that are real but not enforced
- CM-08 (git_attributes) — add git-related comments

### L14 — Literal Variation Storm

**Concept:** Stack 4+ literal transforms. Same computation with completely different literal representations.

**Variations:**
- Integer 100 vs float 100.0 vs string "100" vs hex 0x64 vs binary 0b1100100
- String "true"/"false" vs bool true/false vs int 1/0
- Array [1, 2, 3] vs range(1, 3) vs array(1, 2, 3) vs [...[1,2,3]]
- Date strings: "2024-01-15" vs "January 15, 2024" vs strtotime formatting
- Magic numbers replaced with computed expressions (e.g., 3600 vs 60*60 vs pow(60, 2))

### L15 — Structural Divergence

**Concept:** Same logic rendered in fundamentally different PHP structures. The AST looks completely different but execution is identical.

**Variations:**
- `if ($x)` vs `if ($x === true)` vs `if ($x !== false)` vs `if (!empty($x))`
- `$result = $condition ? $a : $b` vs `if ($condition) { $result = $a; } else { $result = $b; }`
- `match ($x) { 'a' => 1, 'b' => 2 }` vs `switch ($x) { case 'a': return 1; case 'b': return 2; }`
- `foreach ($items as $item)` vs `for ($i = 0; $i < count($items); $i++)`
- `array_map(fn($x) => $x * 2, $arr)` vs `foreach ($arr as &$x) { $x *= 2; }`
- `isset($x) ? $x : $default` vs `$x ?? $default`
- `in_array($x, [1,2,3])` vs `match(true) { $x === 1 => true, $x === 2 => true, default => false }`

### L16 — Function Signature Divergence

**Concept:** Same logic but function/method signatures are completely different (different params, different return types, different names). Requires cross-file analysis to detect.

**Variations:**
- `function foo(int $a, int $b)` vs `function bar(float $x, float $y)` — same body logic
- Method with constructor injection vs static method with global
- `__invoke()` class vs plain function
- Callable passed as `$callback` vs string 'function_name' vs `[$obj, 'method']`
- Named arguments vs positional arguments (when calling)

### L17 — OOP Structural Divergence

**Concept:** Same logic in different OOP patterns. Different class structures, inheritance models, trait usages.

**Variations:**
- Single class with methods vs trait + using class
- Inheritance hierarchy (parent::method()) vs composition (dependency->method())
- Interface implementation vs concrete class
- Trait reuse vs copy-pasted methods across classes
- Static methods vs singleton instance methods
- Flyweight pattern vs repeated object creation

### L18 — Namespace and Autoloading Divergence

**Concept:** Same code logic but in different namespaces, requiring namespace normalization to detect.

**Variations:**
- Same class in `A\B\C\Class` vs `D\E\F\Class` with use statements
- Global functions vs namespaced functions
- Mixed: some code in namespace, some in global
- PSR-4 vs PSR-0 autoloading layouts
- `require`/`include` vs `require_once`/`include_once` behavior differences
- Composer autoload vs manual autoload

### L19 — Mixed Control-Flow + Semantic

**Concept:** Stacking CF-** transforms with SEM-** transforms simultaneously.

**Example set idea:**
- CF-02 (match_switch) + SEM-02 (inline_vs_extracted) — one is inlined, one uses helper function
- CF-03 (guard_nested) + SEM-04 (oo_split) — guard pattern in OO style vs procedural
- CF-04 (loop_form: for vs foreach) + SEM-03 (rule_reexpression) — loop variation + different business rule expression

### L20 — The Kitchen Sink (10-20 Stacked Variations)

**Concept:** The ultimate challenge — 10-20 transform types stacked on a single clone. This tests the limits of deduplication tooling. Designed to be refactorable into a single parameterized function, but only after extreme normalization.

**Example composition:**
1. Exact original (for ground truth)
2. Stack: WS-01,02,03,04,05,06,07,09 (7 whitespace transforms)
3. Stack: CM-01,02,03,05,06 (5 comment transforms)
4. Stack: RN-01,02,03,04,05 (5 rename transforms)
5. Stack: LT-01,02,03 (3 literal transforms)
6. Stack: TY-01,02 (type transforms)
7. Stack: CF-01,02,03,04,05 (5 control-flow transforms)
8. Stack: API-01,02,03,04,05 (5 API idiom transforms)
9. Stack: SEM-01,02,03,04 (4 semantic transforms)
10. Result: 34+ stacked transforms on a single clone

**Difficulty variants within L20:**
- L20a: 10-12 stacked transforms (challenging but solvable)
- L20b: 15-17 stacked transforms (very hard)
- L20c: 20+ stacked transforms (near-adversarial)

---

## Part 3: Structural Variations (Sets Within Sets)

### 3.1 — Multi-Cluster Samples

**Concept:** A single sample where 2, 3, 4+ different clone clusters exist simultaneously. Each cluster may be a different clone type or variation level.

**Example:**
```
FileA.php: [UniqueCode] [Clone1] [UniqueCode] [Clone2] [UniqueCode]
FileB.php: [UniqueCode] [Clone1] [UniqueCode] [Clone2] [UniqueCode]
FileC.php: [UniqueCode] [Clone1] [UniqueCode] [Clone3] [UniqueCode]
```
Here Clone1 and Clone2 are separate clusters. Clone1 appears in all 3 files, Clone2 in A and B only, Clone3 in C only.

**Multi-cluster variations:**
- 2 clusters of Type-1 (exact)
- 2 clusters, one Type-1 and one Type-4
- 3 clusters of varying types
- Clusters that overlap (share lines)
- Clusters that are nested (one clone inside another clone)

### 3.2 — Varying Clone Instance Counts

**Concept:** Sets where the same clone appears 2, 3, 4, 5, 10, 20 times.

**Use cases:**
- Test scalability of detection tools
- Test that tools don't miss instances when there are many
- Test that tools correctly group all instances as the same clone

**Instance count variations:**
- 2 instances: minimal pair (A/B)
- 3 instances: standard (A/B/C) — current standard
- 4 instances: extended
- 5 instances: common in utility libraries
- 10 instances: large utility file
- 20+ instances: framework-level duplication

### 3.3 — Intra-File Clone Clustering

**Concept:** Multiple clone instances of the SAME clone cluster within a single file (not across files).

**Example:**
```php
// File: MathUtils.php
class MathUtils {
    public function calcA() { /* DUPLICATE CODE */ }
    public function calcB() { /* DUPLICATE CODE */ }  // same as calcA
    public function calcC() { /* DUPLICATE CODE */ }  // same as calcA
    public function calcD() { /* DUPLICATE CODE */ }  // same as calcA
}
```

### 3.4 — Clone-to-Unique-Code Ratio Variations

**Concept:** Vary the amount of unique code surrounding clones from very little to very much.

**Ratio scale:**
- **Sparse:** 5 lines unique / 20 lines clone (80% clone density)
- **Balanced:** 20 lines unique / 20 lines clone (50% clone density)
- **Dense:** 50 lines unique / 20 lines clone (29% clone density)
- **Very Dense:** 100 lines unique / 20 lines clone (17% clone density)
- **Extreme:** 200 lines unique / 20 lines clone (9% clone density)

This tests whether detection tools are biased by file context and whether they can find clones embedded in large amounts of unique code.

### 3.5 — Clone Regions Within Clone Regions

**Concept:** A clone inside a clone — nested duplication.

```php
// File A
function process() {
    // Outer clone start
    validate();
    // Inner clone start (also appears in other files)
    $result = compute();
    // Inner clone end
    logResult();
    // Outer clone end
}
```

### 3.6 — Partial Clone Overlap

**Concept:** Clone clusters that share some but not all lines. This is distinct from nested clones.

```
Clone A: lines 10-30
Clone B: lines 20-40 (overlaps with A on lines 20-30)
```

These are NOT the same clone, but they share a sub-clone. Tests whether tools correctly distinguish overlapping-but-different clones.

### 3.7 — One Clone Member with Slight Variation

**Concept:** A clone cluster where one member has a subtle difference (one extra line, one missing line, one different literal).

```
Clone cluster with 3 members:
- FileA: identical
- FileB: identical
- FileC: same but with +1 line difference
```

This tests detection tools' ability to handle "near miss" clones within otherwise identical clusters.

---

## Part 4: Refactorability Axis

### 4.1 — Parametric Refactorability Spectrum

**Concept:** Sets designed to test whether clones can be refactored into parameterized functions. Different levels of difficulty:

**Easy (1 parameter):**
```php
// These 3 can become: function process(float $rate, string $label) { ... }
function addTax($price) { return $price * 1.1; }
function addVat($amount) { return $amount * 1.15; }  // same logic, different literal
function addMarkup($cost) { return $cost * 1.2; }
```

**Medium (2-3 parameters):**
```php
// Need: function calculate($base, $rate, $threshold) { ... }
function applyStandard($base) { return $base > 1000 ? $base * 1.1 : $base; }
function applyPremium($base) { return $base > 500 ? $base * 1.15 : $base; }
```

**Hard (4+ parameters, conditional logic):**
```php
// Need many parameters or complex conditional refactoring
function legacyCalc($a, $b, $c, $d, $e) {
    if ($a > 0) {
        if ($b > 10) { return $a * $b + $c; }
        else { return $a * $d - $e; }
    }
    return 0;
}
```

### 4.2 — Template Method Pattern Candidates

**Concept:** Clones that differ only in a few "hook" methods — ideal for Template Method pattern refactoring.

```php
// 3 files with same structure, different specific methods
class FileAProcessor {
    public function process() {
        $this->validate();     // same
        $data = $this->fetch(); // same
        $this->transform($data); // DIFFERENT
        $this->save();          // same
    }
}
```

### 4.3 — Strategy Pattern Candidates

**Concept:** Clones that implement the same algorithm with different strategies.

```php
// All do "calculate" but with different strategies
class LinearCalculator { public function calculate($x) { return 2 * $x + 1; } }
class QuadraticCalculator { public function calculate($x) { return $x * $x + 2 * $x + 1; } }
class ExponentialCalculator { public function calculate($x) { return pow(2, $x); } }
```

### 4.4 — Exploded vs Composed Variations

**Concept:** Same logic, but one representation is "exploded" into many lines while another is compressed to few lines.

```php
// Exploded (50 lines)
if ($status === 'active') {
    if ($amount > 0) {
        if ($tier === 'premium') {
            $result = $amount * 1.2;
        } else {
            $result = $amount * 1.1;
        }
    }
}

// Composed (1 line)
$result = $status === 'active' && $amount > 0 ? $amount * ($tier === 'premium' ? 1.2 : 1.1) : 0;
```

### 4.5 — Transformations Requiring Business Logic Extraction

**Concept:** Clones that require extracting business rules into separate functions/classes to eliminate duplication.

```php
// In File A: isValidCustomer($customer, $order)
// In File B: isValidUser($user, $purchase) — same logic, different domain terms
// Refactoring: extract validateEntity($entity, $transaction, $typeField, $thresholdField)
```

---

## Part 5: Domain/Semantic Variations

### 5.1 — Cross-Domain Semantic Equivalence

**Concept:** Clones that look completely different but represent the same business concept in different domains.

```
Invoice calculation logic ↔ Quote calculation logic ↔ Order calculation logic
Authorization check ↔ Permission check ↔ Access validation
Email sending ↔ SMS sending ↔ Push notification sending
```

### 5.2 — State Machine Variations

**Concept:** Same state machine (states + transitions) encoded in different ways.

```php
// Switch-based vs array-based vs class-based state machines
// All implement: start → processing → completed (or failed)
// But the code structure looks completely different
```

### 5.3 — Data Structure Encoding Variations

**Concept:** Same data transformations but using different PHP data structures.

```php
// Array-based
$map = ['a' => 1, 'b' => 2]; foreach ($map as $k => $v) { ... }

// ArrayObject-based
$map = new ArrayObject(['a' => 1, 'b' => 2]); foreach ($map as $k => $v) { ... }

// Generator-based
function generate() { yield 'a' => 1; yield 'b' => 2; }
// All produce identical results but code structure differs radically
```

### 5.4 — Functional vs Imperative Equivalence

**Concept:** Same logic in functional PHP (array_filter, array_map, etc.) vs classic imperative loops.

```php
// Functional
$results = array_filter(array_map(fn($x) => $x * 2, $items), fn($x) => $x > 10);

// Imperative
$results = [];
foreach ($items as $item) {
    $doubled = $item * 2;
    if ($doubled > 10) { $results[] = $doubled; }
}
```

### 5.5 — Laravel vs Symfony vs Plain PHP Equivalence

**Concept:** Same business logic rendered in different framework idioms.

```php
// Plain PHP
$pdo = new PDO($dsn, $user, $pass);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Laravel
$user = DB::table('users')->where('id', $id)->first();

// Doctrine
$user = $entityManager->find(User::class, $id);
```

### 5.6 — Same Query, Different ORM Representation

**Concept:** SQL queries that are semantically identical but written differently.

```sql
SELECT * FROM orders WHERE customer_id = 1 AND status = 'pending'

SELECT * FROM orders WHERE status = 'pending' AND customer_id = 1

SELECT * FROM orders WHERE customer_id = 1 AND status IN ('pending')
```

### 5.7 — Business Rule Encoding Variations

**Concept:** Same business rule encoded in different programming constructs.

```
Discount calculation:
- Nested if/else chain
- Early returns (guard clauses)
- Match expression (PHP 8+)
- Lookup table (array)
- Strategy pattern
```

---

## Part 6: Interference and Noise Axis

### 6.1 — Near-Miss Distractors (Bait Files)

**Concept:** Files that LOOK like they contain clones but don't. These are false-positive tests.

**Near-miss types:**
- Same variable names, same structure, different logic
- Same function signatures, same comments, different implementations
- Same control flow shape, different specific operations
- Same class names, different method bodies
- Similar-looking comments describing similar operations

### 6.2 — Chaff Duplication

**Concept:** Real but trivially different code blocks added to files to distract detection tools.

```
FileA.php:  [CloneA]  [SmallUnique]  [CloneA slightly modified]
FileB.php:  [CloneA]  [SmallUnique]  [CloneB]
```

The "slightly modified CloneA" is a false trail.

### 6.3 — Clone Embedding in Large Functions

**Concept:** Clones are embedded deep within large functions (50-200 lines), surrounded by unique logic.

**Difficulty levels:**
- Clone at function start (easy to find)
- Clone in middle of function (medium)
- Clone at end of function (medium)
- Clone spanning multiple regions of function (hard — parts are separated by unique code)

### 6.4 — Dynamic Code Generation Snippets

**Concept:** PHP code that generates other PHP code, creating self-referential duplication patterns.

```php
// Files that define templates used to generate other files
// The duplication is in the template definitions, not the output
```

### 6.5 — Macro/Constant-Based Pseudo-Duplication

**Concept:** Code that uses constants/macros to achieve "pseudo duplication elimination" — same logic is invoked via different constants.

```php
define('PROCESS_A', 'a');
define('PROCESS_B', 'b');
// Same handler function called with different constants
process(PROCESS_A);
process(PROCESS_B);
// But the duplication is still real — handler could be inlined
```

---

## Part 7: Granularity Variations

### 7.1 — Statement-Level Clones

**Concept:** Very small clones (2-5 lines). Tests whether tools can detect micro-duplication.

```php
// Clone is just these 3 lines
if (!isset($data['key'])) {
    $data['key'] = $default;
}
```

### 7.2 — Expression-Level Clones

**Concept:** Even smaller — single expressions duplicated.

```php
// Just this expression duplicated across files
$fullName = trim($firstName . ' ' . $lastName);
```

### 7.3 — Block-Level Clones (Current Standard)

**Concept:** 10-50 line blocks. The current standard in testsets/.

### 7.4 — Function-Level Clones (Current Standard)

**Concept:** Entire functions (20-100 lines). Currently well-covered.

### 7.5 — Class-Level Clones

**Concept:** Entire classes that are nearly identical. Tests whether tools detect class-level duplication.

```php
// ClassA.php and ClassB.php are 90% identical
// Only difference: class name and one method's return value
class InvoiceCalculator { ... }
class QuoteCalculator { ... }  // 90% same as InvoiceCalculator
```

### 7.6 — File-Level Clones

**Concept:** Entire files that are nearly identical. Tests whether tools do file-level detection.

### 7.7 — Multiple-File Pattern Clones

**Concept:** The same pattern of file structure + content repeated across a directory tree.

```
/domain/orders/FileA.php — has Clone1
/domain/orders/FileB.php — has Clone1
/domain/billing/FileC.php — has Clone1 (same clone, different domain)
/domain/shipping/FileD.php — has Clone2 (different clone)
```

---

## Part 8: Edge Case and Adversarial Sets

### 8.1 — Hash Collision Sensitivity

**Concept:** Files whose clones would hash identically in naive implementations but differ in token sequence.

### 8.2 — Unicode and Encoding Tricks

**Concept:** Homoglyphs, homographs, confusable Unicode characters that look identical but differ at byte level.

```php
// Variable named $total vs $tоtal (Cyrillic 'о' instead of Latin 'o')
// These are DIFFERENT identifiers but look IDENTICAL
```

### 8.3 — Line-Split Token Boundary Issues

**Concept:** Code that breaks naive line-based detectors.

```php
// File A: tokens split across lines
$sum =
    $a + $b +
    $c;

// File B: same expression, different line structure
$sum = $a + $b + $c;
```

### 8.4 — Heredoc/Nowdoc Obfuscation

**Concept:** Multi-line strings formatted differently in heredoc vs nowdoc vs double-quoted with escapes.

```php
// Same content, different syntax
$sql = "SELECT *
FROM users
WHERE id = ?";

$sql = <<<SQL
SELECT *
FROM users
WHERE id = ?
SQL;
```

### 8.5 — Opacity Through Complexity

**Concept:** Clones surrounded by so much unique code that they're effectively hidden in noise.

**Noise levels:**
- 10x unique:clone ratio
- 50x unique:clone ratio
- 100x unique:clone ratio
- 500x unique:clone ratio (clone is 1 line in a 500-line file)

### 8.6 — Minified vs Fully Formatted

**Concept:** Same logic, one in minified format (no whitespace, no comments) and one fully formatted.

### 8.7 — Generated Code Clones

**Concept:** Code generated by scaffolding tools (Symfony, Laravel, etc.) that produces similar boilerplate across projects.

### 8.8 — Third-Party Framework Boilerplate

**Concept:** Duplication inherent in framework usage patterns (Laravel controllers, Symfony services).

---

## Part 9: Progressive Complexity Sets

### Concept: Levels Within Levels

Each major level can have internal progression from "easy" to "hard":

| Sub-level | Description | Example |
|-----------|-------------|---------|
| a | 1 transform axis | rename variables only |
| b | 2 transform axes | rename + whitespace |
| c | 3 transform axes | rename + whitespace + comments |
| d | 4 transform axes | + literal changes |
| e | 5+ transform axes | + control flow |

### Progression Example for "Authorization Logic"

```
Level L-Auth-1a: Exact clone of auth check (Type-1)
Level L-Auth-1b: Whitespace + comment variations
Level L-Auth-1c: Variable renames
Level L-Auth-1d: + constant/literal changes
Level L-Auth-1e: + type hints added/removed
Level L-Auth-1f: + try/catch structure changes
Level L-Auth-1g: + different framework idioms (Laravel vs Symfony auth)
Level L-Auth-1h: + extracted to helper function (partial refactor)
Level L-Auth-1i: + nested with other unique auth logic
Level L-Auth-1j: All of the above combined (10+ variations)
```

---

## Part 10: Combined Variation Test Sets

### Concept: "Mutation Testing" Style Sets

Each set undergoes systematic mutation across multiple dimensions:

1. **Whitespace mutations**: indentation, spacing, line breaks
2. **Comment mutations**: docblocks, inline, license headers
3. **Identifier mutations**: variables, functions, classes, namespaces
4. **Literal mutations**: numbers, strings, booleans
5. **Type mutations**: type hints, strict_types, nullable
6. **Control flow mutations**: if/switch/match, for/foreach, ternary
7. **API idiom mutations**: loop vs array_* functions, string methods
8. **Structure mutations**: class vs function, trait vs inheritance
9. **Namespace mutations**: use statements, fully qualified names
10. **Context mutations**: surrounding unique code varying in size

### 10-20 Variation Combination Templates

**Combo Template A (Medium Difficulty, 10 variations):**
1. WS-01 blank_before
2. WS-04 operator_spacing
3. CM-01 docblock
4. CM-05 license_header
5. RN-01 local_vars
6. RN-02 params
7. LT-01 numeric_literals
8. TY-01 strict_types
9. CF-01 if_ternary
10. API-01 map_vs_loop

**Combo Template B (Hard Difficulty, 15 variations):**
1. WS-01,02,03,04,05,06,07 (first 7 whitespace transforms)
2. CM-01,02,03,04 (first 4 comment transforms)
3. RN-01,02,03,04 (first 4 rename transforms)
4. LT-01,02,03 (first 3 literal transforms)
5. CF-01,02,03 (first 3 control-flow transforms)
6. API-01,02 (first 2 API transforms)

**Combo Template C (Expert Difficulty, 20+ variations):**
All transforms from registry applied in combination.

### Sets Where NOT All Variations Apply

**Key insight:** Some sets should have some transform types applied but NOT others, creating partial-but-still-duplicate scenarios.

```
Set A: Apply WS, CM, RN, LT — but NOT CF or API (Type-2 focused with whitespace/comments)
Set B: Apply CF, API, SEM — but NOT WS, CM, RN (Type-3/4 focused, preserving visual similarity)
Set C: Apply WS, CM, CF, API — but NOT RN, LT, SEM (preserve renames as distinguishing factor)
Set D: Apply RN, LT, TY — but NOT WS, CM (preserve structural appearance)
```

This creates sets that test partial normalization capabilities.

---

## Part 11: New Seed Domains (Payload Types)

### Current Seeds

- `access_guard/` — Authorization decision payload
- `csv_import/` — CSV parsing payload
- `invoice_totals/` — Invoice computation payload

### Proposed New Seeds

1. **validation/** — Input validation payload (email, phone, URL, etc.)
2. **sorting/** — Various sorting algorithm implementations
3. **pagination/** — Page/offset calculation logic
4. **date_formatting/** — Date/time formatting and parsing
5. **currency/** — Currency conversion and formatting
6. **json_api/** — JSON encoding/decoding with error handling
7. **file_processing/** — File reading/writing with paths
8. **http_client/** — HTTP request building and response parsing
9. **session_management/** — Session start/read/write/end
10. **cache_invalidation/** — Cache key generation and invalidation
11. **logging/** — Log formatting and dispatching
12. **email_building/** — Email header/body construction
13. **permission_matrix/** — Complex permission checking logic
14. **report_generation/** — Report data aggregation and formatting
15. **state_transition/** — State machine implementation
16. **data_pipeline/** — ETL-style data transformation
17. **rate_limiting/** — Rate limit tracking and enforcement
18. **id_generation/** — Unique ID generation strategies
19. **config_merging/** — Configuration array merging and overriding
20. **error_recovery/** — Retry logic with backoff

---

## Part 12: Size Class Variations

### For Each Seed, Generate Multiple Size Classes

| Size Class | Lines | Token Count | Complexity |
|------------|-------|-------------|-------------|
| XS | 5-10 | 30-50 | Trivial |
| S | 10-20 | 50-100 | Simple |
| M | 20-40 | 100-200 | Standard |
| L | 40-80 | 200-400 | Complex |
| XL | 80-150 | 400-800 | Very Complex |
| XXL | 150-300 | 800-1500 | Substantial |

### Size Class Combinations in Sets

- **Homogeneous:** All 3 carriers same size class
- **Heterogeneous:** 2 small + 1 large, or 1 small + 2 large
- **Mixed:** 3 different size classes

---

## Part 13: Naming and Metadata Schema Extensions

### Extended set.json Fields

```json
{
  "schema_version": 2,
  "set_id": "L12-ex_function-001",
  "level": 12,
  "level_name": "combined_variations",
  "family": "ws_cm_rn_lt_stacked",
  "difficulty_band": "challenging",
  "variation_count": 10,
  "variation_types": ["WS-01", "WS-04", "CM-01", "RN-01", "RN-02", "LT-01", "TY-01", "CF-01", "API-01", "SEM-01"],
  "refactorability": {
    "can_parametrize": true,
    "param_count": 3,
    "requires_pattern": "template_method"
  },
  "files": [...],
  "duplication": {...},
  "interference": [...]
}
```

### New Metadata Dimensions

- `variation_count` — Number of variation axes applied
- `variation_types` — List of transform codes applied
- `clone_instance_count` — How many files have the clone (2-20+)
- `clone_cluster_count` — How many separate clone clusters in this set
- `unique_to_clone_ratio` — Ratio of unique code lines to clone lines
- `refactorability` — Structured assessment of how this could be refactored
- `overlap_with_other_clones` — Whether clones overlap with each other
- `near_miss_count` — How many false-positive bait files are in this set

---

## Part 14: Specific Sample Set Ideas (Detailed)

### Idea 1: "Tax Calculator Zoo"

A set of 20 files, each implementing tax calculation differently:
- 3 exact clones (for ground truth)
- 3 with whitespace variations
- 3 with comment variations
- 3 with variable renames
- 3 with different literal tax rates
- 3 with different control-flow structures (if/else vs match)
- 2 near-miss distractors (look like tax code but are different)

**Total: 20 files, 7 variations, 1 dominant clone cluster + 2 distractors**

### Idea 2: "The Seven Samurai"

Exactly 7 instances of the same clone, each with a different single variation type:
- Instance 1: No variation (pristine)
- Instance 2: Whitespace only
- Instance 3: Comment only
- Instance 4: Renames only
- Instance 5: Literals only
- Instance 6: Control-flow only
- Instance 7: API idioms only

**Purpose: Isolates each variation type's detection difficulty individually**

### Idea 3: "Nested Matryoshka Clones"

Clone inside clone inside clone:
- Outer clone: entire 50-line function
- Middle clone: a 20-line block within that function
- Inner clone: a 5-line expression within the block

**Each level appears in multiple files. Tests tools' ability to detect clones at multiple granularities simultaneously.**

### Idea 4: "Clone Marriage"

Two separate clone clusters that share some code between them:

```
File A: [CloneA unique] [SharedCode] [CloneB unique]
File B: [CloneA unique] [SharedCode] [CloneC unique]
File C: [CloneX unique] [SharedCode] [CloneB unique]
```

SharedCode appears in 3 files as part of different clone clusters. Tests tools' ability to handle shared sub-clones.

### Idea 5: "Evolutionary Clones"

A series where each "generation" is slightly more modified:

```
Gen 1: Exact clone (FileA, FileB)
Gen 2: Same as Gen1 + whitespace (FileC, FileD)
Gen 3: Same as Gen2 + comments (FileE, FileF)
Gen 4: Same as Gen3 + renames (FileG, FileH)
Gen 5: Same as Gen4 + literals (FileI, FileJ)
```

**Purpose: Shows the degradation of detectability as variations accumulate**

### Idea 6: "The Twin Paradox"

Two files that are extremely similar but one has a tiny deliberate difference:
- FileA and FileB are 99% identical
- The 1% difference is a single line or expression
- The clone should still be detected, but the tool should note the delta

### Idea 7: "Mass Duplication Event"

50 files, each with the same 10-line clone embedded at different positions:
- File 1: clone at line 5
- File 2: clone at line 15
- File 3: clone at line 30
- ...
- File 50: clone at line 200

**Tests scalability: can tools find all 50 instances scattered across different positions?**

### Idea 8: "Dependency Injection Variety"

Same logic instantiated via different DI patterns:
- Constructor injection
- Setter injection
- Method injection
- Static factory
- Service locator
- Global singleton

**All implement the same interface and have clone-equivalent method bodies**

### Idea 9: "The Inclusion Problem"

```php
// FileA: has Clone1 + Clone2
// Clone2 is a SUBSET of Clone1 (Clone1 contains Clone2)
```

Tests whether tools correctly identify that the smaller clone is a subset of the larger one, not a separate clone.

### Idea 10: "Cross-Framework Translation"

Same functionality written in:
- Plain PHP
- Laravel
- Symfony
- CodeIgniter
- Yii

All produce identical outputs for identical inputs but code structure differs significantly.

---

## Part 15: Negative Control Sets

### Truly Clean Files (No Clones)

- Pure utility functions with no duplication
- Single-responsibility classes with no repeated logic
- Files with unique business logic, no overlap with any other file

### Self-Contained Variants

- Files that LOOK like they have clones but are actually unique
- Same pattern, different values = actually unique (e.g., validation rules for different fields)
- "Near misses" that are intentionally NOT duplicates

---

## Summary: Prioritization of Ideas

### Tier 1: High Value, Moderate Effort

1. **L11-L15** new level tiers (Deep Renaming Stacks through Structural Divergence)
2. **Multi-cluster samples** (2, 3, 4+ clone clusters per set)
3. **Varying clone instance counts** (2, 5, 10, 20 instances)
4. **New seed domains** (validation, sorting, pagination, date formatting, etc.)
5. **Size class variations** (XS through XXL for each seed)
6. **Progressive complexity sub-levels** (a, b, c, d, e within each level)

### Tier 2: High Value, Higher Effort

7. **L20 "Kitchen Sink"** (10-20 stacked variations)
8. **Cross-domain semantic equivalence** samples
9. **Functional vs imperative equivalence** samples
10. **Template Method / Strategy Pattern** refactor candidates
11. **OOP Structural Divergence** samples
12. **Granularity variations** (statement, expression, class, file level)

### Tier 3: Specialized/Adversarial

13. **Unicode/encoding tricks** (homoglyphs, confusables)
14. **Minified vs formatted** pairs
15. **Generated code** patterns
16. **Mass duplication** (50+ instances)
17. **Clone overlap** scenarios
18. **The Inclusion Problem** (subset clones)

---

*This document is intended to be a living set of ideas. Not every idea needs to be implemented immediately — prioritize based on testing needs and available resources.*
