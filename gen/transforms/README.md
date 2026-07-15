# Transform Registry (`gen/transforms/`)

Parameterized code transformations applied to seed payloads during test-set generation. Each transform represents an axis of variation that makes clones harder to detect.

## Transform Interface

All transforms implement `Gen\Transforms\Transform`:

```php
interface Transform {
    public function code(): string;          // Registry code, e.g. "WS-03"
    public function name(): string;          // Human name, e.g. "blank_inside"
    public function apply(TransformInput $in, array $params, Rng $rng): TransformResult;
}
```

**TransformInput:** `{ lines: list<string>, lineMap: list<int> }` — the payload region, one string per line, plus a map from each output line back to its source line index.

**TransformResult:** `{ lines, lineMap }` — each `lineMap` entry is the source line index the output line came from, or `-1` for a line the transform inserted.

The line map enables **exact ground-truth line numbers** even after wraps or insertions.

## Transform Kinds

| Kind | Description | Implementation |
|------|-------------|----------------|
| `text` | Operate on `token_get_all` stream via `PhpTokens` — never split inside strings/heredocs/multi-line comments | WS, CM groups |
| `ast` | Use nikic/php-parser to decide edits, then apply at token/line level | RN, LT, TY, NS, ST groups |
| `selector` | Pick a hand-written variant body from `gen/seeds/<seed>/variants/` | CF, API, BL, SEM groups |
| `wrapper` | Inject noise (logging, metrics, assertions) or unique code | NZ, UQ groups |
| `encoding` | Byte-level encoding changes (BOM, NBSP, Unicode confusables, EOL) | ENC group |
| `topology` | Metadata directives for multi-cluster assembly (not actual transforms) | CP group |

## Transform Groups

### Whitespace (WS)
Format-only changes. Most are Type-1 clone variation.

| Code | Name | Weight | Clone Type |
|------|------|--------|------------|
| WS-01 | blank_before | 2 | type-1 |
| WS-02 | blank_after | 2 | type-1 |
| WS-03 | blank_inside | 3 | type-1 |
| WS-04 | operator_spacing | 3 | type-1 |
| WS-05 | indent_width | 2 | type-1 |
| WS-06 | line_wrap | 4 | type-1 |
| WS-07 | line_join | 4 | type-1 |
| WS-08 | tabs | 2 | type-1 |
| WS-09 | trailing_space | 2 | type-1 |
| WS-10 | newline_style | 2 | type-1 (encoding) |
| WS-11 | brace_style | 3 | type-1 |
| WS-12 | alignment_padding | 2 | type-1 |

### Comments (CM)
Comment manipulation. Type-1 clone variation.

| Code | Name | Weight | Clone Type |
|------|------|--------|------------|
| CM-01 | line_comment_add | 2 | type-1 |
| CM-02 | block_comment_add | 3 | type-1 |
| CM-03 | docblock | 3 | type-1 |
| CM-04 | inline_trailing | 2 | type-1 |
| CM-05 | comment_remove | 2 | type-1 |
| CM-06 | comment_text_change | 2 | type-1 |
| CM-07 | commented_out_code | 4 | type-1 |
| CM-08 | mid_statement_comment | 4 | type-1 |
| CM-09 | license_header | 3 | type-1 |
| CM-10 | annotation_docblock | 3 | type-1 |
| CM-11 | combined | 5 | type-1 |

### Rename (RN)
Identifier canonicalization. Type-2 clone variation.

| Code | Name | Weight | Clone Type |
|------|------|--------|------------|
| RN-01 | local_vars | 6 | type-2 |
| RN-02 | params | 6 | type-2 |
| RN-03 | functions | 7 | type-2 |
| RN-04 | classes | 7 | type-2 |
| RN-05 | case_convention | 6 | type-2 |

### Literal (LT)
Literal abstraction. Type-2 clone variation.

| Code | Name | Weight | Clone Type |
|------|------|--------|------------|
| LT-01 | numeric | 6 | type-2 |
| LT-02 | string | 6 | type-2 |
| LT-03 | array_values | 6 | type-2 |
| LT-04 | const_vs_literal | 7 | type-2 |
| LT-05 | escape_sequences | 3 | type-2 |
| LT-06 | unicode_escapes | 5 | type-2 |
| LT-07 | boolean_constants | 4 | type-2 |

### Type (TY)
Type hint manipulation. Type-2 clone variation.

| Code | Name | Weight | Clone Type |
|------|------|--------|------------|
| TY-01 | type_hints | 6 | type-2 |
| TY-02 | nullable | 6 | type-2 |

### Namespace (NS)
Import/namespacing changes. Type-2 clone variation.

| Code | Name | Weight | Clone Type |
|------|------|--------|------------|
| NS-01 | imports | 6 | type-2 |
| NS-02 | namespace_depth | 6 | type-2 |

### Statement/Structure (ST)
Structural modifications. Type-3 clone variation.

| Code | Name | Weight | Clone Type |
|------|------|--------|------------|
| ST-01 | insert_logging | 9 | type-3 |
| ST-02 | insert_dead | 10 | type-3 |
| ST-07 | partial_fragment | 8 | type-3 |

### Control-Flow (CF)
Selector-based control-flow rewrites.

### API/Idiom (API)
Selector-based API substitution variants.

### Builder (BL)
Selector-based builder pattern variants.

### Semantic (SEM)
Selector-based semantic/architectural variants.

### Noise (NZ)
Wrapper transforms that inject instrumentation (logging, metrics, assertions). Semantically inert but textually present.

| Code | Name | Weight |
|------|------|--------|
| NZ-01 | i18n_wrapper | varies |
| NZ-02 | feature_flag | varies |
| NZ-03 | debug_dump | varies |
| NZ-04 | env_check | varies |
| NZ-05 | timing | varies |
| NZ-06 | request_context | varies |
| NZ-07 | logging | varies |
| NZ-08 | metrics | varies |
| NZ-09 | security_assertion | varies |
| NZ-10 | framework_attribute | varies |

### Unique-Code (UQ)
Wrapper transforms that inject behavior-preserving unique code into clones.

| Code | Name | Weight |
|------|------|--------|
| UQ-01 | per_currency_rounding | varies |
| UQ-02 | audit_timestamp | varies |
| UQ-03 | bounds_clamping | varies |
| UQ-04 | pre_hook | varies |
| UQ-05 | post_hook | varies |

### Encoding (ENC)
Byte-level encoding transforms.

| Code | Name | Weight |
|------|------|--------|
| ENC-01 | bom_insert | varies |
| ENC-02 | nbsp_indent | varies |
| ENC-03 | unicode_confusable | varies |
| ENC-04 | mixed_eol | varies |
| ENC-05 | escape_sequence | varies |
| ENC-06 | heredoc_to_double_quote | varies |
| ENC-07 | tabs_in_string | varies |

### Legacy (LEG)
Legacy PHP syntax migrations.

### Syntax Modernization (SY)
PHP 5.x → 8.x syntax modernization.

### Refactorability (RF)
Refactoring pattern detection variants.

## Registry File

All transforms are registered in `gen/transforms/registry.json` with:

```json
"WS-03": {
    "name": "blank_inside",
    "kind": "text",
    "weight": 3,
    "impact": 1,
    "clone_type": "type-1",
    "requires": ["whitespace_normalization"],
    "implemented": true,
    "class": "Gen\\Transforms\\Ws\\BlankInside"
}
```

The `weight` feeds the difficulty formula: `score = level_base + Σ weight + intensity_bonus`.

## Adding a New Transform

1. Add a class under `gen/transforms/<Group>/<Name>.php` implementing `Transform`.
2. Add its file to `gen/bootstrap.php`.
3. Register it in `gen/transforms/registry.json` with `implemented: true`, `class`, `weight`, `kind`, `clone_type`, and `requires`.
4. Keep it deterministic (draw only from the passed `Rng`) and syntax-safe.
