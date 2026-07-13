# Foundation Review — Graduated Duplication-Detection Corpus (P0)

**Reviewer:** independent adversarial reviewer (did not build it).
**Date:** 2026-07-13 · **Repo:** `/home/sites/php-duplication-samples` · **PHP:** 8.3.6 · nikic/php-parser ^5 installed.

---

## VERDICT: **FAIL**

The engine, schemas, verifier, bench runner, determinism gate, and 4 of the 5 pilots are solid
and genuinely well built. The Foundation FAILs on one **blocker** (the L06 semantic pilot ships a
byte-identical carrier pair that a token tool detects as a real clone, contradicting its own
`token_based:false` label — masked, not fixed, by `min_members_for_credit=3`), plus a supporting
**major** verifier gap that let it through, and a **major** out-of-scope write (`.gitignore`).
Per the rubric (any blocker or major ⇒ FAIL), the wave cannot open until L06 is re-authored.

---

## Checklist (§12 QA / §18.7 Definition of Done)

| # | Item | Verdict | Note |
|---|---|---|---|
| §12.1 / DoD-2 | Syntax `php -l` all src | ✅ | 25/25 rendered `src/*.php` lint clean. |
| §12.2 / DoD-3 | Schema + cross-checks | ✅ | Real draft-07-subset validator (`gen/lib/JsonSchema.php`); schemas match §7.1/§7.2; set/expected/manifest all validate; set_id↔path, registry codes, carrier-role all enforced. |
| §12.3 / DoD-5 | Positive proof (L1–L5) | ✅ | Token-normalized equality proven; A==C confirmed independently for L01/L02/L04. |
| §12.4 / DoD-5 | Behavioral equiv (L6+) | ✅ | `access_guard` eq-test is genuine (28 input combos, real diff); `invoice_totals` OK (5×1). |
| §12.5 / DoD-6 | Negative assurance (≥40 tok) | ⚠️ | Check is real and discriminates (carrier-carrier=147 tok; distractors 30/12 tok) **but** excludes cluster members, so it cannot catch the L06 identical-carrier defect. |
| §12.6 / DoD-4 | Line-number audit | ✅ | Independently re-derived every member; all bound `function <symbol>`…`}`; generator-emitted (byte-identical on regen). |
| §12.7 / DoD-9 | Determinism (`--check`) | ✅ | `--check` OK; hand-mutation → drift detected, exit 1; regen restores identical md5. Real gate, not a no-op. |
| §12.8 / DoD-7 | Hygiene / no leakage | ✅ | No `clone/dup/carrier/distractor/L#` in names, namespaces, comments. (`payload` hits are a benign `fingerprint(array $payload)` param.) LF/UTF-8/no-BOM enforced. |
| DoD-1 | Shape 3+1+1 / L0 distractor-clean | ✅* | Pilots correct; but verify.php does **not** assert the exact role counts (see MINOR-2). |
| DoD-8 | Benchmarkable, aligns w/ expectation | ⚠️ | L01/L02/L04 recall 1.00 (token-detectable); L06 recall 0.00 — but 0.00 is *manufactured* by `min_members=3`, not a genuine token miss (see BLOCKER). |
| DoD-10 | Scope & git | ❌ | No git subcommand run and no protected corpus file touched, **but** `.gitignore` (globally denied) was modified. |
| §7.2 | detection_expectation correct per level | ❌ | L06 `token_based:false` is empirically false — phpcpd reports a real A–B clone. |

---

## Issues (severity — file:line — problem — fix)

### BLOCKER-1 — `testsets/L06_controlflow_rewrites/cf_guard_nested/001/` — two carriers are byte-identical, so a token tool detects a real clone in a `token_based:false` set
- `src/DocumentAccessPolicy.php:9-27` and `src/FolderAccessPolicy.php:18-36` — the `authorize()` method is **byte-for-byte identical** (`diff` empty; 121 significant tokens). Both are marked `pristine:true` in `expected.json:16-29`.
- Empirical proof a token tool cracks it (the exact flags `bench/run-testsets.php:45` uses):
  ```
  $ php bench/tools/phpcpd.phar --fuzzy --min-lines 5 --min-tokens 50 <L06>/src
  Found 1 clones with 21 duplicated lines in 2 files:
    - DocumentAccessPolicy.php:9-30
      FolderAccessPolicy.php:18-39
  ```
  Both reported members **are ground-truth carriers** — the bench even scores `precision 1.00` on them. The *only* thing forcing `recall 0.00` is `expected.json:60` `min_members_for_credit:3` (phpcpd found 2 of 3). With the default `2`, recall would be `1.00`, flatly contradicting `expected.json:40` `token_based:false`.
- Why it corrupts R8: the capability profile would conclude "token tools find nothing at L6," which is false — phpcpd finds a correctly-located duplicate here. This violates §18.1-D5 (one clean axis), §6-rule-3 (asymmetric interference: A pristine / B moderate / C heavy — three *distinct* forms), and §20.3 ("wrong detection_expectation is a profiling bug even if scoring passes").
- **Verdict on the builder's `min_members=3` question: NOT an acceptable mitigation.** It hides a token-detectable clone in the scoring layer instead of removing it. The set is not a clean semantic probe.
- **Required fix:** give L06 three genuinely-distinct control-flow forms so no two carriers are token-identical. Author a *second* hand-written, equivalence-verified variant (e.g. B = single-return boolean-OR / `match` form; C = nested) in `gen/seeds/access_guard/variants/`, wire the recipe so carriers A/B/C are pairwise non-token-identical, then set `token_based:false` legitimately and return `min_members_for_credit` to the default `2`.

### MAJOR-1 — `gen/verify.php:334-409` (negativeProof) / `gen/verify.php:280-295` (positiveProof) — verifier cannot detect token-identical carriers in a `token_based:false` cluster
- `negativeProof` compares only *non-cluster* regions and trap regions, so it never compares two carriers to each other. `positiveProof` for `level>=6` runs only the behavioral eq-test and does no token comparison. Net effect: nothing asserts that members of a `token_based:false` cluster are mutually token-distinct — which is exactly why BLOCKER-1 passed `[PASS] L06`.
- **Fix:** add a check — for any cluster with `detection_expectation.token_based == false`, assert no pair of members shares a ≥40-token `type2` run (fail if they do). This makes the whole fan-out safe against repeating BLOCKER-1.

### MAJOR-2 — `.gitignore` (root) — out-of-scope write of a globally-denied file
- `git diff` shows the build added `/vendor` to `.gitignore`. `.gitignore` is on the §18.6 **global deny-list** ("all roles, all tasks") and is not in the Foundation allow-list; §18.7-10 requires "wrote nothing outside its allow-list." Content is benign (ignoring the dev-only vendor dir), but the rule is a stated non-negotiable ("writing outside the allow-list is a hard failure — stop and report").
- No `git` subcommand was executed and no protected corpus file (`samples.json`, `README.md`, `plan_samples.md`, `prompt_samples.md`, `samples/**`, `refactored/**`) was modified — only this one line.
- **Fix:** revert the `.gitignore` change and leave version-control hygiene (incl. ignoring `/vendor`) to the human, per §18.1-2.

### MINOR-1 — `testsets/L00_no_duplication/nd_distinct_domains/001/expected.json:5` — empty `non_duplicates` vs §6-rule-4
- §6 rule 4 says L0 sets carry "populated `non_duplicates`"; this set has `[]`. Defensible under §7.2 (non_duplicates marks deliberate bait only, and `nd_distinct_domains` has none), but the negative-control *pilot* would exercise the trap-FP path better if it were an `nd_boilerplate`-style set with real bait. Consider adding a bait region or note the intentional divergence.

### MINOR-2 — `gen/verify.php:158-179` — role-count shape (DoD-1) not mechanically enforced
- Cross-checks verify roles are consistent and cluster members are carriers, but never assert "exactly 3 carrier + 1 distractor + 1 clean" (§18.7-1). A future 2-carrier/2-distractor set would pass verify. Add an explicit role-count assertion.

### MINOR-3 — `set.json.interference` double-lists the same code — difficulty arithmetic
- L02/L04 list the family code twice (once per `applied_to` target: `WS-03,WS-03` / `RN-01,RN-01`) yet `difficulty.score` counts it once (L02=10+3=13, L04=30+6=36). Consistent and reproducible, but §7.1 ("sum every applied code's weight") is ambiguous for a single axis applied to two carriers. Fine to keep; worth a one-line comment in the score routine so future compound sets don't accidentally double-count.

---

## Verified green (independently re-run, not taken on faith)

- `php gen/verify.php --all` → `[PASS]` ×5; and per-pilot `--set=<id>` → PASS each.
- **Verifier is real, not stubbed** (read `gen/verify.php` in full): does `php -l`, JSON-Schema validation via `gen/lib/JsonSchema.php` (enforces type/required/enum/const/pattern/additionalProperties — not permissive), set_id↔path + registry + carrier-role cross-checks, positive token-equality proof, ≥40-token negative assurance, line-number audit, LF/UTF-8/BOM + name/comment hygiene, and a determinism re-render.
- `php gen/build.php --check` → byte-clean; **hand-mutated** `L01/src/InvoiceMath.php` → `--check` and `verify` both reported drift and **exit 1**; regenerated with `build.php --set=` → md5 restored identical. Drift gate proven.
- `php -l` clean on all **25** rendered `testsets/**/src/*.php`.
- Both equivalence tests pass and are genuine: `access_guard` (28 real input combos, pristine vs nested), `invoice_totals` (5×1).
- `php bench/run-testsets.php --all` → L01/L02/L04 recall 1.00 (token-detectable, correct), L06 recall 0.00 (see BLOCKER), all `trap-FP 0`.
- Line numbers independently re-derived for every cluster member (L01/L02/L04/L06) — all bound `function <symbol>` … closing `}`; generator-emitted (survive byte-identical regen).
- Single-axis confirmed: L02 A==C after blank-strip (whitespace-only); L04 identical after `$var` canonicalization (rename-only — params/literals/calls untouched).
- Distractors are genuine near-misses: `type2` longest-run distractor-vs-carrier = **30** (L01) / **12** (L04) tokens, both < 40; carrier-vs-carrier = 147. Not mislabeled clones. phpcpd flags only the 3 carriers in L01/L02/L04, never the distractor/clean files.
- Leakage grep across all `src/` — clean (only benign `$payload` param).
- Schemas match §7 normative shapes; `manifest.json` validates; `bench/corpora/testsets.ground-truth.json` well-formed.
- Registry weights within §7.1 bands (WS/CM 2–4, RN/LT 6–7, ST 9–12, CF/BL/API 14–18, SEM 21–24); pilot difficulty scores match the mechanical formula and band cutoffs.
- Scope/git: only `.gitignore` modified (MAJOR-2); no git subcommand run; no protected corpus file touched.
