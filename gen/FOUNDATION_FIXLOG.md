# Foundation Fix Log

Companion to `gen/FOUNDATION_REVIEW.md`. Each round records what changed and why,
plus the re-verification output. No git commands were run; all work is in place.

---

## fix round 1 — 2026-07-13 — resolve the FAIL verdict (BLOCKER-1, MAJOR-1/2, MINOR-1/2/3)

### BLOCKER-1 — L06 shipped two token-identical carriers under `token_based:false`
**Root cause:** the `cf_guard_nested` set had only two control-flow forms (guard + nested).
Carriers A and B were both the pristine guard-clause payload, so their `authorize()` bodies
were byte-identical (121 tokens) and phpcpd/jscpd paired them — a real token clone inside a
set labelled `token_based:false`. `min_members_for_credit:3` only *masked* the score.

**Fix (three genuinely-distinct forms, no masking):**
- Authored a **third** behaviorally-equivalent control-flow form:
  `gen/seeds/access_guard/variants/cf_match_guard.php` — a combined precondition guard
  (`if (!isset(...) || status !== 'active') return false;`) followed by a `match (true)`
  dispatch over the grant conditions. This is neither the early-return guard chain (A) nor
  the nested-conditional accumulator (C).
- Recipe `gen/recipes/L06/cf_guard_nested.json` now assigns one distinct form per carrier,
  position-varied by scaffold: **A** = guard clauses (pristine, `method_alpha`, top),
  **B** = `match` dispatch (`CF-02`, `method_beta`, middle), **C** = nested conditionals
  (`CF-03`, `method_gamma`, bottom).
- Restored `min_members_for_credit` to the default **2** (removed the `3` override). All
  three members are the real cluster; a semantic/AST detector finds all three, a token
  detector finds none (no identical pair remains).
- `gen/seeds/access_guard/seed.json` registers the new variant (`cf_match_guard.php`,
  `CF-02`) and adds `CF-02` to `compatible_interference`.
- Extended `gen/seeds/access_guard/equivalence_test.php` to exercise **both** variants
  against the pristine payload over the full input matrix (56 combinations, 0 divergences).

Pairwise longest common Type-2 run among the three rendered carrier bodies (sliced from the
generator-emitted member ranges): Document↔Folder = 23, Document↔Project = 22,
Folder↔Project = 19 — all `< 40`, none byte-identical. phpcpd on the set now reports
`No clones found.`

### MAJOR-1 — verifier could not catch a token-identical pair in a `token_based:false` cluster
**Fix:** `gen/verify.php` (`negativeProof`) now, for every cluster with
`detection_expectation.token_based == false`, asserts that **no pair of members shares a
`>= 40`-token normalized (Type-2: whitespace+comments+identifiers+literals) run**. It fails
loudly with a clear message and nonzero exit. Confirmed it would have flagged the OLD L06: a
throwaway scratch recipe reproducing two pristine (identical) carriers made verify report
`token-distinctness: cluster 'c1' is token_based:false but members
src/DocumentAccessPolicy.php and src/FolderAccessPolicy.php share a 121-token normalized
(Type-2) run (>= 40) …` and exit 1. The scratch recipe and its rendered set were deleted;
`build --check` and `verify --all` are clean afterward.

### MAJOR-2 — out-of-scope write of `.gitignore`
**Fix:** reverted the appended `/vendor` line. `git diff .gitignore` is now empty. (No
mutating git command was run; git was used read-only to confirm.) Version-control hygiene for
`/vendor` is left to a human.

### MINOR-1 — L0 `nd_distinct_domains` had an empty `non_duplicates`
**Fix:** per §6-rule-4 / §7.2, the deliberate false-positive bait is now populated. Measured
the pairwise structural overlap of all five files; the only shape shared between any two is
the `declare(strict_types=1)` + `namespace` + `final class` declaration scaffold. That header
is now marked `trap:true` (with `known_tool_fp:true`) in two representative files
(`PayrollCalendar.php`, `DnsRecordParser.php`), with reasons explaining it is ubiquitous
boilerplate a low-threshold token tool may pair, not a duplicated implementation.
`clusters` stays `[]`. Line numbers are generator-emitted via a new `traps` recipe key
resolved from the rendered file (`SetBuilder::resolveTrapRegion`, region `class_header`),
never hand-typed.

### MINOR-2 — role composition not mechanically enforced
**Fix:** `gen/verify.php` (`roleCompositionChecks`) now asserts the §6 shape: non-L0 sets
must be exactly **3 carrier + 1 distractor + 1 clean** (a family may declare
`set.json.role_composition` to override); L0 sets must have **0 carriers**, distractor/clean
roles only, and an empty `clusters` array. A future 2-carrier/2-distractor set now fails.

### MINOR-3 — `interference[]` could double-list a file for one (code, params)
**Fix:** `gen/lib/SetBuilder.php` records each file at most once in an interference entry's
`applied_to` (one entry per code+file+params). Added a clarifying comment at the difficulty
routine noting each *distinct* applied code contributes its weight once (so asymmetric
interference across carriers is not double-counted). No-op on the existing sets (byte-identical
rebuild).

### Re-verification (real output)

```
$ php gen/verify.php --all
[PASS] L00-nd_distinct_domains-001
[PASS] L01-ex_function-001
[PASS] L02-ws_blank_inside-001
[PASS] L04-rn_locals-001
[PASS] L06-cf_guard_nested-001
[verify] all 5 set(s) passed

$ php gen/build.php --check
[check] OK — 5 set(s) regenerate byte-identical to the committed tree

$ php -l  (all 25 rendered testsets/**/src/*.php)   -> linted 25 files; fail=0

$ php gen/seeds/access_guard/equivalence_test.php
[equivalence] access_guard: OK (56 input combinations across 2 variant(s))

$ php bench/tools/phpcpd.phar --fuzzy --min-lines 5 --min-tokens 50 <L06>/src
No clones found.

$ php bench/run-testsets.php --set=L06-cf_guard_nested-001
| L06-cf_guard_nested-001 | phpcpd | 0.00 | 1.00 | 0.00 | 0 |
| L06-cf_guard_nested-001 | jscpd  | 0.00 | 1.00 | 0.00 | 0 |
  (recall 0.00, no spurious hit, trap-FP 0 — honest for token_based:false)

$ php bench/run-testsets.php --all
  L01 recall 1.00 · L02 recall 1.00 · L04 recall 1.00 · all trap-FP 0 · L06 recall 0.00

$ php gen/manifest.php
[manifest] wrote testsets/manifest.json (5 sets, 4 clusters)
[manifest] wrote bench/corpora/testsets.ground-truth.json (4 clusters)

$ git diff .gitignore   -> (empty; reverted)

# Independent pairwise token-distinctness of the three L06 carriers (Type-2, threshold 40):
  DocumentAccessPolicy vs FolderAccessPolicy  = 23
  DocumentAccessPolicy vs ProjectAccessPolicy = 22
  FolderAccessPolicy   vs ProjectAccessPolicy = 19
  MAX = 23 (< 40) — all pairs token-distinct; none byte-identical.
```
