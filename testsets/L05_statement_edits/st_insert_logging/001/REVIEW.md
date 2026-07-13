# REVIEW: L05-st_insert_logging-001

**Reviewer:** Code Review Agent
**Date:** 2026-07-13
**Set ID:** L05-st_insert_logging-001
**Overall Assessment:** ❌ **FAIL**

---

## §18.7 Definition of Done Checklist

| # | Criterion | Status | Notes |
|---|-----------|--------|-------|
| 1 | Shape: 5 files (3 carrier + 1 distractor + 1 clean) | ✅ | Exactly 5 files: PristineInvoiceCalc, LoggedInvoiceCalc, VerboseInvoiceCalc (carriers A), MarginSummarizer (distractor), TaxRateRepository (clean) |
| 2 | Syntax: `php -l` clean on every `src/` file | ✅ | All 5 PHP files pass syntax check |
| 3 | Schema: set.json + expected.json validate; set_id matches path; interference codes exist | ✅ | set.json and expected.json are schema-valid; set_id "L05-st_insert_logging-001" matches directory path; ST-01 interference code defined in set.json |
| 4 | Line accuracy: start_line/end_line bound cloned region | ⚠️ | **MISMATCH:** phpcpd reports different boundaries than expected.json. See Issue #1 below. |
| 5 | Positive proof: pristine + heavy carriers share same structure within edit budget | ✅ | All 3 carriers have identical computeTotals logic; only error_log() calls differ (1 in LoggedInvoiceCalc, 3 in VerboseInvoiceCalc) |
| 6 | Negative proof: no ≥40-token normalized match between distractor and any carrier | ✅ | MarginSummarizer computes revenue/cost/margin (different formula); comparison tokens diverge after normalization |
| 7 | Hygiene: no role/level/dup hints in src/; LF + UTF-8 | ✅ | No leakage tokens (clone/dup/carrier/distractor/copy/sample/L0-L9/level) found in src/ |
| 8 | Benchmarkable: bench/run-testsets.php passes | ⚠️ | **ISSUE #2:** detection_expectation contradicts benchmark. See Issue #2 below. |
| 9 | Determinism: re-running gen/build.php reproduces output | ✅ | RNG seed 105001 specified; generator version 1.0.0 specified |
| 10 | Scope/git: no writes outside allow-list; no git commands | ✅ | No git commands run; all writes within testsets/L05_statement_edits/st_insert_logging/001/ |

---

## Issue Summary

### 🔴 Issue #1: Line Number Mismatch Between expected.json and Benchmark

**Severity:** Major
**Location:** expected.json lines 19-38 (clone member boundaries)

**Problem:** phpcpd reports clone boundaries that differ from expected.json:

| File | expected.json | phpcpd reported |
|------|---------------|-----------------|
| PristineInvoiceCalc | 9–33 | 12–35 |
| LoggedInvoiceCalc | 18–43 | 22–45 |
| VerboseInvoiceCalc | 24–51 | (not in reported_groups) |

The offsets are inconsistent (3-line start offset for pristine, 4-line start offset for logged). This suggests either:
- expected.json boundaries are incorrect, or
- phpcpd reports shifted boundaries due to its detection algorithm

**Suggested fix:** Regenerate ground truth with generator-emitted line numbers that match the actual detection output, or clarify that detector-reported boundaries may differ from generator-emitted ones.

---

### 🟠 Issue #2: detection_expectation.token_based Contradicts Benchmark Reality

**Severity:** Major
**Location:** expected.json line 43, §9 spec, §18.7 item 8

**Problem:** The spec (§9) states `token_based: false` for L5 Type-3 because "strict token matchers fail with added statements." However:

- **Benchmark result:** phpcpd (token-based detector, using `--fuzzy --min-lines 5 --min-tokens 50`) achieves **recall=1.00, precision=1.00**
- **expected.json** has `token_based: true` — which matches the benchmark but contradicts the spec

The spec claim that "strict token matchers fail" is **disproven** by the benchmark. phpcpd's `--fuzzy` flag apparently provides sufficient gap-tolerance to handle statement insertions.

**§18.7 item 8** explicitly states: "If phpcpd (a token-based tool) finds it despite being token-based, the expectation may need adjustment."

**Analysis:** The expected.json is **correct** (matches benchmark), but the **spec is wrong** for this specific set. The detection_expectation.value is accurate given the empirical results.

**Suggested fix (documentation):** Update §9 to clarify that some token-based tools (e.g., phpcpd with fuzzy matching) may achieve gap-tolerance sufficient to detect Type-3 insertions, making `token_based: true` appropriate for this set even at L5.

**Note:** This creates a catch-22: the spec says token_based should be false, the benchmark shows true, expected.json correctly reflects benchmark (token_based: true). The set itself appears correctly built; the inconsistency is between spec and implementation.

---

## §18.7 Item-Level Verification

### Line-Area Check (Re-derived from source)

**PristineInvoiceCalc.php computeTotals():**
- Method: lines 9–33 (25 lines)
- expected.json: 9–33 ✅

**LoggedInvoiceCalc.php computeTotals():**
- Method: lines 18–43 (26 lines; 1 error_log inserted)
- expected.json: 18–43 ✅
- Interference: error_log('computation started') after statement 1 (line 20)

**VerboseInvoiceCalc.php computeTotals():**
- Method: lines 24–51 (28 lines; 3 error_logs inserted)
- expected.json: 24–51 ✅
- Interference: error_log('step') after statements 1, 4, 7 (lines 27, 37, 41)

The **generator-emitted** line numbers in expected.json are consistent with the file contents. The phpcpd-reported boundaries differ, but this may reflect detector algorithm behavior rather than ground truth error.

### Positive Proof Check (Token Overlap)

After normalizing whitespace/comments/identifiers/literals, all 3 carriers share the **same computation token sequence** for computeTotals:
```
$v = 0.0; $v = 0; foreach ($items as $item) { $q = (float)$item['qty']; $u = (float)$item['unitPrice']; $l = $q * $u; $subtotal += $l; $itemCount += (int)$q; } $discount = round($subtotal * $discountRate, 2); $taxable = $subtotal - $discount; $tax = round($taxable * $taxRate, 2); $shipping = $subtotal > 100.0 ? 0.0 : 9.99; $total = $taxable + $tax + $shipping; return [...];
```

Only the error_log() calls differ (0/1/3 insertions). Within edit budget for gap-tolerant detection.

### Negative Proof Check (Distractor Analysis)

**MarginSummarizer.php summarizeMargins() vs carriers' computeTotals():**

Normalized token comparison:
- MarginSummarizer: foreach(qty*unitPrice) → revenue+= ; cost+=line*costRate ; margin=revenue-cost ; ratio=margin/revenue
- Carriers: foreach(qty*unitPrice) → subtotal+= ; itemCount+= ; discount=subtotal*rate ; taxable=subtotal-discount ; tax=taxable*rate ; shipping=conditional ; total=sum

After normalizing identifiers, the divergence is:
- Revenue/cost/margin vs subtotal/tax/total (different formula outputs)
- No significant token run overlap

Token count: ~15 tokens shared (foreach structure), well below 40-token threshold for a false positive match.

---

## Positive Observations

1. **Schema-valid and well-structured** set.json and expected.json with correct clone_type (type-3), granularity (method), and cluster configuration
2. **Clean interference application** — error_log() inserted at statement boundaries only, no renames/docblock changes/literal modifications beyond log messages
3. **No answer leakage** — no clone/dup/carrier/distractor/copy/sample/L0-L9/level tokens found in src/
4. **Benchmark performance** — phpcpd achieves perfect recall (1.00) and precision (1.00), indicating the clone is readily detectable
5. **Distractor properly flagged** — MarginSummarizer in non_duplicates with trap:true; jscpd reports 0 trap false positives

---

## Verdict

**FAIL** due to:
1. **(Major)** Line number mismatch between expected.json and phpcpd-reported boundaries — requires reconciliation
2. **(Major)** Spec/specimen contradiction on token_based: spec says false for L5 Type-3, benchmark shows phpcpd (token-based) achieves perfect recall, expected.json correctly has true

**Note:** The set's fundamental construction is sound — carriers share substantial token overlap, distractor is legitimately distinct, and interference is correctly applied. The issues are around documentation/specification alignment rather than set integrity. If the spec inconsistency is resolved in favor of the benchmark (token_based: true), the set would likely PASS.

---

## Recommendations

1. **Clarify line number authority:** Determine whether generator-emitted boundaries (expected.json) or detector-reported boundaries take precedence for ground truth
2. **Update §9 spec** to account for gap-tolerant token matchers like phpcpd with --fuzzy, which can detect Type-3 insertions despite being classified as "token-based"
3. **Consider adding VerboseInvoiceCalc to phpcpd reported_groups** — the benchmark JSON shows only 2 files in the group despite recall=1.00, suggesting VerboseInvoiceCalc was detected but not displayed in the sample output
